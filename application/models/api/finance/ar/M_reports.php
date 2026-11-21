<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_reports extends MY_Model
{
    private $company_id = 1;

    private function _normalize_status($status)
    {
        $status = strtoupper(trim((string)$status));
        return $status === '' ? 'ALL' : $status;
    }

    private function _normalize_group_by($groupBy)
    {
        $groupBy = strtolower(trim((string)$groupBy));

        $allowed = [
            'customer',
            'party',
            'party_type',
            'region',
            'invoice',
            'none',
            'all',
        ];

        if (!in_array($groupBy, $allowed, true)) {
            return 'customer';
        }

        return $groupBy;
    }

    private function _bucket_by_age($age)
    {
        $age = (int)$age;

        if ($age <= 30) return '0_30';
        if ($age <= 60) return '31_60';
        if ($age <= 90) return '61_90';

        return '90_plus';
    }

    private function _empty_bucket_row()
    {
        return [
            '0_30'    => 0.0,
            '31_60'   => 0.0,
            '61_90'   => 0.0,
            '90_plus' => 0.0,
            'total'   => 0.0,
        ];
    }

    private function _summary_key($row, $groupBy)
    {
        if ($groupBy === 'none' || $groupBy === 'all') {
            return 'ALL';
        }

        if ($groupBy === 'invoice') {
            return !empty($row['invoice_no']) ? $row['invoice_no'] : '(NO INVOICE)';
        }

        if ($groupBy === 'party' || $groupBy === 'party_type') {
            $type = strtoupper(trim((string)($row['party_type'] ?? '')));
            return $type !== '' ? $type : '(NO TYPE)';
        }

        if ($groupBy === 'region') {
            return !empty($row['region_id']) ? $row['region_id'] : '(NO REGION)';
        }

        /*
         * Default: customer / party name.
         */
        $name = trim((string)($row['party_name'] ?? ''));

        if ($name === '') {
            $name = trim((string)($row['party_code'] ?? ''));
        }

        return $name !== '' ? $name : '(UNKNOWN)';
    }

    private function _apply_ar_invoice_filters($p)
    {
        $dateFrom  = trim((string)($p['date_from'] ?? ''));
        $dateTo    = trim((string)($p['date_to'] ?? ''));
        $region    = trim((string)($p['region_id'] ?? ''));
        $search    = trim((string)($p['search'] ?? ''));
        $partyType = strtoupper(trim((string)($p['party_type'] ?? '')));

        if ($dateFrom !== '') {
            $this->erp->where('ai.posting_date >=', $dateFrom);
        }

        if ($dateTo !== '') {
            $this->erp->where('ai.posting_date <=', $dateTo);
        }

        if ($region !== '') {
            $this->erp->where('ai.region_id', $region);
        } else {
            $this->apply_company_filter_alias('ai', 'region_id');
        }

        if ($partyType !== '') {
            $this->erp->where('UPPER(ai.party_type)', $partyType);
        }

        if ($search !== '') {
            $this->erp->group_start()
                ->like('ai.invoice_no', $search)
                ->or_like('ai.job_no', $search)
                ->or_like('ai.party_type', $search)
                ->or_like('ai.party_code', $search)
                ->or_like('ai.party_name', $search)
            ->group_end();
        }
    }

    /**
     * AR Aging source:
     * ar_invoices
     *
     * Params:
     * - as_of
     * - date_from
     * - date_to
     * - status: ALL / UNPAID / PARTIAL / PAID / SUBMITTED / CANCELLED
     * - group_by: customer / party_type / region / invoice / all
     * - region_id
     * - party_type
     * - search
     */
    public function ar_aging($p = [])
    {
        $asOf    = !empty($p['as_of']) ? $p['as_of'] : date('Y-m-d');
        $status  = $this->_normalize_status($p['status'] ?? 'ALL');
        $groupBy = $this->_normalize_group_by($p['group_by'] ?? 'customer');

        $this->erp->select("
            ai.ar_invoice_id,
            ai.company_id,
            ai.region_id,

            ai.source_db,
            ai.source_table,
            ai.source_id,
            ai.source_ref,

            ai.invoice_no,
            ai.job_no,
            ai.posting_date,

            DATEDIFF(".$this->erp->escape($asOf).", ai.posting_date) AS age_days,

            ai.party_type,
            ai.party_code,
            ai.party_name,

            ai.currency,
            ai.exchange_rate,

            ai.grand_total,
            ai.ppn_total,
            ai.dpp_total,
            ai.grand_total_idr,
            ai.ppn_total_idr,
            ai.dpp_total_idr,

            ai.paid_total,
            ai.outstanding_total,
            ai.status,
            ai.is_opening,
            ai.created_at,
            ai.updated_at
        ", false)
            ->from('ar_invoices ai')
            ->where('ai.company_id', $this->company_id)
            ->where('ai.posting_date <=', $asOf);

        $this->_apply_ar_invoice_filters($p);

        if ($status === 'ALL') {
            $this->erp->where("UPPER(ai.status) NOT IN ('CANCELLED','VOID')", null, false);
            $this->erp->where('COALESCE(ai.outstanding_total,0) >', 0);
        } elseif ($status === 'UNPAID') {
            $this->erp->where("UPPER(ai.status) NOT IN ('CANCELLED','VOID')", null, false);
            $this->erp->where('COALESCE(ai.paid_total,0) <=', 0);
            $this->erp->where('COALESCE(ai.outstanding_total,0) >', 0);
        } elseif ($status === 'PARTIAL') {
            $this->erp->where("UPPER(ai.status) NOT IN ('CANCELLED','VOID')", null, false);
            $this->erp->where('COALESCE(ai.paid_total,0) >', 0);
            $this->erp->where('COALESCE(ai.outstanding_total,0) >', 0);
        } elseif ($status === 'PAID') {
            $this->erp->where("UPPER(ai.status) NOT IN ('CANCELLED','VOID')", null, false);
            $this->erp->where('COALESCE(ai.paid_total,0) >', 0);
            $this->erp->where('COALESCE(ai.outstanding_total,0) <=', 0);
        } elseif ($status === 'CANCELLED' || $status === 'VOID') {
            $this->erp->where('UPPER(ai.status)', $status);
        } else {
            $this->erp->where('UPPER(ai.status)', $status);
        }

        $rows = $this->erp
            ->order_by('age_days', 'DESC')
            ->order_by('ai.posting_date', 'ASC')
            ->order_by('ai.ar_invoice_id', 'ASC')
            ->get()
            ->result_array();

        $summary = [];
        $details = [];

        foreach ($rows as $r) {
            $age    = (int)($r['age_days'] ?? 0);
            $amount = (float)($r['outstanding_total'] ?? 0);
            $bucket = $this->_bucket_by_age($age);
            $key    = $this->_summary_key($r, $groupBy);

            if (!isset($summary[$key])) {
                $summary[$key] = $this->_empty_bucket_row();
            }

            $summary[$key][$bucket] += $amount;
            $summary[$key]['total'] += $amount;

            $r['ar_invoice_id']     = (int)($r['ar_invoice_id'] ?? 0);
            $r['source_id']         = (string)($r['source_id'] ?? '');
            $r['age_days']          = $age;
            $r['exchange_rate']     = (float)($r['exchange_rate'] ?? 1);
            $r['grand_total']       = (float)($r['grand_total'] ?? 0);
            $r['ppn_total']         = (float)($r['ppn_total'] ?? 0);
            $r['dpp_total']         = (float)($r['dpp_total'] ?? 0);
            $r['grand_total_idr']   = (float)($r['grand_total_idr'] ?? 0);
            $r['ppn_total_idr']     = (float)($r['ppn_total_idr'] ?? 0);
            $r['dpp_total_idr']     = (float)($r['dpp_total_idr'] ?? 0);
            $r['paid_total']        = (float)($r['paid_total'] ?? 0);
            $r['outstanding_total'] = (float)($r['outstanding_total'] ?? 0);
            $r['bucket']            = $bucket;
            $r['group_key']         = $key;

            $details[] = $r;
        }

        foreach ($summary as &$v) {
            foreach ($v as $bk => $val) {
                $v[$bk] = (float)$val;
            }
        }
        unset($v);

        return [
            'as_of'    => $asOf,
            'status'   => $status,
            'group_by' => $groupBy,
            'summary'  => $summary,
            'details'  => $details,
        ];
    }

    /**
     * Summary AR outstanding.
     */
    public function ar_outstanding_summary($p = [])
    {
        $asOf = !empty($p['as_of']) ? $p['as_of'] : date('Y-m-d');

        $this->erp->select("
            COUNT(*) AS invoice_count,
            SUM(COALESCE(ai.grand_total,0)) AS grand_total,
            SUM(COALESCE(ai.grand_total_idr,0)) AS grand_total_idr,
            SUM(COALESCE(ai.paid_total,0)) AS paid_total,
            SUM(COALESCE(ai.outstanding_total,0)) AS outstanding_total
        ", false)
            ->from('ar_invoices ai')
            ->where('ai.company_id', $this->company_id)
            ->where('ai.posting_date <=', $asOf)
            ->where("UPPER(ai.status) NOT IN ('CANCELLED','VOID')", null, false)
            ->where('COALESCE(ai.outstanding_total,0) >', 0);

        $this->_apply_ar_invoice_filters($p);

        $row = $this->erp->get()->row_array();

        return [
            'as_of'             => $asOf,
            'invoice_count'     => (int)($row['invoice_count'] ?? 0),
            'grand_total'       => (float)($row['grand_total'] ?? 0),
            'grand_total_idr'   => (float)($row['grand_total_idr'] ?? 0),
            'paid_total'        => (float)($row['paid_total'] ?? 0),
            'outstanding_total' => (float)($row['outstanding_total'] ?? 0),
        ];
    }

    /**
     * AR invoice report/list.
     */
    public function ar_invoice_report($p = [])
    {
        $status = $this->_normalize_status($p['status'] ?? 'ALL');

        $this->erp->select("
            ai.ar_invoice_id,
            ai.company_id,
            ai.region_id,

            ai.source_db,
            ai.source_table,
            ai.source_id,
            ai.source_ref,

            ai.invoice_no,
            ai.job_no,
            ai.posting_date,

            ai.party_type,
            ai.party_code,
            ai.party_name,

            ai.currency,
            ai.exchange_rate,
            ai.grand_total,
            ai.ppn_total,
            ai.dpp_total,
            ai.grand_total_idr,
            ai.ppn_total_idr,
            ai.dpp_total_idr,
            ai.paid_total,
            ai.outstanding_total,
            ai.status,
            ai.is_opening,
            ai.created_at,
            ai.updated_at
        ", false)
            ->from('ar_invoices ai')
            ->where('ai.company_id', $this->company_id);

        $this->_apply_ar_invoice_filters($p);

        if ($status === 'ALL') {
            $this->erp->where("UPPER(ai.status) NOT IN ('CANCELLED','VOID')", null, false);
        } else {
            $this->erp->where('UPPER(ai.status)', $status);
        }

        $rows = $this->erp
            ->order_by('ai.posting_date', 'DESC')
            ->order_by('ai.ar_invoice_id', 'DESC')
            ->get()
            ->result_array();

        foreach ($rows as &$r) {
            $r['ar_invoice_id']     = (int)($r['ar_invoice_id'] ?? 0);
            $r['exchange_rate']     = (float)($r['exchange_rate'] ?? 1);
            $r['grand_total']       = (float)($r['grand_total'] ?? 0);
            $r['ppn_total']         = (float)($r['ppn_total'] ?? 0);
            $r['dpp_total']         = (float)($r['dpp_total'] ?? 0);
            $r['grand_total_idr']   = (float)($r['grand_total_idr'] ?? 0);
            $r['ppn_total_idr']     = (float)($r['ppn_total_idr'] ?? 0);
            $r['dpp_total_idr']     = (float)($r['dpp_total_idr'] ?? 0);
            $r['paid_total']        = (float)($r['paid_total'] ?? 0);
            $r['outstanding_total'] = (float)($r['outstanding_total'] ?? 0);
        }
        unset($r);

        return $rows;
    }

    public function ar_aging_by_party_type($p = [])
    {
        $p['group_by'] = 'party_type';
        return $this->ar_aging($p);
    }

    public function ar_aging_by_customer($p = [])
    {
        $p['group_by'] = 'customer';
        return $this->ar_aging($p);
    }

    public function ar_aging_by_region($p = [])
    {
        $p['group_by'] = 'region';
        return $this->ar_aging($p);
    }
}