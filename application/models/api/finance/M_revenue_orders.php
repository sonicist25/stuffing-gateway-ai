<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Install as:
 * application/models/api/finance/M_revenue_orders.php
 */
class M_revenue_orders extends MY_Model
{
    private $company_id = 1;

    private function num($v)
    {
        if ($v === null || $v === '') return 0.0;
        if (is_string($v)) $v = str_replace([',', ' '], ['', ''], $v);
        return is_numeric($v) ? (float)$v : 0.0;
    }

    private function money($v, $dec = 2)
    {
        return round($this->num($v), $dec);
    }

    private function currency($v)
    {
        $v = strtoupper(trim((string)$v));
        if (in_array($v, ['RP', 'RP.', 'IDR', 'RUPIAH'], true)) return 'IDR';
        return $v !== '' ? $v : 'IDR';
    }

    private function item_hash(array $row)
    {
        $keys = [
            'source_table','source_id','source_key','code_service','item_service',
            'qty','unit_price','amount','currency','exchange_rate','ppn','prep_coll',
            'group_code','bill_to_type','bill_to_code','bill_to_name','billing_document_type'
        ];

        $normalized = [];
        foreach ($keys as $k) $normalized[$k] = $row[$k] ?? null;
        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function find_order($company_id, $region_id, $source_db, $source_type, $source_key)
    {
        return $this->erp->from('revenue_orders')
            ->where('company_id', (int)$company_id)
            ->where('region_id', (string)$region_id)
            ->where('source_db', (string)$source_db)
            ->where('source_type', (string)$source_type)
            ->where('source_key', (string)$source_key)
            ->get()->row_array();
    }

    private function create_order(array $p)
    {
        $now = date('Y-m-d H:i:s');
        $tmp = 'PENDING-' . bin2hex(random_bytes(8));

        $this->erp->insert('revenue_orders', [
            'company_id'   => (int)$p['company_id'],
            'region_id'    => (string)$p['region_id'],
            'order_no'     => $tmp,
            'source_db'    => (string)$p['source_db'],
            'source_type'  => (string)$p['source_type'],
            'source_key'   => (string)$p['source_key'],
            'job_no'       => (string)$p['job_no'],
            'posting_date' => $p['posting_date'] ?: date('Y-m-d'),
            'sales_id'     => !empty($p['sales_id']) ? (int)$p['sales_id'] : null,
            'sales_name'   => $p['sales_name'] ?: null,
            'status'       => 'SUBMITTED',
            'created_by'   => $p['actor'] ?: 'INTERNAL_API',
            'created_at'   => $now,
            'updated_by'   => $p['actor'] ?: 'INTERNAL_API',
            'updated_at'   => $now,
        ]);

        $id = (int)$this->erp->insert_id();
        if ($id <= 0) return null;

        $orderNo = 'RO-' . date('Y') . '-' . str_pad((string)$id, 7, '0', STR_PAD_LEFT);
        $this->erp->where('revenue_order_id', $id)->update('revenue_orders', ['order_no' => $orderNo]);

        return $this->erp->where('revenue_order_id', $id)->get('revenue_orders')->row_array();
    }

    private function active_paid_total($ar_invoice_id, $invoice_no = '')
    {
        $ar_invoice_id = (int)$ar_invoice_id;
        $paid = 0.0;

        if ($ar_invoice_id > 0 && $this->erp->table_exists('ar_invoices')) {
            $row = $this->erp->select('paid_total')
                ->where('ar_invoice_id', $ar_invoice_id)
                ->get('ar_invoices')->row_array();
            $paid = max($paid, $this->num($row['paid_total'] ?? 0));
        }

        if (!$this->erp->table_exists('ar_receipts') || !$this->erp->table_exists('ar_receipt_refs')) {
            return $paid;
        }

        $this->erp->select('COALESCE(SUM(rr.allocated_amount),0) AS paid_total', false)
            ->from('ar_receipt_refs rr')
            ->join('ar_receipts r', 'r.rcpt_id = rr.rcpt_id', 'inner')
            ->where_in('r.status', ['SUBMITTED', 'POSTED']);

        if ($ar_invoice_id > 0 && $this->erp->field_exists('ar_invoice_id', 'ar_receipt_refs')) {
            $this->erp->where('rr.ar_invoice_id', $ar_invoice_id);
        } elseif ($invoice_no !== '' && $this->erp->field_exists('invoice_no', 'ar_receipt_refs')) {
            $this->erp->where('rr.invoice_no', $invoice_no);
        } else {
            return $paid;
        }

        $row = $this->erp->get()->row_array();
        return max($paid, $this->num($row['paid_total'] ?? 0));
    }

    private function find_invoice($company_id, $region_id, $ar_invoice_id = 0, $invoice_no = '')
    {
        if (!$this->erp->table_exists('ar_invoices')) return null;

        $this->erp->from('ar_invoices')
            ->where('company_id', (int)$company_id)
            ->where('region_id', (string)$region_id);

        if ((int)$ar_invoice_id > 0) {
            $this->erp->where('ar_invoice_id', (int)$ar_invoice_id);
        } elseif (trim((string)$invoice_no) !== '') {
            $this->erp->where('invoice_no', trim((string)$invoice_no));
        } else {
            return null;
        }

        return $this->erp->get()->row_array();
    }

    private function cancel_posted_gl_for_revision(array $invoice, $reason)
    {
        if (!$this->erp->table_exists('gl_entries')) return true;

        $invoiceNo = trim((string)($invoice['invoice_no'] ?? ''));
        if ($invoiceNo === '') return true;

        $upd = ['status' => 'CANCELLED'];
        if ($this->erp->field_exists('updated_at', 'gl_entries')) $upd['updated_at'] = date('Y-m-d H:i:s');

        $this->erp->where('voucher_type', 'ARINV')
            ->where('voucher_no', $invoiceNo)
            ->where('status', 'POSTED');

        if ($this->erp->field_exists('company_id', 'gl_entries')) {
            $this->erp->where('company_id', (int)$invoice['company_id']);
        }
        if ($this->erp->field_exists('region_id', 'gl_entries')) {
            $this->erp->where('region_id', (string)$invoice['region_id']);
        }

        $this->erp->update('gl_entries', $upd);

        // Kalau posting AR sebelumnya juga mengakui COGS otomatis,
        // nonaktifkan JE tersebut selama invoice menunggu revisi.
        // Re-post invoice akan membentuk posting aktif yang baru.
        $this->erp->where('voucher_type', 'JE')
            ->where('voucher_no', 'COGS-' . $invoiceNo)
            ->where('status', 'POSTED');

        if ($this->erp->field_exists('company_id', 'gl_entries')) {
            $this->erp->where('company_id', (int)$invoice['company_id']);
        }
        if ($this->erp->field_exists('region_id', 'gl_entries')) {
            $this->erp->where('region_id', (string)$invoice['region_id']);
        }

        $this->erp->update('gl_entries', $upd);
        return true;
    }

    private function mark_invoice_revision_required(array $invoice, $reason)
    {
        $now = date('Y-m-d H:i:s');
        $this->cancel_posted_gl_for_revision($invoice, $reason);

        $upd = [
            'status' => 'REVISION',
            'updated_at' => $now,
        ];
        if ($this->erp->field_exists('revision_required', 'ar_invoices')) $upd['revision_required'] = 1;
        if ($this->erp->field_exists('revision_reason', 'ar_invoices')) $upd['revision_reason'] = $reason;
        if ($this->erp->field_exists('revision_at', 'ar_invoices')) $upd['revision_at'] = $now;
        // Selama REVISION, invoice tidak boleh muncul sebagai collectible/open AR.
        // Nilai dokumen tetap tersimpan di grand_total untuk audit; saat re-post,
        // M_posting akan menghitung kembali outstanding_total yang aktif.
        if ($this->erp->field_exists('outstanding_total', 'ar_invoices')) $upd['outstanding_total'] = 0;

        $this->erp->where('ar_invoice_id', (int)$invoice['ar_invoice_id'])->update('ar_invoices', $upd);
    }

    private function recalc_order($revenue_order_id)
    {
        $revenue_order_id = (int)$revenue_order_id;

        $rows = $this->erp->select('currency,amount,billing_status,billed_amount')
            ->where('revenue_order_id', $revenue_order_id)
            ->get('revenue_order_items')->result_array();

        $totalIdr = $totalUsd = $billedIdr = $billedUsd = 0.0;
        $cnt = count($rows);
        $billedCount = 0;

        foreach ($rows as $r) {
            $cur = $this->currency($r['currency'] ?? 'IDR');
            $amt = $this->num($r['amount'] ?? 0);
            $billedAmt = $this->num($r['billed_amount'] ?? 0);
            $isBilled = strtoupper((string)($r['billing_status'] ?? 'UNBILLED')) === 'BILLED';

            if ($cur === 'USD') {
                $totalUsd += $amt;
                if ($isBilled) $billedUsd += ($billedAmt > 0 ? $billedAmt : $amt);
            } else {
                $totalIdr += $amt;
                if ($isBilled) $billedIdr += ($billedAmt > 0 ? $billedAmt : $amt);
            }
            if ($isBilled) $billedCount++;
        }

        if ($cnt === 0 || $billedCount === 0) $status = 'SUBMITTED';
        elseif ($billedCount < $cnt) $status = 'PARTIALLY_BILLED';
        else $status = 'FULLY_BILLED';

        $this->erp->where('revenue_order_id', $revenue_order_id)->update('revenue_orders', [
            'total_idr'   => round($totalIdr, 2),
            'total_usd'   => round($totalUsd, 4),
            'billed_idr'  => round($billedIdr, 2),
            'billed_usd'  => round($billedUsd, 4),
            'status'      => $status,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function sync(array $p)
    {
        $companyId = (int)($p['company_id'] ?? 1);
        $regionId  = trim((string)($p['region_id'] ?? ''));
        $sourceDb  = trim((string)($p['source_db'] ?? ''));
        $sourceType= strtoupper(trim((string)($p['source_type'] ?? 'JOB_INCOME')));
        $sourceKey = trim((string)($p['source_key'] ?? ''));
        $jobNo     = trim((string)($p['job_no'] ?? $sourceKey));
        $items     = $p['items'] ?? [];
        $actor     = trim((string)($p['actor'] ?? 'INTERNAL_API'));

        if ($regionId === '' || $sourceDb === '' || $sourceKey === '' || $jobNo === '' || !is_array($items)) {
            return ['ok'=>false,'http'=>422,'message'=>'INVALID_REVENUE_ORDER_PAYLOAD'];
        }

        $this->erp->trans_begin();

        $order = $this->find_order($companyId, $regionId, $sourceDb, $sourceType, $sourceKey);
        if (!$order) {
            $order = $this->create_order([
                'company_id'=>$companyId,'region_id'=>$regionId,'source_db'=>$sourceDb,
                'source_type'=>$sourceType,'source_key'=>$sourceKey,'job_no'=>$jobNo,
                'posting_date'=>trim((string)($p['posting_date'] ?? date('Y-m-d'))),
                'sales_id'=>$p['sales_id'] ?? null,'sales_name'=>$p['sales_name'] ?? null,
                'actor'=>$actor,
            ]);
        }

        if (!$order) {
            $err = $this->erp->error();
            $this->erp->trans_rollback();
            return ['ok'=>false,'http'=>500,'message'=>'FAILED_CREATE_REVENUE_ORDER','data'=>$err];
        }

        $orderId = (int)$order['revenue_order_id'];
        $seen = [];
        $lineNo = 0;

        foreach ($items as $raw) {
            if (!is_array($raw)) continue;
            $lineNo++;

            $sourceTable = trim((string)($raw['source_table'] ?? ''));
            $sourceId    = trim((string)($raw['source_id'] ?? ''));
            if ($sourceTable === '' || $sourceId === '') {
                $this->erp->trans_rollback();
                return ['ok'=>false,'http'=>422,'message'=>'ITEM_SOURCE_REQUIRED','data'=>['line_no'=>$lineNo]];
            }

            $cur = $this->currency($raw['currency'] ?? 'IDR');
            $qty = $this->num($raw['qty'] ?? 0);
            $unitPrice = $this->num($raw['unit_price'] ?? 0);
            $amount = $this->num($raw['amount'] ?? ($qty * $unitPrice));
            $rate = $this->num($raw['exchange_rate'] ?? 1);
            if ($rate <= 0) $rate = 1;
            $amountIdr = ($cur === 'USD') ? round($amount * $rate, 2) : round($amount, 2);

            $item = [
                'revenue_order_id'      => $orderId,
                'company_id'            => $companyId,
                'region_id'             => $regionId,
                'line_no'               => $lineNo,
                'source_db'             => $sourceDb,
                'source_table'          => $sourceTable,
                'source_id'             => $sourceId,
                'source_key'            => trim((string)($raw['source_key'] ?? $sourceKey)),
                'code_service'          => trim((string)($raw['code_service'] ?? '')) ?: null,
                'item_service'          => trim((string)($raw['item_service'] ?? '')) ?: null,
                'qty'                   => $qty,
                'unit_price'            => $unitPrice,
                'amount'                => $amount,
                'currency'              => $cur,
                'exchange_rate'         => $rate,
                'amount_idr'            => $amountIdr,
                'ppn'                   => trim((string)($raw['ppn'] ?? '')) ?: null,
                'prep_coll'             => trim((string)($raw['prep_coll'] ?? '')) ?: null,
                'group_code'            => trim((string)($raw['group_code'] ?? '')) ?: null,
                'bill_to_type'          => strtoupper(trim((string)($raw['bill_to_type'] ?? ''))) ?: null,
                'bill_to_code'          => trim((string)($raw['bill_to_code'] ?? '')) ?: null,
                'bill_to_name'          => trim((string)($raw['bill_to_name'] ?? '')) ?: null,
                'billing_document_type' => strtoupper(trim((string)($raw['billing_document_type'] ?? ''))) ?: null,
                'source_updated_at'     => !empty($raw['source_updated_at']) ? $raw['source_updated_at'] : null,
                'updated_by'            => $actor,
                'updated_at'            => date('Y-m-d H:i:s'),
            ];
            $item['source_hash'] = $this->item_hash($item);
            $seen[$sourceTable . '|' . $sourceId] = true;

            $existing = $this->erp->from('revenue_order_items')
                ->where('company_id', $companyId)
                ->where('region_id', $regionId)
                ->where('source_db', $sourceDb)
                ->where('source_table', $sourceTable)
                ->where('source_id', $sourceId)
                ->get()->row_array();

            if ($existing) {
                $changed = ($existing['source_hash'] ?? '') !== $item['source_hash'];
                if ($changed && strtoupper((string)$existing['billing_status']) === 'BILLED') {
                    $unbill = $this->unbill_item_internal($existing, 'SOURCE_INCOME_CHANGED');
                    if (!$unbill['ok']) {
                        $this->erp->trans_rollback();
                        return $unbill;
                    }
                }

                $this->erp->where('revenue_order_item_id', (int)$existing['revenue_order_item_id'])
                    ->update('revenue_order_items', $item);
            } else {
                $item['billing_status'] = 'UNBILLED';
                $item['billed_amount'] = 0;
                $item['created_by'] = $actor;
                $item['created_at'] = date('Y-m-d H:i:s');
                $this->erp->insert('revenue_order_items', $item);
            }
        }

        // Rows removed from source: remove only when unbilled; if billed, auto-unbill first.
        $oldRows = $this->erp->where('revenue_order_id', $orderId)->get('revenue_order_items')->result_array();
        foreach ($oldRows as $old) {
            $key = $old['source_table'] . '|' . $old['source_id'];
            if (isset($seen[$key])) continue;

            if (strtoupper((string)$old['billing_status']) === 'BILLED') {
                $unbill = $this->unbill_item_internal($old, 'SOURCE_INCOME_REMOVED');
                if (!$unbill['ok']) {
                    $this->erp->trans_rollback();
                    return $unbill;
                }
            }
            $this->erp->where('revenue_order_item_id', (int)$old['revenue_order_item_id'])->delete('revenue_order_items');
        }

        $this->erp->where('revenue_order_id', $orderId)->update('revenue_orders', [
            'job_no'       => $jobNo,
            'posting_date' => trim((string)($p['posting_date'] ?? '')) ?: date('Y-m-d'),
            'sales_id'     => !empty($p['sales_id']) ? (int)$p['sales_id'] : null,
            'sales_name'   => trim((string)($p['sales_name'] ?? '')) ?: null,
            'updated_by'   => $actor,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->recalc_order($orderId);

        if ($this->erp->trans_status() === false) {
            $err = $this->erp->error();
            $this->erp->trans_rollback();
            return ['ok'=>false,'http'=>500,'message'=>'REVENUE_ORDER_SYNC_FAILED','data'=>$err];
        }

        $this->erp->trans_commit();
        $fresh = $this->erp->where('revenue_order_id', $orderId)->get('revenue_orders')->row_array();
        return ['ok'=>true,'http'=>200,'message'=>'REVENUE_ORDER_SYNC_OK','data'=>$fresh];
    }

    private function find_item_by_source(array $p)
    {
        $companyId = (int)($p['company_id'] ?? 1);
        $regionId = trim((string)($p['region_id'] ?? ''));
        $sourceDb = trim((string)($p['source_db'] ?? ''));
        $sourceTable = trim((string)($p['source_table'] ?? ''));
        $sourceId = trim((string)($p['source_id'] ?? ''));

        if ($regionId === '' || $sourceDb === '' || $sourceTable === '' || $sourceId === '') return null;

        return $this->erp->from('revenue_order_items')
            ->where('company_id', $companyId)
            ->where('region_id', $regionId)
            ->where('source_db', $sourceDb)
            ->where('source_table', $sourceTable)
            ->where('source_id', $sourceId)
            ->get()->row_array();
    }

    private function unbill_item_internal(array $item, $reason)
    {
        if (strtoupper((string)($item['billing_status'] ?? 'UNBILLED')) !== 'BILLED') {
            return ['ok'=>true,'http'=>200,'message'=>'ALREADY_UNBILLED','data'=>$item];
        }

        $invoice = $this->find_invoice(
            (int)$item['company_id'],
            (string)$item['region_id'],
            (int)($item['ar_invoice_id'] ?? 0),
            (string)($item['invoice_no'] ?? '')
        );

        $paidTotal = 0.0;
        if ($invoice) {
            $paidTotal = $this->active_paid_total((int)$invoice['ar_invoice_id'], (string)$invoice['invoice_no']);
        }

        if ($paidTotal > 0.00001) {
            return [
                'ok'=>false,'http'=>409,'message'=>'INCOME_ALREADY_PAID_CANNOT_EDIT',
                'data'=>['invoice_no'=>$item['invoice_no'],'paid_total'=>$paidTotal]
            ];
        }

        if ($invoice) {
            $this->mark_invoice_revision_required($invoice, $reason);
        }

        // Detach only this AR invoice line when linkage exists.
        if ($this->erp->table_exists('ar_invoice_items') && $this->erp->field_exists('revenue_order_item_id', 'ar_invoice_items')) {
            $this->erp->where('revenue_order_item_id', (int)$item['revenue_order_item_id'])->delete('ar_invoice_items');
        }

        $this->erp->where('revenue_order_item_id', (int)$item['revenue_order_item_id'])
            ->update('revenue_order_items', [
                'billing_status'     => 'UNBILLED',
                'ar_invoice_id'      => null,
                'ar_invoice_item_id' => null,
                'invoice_no'         => null,
                'billed_amount'      => 0,
                'billed_at'          => null,
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);

        $this->recalc_order((int)$item['revenue_order_id']);

        return [
            'ok'=>true,'http'=>200,'message'=>'AUTO_UNBILL_OK',
            'data'=>[
                'revenue_order_item_id'=>(int)$item['revenue_order_item_id'],
                'old_invoice_no'=>$item['invoice_no'] ?? null,
                'invoice_revision_required'=>$invoice ? true : false,
            ]
        ];
    }

    public function unbill_item(array $p)
    {
        $item = $this->find_item_by_source($p);

        // Legacy row: no RO item yet. We can still guard by invoice payment and mark the
        // ERP invoice revision-required. This makes migration non-disruptive.
        if (!$item) {
            $companyId = (int)($p['company_id'] ?? 1);
            $regionId = trim((string)($p['region_id'] ?? ''));
            $invoiceNo = trim((string)($p['invoice_no'] ?? ''));

            if ($invoiceNo === '') {
                return ['ok'=>true,'http'=>200,'message'=>'LEGACY_ITEM_WITHOUT_INVOICE'];
            }

            $invoice = $this->find_invoice($companyId, $regionId, 0, $invoiceNo);
            if ($invoice) {
                $paid = $this->active_paid_total((int)$invoice['ar_invoice_id'], $invoiceNo);
                if ($paid > 0.00001) {
                    return ['ok'=>false,'http'=>409,'message'=>'INCOME_ALREADY_PAID_CANNOT_EDIT','data'=>['invoice_no'=>$invoiceNo,'paid_total'=>$paid]];
                }

                $this->erp->trans_begin();
                $this->mark_invoice_revision_required($invoice, 'LEGACY_SOURCE_INCOME_CHANGED');
                if ($this->erp->trans_status() === false) {
                    $this->erp->trans_rollback();
                    return ['ok'=>false,'http'=>500,'message'=>'FAILED_MARK_INVOICE_REVISION'];
                }
                $this->erp->trans_commit();
            }

            return ['ok'=>true,'http'=>200,'message'=>'LEGACY_AUTO_UNBILL_ALLOWED','data'=>['invoice_revision_required'=>(bool)$invoice]];
        }

        $this->erp->trans_begin();
        $res = $this->unbill_item_internal($item, trim((string)($p['reason'] ?? 'SOURCE_INCOME_CHANGED')) ?: 'SOURCE_INCOME_CHANGED');

        if (!$res['ok'] || $this->erp->trans_status() === false) {
            $this->erp->trans_rollback();
            return $res['ok'] ? ['ok'=>false,'http'=>500,'message'=>'AUTO_UNBILL_FAILED'] : $res;
        }

        $this->erp->trans_commit();
        return $res;
    }

    public function mark_billed(array $p)
    {
        $companyId = (int)($p['company_id'] ?? 1);
        $regionId = trim((string)($p['region_id'] ?? ''));
        $invoiceNo = trim((string)($p['invoice_no'] ?? ''));
        $arInvoiceId = (int)($p['ar_invoice_id'] ?? 0);
        $items = $p['items'] ?? [];

        if ($regionId === '' || $invoiceNo === '' || !is_array($items) || !$items) {
            return ['ok'=>false,'http'=>422,'message'=>'INVALID_MARK_BILLED_PAYLOAD'];
        }

        // Caller may only know the document number. Resolve ERP AR id here so
        // operational code does not need a direct ERP DB dependency.
        if ($arInvoiceId <= 0) {
            $invoice = $this->find_invoice($companyId, $regionId, 0, $invoiceNo);
            if ($invoice) $arInvoiceId = (int)$invoice['ar_invoice_id'];
        }

        $this->erp->trans_begin();
        $touchedOrders = [];
        $mirrors = [];

        foreach ($items as $ref) {
            if (!is_array($ref)) continue;
            $item = null;
            if (!empty($ref['revenue_order_item_id'])) {
                $item = $this->erp->where('company_id',$companyId)
                    ->where('region_id',$regionId)
                    ->where('revenue_order_item_id',(int)$ref['revenue_order_item_id'])
                    ->get('revenue_order_items')->row_array();
            } else {
                $ref['company_id'] = $companyId;
                $ref['region_id'] = $regionId;
                $item = $this->find_item_by_source($ref);
            }
            if (!$item) {
                $this->erp->trans_rollback();
                return ['ok'=>false,'http'=>404,'message'=>'REVENUE_ORDER_ITEM_NOT_FOUND','data'=>$ref];
            }

            $arItemId = !empty($ref['ar_invoice_item_id']) ? (int)$ref['ar_invoice_item_id'] : null;

            // Resolve exact AR line automatically when M_posting stores the original
            // Income source on ar_invoice_items.source_table/source_id.
            if (!$arItemId && $arInvoiceId > 0 && $this->erp->table_exists('ar_invoice_items')) {
                if ($this->erp->field_exists('source_table','ar_invoice_items') && $this->erp->field_exists('source_id','ar_invoice_items')) {
                    $arLine = $this->erp
                        ->select('ar_invoice_item_id')
                        ->where('ar_invoice_id', $arInvoiceId)
                        ->where('source_table', (string)$item['source_table'])
                        ->where('source_id', (string)$item['source_id'])
                        ->limit(1)->get('ar_invoice_items')->row_array();
                    if ($arLine) $arItemId = (int)$arLine['ar_invoice_item_id'];
                }
            }

            $billedAmount = array_key_exists('billed_amount',$ref) ? $this->num($ref['billed_amount']) : $this->num($item['amount']);
            $documentType = strtoupper(trim((string)($p['document_type'] ?? $item['billing_document_type'] ?? '')));

            $this->erp->where('revenue_order_item_id',(int)$item['revenue_order_item_id'])
                ->update('revenue_order_items', [
                    'billing_status'=>'BILLED',
                    'billing_document_type'=>$documentType !== '' ? $documentType : null,
                    'ar_invoice_id'=>$arInvoiceId > 0 ? $arInvoiceId : null,
                    'ar_invoice_item_id'=>$arItemId,
                    'invoice_no'=>$invoiceNo,
                    'billed_amount'=>$billedAmount,
                    'billed_at'=>date('Y-m-d H:i:s'),
                    'updated_at'=>date('Y-m-d H:i:s'),
                ]);

            if ($arItemId && $this->erp->table_exists('ar_invoice_items') && $this->erp->field_exists('revenue_order_item_id','ar_invoice_items')) {
                $this->erp->where('ar_invoice_item_id',$arItemId)->update('ar_invoice_items', [
                    'revenue_order_item_id'=>(int)$item['revenue_order_item_id']
                ]);
            }

            $touchedOrders[(int)$item['revenue_order_id']] = true;
            $mirrors[] = [
                'source_db'=>$item['source_db'],
                'source_table'=>$item['source_table'],
                'source_id'=>$item['source_id'],
                'invoice_no'=>$invoiceNo,
                'billing_status'=>'BILLED',
            ];
        }

        foreach (array_keys($touchedOrders) as $orderId) $this->recalc_order($orderId);

        if ($arInvoiceId > 0 && $this->erp->table_exists('ar_invoices')) {
            $upd = ['revision_required'=>0,'revision_reason'=>null,'revision_at'=>null];
            if ($this->erp->field_exists('updated_at','ar_invoices')) $upd['updated_at'] = date('Y-m-d H:i:s');
            $this->erp->where('ar_invoice_id',$arInvoiceId)->update('ar_invoices',$upd);
        }

        if ($this->erp->trans_status() === false) {
            $this->erp->trans_rollback();
            return ['ok'=>false,'http'=>500,'message'=>'MARK_BILLED_FAILED'];
        }
        $this->erp->trans_commit();

        return ['ok'=>true,'http'=>200,'message'=>'MARK_BILLED_OK','data'=>['mirror_updates'=>$mirrors]];
    }

    // =========================================================
    // READ ENDPOINTS FOR ERP FRONTEND
    // =========================================================

    private function frontend_region()
    {
        return trim((string)$this->auth_region());
    }

    private function frontend_date($value)
    {
        $value = trim((string)$value);
        return preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value) ? $value : '';
    }

    private function apply_frontend_order_filters(array $p)
    {
        $regionId = $this->frontend_region();

        $this->erp->from('revenue_orders ro')
            ->where('ro.company_id', $this->company_id)
            ->where('ro.region_id', $regionId);

        $q = trim((string)($p['q'] ?? ''));
        if ($q !== '') {
            $this->erp->group_start()
                ->like('ro.order_no', $q)
                ->or_like('ro.job_no', $q)
                ->or_like('ro.source_key', $q)
                ->or_like('ro.sales_name', $q)
                ->group_end();
        }

        $status = strtoupper(trim((string)($p['status'] ?? '')));
        if (in_array($status, ['SUBMITTED','PARTIALLY_BILLED','FULLY_BILLED','CANCELLED'], true)) {
            $this->erp->where('ro.status', $status);
        }

        $sourceType = strtoupper(trim((string)($p['source_type'] ?? '')));
        if (in_array($sourceType, ['JOB_INCOME','GLOBAL_INCOME'], true)) {
            $this->erp->where('ro.source_type', $sourceType);
        }

        $dateFrom = $this->frontend_date($p['date_from'] ?? '');
        $dateTo   = $this->frontend_date($p['date_to'] ?? '');
        if ($dateFrom !== '') $this->erp->where('ro.posting_date >=', $dateFrom);
        if ($dateTo !== '') $this->erp->where('ro.posting_date <=', $dateTo);
    }

    public function frontend_list(array $p)
    {
        if (!$this->erp->table_exists('revenue_orders') || !$this->erp->table_exists('revenue_order_items')) {
            return ['ok'=>false,'http'=>500,'message'=>'REVENUE_ORDER_TABLE_NOT_FOUND'];
        }

        $regionId = $this->frontend_region();
        if ($regionId === '') {
            return ['ok'=>false,'http'=>403,'message'=>'REGION_NOT_FOUND'];
        }

        $limit = (int)($p['limit'] ?? 25);
        if ($limit <= 0) $limit = 25;
        if ($limit > 100) $limit = 100;

        $offset = (int)($p['offset'] ?? 0);
        if ($offset < 0) $offset = 0;

        $this->apply_frontend_order_filters($p);
        $total = (int)$this->erp->count_all_results();

        $this->erp->select("\n            ro.revenue_order_id,\n            ro.order_no,\n            ro.source_type,\n            ro.source_key,\n            ro.job_no,\n            ro.posting_date,\n            ro.sales_id,\n            ro.sales_name,\n            ro.total_idr,\n            ro.total_usd,\n            ro.billed_idr,\n            ro.billed_usd,\n            GREATEST(COALESCE(ro.total_idr,0) - COALESCE(ro.billed_idr,0), 0) AS unbilled_idr,\n            GREATEST(COALESCE(ro.total_usd,0) - COALESCE(ro.billed_usd,0), 0) AS unbilled_usd,\n            ro.status,\n            ro.created_by,\n            ro.created_at,\n            ro.updated_by,\n            ro.updated_at,\n            (SELECT COUNT(*) FROM revenue_order_items ri WHERE ri.revenue_order_id = ro.revenue_order_id) AS item_count,\n            (SELECT COUNT(*) FROM revenue_order_items ri WHERE ri.revenue_order_id = ro.revenue_order_id AND COALESCE(ri.billing_status,'UNBILLED') <> 'BILLED') AS unbilled_item_count\n        ", false);
        $this->apply_frontend_order_filters($p);
        $rows = $this->erp
            ->order_by('ro.updated_at', 'DESC')
            ->order_by('ro.revenue_order_id', 'DESC')
            ->limit($limit, $offset)
            ->get()->result_array();

        // Summary follows the same filter/search, but ignores pagination.
        $this->erp->select("\n            COUNT(*) AS total_orders,\n            COALESCE(SUM(ro.total_idr),0) AS total_idr,\n            COALESCE(SUM(ro.total_usd),0) AS total_usd,\n            COALESCE(SUM(ro.billed_idr),0) AS billed_idr,\n            COALESCE(SUM(ro.billed_usd),0) AS billed_usd,\n            COALESCE(SUM(GREATEST(COALESCE(ro.total_idr,0)-COALESCE(ro.billed_idr,0),0)),0) AS unbilled_idr,\n            COALESCE(SUM(GREATEST(COALESCE(ro.total_usd,0)-COALESCE(ro.billed_usd,0),0)),0) AS unbilled_usd,\n            SUM(CASE WHEN ro.status='SUBMITTED' THEN 1 ELSE 0 END) AS submitted_count,\n            SUM(CASE WHEN ro.status='PARTIALLY_BILLED' THEN 1 ELSE 0 END) AS partial_count,\n            SUM(CASE WHEN ro.status='FULLY_BILLED' THEN 1 ELSE 0 END) AS fully_billed_count\n        ", false);
        $this->apply_frontend_order_filters($p);
        $summary = $this->erp->get()->row_array() ?: [];

        return [
            'ok'=>true,
            'http'=>200,
            'message'=>'OK',
            'data'=>[
                'rows'=>$rows,
                'total'=>$total,
                'limit'=>$limit,
                'offset'=>$offset,
                'region_id'=>$regionId,
                'summary'=>$summary,
            ],
        ];
    }

    public function frontend_detail(array $p)
    {
        if (!$this->erp->table_exists('revenue_orders') || !$this->erp->table_exists('revenue_order_items')) {
            return ['ok'=>false,'http'=>500,'message'=>'REVENUE_ORDER_TABLE_NOT_FOUND'];
        }

        $regionId = $this->frontend_region();
        if ($regionId === '') {
            return ['ok'=>false,'http'=>403,'message'=>'REGION_NOT_FOUND'];
        }

        $id = (int)($p['revenue_order_id'] ?? $p['id'] ?? 0);
        if ($id <= 0) {
            return ['ok'=>false,'http'=>422,'message'=>'revenue_order_id required'];
        }

        $order = $this->erp->from('revenue_orders')
            ->where('company_id', $this->company_id)
            ->where('region_id', $regionId)
            ->where('revenue_order_id', $id)
            ->get()->row_array();

        if (!$order) {
            return ['ok'=>false,'http'=>404,'message'=>'REVENUE_ORDER_NOT_FOUND'];
        }

        $order['unbilled_idr'] = max(0, $this->num($order['total_idr'] ?? 0) - $this->num($order['billed_idr'] ?? 0));
        $order['unbilled_usd'] = max(0, $this->num($order['total_usd'] ?? 0) - $this->num($order['billed_usd'] ?? 0));

        $hasArInvoices = $this->erp->table_exists('ar_invoices');
        if ($hasArInvoices) {
            $this->erp->select("\n                ri.*,\n                ai.status AS invoice_status,\n                ai.posting_date AS invoice_posting_date,\n                ai.paid_total AS invoice_paid_total,\n                ai.outstanding_total AS invoice_outstanding_total\n            ", false)
                ->from('revenue_order_items ri')
                ->join('ar_invoices ai', 'ai.ar_invoice_id = ri.ar_invoice_id AND ai.company_id = ri.company_id AND ai.region_id = ri.region_id', 'left');
        } else {
            $this->erp->select('ri.*')->from('revenue_order_items ri');
        }

        $items = $this->erp
            ->where('ri.company_id', $this->company_id)
            ->where('ri.region_id', $regionId)
            ->where('ri.revenue_order_id', $id)
            ->order_by('ri.line_no', 'ASC')
            ->order_by('ri.revenue_order_item_id', 'ASC')
            ->get()->result_array();

        $this->erp->select("\n            COALESCE(NULLIF(bill_to_type,''),'UNSPECIFIED') AS bill_to_type,\n            COALESCE(bill_to_code,'') AS bill_to_code,\n            COALESCE(bill_to_name,'') AS bill_to_name,\n            currency,\n            COUNT(*) AS item_count,\n            SUM(amount) AS total_amount,\n            SUM(CASE WHEN billing_status='BILLED' THEN amount ELSE 0 END) AS billed_amount,\n            SUM(CASE WHEN COALESCE(billing_status,'UNBILLED') <> 'BILLED' THEN amount ELSE 0 END) AS unbilled_amount\n        ", false)
            ->from('revenue_order_items')
            ->where('company_id', $this->company_id)
            ->where('region_id', $regionId)
            ->where('revenue_order_id', $id)
            ->group_by(['bill_to_type','bill_to_code','bill_to_name','currency'])
            ->order_by('bill_to_type', 'ASC');

        $byParty = $this->erp->get()->result_array();

        return [
            'ok'=>true,
            'http'=>200,
            'message'=>'OK',
            'data'=>[
                'order'=>$order,
                'items'=>$items,
                'by_party'=>$byParty,
            ],
        ];
    }

}
