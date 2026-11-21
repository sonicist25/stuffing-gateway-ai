<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Outstanding AR Invoice list.
 *
 * Patch notes:
 * - Source lama sudah diarahkan ke ar_invoices.
 * - Output tetap menjaga finance_id untuk backward compatibility FE lama.
 * - Field ar_invoices dibuat fleksibel karena beberapa struktur ERP bisa berbeda.
 */
class M_outstanding extends MY_Model
{
    private $company_id = 1;
    private $invoice_table = 'ar_invoices';

    public function list($p)
    {
        $limit = (int)($p['limit'] ?? 200);
        if ($limit <= 0) {
            $limit = 200;
        }

        $offset = (int)($p['offset'] ?? 0);
        if ($offset < 0) {
            $offset = 0;
        }

        $meta = $this->_invoice_meta();

        if (empty($meta['pk']) || empty($meta['total'])) {
            return [
                'limit'   => $limit,
                'offset'  => $offset,
                'total'   => 0,
                'rows'    => [],
                'message' => 'Required AR invoice fields not found',
                'meta'    => [
                    'table' => $this->invoice_table,
                    'pk'    => $meta['pk'],
                    'total' => $meta['total'],
                ],
            ];
        }

        // Count query
        $this->_build_outstanding_query($p, $meta, false);
        $total = (int)$this->erp->count_all_results();

        // Data query
        $this->_build_outstanding_query($p, $meta, true);
        $rows = $this->erp
            ->limit($limit, $offset)
            ->get()
            ->result_array();

        foreach ($rows as &$r) {
            // Backward compatibility: FE lama masih membaca finance_id.
            $r['finance_id']  = (int)($r['finance_id'] ?? 0);
            $r['invoice_id']  = (int)($r['invoice_id'] ?? $r['finance_id']);
            $r['grand_total'] = (float)($r['grand_total'] ?? 0);
            $r['paid_total']  = (float)($r['paid_total'] ?? 0);
            $r['outstanding'] = (float)($r['outstanding'] ?? 0);
            $r['kurs']        = (float)($r['kurs'] ?? 1);

            if (empty($r['currency'])) {
                $r['currency'] = 'IDR';
            }
        }
        unset($r);

        return [
            'limit'  => $limit,
            'offset' => $offset,
            'total'  => $total,
            'rows'   => $rows,
        ];
    }

    private function _build_outstanding_query($p, $meta, $withSelect)
    {
        $table = $this->invoice_table;
        $alias = 'i';

        $pkField       = $meta['pk'];
        $noField       = $meta['no'];
        $jobField      = $meta['job'];
        $custTypeField = $meta['customer_type'];
        $custCodeField = $meta['customer_code'];
        $dateField     = $meta['date'];
        $currencyField = $meta['currency'];
        $kursField     = $meta['kurs'];
        $totalField    = $meta['total'];
        $paidField     = $meta['paid'];
        $outField      = $meta['outstanding'];
        $statusField   = $meta['status'];
        $paidDateField = $meta['paid_date'];

        $totalExpr = 'COALESCE(' . $alias . '.' . $totalField . ',0)';
        $paidExpr  = $paidField ? 'COALESCE(' . $alias . '.' . $paidField . ',0)' : '0';

        if ($outField) {
            // Kalau field outstanding ada, gunakan nilainya.
            // Jika null, fallback ke total - paid.
            $outstandingExpr = 'COALESCE(' . $alias . '.' . $outField . ', (' . $totalExpr . ' - ' . $paidExpr . '))';
        } else {
            $outstandingExpr = '(' . $totalExpr . ' - ' . $paidExpr . ')';
        }

        if ($withSelect) {
            $select = [
                $alias . '.' . $pkField . ' AS finance_id', // backward compatibility FE lama
                $alias . '.' . $pkField . ' AS invoice_id',
                ($noField ? $alias . '.' . $noField : 'CAST(' . $alias . '.' . $pkField . ' AS CHAR)') . ' AS invoice_no',
                ($jobField ? $alias . '.' . $jobField : 'NULL') . ' AS job_no',
                ($custTypeField ? $alias . '.' . $custTypeField : 'NULL') . ' AS customer_type',
                ($custCodeField ? $alias . '.' . $custCodeField : 'NULL') . ' AS customer_code',
                ($dateField ? $alias . '.' . $dateField : 'NULL') . ' AS invoice_date_yyyymmdd',
                ($currencyField ? $alias . '.' . $currencyField : "'IDR'") . ' AS currency',
                ($kursField ? $alias . '.' . $kursField : '1') . ' AS kurs',
                $totalExpr . ' AS grand_total',
                $paidExpr . ' AS paid_total',
                $outstandingExpr . ' AS outstanding',
                ($statusField ? $alias . '.' . $statusField : "'unpaid'") . ' AS status',
                ($paidDateField ? $alias . '.' . $paidDateField : 'NULL') . ' AS tgl_bayar',
            ];

            $this->erp->select(implode(",\n                ", $select), false);
        }

        $this->erp->from($table . ' ' . $alias);

        if ($this->_field_exists($table, 'company_id')) {
            $this->erp->where($alias . '.company_id', $this->company_id);
        }

        if ($this->_field_exists($table, 'region_id')) {
            $this->_apply_region_filter_safe($alias . '.region_id');
        }

        if ($statusField) {
            $this->erp->where("LOWER(COALESCE({$alias}.{$statusField}, '')) NOT IN ('cancelled','canceled','void')", null, false);
        }

        // Only outstanding invoice.
        $this->erp->where('(' . $outstandingExpr . ') >', 0.01, false);

        if (!empty($p['customer_type']) && $custTypeField) {
            $this->erp->where($alias . '.' . $custTypeField, strtoupper((string)$p['customer_type']));
        }

        if (!empty($p['customer_code']) && $custCodeField) {
            $this->erp->where($alias . '.' . $custCodeField, (string)$p['customer_code']);
        }

        if (!empty($p['search'])) {
            $q = trim((string)$p['search']);
            if ($q !== '') {
                $this->erp->group_start();

                if ($noField) {
                    $this->erp->like($alias . '.' . $noField, $q);
                } else {
                    $this->erp->like($alias . '.' . $pkField, $q);
                }

                if ($jobField) {
                    $this->erp->or_like($alias . '.' . $jobField, $q);
                }

                if ($custCodeField) {
                    $this->erp->or_like($alias . '.' . $custCodeField, $q);
                }

                $this->erp->group_end();
            }
        }

        if ($dateField) {
            $this->erp->order_by($alias . '.' . $dateField, 'DESC');
        }

        $this->erp->order_by($alias . '.' . $pkField, 'DESC');
    }

    private function _invoice_meta()
    {
        $table = $this->invoice_table;

        return [
            'pk'            => $this->_pick_field($table, ['invoice_id', 'ar_invoice_id', 'id']),
            'no'            => $this->_pick_field($table, ['invoice_no', 'ar_invoice_no', 'number', 'inv_no', 'no_invoice']),
            'job'           => $this->_pick_field($table, ['job_no', 'job_id', 'source_key', 'kode_ref', 'reference_no']),
            'customer_type' => $this->_pick_field($table, ['customer_type', 'party_type', 'kepada', 'bill_to_type']),
            'customer_code' => $this->_pick_field($table, ['customer_code', 'party_code', 'customer_id', 'kepada_kode', 'bill_to_code']),
            'date'          => $this->_pick_field($table, ['posting_date', 'invoice_date', 'date', 'tgl_invoice', 'created_at']),
            'currency'      => $this->_pick_field($table, ['currency', 'currency_code']),
            'kurs'          => $this->_pick_field($table, ['kurs', 'posting_kurs', 'conversion_rate', 'payment_rate', 'exchange_rate']),
            'total'         => $this->_pick_field($table, ['grand_total', 'total', 'base_total', 'net_total']),
            'paid'          => $this->_pick_field($table, ['paid_total', 'amount_paid', 'paid_amount', 'overdue']),
            'outstanding'   => $this->_pick_field($table, ['outstanding_total', 'outstanding', 'balance_due', 'remaining_total']),
            'status'        => $this->_pick_field($table, ['status', 'payment_status']),
            'paid_date'     => $this->_pick_field($table, ['tgl_bayar', 'paid_at', 'payment_date']),
        ];
    }

    private function _pick_field($table, $candidates)
    {
        foreach ($candidates as $field) {
            if ($this->_field_exists($table, $field)) {
                return $field;
            }
        }

        return null;
    }

    private function _field_exists($table, $field)
    {
        return $this->erp->field_exists($field, $table);
    }

    private function _apply_region_filter_safe($fieldName)
    {
        if (method_exists($this, 'apply_region_filter')) {
            $this->apply_region_filter($fieldName);
            return;
        }

        $CI =& get_instance();
        $regionId = $CI->session->userdata('region_id');

        if ($regionId !== null && $regionId !== '') {
            $this->erp->where($fieldName, $regionId);
        }
    }
}
