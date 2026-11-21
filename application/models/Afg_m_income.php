<?php
class Afg_m_income extends CI_Model
{

  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }

        // Revenue Order API helper. The helper uses the same finance_api_key
        // already used by the accounting integration.
        $this->load->helper('revenue_order');

        // Paid Income edit override whitelist.
        // Loaded as a config section so user IDs are not scattered in model methods.
        $this->config->load('income_override', TRUE);
    }

    private function income_billing_meta($table)
    {
        $map = [
            'inf_income_selling_price' => ['ref_col'=>'ship_co', 'key_col'=>'kode_ref', 'payment_col'=>'payment'],
            'inf_income_from_agent'     => ['ref_col'=>'dn_no',   'key_col'=>'kode_ref', 'payment_col'=>'payment'],
            'inf_income_from_coloader'  => ['ref_col'=>'dn_no',   'key_col'=>'kode_ref', 'payment_col'=>'payment'],
            'x_glbincome'               => ['ref_col'=>'dn_no',   'key_col'=>'job_no',   'payment_col'=>null],
        ];
        return $map[$table] ?? null;
    }
	
    /**
     * Ambil whitelist override dari config section income_override.
     * Constructor memuat config dengan $this->config->load('income_override', TRUE),
     * jadi key harus dibaca dari section tersebut.
     */
    private function income_override_user_ids($key)
    {
        $allowed = $this->config->item($key, 'income_override');

        // Backward compatibility kalau suatu deployment memuat config tanpa section.
        if (!is_array($allowed)) {
            $allowed = $this->config->item($key);
        }

        if (!is_array($allowed)) {
            $allowed = [];
        }

        return array_values(array_unique(array_map('intval', $allowed)));
    }

    private function can_override_paid_income_delete()
    {
        $userId = (int)$this->session->userdata('user_id');
        if ($userId <= 0) return false;

        return in_array(
            $userId,
            $this->income_override_user_ids('paid_delete_user_ids'),
            true
        );
    }

    private function income_billing_sensitive_changes(array $changes)
    {
        $sensitive = [
            'container','code_service','item_service','qty','mchgs','unit_price','price',
            'currency','prep_coll','receive_from','agent','g','ppn','invoice_db',
            'payeble_to','group','hb_l'
        ];
        foreach ($sensitive as $key) {
            if (array_key_exists($key, $changes)) return true;
        }
        return false;
    }

    private function local_invoice_is_paid($invoice_no)
    {
        $invoice_no = trim((string)$invoice_no);
        if ($invoice_no === '' || !$this->dbx->table_exists('x_finance')) return false;

        $row = $this->dbx->select('status')
            ->where('number', $invoice_no)
            ->where('region_id', $this->session->userdata('region_id'))
            ->limit(1)->get('x_finance')->row_array();

        return strtolower(trim((string)($row['status'] ?? ''))) === 'paid';
    }


    /**
     * User khusus yang boleh mengoreksi source Income walaupun invoice sudah PAID.
     * Otorisasi SELALU dari session user_id, tidak pernah dari request/POST.
     */
    private function can_override_paid_income()
    {
        $userId = (int)$this->session->userdata('user_id');
        if ($userId <= 0) return false;

        return in_array(
            $userId,
            $this->income_override_user_ids('paid_edit_user_ids'),
            true
        );
    }

    /**
     * Log setiap otorisasi override PAID.
     * Tabel audit sengaja berada di operational DB yang sama dengan Income source.
     * Jika migration audit belum dijalankan, edit tetap tidak dibuat gagal:
     * warning ditulis ke CI log agar operasional tidak terhenti.
     */
    private function log_paid_income_override($table, $id, array $row, array $changes, $invoiceNo, $action = 'EDIT', $paymentSource = '')
    {
        if (!$this->dbx->table_exists('income_paid_override_audit')) {
            log_message(
                'error',
                'PAID_INCOME_OVERRIDE audit table missing. user_id='
                . (int)$this->session->userdata('user_id')
                . ' source=' . $table . '#' . (int)$id
                . ' invoice=' . (string)$invoiceNo
            );
            return;
        }

        $meta = $this->income_billing_meta($table);
        $sourceKey = $meta ? (string)($row[$meta['key_col']] ?? '') : '';

        $this->dbx->insert('income_paid_override_audit', [
            'user_id'        => (int)$this->session->userdata('user_id'),
            'username'       => (string)$this->session->userdata('username'),
            'fullname'       => (string)$this->session->userdata('fullname'),
            'region_id'      => (string)$this->session->userdata('region_id'),
            'source_db'      => (string)$this->dbx->database,
            'source_table'   => (string)$table,
            'source_id'      => (int)$id,
            'source_key'     => $sourceKey,
            'invoice_no'     => trim((string)$invoiceNo),
            'action_type'    => strtoupper((string)$action),
            'payment_source' => trim((string)$paymentSource),
            'before_json'    => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'changes_json'   => json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    private function allow_paid_income_override($table, $id, array $row, array $changes, $invoiceNo, $paymentSource)
    {
        $this->log_paid_income_override(
            $table,
            $id,
            $row,
            $changes,
            $invoiceNo,
            'EDIT',
            $paymentSource
        );

        return [
            'ok'             => true,
            'status'         => 1,
            'reset'          => [],
            'paid_override'  => true,
            'invoice_no'     => trim((string)$invoiceNo),
            'old_invoice_no' => trim((string)$invoiceNo),
            'message'        => 'PAID_INCOME_EDIT_OVERRIDE_GRANTED',
        ];
    }

    /**
     * User whitelist boleh menghapus SOURCE operational yang sudah PAID.
     *
     * Yang TIDAK dilakukan:
     * - tidak unbill invoice
     * - tidak clear ship_co / dn_no terlebih dahulu
     * - tidak cancel AR invoice / payment / GL
     *
     * Audit menyimpan snapshot row sebelum source dihapus.
     */
    private function allow_paid_income_delete_override($table, $id, array $row, $invoiceNo, $paymentSource)
    {
        $this->log_paid_income_override(
            $table,
            $id,
            $row,
            ['deleted' => true],
            $invoiceNo,
            'DELETE',
            $paymentSource
        );

        return [
            'ok'                   => true,
            'status'               => 1,
            'reset'                => [],
            'paid_override'        => true,
            'paid_override_delete' => true,
            'invoice_no'           => trim((string)$invoiceNo),
            'old_invoice_no'       => trim((string)$invoiceNo),
            'payment_source'       => trim((string)$paymentSource),
            'message'              => 'PAID_INCOME_DELETE_OVERRIDE_GRANTED',
        ];
    }


    /**
     * Cari header invoice lokal berdasarkan nomor invoice.
     * Dipakai saat source Income yang sudah billed diedit/dihapus.
     */
    private function local_invoice_header($invoiceNo, $region)
    {
        $invoiceNo = trim((string)$invoiceNo);
        $region    = trim((string)$region);

        if ($invoiceNo === '' || $region === '' || !$this->dbx->table_exists('x_finance')) {
            return null;
        }

        return $this->dbx
            ->select('id,job_no,number,jenis,jenis_code,status,region_id')
            ->where('number', $invoiceNo)
            ->where('region_id', $region)
            ->where_in('jenis', ['INVOICE', 'DEBIT NOTE'])
            ->limit(1)
            ->get('x_finance')
            ->row_array();
    }

    private function income_ppn_half_up_int($dpp, $num, $den)
    {
        $dpp = (int)$dpp;
        $num = (int)$num;
        $den = (int)$den;

        if ($den <= 0) return 0;

        return intdiv(
            ($dpp * $num) + intdiv($den, 2),
            $den
        );
    }

    /**
     * Hitung ulang invoice lokal setelah satu source income dilepas.
     *
     * - VAT lama dihapus dan dibangun ulang.
     * - Invoice header tetap dipertahankan walaupun akhirnya total 0.
     * - Ini hanya menyentuh invoice lokal x_finance/x_finance_item.
     */
    private function recalculate_local_invoice_after_income_release($financeId, $region)
    {
        $financeId = (int)$financeId;
        $region    = trim((string)$region);

        if ($financeId <= 0 || $region === '') {
            return ['ok'=>false, 'error'=>'INVALID_RECALCULATE_ARGUMENT'];
        }

        $finance = $this->dbx
            ->select('id,job_no,number,jenis,jenis_code,region_id')
            ->where('id', $financeId)
            ->where('region_id', $region)
            ->where_in('jenis', ['INVOICE', 'DEBIT NOTE'])
            ->limit(1)
            ->get('x_finance')
            ->row_array();

        if (!$finance) {
            return ['ok'=>false, 'error'=>'LOCAL_FINANCE_NOT_FOUND'];
        }

        $jenis     = strtoupper(trim((string)($finance['jenis'] ?? '')));
        $jenisCode = trim((string)($finance['jenis_code'] ?? '1'));
        $taxCodes  = ['TAX10','TAX1','TAX 11','TAX 1.1','TAX 12','TAX 1.2'];

        /*
         * VAT hanya relevan untuk INVOICE. DEBIT NOTE jangan disentuh
         * dengan rule PPN invoice.
         */
        if ($jenis === 'INVOICE') {
            $this->dbx
                ->where('number', $financeId)
                ->where('region_id', $region)
                ->group_start()
                    ->where('item', 'VAT')
                    ->or_where_in('code', $taxCodes)
                ->group_end()
                ->delete('x_finance_item');

            if ($this->dbx->trans_status() === false) {
                return ['ok'=>false, 'error'=>'FAILED_DELETE_OLD_VAT'];
            }
        }

        $items = $this->dbx
            ->where('number', $financeId)
            ->where('region_id', $region)
            ->order_by('id', 'ASC')
            ->get('x_finance_item')
            ->result_array();

        $dppTotal = 0;
        $ppnValues = [];
        $first = null;

        foreach ($items as $item) {
            if ($first === null) $first = $item;

            $price = (int)round((float)($item['price'] ?? 0));
            $dppTotal += $price;

            if ($jenis === 'INVOICE') {
                $ppn = trim((string)($item['ppn'] ?? ''));
                if ($ppn !== '' && strtoupper($ppn) !== 'OK') {
                    $ppnValues[$ppn] = $ppn;
                }
            }
        }

        $ppnTotal = 0;
        $tarifPpn = 0;
        $taxCode  = null;

        if ($jenis === 'INVOICE' && $jenisCode === '3' && $dppTotal > 0) {
            $ppnValues = array_values($ppnValues);

            if (count($ppnValues) > 1) {
                return [
                    'ok'=>false,
                    'error'=>'MULTIPLE_PPN_AFTER_INCOME_RELEASE',
                    'data'=>$ppnValues,
                ];
            }

            if (count($ppnValues) === 0) {
                return [
                    'ok'=>false,
                    'error'=>'PPN_NOT_FOUND_AFTER_INCOME_RELEASE',
                ];
            }

            $ppn = $ppnValues[0];

            foreach ($items as $item) {
                $lineDpp = (int)round((float)($item['price'] ?? 0));

                if ($ppn === '1.1%') {
                    $ppnTotal += $this->income_ppn_half_up_int($lineDpp, 11, 1000);
                    $taxCode = 'TAX 1.1';
                    $tarifPpn = 0.011;
                } elseif ($ppn === '1.2%') {
                    $ppnTotal += $this->income_ppn_half_up_int($lineDpp, 12, 1000);
                    $taxCode = 'TAX 1.2';
                    $tarifPpn = 0.012;
                } elseif ($ppn === '11%') {
                    $ppnTotal += $this->income_ppn_half_up_int($lineDpp, 11, 100);
                    $taxCode = 'TAX 11';
                    $tarifPpn = 0.11;
                } elseif ($ppn === '12%') {
                    $ppnTotal += $this->income_ppn_half_up_int($lineDpp, 11, 100);
                    $taxCode = 'TAX 12';
                    $tarifPpn = 0.11;
                } else {
                    return [
                        'ok'=>false,
                        'error'=>'UNKNOWN_PPN_AFTER_INCOME_RELEASE',
                        'ppn'=>$ppn,
                    ];
                }
            }

            if ($taxCode !== null && $ppnTotal > 0) {
                $vat = [
                    'jenis'      => 'INVOICE',
                    'kode_ref'   => (string)$finance['job_no'],
                    'bl_no'      => (string)($first['bl_no'] ?? ''),
                    'number'     => $financeId,
                    'code'       => $taxCode,
                    'item'       => 'VAT',
                    'qty'        => $tarifPpn,
                    'currency'   => 'RP.',
                    'unit_price' => $dppTotal,
                    'price'      => $ppnTotal,
                    'remarks'    => '',
                    'pre_col'    => 'PREPAID',
                    'id_tarik'   => 1,
                    'ppn'        => 'ok',
                    'region_id'  => $region,
                    'anak_job'   => (string)$finance['job_no'],
                ];

                if ($this->dbx->field_exists('source_income_id', 'x_finance_item')) {
                    $vat['source_income_id'] = null;
                }
                if ($this->dbx->field_exists('income_source_table', 'x_finance_item')) {
                    $vat['income_source_table'] = null;
                }
                if ($this->dbx->field_exists('income_source_id', 'x_finance_item')) {
                    $vat['income_source_id'] = null;
                }

                if (!$this->dbx->insert('x_finance_item', $vat)) {
                    return [
                        'ok'=>false,
                        'error'=>'FAILED_REBUILD_VAT_AFTER_INCOME_RELEASE',
                        'db_error'=>$this->dbx->error(),
                    ];
                }
            }
        }

        $grandTotal = $dppTotal + $ppnTotal;

        $update = [
            'grand_total' => $grandTotal,
        ];

        if ($jenis === 'INVOICE') {
            $update['ppn']       = $ppnTotal;
            $update['tarif_ppn'] = $ppnTotal > 0 ? $tarifPpn : 0;
        }

        if ($this->dbx->field_exists('updated_by', 'x_finance')) {
            $update['updated_by'] = (string)$this->session->userdata('fullname');
        }
        if ($this->dbx->field_exists('updated_at', 'x_finance')) {
            $update['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->dbx
            ->where('id', $financeId)
            ->where('region_id', $region)
            ->update('x_finance', $update);

        if ($this->dbx->trans_status() === false) {
            return [
                'ok'=>false,
                'error'=>'FAILED_UPDATE_FINANCE_TOTAL_AFTER_RELEASE',
                'db_error'=>$this->dbx->error(),
            ];
        }

        return [
            'ok'          => true,
            'finance_id'  => $financeId,
            'invoice_no'  => (string)$finance['number'],
            'jenis'       => $jenis,
            'remaining'   => count($items),
            'dpp_total'   => $dppTotal,
            'ppn_total'   => $ppnTotal,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Lepaskan satu inf_income_selling_price dari invoice lokal lama.
     *
     * source_income_id adalah jalur utama.
     * Untuk invoice legacy sebelum migration, fallback matching hanya dipakai
     * bila hasilnya TEPAT SATU x_finance_item.
     */
    private function detach_selling_income_from_local_invoice($incomeId, array $row, $invoiceNo, $region)
    {
        $incomeId  = (int)$incomeId;
        $invoiceNo = trim((string)$invoiceNo);
        $region    = trim((string)$region);

        if ($incomeId <= 0 || $invoiceNo === '' || $region === '') {
            return ['ok'=>true, 'released'=>false];
        }

        if (!$this->dbx->table_exists('x_finance') || !$this->dbx->table_exists('x_finance_item')) {
            return ['ok'=>false, 'error'=>'LOCAL_INVOICE_TABLE_NOT_FOUND'];
        }

        $finance = $this->local_invoice_header($invoiceNo, $region);

        /*
         * Invoice lokal sudah tidak ada: linkage source dianggap orphan.
         * Caller tetap boleh clear ship_co/ditarik.
         */
        if (!$finance) {
            return [
                'ok'=>true,
                'released'=>true,
                'orphan_invoice'=>true,
                'invoice_no'=>$invoiceNo,
            ];
        }

        if (strtolower(trim((string)($finance['status'] ?? ''))) === 'paid') {
            return [
                'ok'=>false,
                'error'=>'LOCAL_INVOICE_ALREADY_PAID',
                'invoice_no'=>$invoiceNo,
            ];
        }

        $financeId = (int)$finance['id'];

        $this->dbx->trans_begin();

        $deletedItemIds = [];

        /*
         * Direct mapping baru:
         * - source_income_id (compatibility selling price)
         * - income_source_table + income_source_id (generic)
         */
        $mappedIds = [];

        if ($this->dbx->field_exists('source_income_id', 'x_finance_item')) {
            $rows = $this->dbx
                ->select('id')
                ->where('number', $financeId)
                ->where('region_id', $region)
                ->where('source_income_id', $incomeId)
                ->get('x_finance_item')
                ->result_array();

            foreach ($rows as $r) {
                $mappedIds[] = (int)$r['id'];
            }
        }

        if (
            $this->dbx->field_exists('income_source_table', 'x_finance_item')
            && $this->dbx->field_exists('income_source_id', 'x_finance_item')
        ) {
            $rows = $this->dbx
                ->select('id')
                ->where('number', $financeId)
                ->where('region_id', $region)
                ->where('income_source_table', 'inf_income_selling_price')
                ->where('income_source_id', $incomeId)
                ->get('x_finance_item')
                ->result_array();

            foreach ($rows as $r) {
                $mappedIds[] = (int)$r['id'];
            }
        }

        $mappedIds = array_values(array_unique(array_filter($mappedIds)));

        if ($mappedIds) {
            $deletedItemIds = $mappedIds;

            $this->dbx
                ->where_in('id', $deletedItemIds)
                ->where('number', $financeId)
                ->where('region_id', $region)
                ->delete('x_finance_item');
        }

        /*
         * Fallback untuk historical x_finance_item yang source_income_id masih NULL.
         */
        if (!$deletedItemIds) {
            $taxCodes = ['TAX10','TAX1','TAX 11','TAX 1.1','TAX 12','TAX 1.2'];

            $legacyItems = $this->dbx
                ->where('number', $financeId)
                ->where('region_id', $region)
                ->where('id_tarik !=', 0)
                ->where_not_in('code', $taxCodes)
                ->where('item !=', 'VAT')
                ->get('x_finance_item')
                ->result_array();

            $matches = [];

            $norm = function ($v) {
                return strtoupper(trim((string)$v));
            };

            foreach ($legacyItems as $item) {
                if ($norm($item['kode_ref'] ?? '') !== $norm($row['kode_ref'] ?? '')) continue;
                if ($norm($item['code'] ?? '') !== $norm($row['code_service'] ?? '')) continue;
                if ($norm($item['item'] ?? '') !== $norm($row['item_service'] ?? '')) continue;
                if ($norm($item['currency'] ?? '') !== $norm($row['currency'] ?? '')) continue;

                if (abs((float)($item['qty'] ?? 0) - (float)($row['qty'] ?? 0)) > 0.0001) continue;
                if (abs((float)($item['unit_price'] ?? 0) - (float)($row['unit_price'] ?? 0)) > 0.01) continue;

                $sourcePrice = (int)round((float)($row['price'] ?? 0));
                $itemPrice   = (int)round((float)($item['price'] ?? 0));
                if (abs($itemPrice - $sourcePrice) > 1) continue;

                $matches[] = (int)$item['id'];
            }

            if (count($matches) > 1) {
                $this->dbx->trans_rollback();

                return [
                    'ok'=>false,
                    'error'=>'AMBIGUOUS_LEGACY_INVOICE_ITEM',
                    'invoice_no'=>$invoiceNo,
                    'source_income_id'=>$incomeId,
                    'invoice_item_ids'=>$matches,
                ];
            }

            if (count($matches) === 1) {
                $deletedItemIds = $matches;

                $this->dbx
                    ->where('id', $matches[0])
                    ->where('number', $financeId)
                    ->where('region_id', $region)
                    ->delete('x_finance_item');
            }
        }

        if ($this->dbx->trans_status() === false) {
            $error = $this->dbx->error();
            $this->dbx->trans_rollback();

            return [
                'ok'=>false,
                'error'=>'FAILED_DELETE_LOCAL_INVOICE_ITEM',
                'db_error'=>$error,
            ];
        }

        $recalc = $this->recalculate_local_invoice_after_income_release(
            $financeId,
            $region
        );

        if (empty($recalc['ok'])) {
            $this->dbx->trans_rollback();

            return [
                'ok'=>false,
                'error'=>'FAILED_RECALCULATE_OLD_INVOICE',
                'invoice_no'=>$invoiceNo,
                'recalculate'=>$recalc,
            ];
        }

        if ($this->dbx->trans_status() === false) {
            $error = $this->dbx->error();
            $this->dbx->trans_rollback();

            return [
                'ok'=>false,
                'error'=>'LOCAL_INVOICE_RELEASE_TRANSACTION_FAILED',
                'db_error'=>$error,
            ];
        }

        $this->dbx->trans_commit();

        return [
            'ok'=>true,
            'released'=>true,
            'invoice_no'=>$invoiceNo,
            'finance_id'=>$financeId,
            'deleted_invoice_item_ids'=>$deletedItemIds,
            'invoice_item_missing'=>empty($deletedItemIds),
            'recalculate'=>$recalc,
        ];
    }


    /**
     * Called BEFORE billing-sensitive edit/delete.
     *
     * Rules:
     * - UNBILLED: mutation boleh lanjut.
     * - BILLED + unpaid: ERP di-unbill/revision otomatis, local link di-reset.
     * - BILLED + paid:
     *     * normal user: block.
     *     * paid_edit whitelist: EDIT source boleh, invoice tetap immutable.
     *     * paid_delete whitelist: DELETE source boleh, invoice tetap immutable.
     *
     * $force=true + $changes=[] dipakai khusus delete paths.
     */
    private function prepare_income_mutation($table, $id, array $changes = [], $force = false)
    {
        $meta = $this->income_billing_meta($table);
        if (!$meta) {
            return ['ok'=>false, 'status'=>0, 'error'=>'INVALID_INCOME_SOURCE'];
        }

        $id = (int)$id;
        if ($id <= 0) {
            return ['ok'=>false, 'status'=>0, 'error'=>'INVALID_INCOME_ID'];
        }

        if (!$force && !$this->income_billing_sensitive_changes($changes)) {
            return ['ok'=>true, 'status'=>1, 'reset'=>[]];
        }

        $region = trim((string)$this->session->userdata('region_id'));

        $row = $this->dbx
            ->where('id', $id)
            ->where('region_id', $region)
            ->get($table)
            ->row_array();

        if (!$row) {
            return ['ok'=>false, 'status'=>0, 'error'=>'INCOME_ITEM_NOT_FOUND'];
        }

        $refCol    = $meta['ref_col'];
        $invoiceNo = trim((string)($row[$refCol] ?? ''));

        $billingStatus = $this->dbx->field_exists('billing_status', $table)
            ? strtoupper(trim((string)($row['billing_status'] ?? 'UNBILLED')))
            : ($invoiceNo !== '' ? 'BILLED' : 'UNBILLED');

        /*
         * Data legacy bisa mempunyai ditarik=1 tetapi ship_co kosong.
         * Untuk selling price, kondisi tersebut tetap dianggap belum linked
         * ke invoice sehingga edit boleh lanjut dan flag akan dinormalisasi.
         */
        if ($billingStatus !== 'BILLED' && $invoiceNo === '') {
            $reset = [];

            if (
                $table === 'inf_income_selling_price'
                && $this->dbx->field_exists('ditarik', $table)
                && (int)($row['ditarik'] ?? 0) !== 0
            ) {
                $reset['ditarik'] = 0;
            }

            return ['ok'=>true, 'status'=>1, 'reset'=>$reset];
        }

        $isDeleteAttempt       = ($force === true && empty($changes));
        $canPaidEditOverride   = $this->can_override_paid_income();
        $canPaidDeleteOverride = $this->can_override_paid_income_delete();

        /*
         * Paid authority = x_finance / ERP, bukan marker payment di source.
         * Marker source historis bisa stale setelah revision/unbill.
         */
        $financeSaysPaid = $this->local_invoice_is_paid($invoiceNo);

        if ($financeSaysPaid) {
            $paymentSource = 'X_FINANCE_STATUS';

            if ($isDeleteAttempt) {
                if (!$canPaidDeleteOverride) {
                    return [
                        'ok'         => false,
                        'status'     => 0,
                        'http'       => 409,
                        'error'      => 'PAID_INCOME_DELETE_NOT_ALLOWED',
                        'invoice_no' => $invoiceNo,
                    ];
                }

                return $this->allow_paid_income_delete_override(
                    $table,
                    $id,
                    $row,
                    $invoiceNo,
                    $paymentSource
                );
            }

            if (!$canPaidEditOverride) {
                return [
                    'ok'         => false,
                    'status'     => 0,
                    'http'       => 409,
                    'error'      => 'INCOME_ALREADY_PAID_CANNOT_EDIT',
                    'invoice_no' => $invoiceNo,
                ];
            }

            /*
             * Override PAID memang sengaja tidak detach invoice lama:
             * invoice/payment/GL dianggap immutable dan perubahan source diaudit.
             */
            return $this->allow_paid_income_override(
                $table,
                $id,
                $row,
                $changes,
                $invoiceNo,
                $paymentSource
            );
        }

        /*
         * Tidak ada local paid marker.
         * Minta ERP melakukan auto-unbill untuk BILLED + unpaid.
         */
        $api = finance_revenue_unbill_item([
            'company_id'   => 1,
            'region_id'    => $region,
            'source_db'    => (string)$this->dbx->database,
            'source_table' => $table,
            'source_id'    => (string)$id,
            'source_key'   => (string)($row[$meta['key_col']] ?? ''),
            'invoice_no'   => $invoiceNo,
            'reason'       => $isDeleteAttempt
                ? 'SOURCE_INCOME_DELETED'
                : 'SOURCE_INCOME_CHANGED',
        ]);

        if (empty($api['ok'])) {
            $message = strtoupper(trim((string)($api['response']['message'] ?? 'AUTO_UNBILL_FAILED')));
            $errorCode = strtoupper(trim((string)($api['response']['error'] ?? '')));

            $erpSaysPaid = (
                $message === 'INCOME_ALREADY_PAID_CANNOT_EDIT'
                || $message === 'INCOME_ALREADY_PAID_CANNOT_DELETE'
                || $errorCode === 'INCOME_ALREADY_PAID_CANNOT_EDIT'
                || $errorCode === 'INCOME_ALREADY_PAID_CANNOT_DELETE'
                || strpos($message, 'ALREADY_PAID') !== false
                || strpos($message, 'PAID_CANNOT_EDIT') !== false
                || strpos($message, 'PAID_CANNOT_DELETE') !== false
            );

            if ($erpSaysPaid) {
                if ($isDeleteAttempt) {
                    if (!$canPaidDeleteOverride) {
                        return [
                            'ok'         => false,
                            'status'     => 0,
                            'http'       => 409,
                            'error'      => 'PAID_INCOME_DELETE_NOT_ALLOWED',
                            'invoice_no' => $invoiceNo,
                            'erp'        => $api['response'] ?? null,
                        ];
                    }

                    $override = $this->allow_paid_income_delete_override(
                        $table,
                        $id,
                        $row,
                        $invoiceNo,
                        'ERP_PAYMENT_ALLOCATION'
                    );
                    $override['erp'] = $api['response'] ?? null;
                    return $override;
                }

                if ($canPaidEditOverride) {
                    $override = $this->allow_paid_income_override(
                        $table,
                        $id,
                        $row,
                        $changes,
                        $invoiceNo,
                        'ERP_PAYMENT_ALLOCATION'
                    );
                    $override['erp'] = $api['response'] ?? null;
                    return $override;
                }
            }

            return [
                'ok'         => false,
                'status'     => 0,
                'http'       => (int)($api['code'] ?? 500),
                'error'      => $api['response']['message'] ?? 'AUTO_UNBILL_FAILED',
                'invoice_no' => $invoiceNo,
                'erp'        => $api['response'] ?? null,
            ];
        }

        /*
         * ERP sudah mengizinkan unbill.
         *
         * Untuk selling price, lepaskan x_finance_item lokal yang berasal
         * dari source income ini dan recalculate invoice lama.
         */
        $localRelease = null;

        if ($table === 'inf_income_selling_price' && $invoiceNo !== '') {
            $localRelease = $this->detach_selling_income_from_local_invoice(
                $id,
                $row,
                $invoiceNo,
                $region
            );

            if (empty($localRelease['ok'])) {
                log_message(
                    'error',
                    'LOCAL_INVOICE_RELEASE_FAILED source=' . $table . '#' . $id
                    . ' invoice=' . $invoiceNo
                    . ' detail=' . json_encode($localRelease)
                );

                return [
                    'ok'         => false,
                    'status'     => 0,
                    'http'       => 500,
                    'error'      => 'LOCAL_INVOICE_RELEASE_FAILED',
                    'invoice_no' => $invoiceNo,
                    'erp'        => $api['response'] ?? null,
                    'local'      => $localRelease,
                ];
            }
        }

        /*
         * Reset local source agar item bisa ditarik ke invoice/group baru.
         */
        $reset = [$refCol => ''];

        if (
            $table === 'inf_income_selling_price'
            && $this->dbx->field_exists('ditarik', $table)
        ) {
            $reset['ditarik'] = 0;
        }

        if ($this->dbx->field_exists('billing_status', $table)) {
            $reset['billing_status'] = 'UNBILLED';
        }
        if ($this->dbx->field_exists('billed_at', $table)) {
            $reset['billed_at'] = null;
        }
        if ($this->dbx->field_exists('billed_by', $table)) {
            $reset['billed_by'] = null;
        }

        return [
            'ok'                    => true,
            'status'                => 1,
            'reset'                 => $reset,
            'auto_unbilled'         => true,
            'old_invoice_no'        => $invoiceNo,
            'erp'                   => $api['response'] ?? null,
            'local_invoice_release' => $localRelease,
        ];
    }

    /**
     * Satu pintu delete Income source.
     *
     * Penting: proses per-row. Karena auto-unbill adalah call ke ERP eksternal,
     * transaksi DB lokal tidak bisa membatalkan side-effect ERP secara atomik.
     * Dengan delete per-row, tidak ada row yang sudah di-unbill tapi kemudian
     * dibiarkan tetap ada karena batch delete gagal pada row berikutnya.
     */
    private function guarded_delete_income_ids($table, array $ids)
    {
        $meta = $this->income_billing_meta($table);
        if (!$meta) {
            return ['status'=>0, 'ok'=>false, 'error'=>'INVALID_INCOME_SOURCE'];
        }

        $region = trim((string)$this->session->userdata('region_id'));

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return ['status'=>0, 'ok'=>false, 'error'=>'NO_INCOME_IDS'];
        }

        $deletedIds         = [];
        $affected           = 0;
        $paidOverrideDelete = false;
        $autoUnbilled       = false;

        foreach ($ids as $id) {
            $guard = $this->prepare_income_mutation($table, $id, [], true);

            if (empty($guard['ok'])) {
                $guard['partial_deleted_ids'] = $deletedIds;
                $guard['partial_affected']    = $affected;
                return $guard;
            }

            $this->dbx
                ->where('id', $id)
                ->where('region_id', $region)
                ->delete($table);

            $rowAffected = (int)$this->dbx->affected_rows();

            if ($rowAffected <= 0) {
                return [
                    'status'              => 0,
                    'ok'                  => false,
                    'error'               => 'INCOME_DELETE_FAILED',
                    'source_table'        => $table,
                    'source_id'           => $id,
                    'partial_deleted_ids' => $deletedIds,
                    'partial_affected'    => $affected,
                ];
            }

            $deletedIds[] = $id;
            $affected += $rowAffected;

            if (!empty($guard['paid_override_delete'])) {
                $paidOverrideDelete = true;
            }
            if (!empty($guard['auto_unbilled'])) {
                $autoUnbilled = true;
            }
        }

        return [
            'status'               => 1,
            'ok'                   => true,
            'affected'             => $affected,
            'deleted_ids'          => $deletedIds,
            'paid_override'        => $paidOverrideDelete,
            'paid_override_delete' => $paidOverrideDelete,
            'auto_unbilled'        => $autoUnbilled,
        ];
    }

    private function filter_income_changes($table, array $changes)
    {
        $allowed = [
            'inf_income_selling_price' => [
                'container','code_service','item_service','qty','mchgs','unit_price','price',
                'currency','prep_coll','receive_from','remark','agent','g','ppn','invoice_db'
            ],
            'inf_income_from_agent' => [
                'item_service','container','currency','qty','unit_price','price','agent','invoice_db','g','prep_coll','hb_l'
            ],
            'inf_income_from_coloader' => [
                'container','code_service','item_service','currency','qty','unit_price','price','invoice_db','g','ppn','prep_coll','mchgs'
            ],
            'x_glbincome' => [
                'container','code_service','item_service','qty','unit_price','price','currency','prep_coll','payeble_to','group','remark'
            ],
        ];
        $keys = $allowed[$table] ?? [];
        return array_intersect_key($changes, array_flip($keys));
    }

    private function normalize_income_changes(array $changes)
    {
        foreach ($changes as $k => $v) {
            if (is_string($v)) $changes[$k] = trim(strip_tags($v));
        }
        foreach (['qty','unit_price','price','mchgs'] as $n) {
            if (isset($changes[$n])) {
                $x = str_replace([',',' '], '', (string)$changes[$n]);
                $changes[$n] = is_numeric($x) ? $x : 0;
            }
        }
        return $changes;
    }

    private function revenue_item_from_row($table, array $row, array $context)
    {
        $currency = strtoupper(trim((string)($row['currency'] ?? 'IDR')));
        if (in_array($currency, ['RP','RP.','RUPIAH',''], true)) $currency = 'IDR';
        $qty = (float)str_replace([',',' '], '', (string)($row['qty'] ?? 0));
        $unit = (float)str_replace([',',' '], '', (string)($row['unit_price'] ?? 0));
        $amount = (float)str_replace([',',' '], '', (string)($row['price'] ?? 0));
        if (abs($amount) < 0.000001) $amount = $qty * $unit;

        $billType = 'OTHER'; $billCode = ''; $billName = '';
        if ($table === 'inf_income_selling_price') {
            $billType = 'SHIPPER';
            $billCode = (string)($context['shipper_code'] ?? '');
            $billName = (string)($context['shipper_name'] ?? '');
        } elseif ($table === 'inf_income_from_agent') {
            $billType = 'AGENT';
            $billCode = (string)($row['agent'] ?? '');
            $billName = $billCode;
        } elseif ($table === 'inf_income_from_coloader') {
            $billType = 'CARRIER_COLOADER';
            $billCode = (string)($context['carrier'] ?? '');
            $billName = $billCode;
        } elseif ($table === 'x_glbincome') {
            $billCode = (string)($row['payeble_to'] ?? '');
            $billType = $billCode !== '' ? 'AGENT' : 'OTHER';
            $billName = $billCode;
        }

        return [
            'source_table'          => $table,
            'source_id'             => (string)$row['id'],
            'source_key'            => (string)($row['kode_ref'] ?? $row['job_no'] ?? ''),
            'code_service'          => $row['code_service'] ?? null,
            'item_service'          => $row['item_service'] ?? null,
            'qty'                   => $qty,
            'unit_price'            => $unit,
            'amount'                => $amount,
            'currency'              => $currency,
            'exchange_rate'         => $currency === 'USD' ? (float)($context['kurs'] ?? 1) : 1,
            'ppn'                   => $row['ppn'] ?? null,
            'prep_coll'             => $row['prep_coll'] ?? null,
            'group_code'            => $row['g'] ?? $row['group'] ?? null,
            'bill_to_type'          => $billType,
            'bill_to_code'          => $billCode,
            'bill_to_name'          => $billName,
            'billing_document_type' => $row['invoice_db'] ?? (($row['prep_coll'] ?? '') === 'COLLECT' ? 'DEBIT NOTE' : 'INVOICE'),
            'source_updated_at'     => $row['updated_at'] ?? $row['created_at'] ?? null,
        ];
    }


    /**
     * Resolve sales person untuk Revenue Order.
     *
     * Source of truth:
     * - trnstuffingplandetail.sales = mstadmin.user_id
     * - nama yang dikirim ke ERP = mstadmin.fullname
     *
     * Resolver dibuat defensif:
     * - menerima user_id langsung dari form/context
     * - bila kosong, cari sales dari jobsheet berdasarkan sp_detail_id / kode_ref / code
     * - fallback string username/fullname untuk data legacy
     */
    private function resolve_revenue_sales($sales_value = null, $job_ref = '', $sp_detail_id = 0, $region = '')
    {
        $region       = trim((string)$region);
        $job_ref      = trim((string)$job_ref);
        $sp_detail_id = (int)$sp_detail_id;
        $sales_raw    = trim((string)$sales_value);

        // Jika sales_id tidak dikirim dari form, ambil langsung dari jobsheet.
        if ($sales_raw === '' && $this->dbx->table_exists('trnstuffingplandetail')) {
            $this->dbx->select('sales')
                ->from('trnstuffingplandetail');

            if ($sp_detail_id > 0) {
                $this->dbx->where('sp_detail_id', $sp_detail_id);
            } elseif ($job_ref !== '') {
                $this->dbx->group_start()
                    ->where('kode_ref', $job_ref)
                    ->or_where('code', $job_ref)
                    ->group_end();
            } else {
                return ['sales_id' => null, 'sales_name' => null];
            }

            if ($region !== '' && $this->dbx->field_exists('region_id', 'trnstuffingplandetail')) {
                $this->dbx->where('region_id', $region);
            }

            $detail = $this->dbx->limit(1)->get()->row_array();
            $sales_raw = trim((string)($detail['sales'] ?? ''));
        }

        if ($sales_raw === '' || !$this->dbx->table_exists('mstadmin')) {
            return [
                'sales_id'   => is_numeric($sales_raw) && (int)$sales_raw > 0 ? (int)$sales_raw : null,
                'sales_name' => null,
            ];
        }

        $this->dbx->select('user_id,username,fullname,region_id,active')
            ->from('mstadmin');

        if (ctype_digit($sales_raw) && (int)$sales_raw > 0) {
            // trnstuffingplandetail.sales memang BIGINT -> mstadmin.user_id.
            $this->dbx->where('user_id', (int)$sales_raw);
        } else {
            // Fallback untuk data legacy yang pernah menyimpan username/fullname.
            $this->dbx->group_start()
                ->where('username', $sales_raw)
                ->or_where('fullname', $sales_raw)
                ->group_end();

            // Untuk lookup berbasis nama, scope region agar nama sama antar-region
            // tidak salah resolve. Untuk user_id tidak perlu karena PK global unique.
            if ($region !== '' && $this->dbx->field_exists('region_id', 'mstadmin')) {
                $this->dbx->where('region_id', $region);
            }
        }

        $admin = $this->dbx->limit(1)->get()->row_array();

        if (!$admin) {
            return [
                'sales_id'   => ctype_digit($sales_raw) && (int)$sales_raw > 0 ? (int)$sales_raw : null,
                'sales_name' => null,
            ];
        }

        $fullname = trim((string)($admin['fullname'] ?? ''));
        if ($fullname === '') {
            $fullname = trim((string)($admin['username'] ?? ''));
        }

        return [
            'sales_id'   => (int)$admin['user_id'],
            'sales_name' => $fullname !== '' ? $fullname : null,
        ];
    }

    public function sync_revenue_order($kode_ref, $sp_detail_id = 0, $kurs = 0, array $extra = [])
    {
        $kode_ref = trim((string)$kode_ref);
        $region = trim((string)$this->session->userdata('region_id'));
        if ($kode_ref === '' || $region === '') return ['status'=>0,'error'=>'KODE_REF_OR_REGION_REQUIRED'];

        // Keep the dependency intentionally small: these fields are already used
        // by the existing Income screen. Carrier is sent by the form because the
        // coloader receivable party belongs to the active jobsheet context.
        $this->dbx->select('sp_detail_id,kode_ref,shipper,sales')
            ->from('trnstuffingplandetail')
            ->where('kode_ref', $kode_ref);
        if ($this->dbx->field_exists('region_id','trnstuffingplandetail')) {
            $this->dbx->where('region_id', $region);
        }
        if ((int)$sp_detail_id > 0) $this->dbx->where('sp_detail_id', (int)$sp_detail_id);
        $ctxRow = $this->dbx->limit(1)->get()->row_array();
        if (!$ctxRow) return ['status'=>0,'error'=>'JOB_DETAIL_NOT_FOUND'];

        $shipperCode = trim((string)($extra['shipper_code'] ?? $ctxRow['shipper'] ?? ''));
        $shipperName = '';
        if ($shipperCode !== '') {
            $resolved = $this->get_shipper_id($shipperCode);
            if ($resolved && $resolved !== '0') $shipperName = (string)$resolved;
        }

        $context = [
            'shipper_code' => $shipperCode,
            'shipper_name' => $shipperName,
            'carrier'      => trim((string)($extra['carrier'] ?? '')),
            'kurs'         => (float)$kurs > 0 ? (float)$kurs : 1,
        ];
        if ($context['kurs'] <= 0) $context['kurs'] = 1;

        $items = [];
        foreach (['inf_income_selling_price','inf_income_from_agent','inf_income_from_coloader'] as $table) {
            $rows = $this->dbx->where('kode_ref',$kode_ref)->where('region_id',$region)->order_by('id','ASC')->get($table)->result_array();
            foreach ($rows as $row) $items[] = $this->revenue_item_from_row($table, $row, $context);
        }

        if (!$items) return ['status'=>0,'http'=>422,'error'=>'INCOME_ITEMS_EMPTY'];

        $salesRaw = trim((string)($extra['sales_id'] ?? $ctxRow['sales'] ?? ''));
        $sales = $this->resolve_revenue_sales(
            $salesRaw,
            $kode_ref,
            (int)$sp_detail_id,
            $region
        );

        $payload = [
            'company_id'=>1, 'region_id'=>$region, 'source_db'=>(string)$this->dbx->database,
            'source_type'=>'JOB_INCOME', 'source_key'=>$kode_ref, 'job_no'=>$kode_ref,
            'posting_date'=>date('Y-m-d'),
            'sales_id'=>$sales['sales_id'],
            'sales_name'=>$sales['sales_name'],
            'actor'=>(string)$this->session->userdata('fullname'), 'items'=>$items,
        ];

        $res = finance_revenue_sync($payload);
        return [
            'status'=>!empty($res['ok']) ? 1 : 0,
            'message'=>$res['response']['message'] ?? (!empty($res['ok']) ? 'REVENUE_ORDER_SYNC_OK' : 'REVENUE_ORDER_SYNC_FAILED'),
            'data'=>$res['response']['data'] ?? null, 'http'=>$res['code'] ?? 0
        ];
    }

    public function sync_global_revenue_order($job_no)
    {
        $job_no = trim((string)$job_no);
        $region = trim((string)$this->session->userdata('region_id'));
        if ($job_no === '' || $region === '') return ['status'=>0,'error'=>'JOB_NO_OR_REGION_REQUIRED'];

        $rows = $this->dbx->where('job_no',$job_no)->where('region_id',$region)->order_by('id','ASC')->get('x_glbincome')->result_array();
        if (!$rows) return ['status'=>0,'http'=>422,'error'=>'GLOBAL_INCOME_ITEMS_EMPTY'];

        $context = ['carrier'=>'', 'kurs'=>1];
        $items = [];
        foreach ($rows as $row) $items[] = $this->revenue_item_from_row('x_glbincome', $row, $context);

        // Global Income tidak mengirim sales dari form.
        // Resolve dari jobsheet berdasarkan job_no: kode_ref detail ATAU code/header.
        $sales = $this->resolve_revenue_sales(null, $job_no, 0, $region);

        $res = finance_revenue_sync([
            'company_id'=>1,'region_id'=>$region,'source_db'=>(string)$this->dbx->database,
            'source_type'=>'GLOBAL_INCOME','source_key'=>$job_no,'job_no'=>$job_no,
            'posting_date'=>date('Y-m-d'),
            'sales_id'=>$sales['sales_id'],
            'sales_name'=>$sales['sales_name'],
            'actor'=>(string)$this->session->userdata('fullname'),'items'=>$items,
        ]);
        return [
            'status'=>!empty($res['ok']) ? 1 : 0,
            'message'=>$res['response']['message'] ?? (!empty($res['ok']) ? 'REVENUE_ORDER_SYNC_OK' : 'REVENUE_ORDER_SYNC_FAILED'),
            'data'=>$res['response']['data'] ?? null,'http'=>$res['code'] ?? 0
        ];
    }

    public function mark_income_source_billed($table, $id, $invoice_no)
    {
        $meta = $this->income_billing_meta($table);
        if (!$meta || (int)$id <= 0 || trim((string)$invoice_no) === '') return false;
        $upd = [$meta['ref_col']=>trim((string)$invoice_no)];
        if ($this->dbx->field_exists('billing_status',$table)) $upd['billing_status']='BILLED';
        if ($this->dbx->field_exists('billed_at',$table)) $upd['billed_at']=date('Y-m-d H:i:s');
        if ($this->dbx->field_exists('billed_by',$table)) $upd['billed_by']=$this->session->userdata('fullname');
        $this->dbx->where('id',(int)$id)->where('region_id',$this->session->userdata('region_id'))->update($table,$upd);
        return $this->dbx->affected_rows() >= 0;
    }
	
	function update_sales($data)  
	{
		// 1. Mulai Transaksi Database
		$this->dbx->trans_start();

		// Update stuffing plan detail
		$this->dbx->update(
			'trnstuffingplandetail',
			array('sales' => $data['sales']),
			array("kode_ref" => $data['kode_ref'])
		);
		
		// Cek apakah kode_ref sudah ada di actual_sales
		$q = $this->dbx->get_where("actual_sales", array("kode_ref" => $data["kode_ref"]));
		if ($q->num_rows() > 0) {
			// Update salesman_id
			$this->dbx->update(
				'actual_sales',
				array('salesman_id' => $data['sales']),
				array("kode_ref" => $data['kode_ref'])
			);
		} else {
			// Insert baru kalau belum ada
			$this->dbx->insert('actual_sales', array(
				'kode_ref'      => $data['kode_ref'],
				'region_id'     => $data['region_id']??$this->session->userdata("region_id"),
				'salesman_id'   => $data['sales'],
				'year'          => date('Y'),
				'month'         => date('n'),
				'actual_revenue'=> 0,
				'actual_profit' => 0,
				'created_at'    => date('Y-m-d H:i:s')
			));
		}

		// 🔹 Update x_tracking_export.id_sales jika kode_ref ada
		$q2 = $this->dbx->get_where("x_tracking_export", array("no_job" => $data["kode_ref"]));
		if ($q2->num_rows() > 0) {
			$this->dbx->update(
				'x_tracking_export',
				array('id_sales' => $data['sales']),
				array("no_job" => $data['kode_ref'])
			);
		}

		// 2. Selesaikan Transaksi Database
		$this->dbx->trans_complete();

		// 3. Cek Status Transaksi & Catat Log
		if ($this->dbx->trans_status() === FALSE) {
			catat_log('Gagal memperbarui/menambahkan data sales untuk Kode Ref: ' . $data['kode_ref'], 'Modul Income');
			return array("status" => 0); 
		} else {
			catat_log('Berhasil memperbarui data sales menjadi '.$data['sales'].' untuk Kode Ref: ' . $data['kode_ref'], 'Modul Income');
			return array("status" => 1); 
		}
	}


	function get_data_agent_global()
	{
		$this->dbx->select('agent_company_code as payable');
		$this->dbx->where('region_id',$this->session->userdata("region_id"));
		$q3 = $this->dbx->get('refagentcompany')->result_array();
		return $q3;
	}
	
function calculate_discount_sum($data) {
    $result = [];

    // Group data by 'ppn' and 'g', only process when ppn = "12%"
    foreach ($data['myData'] as $array) {
        if (!isset($array['ppn']) || $array['ppn'] !== '12%') {
            continue; // Skip if ppn is not "12%"
        }

        // Keep empty 'g' values unchanged
        $group = isset($array['g']) ? $array['g'] : '';
        $key = "12%|$group";

        // Initialize if not set
        if (!isset($result[$key])) {
            $result[$key] = ['total_price' => 0, 'sum' => 0];
        }

        // Convert price to float and sum it
        $price = isset($array['price']) ? (float)$array['price'] : 0;
        $result[$key]['total_price'] += ceil($price);

        // Calculate the discounted tax
        $value = ceil($price) * (11 / 12) * 0.12;
        $result[$key]['sum'] += round($value);
    }

    return $result;
}



	function tarik_data_pajak($data) {
		
		
		foreach($data as $row => $key){
			$a[]=$key;
		}
		
		foreach($key as $r => $k){
			$b[]=array($k["ppn"],$k["price"],$k["g"],$k["kode_ref"],$k["sp_detail_id"]);
		}
		
		
		
		$data_summ = array();
		foreach ( $b as $val ) {
			$data_summ[$val[0]][ $val[2] ] = 0;	
		}

		foreach ( $b as $list ) {
			$number = str_replace( ",", ".", ceil($list[1]) ) * 1;
    		$data_summ[$list[0]][ $list[2] ] += (float)$number;
		}
		//print_r($data_summ);
		//die();
		
		if(isset($data_summ["1.2%"])) {	
		
		$TASK1 = $this->dbx->get_where("inf_income_selling_price",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TAX 1.2"));
		
		if($TASK1->num_rows()>=1) {
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_selling_price',
				array_map('intval', array_column($TASK1->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
			foreach($data_summ["1.2%"] as $row1 => $key1){
				$this->dbx->insert('inf_income_selling_price',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 1.2',
					'item_service'=>'TAX 1.2% ',
					'qty'=>'0.012',
					'unit_price'=>ceil($key1),
					'price'=>ceil($key1 * 0.012),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row1,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach($data_summ["1.2%"] as $row1 => $key1){
				$this->dbx->insert('inf_income_selling_price',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 1.2',
					'item_service'=>'TAX 1.2% ',
					'qty'=>'0.012',
					'unit_price'=>ceil($key1),
					'price'=>ceil($key1 * 0.012),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row1,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		
		$COST1 = $this->dbx->get_where("inf_cost_tbl_satu",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TAX 1.2"));
		if($COST1->num_rows()>=1) {
			$this->dbx->where('kode_ref', $k['kode_ref']);
			$this->dbx->where('region_id', $this->session->userdata("region_id"));
			$this->dbx->group_start();
			$this->dbx->where('code_service', 'TAX 1.2');
			//$this->dbx->or_like('code_service', 'TAX 1.1');
			$this->dbx->or_like('code_service', 'TAX1.2');
			$this->dbx->group_end();
			$this->dbx->delete('inf_cost_tbl_satu');
			//print_r($this->dbx->last_query());
			foreach($data_summ["1.2%"] as $row2 => $key2){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 1.2',
					'item_service'=>'TAX 1.2% ',
					'qty'=>'0.012',
					'unit_price'=>ceil($key2),
					'price'=>ceil($key2 * 0.012),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row2,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
					'payable_to'=>'PAJAK',
					
				));
				//print_r($this->dbx->last_query());
			}
		}
		else 
		{
			foreach($data_summ["1.2%"] as $row2 => $key2){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 1.2',
					'item_service'=>'TAX 1.2% ',
					'qty'=>'0.012',
					'unit_price'=>ceil($key2),
					'price'=>ceil($key2 * 0.012),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row2,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
					'payable_to'=>'PAJAK',
				));
			}
		}
		
	}
		
	if(isset($data_summ["12%"])) {
		$result = $this->calculate_discount_sum($data);
		//print_r($pajak12);
		//die();
		$TASK10 = $this->dbx->get_where("inf_income_selling_price",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TAX 12"));
		if($TASK10->num_rows()>=1) {
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_selling_price',
				array_map('intval', array_column($TASK10->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
			foreach ($result as $key => $values) {
				list($ppn, $group) = explode('|', $key);
				$this->dbx->insert('inf_income_selling_price',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 12',
					'item_service'=>'TAX 12% ',
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$group,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach ($result as $key => $values) {
				list($ppn, $group) = explode('|', $key);
				$this->dbx->insert('inf_income_selling_price',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 12',
					'item_service'=>'TAX 12% ',
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$group,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}	
		}
		
		
		$COST10 = $this->dbx->get_where("inf_cost_tbl_satu",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TAX 12"));
		if($COST10->num_rows()>=1) {
			$this->dbx->where('kode_ref', $k['kode_ref']);
			$this->dbx->where('region_id', $this->session->userdata("region_id"));
			$this->dbx->group_start();
			$this->dbx->where('code_service', 'TAX 12');
			$this->dbx->or_like('code_service', 'TAX 11');
			$this->dbx->group_end();
			$this->dbx->delete('inf_cost_tbl_satu');	
			foreach ($result as $key => $values) {
				list($ppn, $group) = explode('|', $key);
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 12',
					'item_service'=>'TAX 12% ',
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$group,
					//"ppn"=>"",
					'payable_to'=>'PAJAK',
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach($data_summ["12%"] as $row2 => $key2){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 12',
					'item_service'=>'TAX 12% ',
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$group,
					//"ppn"=>"",
					'payable_to'=>'PAJAK',
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}	
		}
		
	 }
	 
	 if(isset($data_summ["1.1%"])) {	
		
		$TASK1 = $this->dbx->get_where("inf_income_selling_price",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TAX 1.1"));
		
		////print_r($TASK1->result_object()[0]->currency);
		
		if($TASK1->num_rows()>=1) {
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_selling_price',
				array_map('intval', array_column($TASK1->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
			foreach($data_summ["1.1%"] as $row1 => $key1){
				$this->dbx->insert('inf_income_selling_price',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 1.1',
					'item_service'=>'TAX 1.1% ',
					'qty'=>'0.011',
					'unit_price'=>$key1,
					'price'=>($key1 * 0.011),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row1,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach($data_summ["1.1%"] as $row1 => $key1){
				$this->dbx->insert('inf_income_selling_price',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 1.1',
					'item_service'=>'TAX 1.1% ',
					'qty'=>'0.011',
					'unit_price'=>$key1,
					'price'=>ceil($key1 * 0.011),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row1,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		
		$COST1p1 = $this->dbx->get_where("inf_cost_tbl_satu",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TAX 1.1"));
		if($COST1p1->num_rows()>=1) {
			$this->dbx->where('kode_ref', $k['kode_ref']);
			$this->dbx->where('region_id', $this->session->userdata("region_id"));
			$this->dbx->group_start();
			//$this->dbx->where('code_service', 'TAX 1.2');
			$this->dbx->or_like('code_service', 'TAX 1.1');
			//$this->dbx->or_like('code_service', 'TAX1.2');
			$this->dbx->group_end();
			$this->dbx->delete('inf_cost_tbl_satu');
			foreach($data_summ["1.1%"] as $row1 => $key1){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 1.1',
					'item_service'=>'TAX 1.1% ',
					'qty'=>'0.011',
					'unit_price'=>$key1,
					'price'=>ceil($key1 * 0.011),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row1,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
					'payable_to'=>'PAJAK',
					
				));
			}
		}
		else 
		{
			foreach($data_summ["1.1%"] as $row1 => $key1){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 1.1',
					'item_service'=>'TAX 1.1% ',
					'qty'=>'0.011',
					'unit_price'=>$key1,
					'price'=>ceil($key1 * 0.011),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row1,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
					'payable_to'=>'PAJAK',
				));
			}
		}
		
	}
	
	if(isset($data_summ["11%"])) {
		
		$TASK11 = $this->dbx->get_where("inf_income_selling_price",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TAX 11"));
		if($TASK11->num_rows()>=1) {
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_selling_price',
				array_map('intval', array_column($TASK11->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
			foreach($data_summ["11%"] as $row2 => $key2){
				$this->dbx->insert('inf_income_selling_price',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 11',
					'item_service'=>'TAX 11% ',
					'qty'=>'0.11',
					'unit_price'=>$key2,
					'price'=>ceil($key2 * 0.11),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row2,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach($data_summ["11%"] as $row2 => $key2){
				$this->dbx->insert('inf_income_selling_price',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 11',
					'item_service'=>'TAX 11% ',
					'qty'=>'0.11',
					'unit_price'=>$key2,
					'price'=>ceil($key2 * 0.11),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row2,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}	
		}
		
		
		$COST11 = $this->dbx->get_where("inf_cost_tbl_satu",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TAX 11"));
		if($COST11->num_rows()>=1) {
			$this->dbx->delete('inf_cost_tbl_satu',array("kode_ref"=>$k['kode_ref'],'code_service'=>'TAX 11','region_id'=>$this->session->userdata("region_id")));	
			foreach($data_summ["11%"] as $row2 => $key2){
				//if($row2===0) { $row2 = "";}
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 11',
					'item_service'=>'TAX 11% ',
					'qty'=>'0.11',
					'unit_price'=>$key2,
					'price'=>ceil($key2 * 0.11),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row2,
					//"ppn"=>"",
					'payable_to'=>'PAJAK',
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach($data_summ["11%"] as $row2 => $key2){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TAX 11',
					'item_service'=>'TAX 11% ',
					'qty'=>'0.11',
					'unit_price'=>$key2,
					'price'=>ceil($key2 * 0.11),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row2,
					//"ppn"=>"",
					'payable_to'=>'PAJAK',
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}	
		}
		
	 }
	
		
		return array($data,$data_summ);

		
	}
	
function tarik_data_pajak_coloader($data) {
		
		
		foreach($data as $row => $key){
			$a[]=$key;
		}
		
		foreach($key as $r => $k){
			$b[]=array($k["ppn"],$k["price"],$k["g"],$k["kode_ref"],$k["sp_detail_id"]);
		}
		
		
		
		$data_summ = array();
		foreach ( $b as $val ) {
			$data_summ[$val[0]][ $val[2] ] = 0;	
		}

		foreach ( $b as $list ) {
			$number = str_replace( ",", ".", ceil($list[1]) ) * 1;
    		$data_summ[$list[0]][ $list[2] ] += (float)$number;
		}
		//print_r($data_summ);
		//die();
		
	if(isset($data_summ["12%"])) {
		$result = $this->calculate_discount_sum($data);
		//print_r($pajak12);
		//die();
		$TASK10 = $this->dbx->get_where("inf_income_from_coloader",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TXC 12"));
		if($TASK10->num_rows()>=1) {
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_from_coloader',
				array_map('intval', array_column($TASK10->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
			foreach ($result as $key => $values) {
				list($ppn, $group) = explode('|', $key);
				$this->dbx->insert('inf_income_from_coloader',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'g'=>$group,
					'invoice_db'=>'INVOICE',
					'code_service'=>'TXC 12',
					'item_service'=>'PPN 11% ',
					'ditarik'=>0,
					'region_id'=>$this->session->userdata("region_id"),
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach ($result as $key => $values) {
				list($ppn, $group) = explode('|', $key);
				$this->dbx->insert('inf_income_from_coloader',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'g'=>$group,
					'invoice_db'=>'INVOICE',
					'code_service'=>'TXC 12',
					'item_service'=>'PPN 11% ',
					'ditarik'=>0,
					'region_id'=>$this->session->userdata("region_id"),
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}	
		}
		
		
		$COST10 = $this->dbx->get_where("inf_cost_tbl_satu",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TXC 12"));
		if($COST10->num_rows()>=1) {
			$this->dbx->where('kode_ref', $k['kode_ref']);
			$this->dbx->where('region_id', $this->session->userdata("region_id"));
			$this->dbx->group_start();
			$this->dbx->where('code_service', 'TXC 12');
			$this->dbx->or_like('code_service', 'TXC 11');
			$this->dbx->group_end();
			$this->dbx->delete('inf_cost_tbl_satu');	
			foreach ($result as $key => $values) {
				list($ppn, $group) = explode('|', $key);
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TXC 12',
					'item_service'=>'PPN 11% ',
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$group,
					//"ppn"=>"",
					'payable_to'=>'PAJAK',
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach($data_summ["12%"] as $row2 => $key2){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TXC 12',
					'item_service'=>'PPN 11% ',
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$group,
					//"ppn"=>"",
					'payable_to'=>'PAJAK',
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}	
		}
		
	 }
	 
	 if(isset($data_summ["1.1%"])) {	
		
		$TASK1 = $this->dbx->get_where("inf_income_from_coloader",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TXC 1.1"));
		
		////print_r($TASK1->result_object()[0]->currency);
		
		if($TASK1->num_rows()>=1) {
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_from_coloader',
				array_map('intval', array_column($TASK1->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
			foreach($data_summ["1.1%"] as $row1 => $key1){
				$this->dbx->insert('inf_income_from_coloader',
					array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'g'=>$group,
					'invoice_db'=>'INVOICE',
					'code_service'=>'TXC 1.1',
					'item_service'=>'PPN 1.1% ',
					'ditarik'=>0,
					'region_id'=>$this->session->userdata("region_id"),
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}
		else 
		{
			foreach($data_summ["1.1%"] as $row1 => $key1){
				$this->dbx->insert('inf_income_from_coloader',
					array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'qty'=>'0.11',
					'unit_price'=>$values['total_price'],
					'price'=> $values['sum'],
					'currency'=>'RP.',
					'g'=>$group,
					'invoice_db'=>'INVOICE',
					'code_service'=>'TXC 1.1',
					'item_service'=>'PPN 1.1% ',
					'ditarik'=>0,
					'region_id'=>$this->session->userdata("region_id"),
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
		}

		
		$COST1p1 = $this->dbx->get_where("inf_cost_tbl_satu",array("kode_ref"=>$k["kode_ref"],'region_id'=>$this->session->userdata("region_id"),"code_service"=>"TXC 1.1"));
		if($COST1p1->num_rows()>=1) {
			$this->dbx->where('kode_ref', $k['kode_ref']);
			$this->dbx->where('region_id', $this->session->userdata("region_id"));
			$this->dbx->group_start();
			//$this->dbx->where('code_service', 'TAX 1.2');
			$this->dbx->or_like('code_service', 'TXC 1.1');
			//$this->dbx->or_like('code_service', 'TAX1.2');
			$this->dbx->group_end();
			$this->dbx->delete('inf_cost_tbl_satu');
			foreach($data_summ["1.1%"] as $row1 => $key1){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TXC 1.1',
					'item_service'=>'PPN 1.1% ',
					'qty'=>'0.011',
					'unit_price'=>$key1,
					'price'=>ceil($key1 * 0.011),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row1,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
					'payable_to'=>'PAJAK',
					
				));
			}
		}
		else 
		{
			foreach($data_summ["1.1%"] as $row1 => $key1){
				$this->dbx->insert('inf_cost_tbl_satu',
				array(
					'sp_detail_id'=>$k["sp_detail_id"],
					'kode_ref'=>$k['kode_ref'],
					'code_service'=>'TXC 1.1',
					'item_service'=>'PPN 1.1% ',
					'qty'=>'0.011',
					'unit_price'=>$key1,
					'price'=>ceil($key1 * 0.011),
					'currency'=>'RP.',
					'prep_coll'=>'PREPAID',
					'region_id'=>$this->session->userdata("region_id"),
					'g'=>$row1,
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
					'payable_to'=>'PAJAK',
				));
			}
		}
		
	}
	
	return array($data,$data_summ);

	}
    function update_price($data)
    {
        $table = (string)($data['table'] ?? '');
        $id = (int)($data['id'] ?? 0);
        $allowed = ['inf_income_selling_price','inf_income_from_agent','inf_income_from_coloader'];
        if (!in_array($table, $allowed, true) || $id <= 0) {
            return ['status'=>0,'error'=>'INVALID_UPDATE_PRICE_PAYLOAD'];
        }

        $price = str_replace([',',' '], '', (string)($data['price'] ?? 0));
        $price = is_numeric($price) ? $price : 0;

        $guard = $this->prepare_income_mutation($table, $id, ['price'=>$price]);
        if (empty($guard['ok'])) return $guard;

        $upd = array_merge(['price'=>$price], $guard['reset'] ?? []);
        $upd['updated_by'] = $this->session->userdata('fullname');
        $upd['updated_at'] = date('Y-m-d H:i:s');

        $this->dbx->where('id',$id)->where('region_id',$this->session->userdata('region_id'))->update($table,$upd);
        return [
            'status'=>1,
            'affected'=>$this->dbx->affected_rows(),
            'auto_unbilled'=>!empty($guard['auto_unbilled']),
            'paid_override'=>!empty($guard['paid_override']),
            'old_invoice_no'=>$guard['old_invoice_no'] ?? null,
        ];
    }


	
	function update_ppn($data) {
        // Compatibility endpoint only. Never mutate x_finance_item directly.
        // The Income source is canonical; invoice detail is rebuilt on re-post.
        $id = (int)($data['id'] ?? 0);
        $ppn = trim((string)($data['ppn'] ?? ''));
        if ($id <= 0) return ['status'=>0,'error'=>'INVALID_INCOME_ID'];

        $guard = $this->prepare_income_mutation('inf_income_selling_price', $id, ['ppn'=>$ppn]);
        if (empty($guard['ok'])) return $guard;

        $upd = array_merge(['ppn'=>$ppn], $guard['reset'] ?? []);
        $upd['updated_by'] = $this->session->userdata('fullname');
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $this->dbx->where('id',$id)->where('region_id',$this->session->userdata('region_id'))
            ->update('inf_income_selling_price',$upd);

        return [
            'status'=>1,
            'affected'=>$this->dbx->affected_rows(),
            'auto_unbilled'=>!empty($guard['auto_unbilled']),
            'paid_override'=>!empty($guard['paid_override']),
            'old_invoice_no'=>$guard['old_invoice_no'] ?? null,
        ];
	}

	function update_ppn_coloader($data) {
        // Compatibility endpoint only; same rule as selling-price PPN.
        $id = (int)($data['id'] ?? 0);
        $ppn = trim((string)($data['ppn'] ?? ''));
        if ($id <= 0) return ['status'=>0,'error'=>'INVALID_INCOME_ID'];

        $guard = $this->prepare_income_mutation('inf_income_from_coloader', $id, ['ppn'=>$ppn]);
        if (empty($guard['ok'])) return $guard;

        $upd = array_merge(['ppn'=>$ppn], $guard['reset'] ?? []);
        $upd['updated_by'] = $this->session->userdata('fullname');
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $this->dbx->where('id',$id)->where('region_id',$this->session->userdata('region_id'))
            ->update('inf_income_from_coloader',$upd);

        return [
            'status'=>1,
            'affected'=>$this->dbx->affected_rows(),
            'auto_unbilled'=>!empty($guard['auto_unbilled']),
            'paid_override'=>!empty($guard['paid_override']),
            'old_invoice_no'=>$guard['old_invoice_no'] ?? null,
        ];
	}

    public function saving_income($data)
    {
        $id       = isset($data['id']) ? (int)$data['id'] : 0;
        $kode_ref = trim((string)($data['kode_ref'] ?? ''));
        $table    = (string)($data['table'] ?? 'inf_income_selling_price');
        $changes  = isset($data['changes']) && is_array($data['changes']) ? $data['changes'] : [];

        $allowed = ['inf_income_selling_price','inf_income_from_agent','inf_income_from_coloader'];
        if (!in_array($table, $allowed, true)) return ['status'=>0,'error'=>'Invalid table'];
        if ($id <= 0 || $kode_ref === '' || !$changes) return ['status'=>0,'error'=>'Bad payload'];

        $changes = $this->filter_income_changes($table, $changes);
        $changes = $this->normalize_income_changes($changes);
        if (!$changes) return ['status'=>0,'error'=>'No editable changes'];

        $guard = $this->prepare_income_mutation($table, $id, $changes);
        if (empty($guard['ok'])) return $guard;
        $changes = array_merge($changes, $guard['reset'] ?? []);

        $changes['updated_by'] = $this->session->userdata('fullname');
        $changes['updated_at'] = date('Y-m-d H:i:s');

        $this->dbx->where('id',$id)->where('region_id',$this->session->userdata('region_id'))->update($table,$changes);
        $affected = $this->dbx->affected_rows();

        if ($affected > 0) {
            $this->dbx->where('kode_ref',$kode_ref)->where('region_id',$this->session->userdata('region_id'))->update('inf_income', [
                'last_update_by'=>$this->session->userdata('fullname'),
                'last_update_at'=>date('Y-m-d H:i:s')
            ]);
        }

        return [
            'status'=>1,
            'affected'=>$affected,
            'auto_unbilled'=>!empty($guard['auto_unbilled']),
            'paid_override'=>!empty($guard['paid_override']),
            'old_invoice_no'=>$guard['old_invoice_no'] ?? null,
        ];
    }
    function saving_agent($data)
    {
        $row = is_array($data['myData'] ?? null) ? $data['myData'] : [];
        return $this->saving_income([
            'id'=>(int)($data['id'] ?? 0),
            'kode_ref'=>(string)($row['kode_ref'] ?? ''),
            'table'=>'inf_income_from_agent',
            'changes'=>$row,
        ]);
    }
    function saving_coloader($data)
    {
        $row = is_array($data['myData'] ?? null) ? $data['myData'] : [];
        return $this->saving_income([
            'id'=>(int)($data['id'] ?? 0),
            'kode_ref'=>(string)($row['kode_ref'] ?? ''),
            'table'=>'inf_income_from_coloader',
            'changes'=>$row,
        ]);
    }
    function manage_from_agent($data)
    {
        $oper=(string)($data['oper'] ?? '');
        unset($data['oper']);
        $region=(string)$this->session->userdata('region_id');
        $fullname=(string)$this->session->userdata('fullname');
        $now=date('Y-m-d H:i:s');

        if ($oper==='add') {
            $data['item_service']=$data['item_service'] ?? 'REBATE AGENT';
            $data['currency']=$data['currency'] ?? 'USD';
            $data['region_id']=$region;
            $data['created_by']=$fullname; $data['created_at']=$now;
            if ($this->dbx->field_exists('billing_status','inf_income_from_agent')) $data['billing_status']='UNBILLED';
            $this->dbx->insert('inf_income_from_agent',$data);
            $id=(int)$this->dbx->insert_id();
            return ['status'=>$id>0?1:0,'id'=>$id,'oper'=>'add'];
        }

        if ($oper==='del') {
            $ids=array_filter(array_map('intval',explode(',',(string)($data['id'] ?? ''))));
            if (!$ids) return ['status'=>0,'error'=>'No ids'];

            $result = $this->guarded_delete_income_ids('inf_income_from_agent', $ids);
            $result['oper'] = 'del';
            return $result;
        }

        if ($oper==='edit') {
            $id=(int)($data['id'] ?? 0);
            $kolom=(string)($data['kolom'] ?? '');
            $allowed=['item_service','container','currency','qty','unit_price','agent','invoice_db','g'];
            if ($id<=0 || !in_array($kolom,$allowed,true)) return ['status'=>0,'error'=>'Invalid edit'];
            $update=[$kolom=>$data['isi'] ?? ''];
            if (isset($data['qty'])) $update['qty']=$data['qty'];
            if (isset($data['agent'])) $update['agent']=$data['agent'];
            if (isset($data['qty'],$data['unit_price'])) $update['price']=(float)$data['qty']*(float)$data['unit_price'];
            $update=$this->normalize_income_changes($this->filter_income_changes('inf_income_from_agent',$update));
            $guard=$this->prepare_income_mutation('inf_income_from_agent',$id,$update);
            if (empty($guard['ok'])) return $guard;
            $update=array_merge($update,$guard['reset'] ?? [],['updated_by'=>$fullname,'updated_at'=>$now]);
            $this->dbx->where('id',$id)->where('region_id',$region)->update('inf_income_from_agent',$update);
            return ['status'=>1,'affected'=>$this->dbx->affected_rows(),'id'=>$id,'oper'=>'edit','kolom'=>$kolom,'auto_unbilled'=>!empty($guard['auto_unbilled']),'paid_override'=>!empty($guard['paid_override'])];
        }
        return ['status'=>0,'error'=>'Invalid oper'];
    }

	
		
	function tarik_data_selling_price($data){	
		$row_misc 	= 	"";
		$row_of		=	"";
		$data_misc	=	array();
		$data_of	=	array();	
		$row_fcl 	= '';
		$data_fcl 	= '';
	
		$q1 = $this->dbx->get_where('inf_income_selling_price', array('kode_ref'=>$data['kode_ref'],'region_id'=>$this->session->userdata("region_id"),'id_tarik '=>'1'));
		
		if($q1->num_rows() > 0 ) 
		{
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_selling_price',
				array_map('intval', array_column($q1->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
		}
		
		$job = substr($data['kode_ref'], 4,3);
		
		if($job != '300' && $job != '301' && $job != '302' && $job != '400' && $job != '402' &&  $job != '600') 
		{
			$data['shipper_id'] = $this->get_shipper_id($data['shipper_id']);
			$q = $this->dbx->get_where('a_selling',array(
				'dari'=>$data['sales'],
				'kepada'=>$data['shipper_id'],
				'valid >='=> $this->fungsi->jin_date_sql($data['valid']), 
			));
			//print_r($this->dbx->last_query());
			//die();
			if($q->num_rows()>0) 
			{
				if($data['type'] == 'FCL' || $data['type'] == 'SF IMPORT FCL' ||  $data['shipment'] == 'FCL') 
				{
                    foreach($q->result() as $row)
					{
  						if ($data['type'] == 'TRUCKING ONLY' || $data['type'] == 'CUSTOM' || $data['type'] == 'TRUCKING VENDOR' || $data['type'] == 'TRUCKING EXTERNAL')
						{ $type = 'FCL';}
						else
						{
						 $type =$data['type'];
						}
						$query_misc = $this->dbx->get_where('a_selling_rate',array(
                                                  'id_selling'=>$row->id_selling,
                                                  'of_emkl'=>1,
                                                  'type'=>$type,
                                                  ));
	
						foreach($query_misc->result() as $row)
						{
							if($row->kondisi=='PREPAID') {
							$invoice_db='INVOICE';
							} else {
							$invoice_db='DEBIT NOTE';
							}
							if($row->set_m3 == 'SET' ) 
							{
								$set = 1;
							}
							else 
							{
								$set = $data['qty'];
							}
						
							if($row->mchgs >= $set )
							{       
                                $set = $row->mchgs;
							}
							if($row->currency=='USD' && $row->kondisi=='PREPAID') {
							$unit_price = floatval($row->price) * floatval($data["kurs"]);
							$currency = "RP.";
							}else{
							$unit_price = $row->price;
							$currency = $row->currency;
							}
							$price = floatval($set) * floatval($unit_price);
							if($row->destination =='' || $row->destination ==$data['destination']) {
							$this->dbx->insert('inf_income_selling_price', array(
								'kode_ref'=>$data['kode_ref'],
								'id_tarik'=>'1',
								'qty'=>$set,
								'mchgs'=>$row->mchgs,
								'item_service'=>$row->nama_service,
								'unit_price'=>$unit_price,
								'price'=>$price,
								'container'=>$row->container,
								'prep_coll'=>$row->kondisi,
								'currency'=>$currency,
								'sp_detail_id'=>$data['spd'],
								'invoice_db'=>$invoice_db,
								'region_id'=>$this->session->userdata("region_id"),
								"created_by"=>$this->session->userdata('fullname'),
								"created_at"=>date('Y-m-d H:i:s'),
							));
							}
						}
						//foreach($q->result() as $row)
						{
							$query = $this->dbx->get_where('a_selling_rate',array(
								//'container'=>$data['container'],
								'destination'=>$data['destination'],
								'id_selling'=>$row->id_selling,
								'of_emkl'=>0,
								'type'=>$type,
							));
						//print_r($this->dbx->last_query());
						//die();
							foreach($query->result() as $row)
							{
								if($row->set_m3 == 'SET' ) 
								{
									$set = '1';
								}
								else 
								{
									$set = $data['qty'];
								}
						
								if($row->mchgs >= $set ) 
								{
									$set = $row->mchgs;
								}
								if($row->currency=='USD' && $row->kondisi=='PREPAID') {
								$unit_price = floatval($row->price) * floatval($data["kurs"]);
								$currency = "RP.";
								}else{
								$unit_price = $row->price;
								$currency = $row->currency;
								}
								$price = floatval($set) * floatval($unit_price);
								if($row->kondisi=='PREPAID') {
								$invoice_db='INVOICE';
								} else {
								$invoice_db='DEBIT NOTE';
								}
								$this->dbx->insert('inf_income_selling_price', array(
									'kode_ref'=>$data['kode_ref'],
									'id_tarik'=>'1',
									'qty'=>$set,
									'mchgs'=>$row->mchgs,
									'item_service'=>$row->nama_service,
									'unit_price'=>$unit_price,
									'price'=>$price,
									'container'=>$row->container,
									'prep_coll'=>$row->kondisi,
									'currency'=>$currency,
									'sp_detail_id'=>$data['spd'],
									'invoice_db'=>$invoice_db,
									'region_id'=>$this->session->userdata("region_id"),
									"created_by"=>$this->session->userdata('fullname'),
									"created_at"=>date('Y-m-d H:i:s'),
								));
							}

						}
					
						$row_misc 	= 	'';
						$row_of		=	'';
						$data_misc	=	'';
						$data_of		=	'';
					
						$row_fcl 	= $query->num_rows();
						$data_fcl 	= $query->result();
					}
				}
				else
				{
				//print_r($q->result());
				//die();
					foreach($q->result() as $row)
					{ 
  						if ($data['type']='COLOAD')
						{
							$data['type']='LCL';
						}
						
							$query_of = $this->dbx->get_where('a_selling_rate',array(
							'id_selling'=>$row->id_selling,
							'of_emkl'=>0,
							'destination'=>$data['destination'],
							'type'=>$data['type'],
						));
						//print_r($this->dbx->last_query());
						//die();

						foreach($query_of->result() as $row_of)
						{
							if($row_of->set_m3 == 'SET' ) 
							{
								$set = '1';
							}
							else 
							{
								$set = $data['qty'];
							}
						
							if($row_of->mchgs >= $set )
							{       
							$set = $row_of->mchgs;
							}
							if($row_of->currency=='USD' && $row_of->kondisi=='PREPAID') {
							$unit_price = floatval($row_of->price) * floatval($data["kurs"]);
							$currency = "RP.";
							}else{
							$unit_price = $row_of->price;
							$currency = $row_of->currency;
							}
							$price = floatval($set) * floatval($unit_price);
							if($row_of->kondisi=='PREPAID') {
							$invoice_db='INVOICE';
							} else {
							$invoice_db='DEBIT NOTE';
							}
							$this->dbx->insert('inf_income_selling_price', array(
								'kode_ref'=>$data['kode_ref'],
								'id_tarik'=>'1',
								'qty'=>$set,
								'mchgs'=>$row_of->mchgs,
								'item_service'=>$row_of->nama_service,
								'unit_price'=>$unit_price,
								'price'=>$price,
								'container'=>$row_of->container,
								'prep_coll'=>$row_of->kondisi,
								'currency'=>$currency,
								'sp_detail_id'=>$data['spd'],
								'invoice_db'=>$invoice_db,
								'region_id'=>$this->session->userdata("region_id"),
								"created_by"=>$this->session->userdata('fullname'),
								"created_at"=>date('Y-m-d H:i:s'),
							));
						}
						
						$query_misc = $this->dbx->get_where('a_selling_rate',array(
                                                  'id_selling'=>$row->id_selling,
												  'of_emkl'=>1,
                                                  'type'=>$data['type'],
                                                  ));
						//print_r($this->dbx->last_query());
						//die();
						foreach($query_misc->result() as $row)
						//print_r($query_misc->result());
						{
							if($row->set_m3 == 'SET' ) 
							{
								$set = '1';
							}
							else 
							{
								$set = $data['qty'];
							}
						
							if($row->mchgs >= $set )
							{       
                              $set = $row->mchgs;
							}
							if($row->currency=='USD' && $row->kondisi=='PREPAID') {
							$unit_price = floatval($row->price) * floatval($data["kurs"]);
							$currency = "RP.";
							}else{
							$unit_price = $row->price;
							$currency = $row->currency;
							}
							if($row->kondisi=='PREPAID') {
							$invoice_db='INVOICE';
							} else {
							$invoice_db='DEBIT NOTE';
							}
							$price = floatval($set) * floatval($unit_price);
							if($row->destination =='' || $row->destination == $data['destination']) {
							//	print_r($row->destination);
							//die();
							$this->dbx->insert('inf_income_selling_price', array(
								'kode_ref'=>$data['kode_ref'],
								'id_tarik'=>'1',
								'qty'=>$set,
								'mchgs'=>$row->mchgs,
								'item_service'=>$row->nama_service,
								'unit_price'=>$unit_price,
								'price'=>$price,
								'container'=>$row->container,
								'prep_coll'=>$row->kondisi,
								'currency'=>$currency,
								'sp_detail_id'=>$data['spd'],
								'invoice_db'=>$invoice_db,
								'region_id'=>$this->session->userdata("region_id"),
								"created_by"=>$this->session->userdata('fullname'),
								"created_at"=>date('Y-m-d H:i:s'),
							));
							}else{
								//print_r('keadaan false');
							//die();	
							}
						}
						//print_r($query_misc->result());
						//die();
					
					}
					//print_r($this->dbx->last_query());
					//die();
					$row_misc 	= 	$query_misc->num_rows();
					$row_of		=	$query_of->num_rows();
					$data_misc	=	$query_misc->result();
					$data_of		=	$query_of->result();
				
					$row_fcl 	= '';
					$data_fcl 	= '';
				}
			}

			return;
		}
		else
		{
						
			$this->dbx->select('service,port_loading,pre_col,type,currency,unit_price,mchgs,container,data_id');
			$this->dbx->from('ref_import_detail');
			$this->dbx->join('ref_import','ref_import.id=ref_import_detail.data_id','left');
			$this->dbx->where('ref_import_detail.port_loading', $data['destination']);
			$this->dbx->where('ref_import.consignee_id', $data['shipper_id']);
			$this->dbx->where('ref_import.sales_id', $data['sales']);
			$q3 = $this->dbx->get();
			//print_r($q3->num_rows());
				//die();
			
			if($q3->num_rows() > 0 ) {
				$wieght = $this->dbx->get_where("trnstuffingplandetail",array("sp_detail_id"=>$data["spd"]))->row()->weight;				
				$w = $wieght/1000;
				foreach($q3->result() as $row){
            	if($row->mchgs >= $data['qty'] ) {       
               	$set = $row->mchgs;
					} else if (($data['qty'])<=$w) {
               	$set = $w;
				 	} else {
                	$set = $data['qty'];
            	}
            	if($row->pre_col=='PREPAID') {
				$invoice_db='INVOICE';
				} else {
				$invoice_db='DEBIT NOTE';
				}
            	$unit_price = $row->unit_price;
            	if($row->currency=='USD' && $row->pre_col=='PREPAID') {
							$unit_price = floatval($row->unit_price) * floatval($data["kurs"]);
							$currency = "RP.";
							}else{
							$unit_price = $row->unit_price;
							$currency = $row->currency;
							}
                                                          
						$this->dbx->insert('inf_income_selling_price', array(
						'kode_ref'=>$data['kode_ref'],
						'id_tarik'=>'1',
						'qty'=>$set,
						'mchgs'=>$row->mchgs,
						'item_service'=>$row->service,
						'unit_price'=>$unit_price,
						'price'=>floatval($set) * floatval($unit_price),
						'container'=>$row->container,
						'prep_coll'=>$row->pre_col,
						'currency'=>$currency,
						'sp_detail_id'=>$data['spd'],
						'invoice_db'=>$invoice_db,
						'region_id'=>$this->session->userdata("region_id"),
						"created_by"=>$this->session->userdata('fullname'),
						"created_at"=>date('Y-m-d H:i:s'),
					));
				}

			}
			
			////print_r(array('job'=>$job,'mode'=>2, "result"=>$data['sales']));	
		}
		return array($data);
	}
	

	function check_data_income($kode_ref){
		$q = $this->dbx->get_where('inf_income',array('kode_ref'=>$kode_ref));
		if($q->num_rows() == 1) {
			return $q->row_array();
		} else {
			$this->dbx->insert('inf_income', array('kode_ref'=>$kode_ref,'Intensiv_tidak_diproses'=>'FALSE','Tarik_insentiv_per_Doc'=>'FALSE','status'=>'unposted','kurs'=>'','region_id'=>$this->session->userdata('region_id')));
			if($this->dbx->affected_rows() > 0) {
				$q = $this->dbx->get_where('inf_income',array('kode_ref'=>$kode_ref));
				return $q->row_array();
				} else {
				return 0;	
			}
		}	
	}
	function check_data_global_income($kode_ref){
		$q = $this->dbx->get_where('inf_income',array('kode_ref'=>$kode_ref));
		if($q->num_rows() == 1) {
			return $q->row_array();
		} else {
			$this->dbx->insert('inf_income', 
			array('kode_ref'=>$kode_ref,'Intensiv_tidak_diproses'=>'FALSE','Tarik_insentiv_per_Doc'=>'FALSE','id_sd'=>"",'status'=>'unposted','kurs'=>'','region_id'=>$this->session->userdata('region_id')));
			if($this->dbx->affected_rows() > 0) {
				$q = $this->dbx->get_where('inf_income',array('kode_ref'=>$kode_ref));
				return $q->row_array();
				} else {
				return 0;	
			}
		}	
	}
	
	 function update_header_income($data) 
	{
		if($data['kode_ref']!='') 
		{
			$this->dbx->update('inf_income',array('kurs'=>$data['kurs'],'tarik_insentiv_per_doc'=>$data['tarik_insentiv_per_doc'],'intensiv_tidak_diproses'=>$data['intensiv_tidak_diproses']),array("kode_ref"=>$data['kode_ref']));
		}
		
		return array("status"=>$this->dbx->affected_rows());
	}
	
function insert_data_selling_price($data){
    $data['region_id']  = $this->session->userdata("region_id");
    $data["created_by"] = $this->session->userdata('fullname');
    $data["created_at"] = date('Y-m-d H:i:s');

    // --- PERBAIKAN: Gunakan nilai default HANYA jika nilainya belum di-set / kosong ---
    
    // Jika qty kosong, jadikan 1. Jika sudah ada isinya, biarkan saja.
    $data["qty"]        = isset($data["qty"]) ? $data["qty"] : 1;
    $data["unit_price"] = isset($data["unit_price"]) ? $data["unit_price"] : 0;
    $data["price"]      = isset($data["price"]) ? $data["price"] : 0;
    
    // Jika saat ditarik datanya adalah USD, ia akan tetap USD. Jika baris baru, jadi RP.
    $data["currency"]   = isset($data["currency"]) ? $data["currency"] : 'RP.';
    $data["prep_coll"]  = isset($data["prep_coll"]) ? $data["prep_coll"] : 'PREPAID';
    $data["invoice_db"] = isset($data["invoice_db"]) ? $data["invoice_db"] : 'INVOICE';
    
    // ---------------------------------------------------------------------------------

    $this->dbx->insert('inf_income_selling_price', $data);
    
    if($this->dbx->affected_rows() > 0) {
        $id = $this->dbx->insert_id(); // Lebih aman dari select_max
        return array('status'=>1, 'id'=>$id);
    } else {
        return array('status'=>0, 'id'=>'');
    }
}
		
	function get_shipper_id($id){
		$q = $this->dbx->get_where('mstshipper',array('id_shipper'=>$id));
		if($q->num_rows()>0) {
			return $q->row()->shipper_name;
		}
		else 
		{
			$this->dbx->where("consignee_import_code =",$id);
			$query = $this->dbx->get('refconsigneeimport');
			if($query->num_rows()>0) {
				return $query->row()->consignee_import_id;
			}
		}
	}
	
	function tarik_data_coloader($data){	
		$q = $this->dbx->get_where('inf_income_from_coloader', array('kode_ref'=>$data['kode_ref'],'region_id'=>$this->session->userdata("region_id"),'id_tarik !='=>'0'));
		
		if($q->num_rows() > 0 ) {
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_from_coloader',
				array_map('intval', array_column($q->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
		}
		$this->dbx->select('*');
		$this->dbx->from('x_rebate_detail');
		$this->dbx->join('x_rebate','x_rebate.ref_no=x_rebate_detail.ref_no','left');
		$this->dbx->where('x_rebate.code_shipco',$data['shipp_co']);
		$this->dbx->where('x_rebate_detail.destination',$data['destination']);
					
		$q1 = $this->dbx->get();	
		
		if($q1->num_rows() > 0 ) {
			foreach ($q1->result() as $row)
			{
				if($row->freight=='PREPAID') {
						$invoice_db='INVOICE';
					} else {
						$invoice_db='DEBIT NOTE';
				}
				$this->dbx->insert('inf_income_from_coloader',array(
					'id_tarik'=>$row->id_detail,
					'mchgs'=>$row->min_rebate,
					'kode_ref'=>$data['kode_ref'],
					'container'=>$data['countainer'],
					'qty'=>$data['qty'],
					'unit_price'=>$row->amount,
					'price'=>floatval($data['qty'])*floatval($row->amount),
					'currency'=>'USD',
					'invoice_db'=>$invoice_db,
					'sp_detail_id'=>$data['spd'],
					'region_id'=>$this->session->userdata("region_id"),
					"created_by"=>$this->session->userdata('fullname'),
					"created_at"=>date('Y-m-d H:i:s'),
				));
			}
			return array('data'=>$data,'q'=>$q->result(),'q1'=>$q1->result());
		} else {
			$data_new = array();
			$q1 = array();
			return array($data,$q->result(),$q1);
		}
	}

	function delete_id_tarik_sama($kode_ref){
		$this->dbx->order_by('id','asc');
		$q = $this->dbx->get_where('inf_income_selling_price',array('kode_ref'=>$kode_ref, 'id_tarik !='=>'0'));
		foreach ($q->result() as $row){
			$id[]=$row->id;
		}
		$awal = array_shift($id)-1;
		$akhir = array_pop($id);
		while($awal<=$akhir) {
			$idnya['id']=$awal;
			$awal=$awal+2;
            $targetId = (int)$idnya['id'];

            $exists = $this->dbx
                ->select('id')
                ->where('id', $targetId)
                ->where('region_id', $this->session->userdata('region_id'))
                ->get('inf_income_selling_price')
                ->row_array();

            if ($exists) {
                $deleteResult = $this->guarded_delete_income_ids(
                    'inf_income_selling_price',
                    [$targetId]
                );

                if (empty($deleteResult['status'])) {
                    return $deleteResult;
                }
            }
		}
	}
		
	function tampil_selling_price($data,$limit){
		$region = $this->session->userdata("region_id");
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		
		return $this->dbx->get_where('inf_income_selling_price',array('kode_ref'=>$data['kode_ref'],'region_id'=>$region))->result();
			
		//return $this->dbx->get_where('inf_income_selling_price',array('kode_ref'=>$data['kode_ref']))->result();	
	}
	
	function get_from_coloader($data,$limit)
	{
		$region = $this->session->userdata("region_id");
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		return $this->dbx->get_where('inf_income_from_coloader',array('kode_ref'=>$data['kode_ref'],'region_id'=>$region))->result();
	}
	
	function get_payment($data)
	{
		$region = $this->session->userdata("region_id");
		return $this->dbx->get_where('x_finance',array('number'=>$data,'region_id'=>$region))->row('status');
	}
    public function manage_income($data)
    {
        $oper = (string)($data['oper'] ?? '');
        unset($data['oper']);
        $region = (string)$this->session->userdata('region_id');
        $fullname = (string)$this->session->userdata('fullname');
        $now = date('Y-m-d H:i:s');

        if (!in_array($oper,['add','edit','del'],true)) return ['status'=>0,'error'=>'Invalid oper'];

        $allowedCols = ['container','code_service','item_service','qty','unit_price','price','currency','prep_coll','receive_from','remark','agent','g','ppn'];
        $kolom = (string)($data['kolom'] ?? '');
        if ($oper !== 'del' && !in_array($kolom,$allowedCols,true)) return ['status'=>0,'error'=>'Invalid kolom'];

        $cleanText = function($v){ return trim(strip_tags((string)$v)); };
        $cleanNum = function($v){ $x=str_replace([' ', ','],'',(string)$v); return is_numeric($x)?(float)$x:0.0; };

        if ($oper === 'add') {
            $spd=(int)($data['spd'] ?? 0); $kode_ref=$cleanText($data['kode_ref'] ?? '');
            if ($spd<=0 || $kode_ref==='' || $kolom==='') return ['status'=>0,'error'=>'Bad payload add'];
            $isi = in_array($kolom,['qty','unit_price','price'],true) ? $cleanNum($data['isi'] ?? 0) : strtoupper($cleanText($data['isi'] ?? ''));
            $this->dbx->insert('inf_income_selling_price',[
                'sp_detail_id'=>$spd,'region_id'=>$region,'kode_ref'=>$kode_ref,$kolom=>$isi,
                'billing_status'=>$this->dbx->field_exists('billing_status','inf_income_selling_price')?'UNBILLED':null,
                'created_by'=>$fullname,'created_at'=>$now
            ]);
            $id=(int)$this->dbx->insert_id();
            return ['status'=>$id>0?1:0,'id'=>$id,'oper'=>'add'];
        }

        if ($oper === 'edit') {
            $id=(int)($data['id'] ?? 0); $kode_ref=$cleanText($data['kode_ref'] ?? '');
            if ($id<=0 || $kolom==='') return ['status'=>0,'error'=>'Bad payload edit'];
            $isiRaw=$data['isi'] ?? '';
            $isi=in_array($kolom,['qty','unit_price','price'],true)?$cleanNum($isiRaw):strtoupper($cleanText($isiRaw));
            $update=[$kolom=>$isi];
            if ($kolom==='prep_coll') $update['invoice_db']=(strtoupper((string)$isiRaw)==='COLLECT')?'DEBIT NOTE':'INVOICE';
            $qty=array_key_exists('qty',$data)?$cleanNum($data['qty']):null;
            $up=array_key_exists('unit_price',$data)?$cleanNum($data['unit_price']):null;
            if ($kolom==='qty') $qty=(float)$isi;
            if ($kolom==='unit_price') $up=(float)$isi;
            if ($qty!==null && $up!==null) $update['price']=$qty*$up;

            $guard=$this->prepare_income_mutation('inf_income_selling_price',$id,$update);
            if (empty($guard['ok'])) return $guard;
            $update=array_merge($update,$guard['reset'] ?? [],['updated_by'=>$fullname,'updated_at'=>$now]);
            $this->dbx->where('id',$id)->where('region_id',$region)->update('inf_income_selling_price',$update);
            return ['status'=>1,'affected'=>$this->dbx->affected_rows(),'oper'=>'edit','auto_unbilled'=>!empty($guard['auto_unbilled']),'paid_override'=>!empty($guard['paid_override']),'old_invoice_no'=>$guard['old_invoice_no'] ?? null];
        }

        $ids=array_filter(array_map('intval',explode(',',(string)($data['id'] ?? ''))));
        if (!$ids) return ['status'=>0,'error'=>'No ids'];

        $result = $this->guarded_delete_income_ids('inf_income_selling_price', $ids);
        $result['oper'] = 'del';
        return $result;
    }


	
	function get_from_agent($data,$limit)
	{
		$region = $this->session->userdata("region_id");
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		return $this->dbx->get_where('inf_income_from_agent',array('kode_ref'=>$data['kode_ref'],'region_id'=>$region))->result();
	}
	
	function add_from_coloader($spd,$kode_ref,$kolom,$isi)
	{
		$data = array('kode_ref'=>$kode_ref,$kolom=>$isi,'region_id'=>$this->session->userdata('region_id'),'updated_by'=>$this->session->userdata('fullname'),'updated_at'=>date('Y-m-d H:i:s'));
		$this->dbx->insert('inf_income_from_coloader',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
    function update_from_coloader($kolom,$isi,$id)
    {
        $id = (int)$id;
        $changes = $this->normalize_income_changes(
            $this->filter_income_changes('inf_income_from_coloader', [$kolom => $isi])
        );

        if ($id <= 0 || !$changes) return '0';

        $guard = $this->prepare_income_mutation(
            'inf_income_from_coloader',
            $id,
            $changes
        );

        if (empty($guard['ok'])) return '0';

        $changes = array_merge($changes, $guard['reset'] ?? []);
        $changes['updated_by'] = $this->session->userdata('fullname');
        $changes['updated_at'] = date('Y-m-d H:i:s');

        $this->dbx
            ->where('id',$id)
            ->where('region_id',$this->session->userdata('region_id'))
            ->update('inf_income_from_coloader',$changes);

        return $this->dbx->affected_rows() > 0 ? '1' : '0';
    }
	
    function delete_from_coloader($id)
    {
        $result = $this->guarded_delete_income_ids(
            'inf_income_from_coloader',
            [(int)$id]
        );

        // Preserve legacy return contract for old callers.
        return !empty($result['status']) ? '1' : '0';
    }
	
	function manage_header_income($data)
	{
		$this->dbx->where('kode_ref',$data['kode_ref']);
		$this->dbx->update('inf_income',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}

    function clear_agent_coloader($kode_ref)
    {
        $kode_ref = trim((string)$kode_ref);
        $region = trim((string)$this->session->userdata('region_id'));

        if ($kode_ref === '' || $region === '') return '0';

        foreach (['inf_income_from_coloader','inf_income_from_agent'] as $table) {
            $rows = $this->dbx
                ->select('id')
                ->where('kode_ref', $kode_ref)
                ->where('region_id', $region)
                ->get($table)
                ->result_array();

            $ids = array_map('intval', array_column($rows, 'id'));
            if (!$ids) continue;

            $result = $this->guarded_delete_income_ids($table, $ids);
            if (empty($result['status'])) return '0';
        }

        return '1';
    }
	
    function update_agent_coloader($data)
    {
        $region = trim((string)$this->session->userdata('region_id'));
        $kodeRef = trim((string)($data['kode_ref'] ?? ''));
        $qty = (float)($data['qty'] ?? 0);

        $q = $this->dbx->get_where(
            'inf_income_from_agent',
            ['kode_ref'=>$kodeRef, 'region_id'=>$region]
        )->result();

        $affected = 0;

        foreach ($q as $row) {
            $changes = [
                'qty'   => $qty,
                'price' => $qty * (float)$row->unit_price,
            ];

            $guard = $this->prepare_income_mutation(
                'inf_income_from_agent',
                (int)$row->id,
                $changes
            );

            if (empty($guard['ok'])) {
                return 0;
            }

            $changes = array_merge($changes, $guard['reset'] ?? []);
            $changes['updated_by'] = $this->session->userdata('fullname');
            $changes['updated_at'] = date('Y-m-d H:i:s');

            $this->dbx
                ->where('id', (int)$row->id)
                ->where('region_id', $region)
                ->update('inf_income_from_agent', $changes);

            $affected += max(0, (int)$this->dbx->affected_rows());
        }

        return $affected;
    }

	function get_consignee_id($consignee)
	{
		$q = $this->dbx->get_where('refconsigneeimport',array('consignee_import_code'=>$consignee));
		if($q->num_rows() > 0 ) {
			return $q->row()->consignee_import_id;
		} else {
			return '0';
		}
	}
    function manage_from_coloader($data)
    {
        $oper=(string)($data['oper'] ?? '');
        unset($data['oper']);
        $region=(string)$this->session->userdata('region_id');
        $fullname=(string)$this->session->userdata('fullname');
        $now=date('Y-m-d H:i:s');

        if ($oper==='add') {
            $data['currency']=$data['currency'] ?? 'USD';
            $data['region_id']=$region; $data['created_by']=$fullname; $data['created_at']=$now;
            if ($this->dbx->field_exists('billing_status','inf_income_from_coloader')) $data['billing_status']='UNBILLED';
            $this->dbx->insert('inf_income_from_coloader',$data);
            $id=(int)$this->dbx->insert_id();
            return ['status'=>$id>0?1:0,'id'=>$id,'oper'=>'add'];
        }

        if ($oper==='del') {
            $ids=array_filter(array_map('intval',explode(',',(string)($data['id'] ?? ''))));
            if (!$ids) return ['status'=>0,'error'=>'No ids'];

            $result = $this->guarded_delete_income_ids('inf_income_from_coloader', $ids);
            $result['oper'] = 'del';
            return $result;
        }

        if ($oper==='edit') {
            $id=(int)($data['id'] ?? 0); $kolom=(string)($data['kolom'] ?? '');
            $allowed=['container','code_service','item_service','currency','qty','unit_price','invoice_db','g','ppn','prep_coll'];
            if ($id<=0 || !in_array($kolom,$allowed,true)) return ['status'=>0,'error'=>'Invalid edit'];
            $update=[$kolom=>$data['isi'] ?? ''];
            if (isset($data['qty'],$data['unit_price'])) $update['price']=(float)$data['qty']*(float)$data['unit_price'];
            if (isset($data['spd'])) $update['sp_detail_id']=(int)$data['spd'];
            $update=$this->normalize_income_changes($this->filter_income_changes('inf_income_from_coloader',$update));
            $guard=$this->prepare_income_mutation('inf_income_from_coloader',$id,$update);
            if (empty($guard['ok'])) return $guard;
            $update=array_merge($update,$guard['reset'] ?? [],['updated_by'=>$fullname,'updated_at'=>$now]);
            $this->dbx->where('id',$id)->where('region_id',$region)->update('inf_income_from_coloader',$update);
            return ['status'=>1,'affected'=>$this->dbx->affected_rows(),'id'=>$id,'oper'=>'edit','kolom'=>$kolom,'auto_unbilled'=>!empty($guard['auto_unbilled']),'paid_override'=>!empty($guard['paid_override'])];
        }
        return ['status'=>0,'error'=>'Invalid oper'];
    }

	
	function tarik_data_insentiv_agent($data)
	{
		$data['shipper_id'] = $this->get_shipper_id($data['shipper_id']);	
		
		$q = $this->dbx->get_where('inf_income_from_agent', array('kode_ref'=>$data['kode_ref'],'id_tarik !='=>'0','region_id'=>$this->session->userdata("region_id")));
		
		if($q->num_rows() > 0 ) 
		{
			$deleteResult = $this->guarded_delete_income_ids(
				'inf_income_from_agent',
				array_map('intval', array_column($q->result_array(), 'id'))
			);
			if (empty($deleteResult['status'])) return $deleteResult;
		}
		
		$this->dbx->join('x_insentive','x_insentive.id=x_insentive_detail.id_insentive','left');
		$q1 = $this->dbx->get_where('x_insentive_detail',array(
					'x_insentive.agent_code'=>$data['agent'],
					'destination'=>$data['destination'],
					'countainer'=>$data['countainer'],
					'currency'=>'USD',
					'region_id'=>$this->session->userdata("region_id"),
		));
			
		if($q1->num_rows()>0) 
		{
			foreach ($q1->result() as $row){
				$this->dbx->insert('inf_income_from_agent',array(
					'id_tarik'=>$row->id,
					'sp_detail_id'=>$data['spd'],
					'kode_ref'=>$data['kode_ref'],
					'container'=>$row->countainer,
					'unit_price'=>$row->amount,
					'price'=>floatval($row->amount) * floatval($data['qty']),
					'currency'=>$row->currency,
					'agent'=>$row->agent_code,
					'invoice_db'=>'DEBIT NOTE',
					'qty'=>$data['qty'],
					'item_service'=>"REBATE AGENT",
					'region_id'=>$this->session->userdata("region_id"),
					'created_by'=>$this->session->userdata('fullname'),
					'created_at'=>date('Y-m-d H:i:s')
				));
			}
		//	return array($data,$q->result(),$q1->result(),$data_new,$q2);
		} 		
		else 
		{
			$data_new = array();
			$q2 = array();
		//	return array($data,$q->result(),$q1->result(),$data_new,$q2);
		}
	}
	
	function get_global_income($data,$limit){
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$q= $this->dbx->get_where('x_glbincome',array('job_no'=>$data['job_no'],'region_id'=>$this->session->userdata('region_id')));
		return $q->result();
	}
	
	function add_global($data){
		$data['region_id']=$this->session->userdata('region_id');
		$data['created_by']=$this->session->userdata('fullname');
		$data['created_at']=date('Y-m-d H:i:s');
		$this->dbx->insert('x_glbincome',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
    function update_global($data,$id)
    {
        $id = (int)$id;
        $changes = $this->normalize_income_changes($this->filter_income_changes('x_glbincome', (array)$data));
        $guard = $this->prepare_income_mutation('x_glbincome', $id, $changes, true);
        if (empty($guard['ok'])) return $guard;

        $changes = array_merge($changes, $guard['reset'] ?? []);
        $changes['region_id']=$this->session->userdata('region_id');
        $changes['updated_by']=$this->session->userdata('fullname');
        $changes['updated_at']=date('Y-m-d H:i:s');

        $this->dbx->where('id',$id)->where('region_id',$this->session->userdata('region_id'))->update('x_glbincome',$changes);
        return [
            'status'=>1,'affected'=>$this->dbx->affected_rows(),
            'auto_unbilled'=>!empty($guard['auto_unbilled']),
            'paid_override'=>!empty($guard['paid_override']),
            'old_invoice_no'=>$guard['old_invoice_no'] ?? null,
        ];
    }
    function delete_global($id)
    {
        $id = (int)$id;
        if ($id <= 0) return ['status'=>0,'error'=>'INVALID_INCOME_ID'];

        return $this->guarded_delete_income_ids('x_glbincome', [$id]);
    }

	
	// FAST: 1) COUNT(*) sekali, 2) ambil data + JOIN x_finance untuk payment, 3) limit+order aman
public function tampil_selling_price_paged(array $p)
{
    $region   = $this->session->userdata('region_id');
    $kode_ref = $p['kode_ref'] ?? '';

    $page = isset($p['page']) ? (int)$p['page'] : 1;
    $rows = isset($p['rows']) ? (int)$p['rows'] : 10;
    $rows = max(1, $rows);
    $offset = max(0, ($page - 1) * $rows);

    // whitelist kolom yang boleh di-order untuk cegah SQL injection
    $allowedSidx = [
        'id','sp_detail_id','kode_ref','mchgs','container','code_service','item_service',
        'qty','unit_price','price','currency','prep_coll','receive_from','remark',
        'agent','ship_co','g','ppn','invoice_db',
        'created_by','created_at','updated_by','updated_at'
    ];
    $sidx = (isset($p['sidx']) && in_array($p['sidx'], $allowedSidx, true))
        ? $p['sidx']
        : 'updated_at';

    $sord = (isset($p['sord']) && strtolower($p['sord']) === 'asc') ? 'asc' : 'desc';

    // =====================================================
    // 1) COUNT cepat + timing
    // =====================================================
    $t0 = microtime(true);

    $this->dbx->from('inf_income_selling_price');
    $this->dbx->where('kode_ref', $kode_ref);
    $this->dbx->where('region_id', $region);
    $cnt = $this->dbx->count_all_results();

    $countTime = microtime(true) - $t0;
    $countSql  = $this->dbx->last_query(); // SQL count

    // =====================================================
    // 2) SELECT data + JOIN x_finance sekali + timing
    // =====================================================
    $t1 = microtime(true);

    $this->dbx->select([
        'i.id','i.sp_detail_id','i.kode_ref','i.mchgs','i.container','i.code_service',
        'i.item_service','i.qty','i.unit_price','i.price','i.currency','i.prep_coll',
        'i.receive_from','i.remark','i.agent','i.ship_co','i.g','i.ppn','i.invoice_db',
        'i.created_by','i.created_at','i.updated_by','i.updated_at',
        'xf.status AS payment_status',
    ]);
    $this->dbx->from('inf_income_selling_price i');

    // LEFT JOIN: kalau nggak ada di x_finance tetap muncul
    $this->dbx->join(
        'x_finance xf',
        'xf.number = i.ship_co AND xf.region_id = ' . $this->dbx->escape($region),
        'left'
    );

    $this->dbx->where('i.kode_ref', $kode_ref);
    $this->dbx->where('i.region_id', $region);
    $this->dbx->order_by($sidx, $sord);
    $this->dbx->limit($rows, $offset);

    $rowsData = $this->dbx->get()->result();

    $selectTime = microtime(true) - $t1;
    $selectSql  = $this->dbx->last_query(); // SQL select

    // =====================================================
    // 3) Hitung total page dan return + debug optional
    // =====================================================
    $totalPages = ($cnt > 0) ? ceil($cnt / $rows) : 0;
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    return [
        'total' => $cnt,
        'rows'  => $rowsData,
        'page'  => $page,
        'pages' => $totalPages,

        // DEBUG – sementara boleh di-print di controller untuk analisa
        'debug' => [
            'count_time'  => $countTime,
            'select_time' => $selectTime,
            'count_sql'   => $countSql,
            'select_sql'  => $selectSql,
        ],
    ];
}

// Paged + JOIN x_finance (hilangkan N+1 get_payment)
public function get_from_agent_paged(array $p)
{
    $region   = $this->session->userdata('region_id');
    $kode_ref = $p['kode_ref'] ?? '';

    $page   = isset($p['page']) ? (int)$p['page'] : 1;
    $rows   = isset($p['rows']) ? (int)$p['rows'] : 10;
    $rows   = max(1, $rows);
    $offset = max(0, ($page - 1) * $rows);

    // whitelist kolom untuk ORDER BY (aman & index-friendly)
    $allowedSidx = [
        'id','sp_detail_id','kode_ref','item_service','container','currency',
        'qty','unit_price','price','agent','invoice_db','dn_no','g',
        'created_by','created_at','updated_by','updated_at'
    ];
    $sidx = isset($p['sidx']) && in_array($p['sidx'], $allowedSidx, true) ? $p['sidx'] : 'updated_at';
    $sord = (isset($p['sord']) && strtolower($p['sord']) === 'asc') ? 'asc' : 'desc';

    // 1) COUNT(*) cepat
    $cnt = $this->dbx
        ->where('kode_ref', $kode_ref)
        ->where('region_id', $region)
        ->count_all_results('inf_income_from_agent');

    // 2) Data + LEFT JOIN ke x_finance berdasarkan dn_no (nomor dokumen keuangan)
    $this->dbx->select([
        'i.id','i.sp_detail_id','i.kode_ref','i.item_service','i.container','i.currency',
        'i.qty','i.unit_price','i.price','i.agent','i.invoice_db','i.dn_no','i.g',
        'i.created_by','i.created_at','i.updated_by','i.updated_at',
        'xf.status AS payment_status'
    ]);
    $this->dbx->from('inf_income_from_agent i');
    // join kunci kombinasi: number=dn_no + region_id sama
    $this->dbx->join('x_finance xf',
        "xf.number = i.dn_no AND xf.region_id = " . $this->dbx->escape($region),
        'left'
    );
    $this->dbx->where('i.kode_ref', $kode_ref);
    $this->dbx->where('i.region_id', $region);
    $this->dbx->order_by($sidx, $sord);
    $this->dbx->limit($rows, $offset);

    $rowsData = $this->dbx->get()->result();

    return ['total' => $cnt, 'rows' => $rowsData];
}

	
	

/**
 * Import item quotation ke Income shipper secara transactional dan idempotent.
 *
 * Penting untuk lifecycle quotation:
 * - return exact Income row IDs agar cancel hanya menyentuh row dari quotation ini.
 * - jika quotation sudah pernah dimuat oleh flow lama, existing IDs dikembalikan
 *   supaya record tersebut dapat "diadopsi" ke linkage tanpa duplicate.
 */
public function import_quotation_items(array $context, array $items)
{
    $spDetailId = (int)($context['sp_detail_id'] ?? 0);
    $kodeRef    = trim((string)($context['kode_ref'] ?? ''));
    $quoteNo    = trim((string)($context['quote_no'] ?? ''));
    $region     = trim((string)$this->session->userdata('region_id'));

    if ($spDetailId <= 0 || $kodeRef === '' || $quoteNo === '' || $region === '') {
        return ['status'=>0, 'message'=>'Konteks quotation/jobsheet tidak lengkap.'];
    }
    if (!$items) {
        return ['status'=>0, 'message'=>'Tidak ada item quotation yang dapat dimasukkan ke Income.'];
    }

    $existingRows = $this->dbx
        ->select('id')
        ->from('inf_income_selling_price')
        ->where('sp_detail_id', $spDetailId)
        ->where('kode_ref', $kodeRef)
        ->where('receive_from', $quoteNo)
        ->where('region_id', $region)
        ->order_by('id', 'ASC')
        ->get()->result_array();

    if ($existingRows) {
        $existingIds = array_values(array_map('intval', array_column($existingRows, 'id')));
        return [
            'status'         => 1,
            'already_loaded' => true,
            'inserted'       => 0,
            'inserted_ids'   => [],
            'income_ids'     => $existingIds,
            'message'        => 'Quotation ini sudah pernah dimuat ke Income.'
        ];
    }

    $now = date('Y-m-d H:i:s');
    $createdBy = (string)$this->session->userdata('fullname');
    $rows = [];

    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $name = trim((string)($item['item_service'] ?? ''));
        if ($name === '') continue;

        $rows[] = [
            'sp_detail_id' => $spDetailId,
            'kode_ref'     => $kodeRef,
            'container'    => trim((string)($item['container'] ?? '')),
            'code_service' => trim((string)($item['code_service'] ?? '')),
            'item_service' => $name,
            'qty'          => (float)($item['qty'] ?? 0),
            'unit_price'   => (float)($item['unit_price'] ?? 0),
            'price'        => (float)($item['price'] ?? 0),
            'currency'     => trim((string)($item['currency'] ?? 'RP.')),
            'prep_coll'    => 'PREPAID',
            'receive_from' => $quoteNo,
            'remark'       => trim((string)($item['remark'] ?? '')),
            'invoice_db'   => 'INVOICE',
            'region_id'    => $region,
            'created_by'   => $createdBy,
            'created_at'   => $now,
        ];
    }

    if (!$rows) {
        return ['status'=>0, 'message'=>'Semua item quotation bersifat non-billable/kosong.'];
    }

    $insertedIds = [];
    $this->dbx->trans_begin();

    foreach ($rows as $row) {
        if (!$this->dbx->insert('inf_income_selling_price', $row)) {
            $this->dbx->trans_rollback();
            return [
                'status'=>0,
                'message'=>'Gagal memasukkan quotation ke Income.',
                'inserted_ids'=>[]
            ];
        }
        $insertedIds[] = (int)$this->dbx->insert_id();
    }

    if ($this->dbx->trans_status() === false) {
        $this->dbx->trans_rollback();
        return ['status'=>0, 'message'=>'Gagal memasukkan quotation ke Income.', 'inserted_ids'=>[]];
    }

    $this->dbx->trans_commit();

    return [
        'status'         => 1,
        'already_loaded' => false,
        'inserted'       => count($insertedIds),
        'inserted_ids'   => $insertedIds,
        'income_ids'     => $insertedIds,
        'message'        => count($insertedIds) . ' item quotation berhasil dimuat ke Income.'
    ];
}

/**
 * Lifecycle quotation memakai tabel linkage yang dibuat migration
 * 2026_09_08_income_quotation_lifecycle.sql.
 */
public function quotation_link_table_ready()
{
    return $this->dbx->table_exists('inf_income_quotation_link');
}

public function get_active_quotation_link($spDetailId, $kodeRef, $region = '')
{
    if (!$this->quotation_link_table_ready()) return null;

    $spDetailId = (int)$spDetailId;
    $kodeRef = trim((string)$kodeRef);
    $region = trim((string)$region);
    if ($region === '') $region = trim((string)$this->session->userdata('region_id'));

    if ($spDetailId <= 0 || $kodeRef === '' || $region === '') return null;

    return $this->dbx
        ->where('sp_detail_id', $spDetailId)
        ->where('kode_ref', $kodeRef)
        ->where('region_id', $region)
        ->where('status', 'ACTIVE')
        ->order_by('id', 'DESC')
        ->limit(1)
        ->get('inf_income_quotation_link')
        ->row_array();
}

public function create_quotation_link(array $data)
{
    if (!$this->quotation_link_table_ready()) {
        return ['status'=>0, 'message'=>'TABLE_INF_INCOME_QUOTATION_LINK_NOT_FOUND'];
    }

    $now = date('Y-m-d H:i:s');
    $row = [
        'quote_no'                    => trim((string)($data['quote_no'] ?? '')),
        'quote_id'                    => (int)($data['quote_id'] ?? 0),
        'opportunity_id'              => (int)($data['opportunity_id'] ?? 0),
        'lead_id'                     => (int)($data['lead_id'] ?? 0),
        'source_type'                 => trim((string)($data['source_type'] ?? '')),
        'sp_detail_id'                => (int)($data['sp_detail_id'] ?? 0),
        'kode_ref'                    => trim((string)($data['kode_ref'] ?? '')),
        'region_id'                   => trim((string)($data['region_id'] ?? $this->session->userdata('region_id'))),
        'previous_quote_status'       => trim((string)($data['previous_quote_status'] ?? '')),
        'previous_opportunity_stage'  => trim((string)($data['previous_opportunity_stage'] ?? '')),
        'accepted_quote_status'       => trim((string)($data['accepted_quote_status'] ?? 'ACCEPTED')),
        'accepted_opportunity_stage'  => trim((string)($data['accepted_opportunity_stage'] ?? 'Rate Accepted')),
        'income_ids_json'             => json_encode(array_values(array_unique(array_map('intval', (array)($data['income_ids'] ?? []))))),
        'status'                      => 'ACTIVE',
        'accepted_by_id'              => (int)($data['accepted_by_id'] ?? 0),
        'accepted_by'                 => trim((string)($data['accepted_by'] ?? '')),
        'accepted_at'                 => $data['accepted_at'] ?? $now,
        'created_at'                  => $now,
        'updated_at'                  => $now,
    ];

    if ($row['quote_no'] === '' || $row['quote_id'] <= 0 || $row['sp_detail_id'] <= 0
        || $row['kode_ref'] === '' || $row['region_id'] === '') {
        return ['status'=>0, 'message'=>'INVALID_QUOTATION_LINK_CONTEXT'];
    }

    // Satu jobsheet detail hanya boleh mempunyai satu quotation ACTIVE.
    $active = $this->get_active_quotation_link(
        $row['sp_detail_id'],
        $row['kode_ref'],
        $row['region_id']
    );
    if ($active) {
        return ['status'=>1, 'already_active'=>true, 'data'=>$active];
    }

    $ok = $this->dbx->insert('inf_income_quotation_link', $row);
    if (!$ok) return ['status'=>0, 'message'=>'FAILED_TO_CREATE_QUOTATION_LINK'];

    $row['id'] = (int)$this->dbx->insert_id();
    return ['status'=>1, 'already_active'=>false, 'data'=>$row];
}

public function mark_quotation_link_cancelled($linkId, $cancelReason = '')
{
    if (!$this->quotation_link_table_ready()) return false;

    $linkId = (int)$linkId;
    if ($linkId <= 0) return false;

    return $this->dbx
        ->where('id', $linkId)
        ->where('status', 'ACTIVE')
        ->update('inf_income_quotation_link', [
            'status'          => 'CANCELLED',
            'cancelled_by_id' => (int)$this->session->userdata('user_id'),
            'cancelled_by'    => trim((string)$this->session->userdata('fullname')),
            'cancelled_at'    => date('Y-m-d H:i:s'),
            'cancel_reason'   => trim((string)$cancelReason),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
}

public function mark_quotation_link_error($linkId, $message)
{
    if (!$this->quotation_link_table_ready()) return false;

    return $this->dbx
        ->where('id', (int)$linkId)
        ->update('inf_income_quotation_link', [
            'last_error' => trim((string)$message),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
}

public function quotation_link_income_ids(array $link)
{
    $ids = [];
    $raw = $link['income_ids_json'] ?? '';
    if (is_string($raw) && trim($raw) !== '') {
        $tmp = json_decode($raw, true);
        if (is_array($tmp)) $ids = $tmp;
    } elseif (is_array($raw)) {
        $ids = $raw;
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if ($ids) return $ids;

    // Fallback untuk linkage lama/migrasi: tetap scope ketat berdasarkan quote/job/region.
    if (empty($link['quote_no']) || empty($link['sp_detail_id']) || empty($link['kode_ref']) || empty($link['region_id'])) {
        return [];
    }

    $rows = $this->dbx
        ->select('id')
        ->from('inf_income_selling_price')
        ->where('sp_detail_id', (int)$link['sp_detail_id'])
        ->where('kode_ref', (string)$link['kode_ref'])
        ->where('receive_from', (string)$link['quote_no'])
        ->where('region_id', (string)$link['region_id'])
        ->get()->result_array();

    return array_values(array_map('intval', array_column($rows, 'id')));
}

public function delete_quotation_income_ids(array $ids)
{
    $region = trim((string)$this->session->userdata('region_id'));
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

    if (!$ids) {
        return [
            'status'=>1,
            'ok'=>true,
            'affected'=>0,
            'deleted_ids'=>[],
            'auto_unbilled'=>false,
            'paid_override_delete'=>false
        ];
    }

    // Retry-safe: setelah kegagalan parsial, row yang sudah terhapus tidak dianggap error.
    $existing = $this->dbx
        ->select('id')
        ->from('inf_income_selling_price')
        ->where('region_id', $region)
        ->where_in('id', $ids)
        ->get()->result_array();

    $existingIds = array_values(array_map('intval', array_column($existing, 'id')));
    if (!$existingIds) {
        return [
            'status'=>1,
            'ok'=>true,
            'affected'=>0,
            'deleted_ids'=>[],
            'auto_unbilled'=>false,
            'paid_override_delete'=>false
        ];
    }

    return $this->guarded_delete_income_ids('inf_income_selling_price', $existingIds);
}

}

	
	
	
?>