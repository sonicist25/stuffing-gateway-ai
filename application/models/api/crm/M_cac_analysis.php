<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_cac_analysis extends CI_Model
{
    /** @var CI_DB_query_builder */
    public $dbx;

    private $tblCost   = 'crm_acquisition_costs';
    private $tblOpp    = 'crm_opportunities';
    private $tblStages = 'crm_opportunity_stages';
    private $tblLeads  = 'crm_leads';

    public function __construct()
    {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    public function dashboard($params = [])
    {
        $periodStart = $this->normalizeDate($params['period_start'] ?? null, date('Y-01-01'));
        $periodEnd   = $this->normalizeDate($params['period_end'] ?? null, date('Y-m-d'));

        $startDate = $periodStart;
        $endDate   = $periodEnd;

        $costSummary = $this->getCostSummary($startDate, $endDate, $params);
        $leadSummary = $this->getLeadSummary($startDate, $endDate, $params);
        $oppSummary  = $this->getOpportunitySummary($startDate, $endDate, $params);

        $totalCost = (float)($costSummary['total_acquisition_cost'] ?? 0);
        $totalLeads = (int)($leadSummary['total_leads'] ?? 0);
        $totalOpportunities = (int)($oppSummary['total_opportunities'] ?? 0);
        $wonCustomers = (int)($oppSummary['won_customers'] ?? 0);
        $totalRevenue = (float)($oppSummary['total_revenue'] ?? 0);
        $totalGrossProfit = (float)($oppSummary['total_gross_profit'] ?? 0);

        $cacPerLead = $totalLeads > 0 ? round($totalCost / $totalLeads, 2) : 0;
        $cacPerOpportunity = $totalOpportunities > 0 ? round($totalCost / $totalOpportunities, 2) : 0;
        $cacPerCustomer = $wonCustomers > 0 ? round($totalCost / $wonCustomers, 2) : 0;

        $winRatePct = $totalOpportunities > 0
            ? round(($wonCustomers / $totalOpportunities) * 100, 2)
            : 0;

        $roiPct = $totalCost > 0
            ? round((($totalGrossProfit - $totalCost) / $totalCost) * 100, 2)
            : 0;

        $bySource = $this->getByLeadSource($startDate, $endDate, $params);
        $byCategory = $this->getByCategory($startDate, $endDate, $params);
        $monthlyTrend = $this->getMonthlyTrend($startDate, $endDate, $params);

        return [
            'status' => true,
            'data' => [
                'period' => [
                    'start' => $periodStart,
                    'end'   => $periodEnd,
                ],
                'filters' => [
                    'region_id'     => $params['region_id'] ?? null,
                    'sales_id'      => $params['sales_id'] ?? null,
                    'mode'          => $params['mode'] ?? null,
                    'service_type'  => $params['service_type'] ?? null,
                    'lead_source'   => $params['lead_source'] ?? null,
                    'cost_category' => $params['cost_category'] ?? null,
                    'cost_scope'    => $params['cost_scope'] ?? null,
                ],
                'summary' => [
                    'total_acquisition_cost' => $totalCost,
                    'direct_cost'            => (float)($costSummary['direct_cost'] ?? 0),
                    'shared_cost'            => (float)($costSummary['shared_cost'] ?? 0),

                    'total_leads'            => $totalLeads,
                    'total_opportunities'    => $totalOpportunities,
                    'won_customers'          => $wonCustomers,

                    'total_revenue'          => $totalRevenue,
                    'total_gross_profit'     => $totalGrossProfit,

                    'cac_per_lead'           => $cacPerLead,
                    'cac_per_opportunity'    => $cacPerOpportunity,
                    'cac_per_customer'       => $cacPerCustomer,

                    'win_rate_pct'           => $winRatePct,
                    'roi_pct'                => $roiPct,
                ],
                'by_source' => $bySource,
                'by_category' => $byCategory,
                'monthly_trend' => $monthlyTrend,
            ],
        ];
    }

    private function getCostSummary($startDate, $endDate, $params)
    {
        $this->dbx->reset_query();

        $this->dbx
            ->select('COALESCE(SUM(amount_idr), 0) AS total_acquisition_cost', false)
            ->select("COALESCE(SUM(CASE WHEN cost_scope = 'SALES' AND allocation_method = 'DIRECT' THEN amount_idr ELSE 0 END), 0) AS direct_cost", false)
            ->select("COALESCE(SUM(CASE WHEN NOT (cost_scope = 'SALES' AND allocation_method = 'DIRECT') THEN amount_idr ELSE 0 END), 0) AS shared_cost", false)
            ->from($this->tblCost)
            ->where('status', 'APPROVED')
            ->where('deleted_at IS NULL', null, false)
            ->where('cost_date >=', $startDate)
            ->where('cost_date <=', $endDate);

        $this->applyCostFilters($params);

        $row = $this->dbx->get()->row_array();

        return $row ?: [
            'total_acquisition_cost' => 0,
            'direct_cost' => 0,
            'shared_cost' => 0,
        ];
    }

    private function getLeadSummary($startDate, $endDate, $params)
    {
        if (!$this->dbx->table_exists($this->tblLeads)) {
            return ['total_leads' => 0];
        }

        $fields = $this->dbx->list_fields($this->tblLeads);

        $createdField = $this->firstExistingField($fields, ['created_at', 'created_date', 'lead_date', 'tanggal']);
        if (!$createdField) {
            return ['total_leads' => 0];
        }

        $this->dbx->reset_query();

        $this->dbx
            ->select('COUNT(*) AS total_leads', false)
            ->from($this->tblLeads . ' l')
            ->where('DATE(l.' . $createdField . ') >=', $startDate)
            ->where('DATE(l.' . $createdField . ') <=', $endDate);

        if (in_array('deleted_at', $fields, true)) {
            $this->dbx->where('l.deleted_at IS NULL', null, false);
        }

        if (!empty($params['region_id']) && strtoupper($params['region_id']) !== 'ALL') {
            if (in_array('region_id', $fields, true)) {
                $this->dbx->where('l.region_id', $params['region_id']);
            }
        }

        if (!empty($params['sales_id'])) {
            if (in_array('owner_sales_id', $fields, true)) {
                $this->dbx->where('l.owner_sales_id', (int)$params['sales_id']);
            } elseif (in_array('sales_id', $fields, true)) {
                $this->dbx->where('l.sales_id', (int)$params['sales_id']);
            } elseif (in_array('created_by', $fields, true)) {
                $this->dbx->where('l.created_by', (int)$params['sales_id']);
            }
        }

        if (!empty($params['lead_source']) && in_array('lead_source', $fields, true)) {
            $this->dbx->where('l.lead_source', $params['lead_source']);
        }

        if (!empty($params['service_type'])) {
            if (in_array('service_type', $fields, true)) {
                $this->dbx->where('l.service_type', $params['service_type']);
            } elseif (in_array('shipment_type', $fields, true)) {
                $this->dbx->where('l.shipment_type', $params['service_type']);
            }
        }

        $row = $this->dbx->get()->row_array();

        return [
            'total_leads' => (int)($row['total_leads'] ?? 0),
        ];
    }

    private function getOpportunitySummary($startDate, $endDate, $params)
    {
        if (!$this->dbx->table_exists($this->tblOpp)) {
            return [
                'total_opportunities' => 0,
                'won_customers' => 0,
                'total_revenue' => 0,
                'total_gross_profit' => 0,
            ];
        }

        $oppFields = $this->dbx->list_fields($this->tblOpp);

        $this->dbx->reset_query();

        $this->dbx
            ->select('COUNT(o.opportunity_id) AS total_opportunities', false)
            ->select('COALESCE(SUM(o.estimated_revenue), 0) AS total_revenue', false)
            ->from($this->tblOpp . ' o');

        if (in_array('gross_profit', $oppFields, true)) {
            $this->dbx->select('COALESCE(SUM(o.gross_profit), 0) AS total_gross_profit', false);
        } else {
            $this->dbx->select('0 AS total_gross_profit', false);
        }

        if (in_array('is_closed_won', $oppFields, true)) {
            $this->dbx->select("SUM(CASE WHEN o.is_closed_won = 1 THEN 1 ELSE 0 END) AS won_customers", false);
        } else {
            $this->dbx
                ->join($this->tblStages . ' s', 's.stage_name = o.stage', 'left')
                ->select("SUM(CASE WHEN s.is_won_stage = 1 THEN 1 ELSE 0 END) AS won_customers", false);
        }

        if (in_array('deleted_at', $oppFields, true)) {
            $this->dbx->where('o.deleted_at IS NULL', null, false);
        }

        $this->dbx->where('DATE(o.created_at) >=', $startDate);
        $this->dbx->where('DATE(o.created_at) <=', $endDate);

        $this->applyOpportunityFilters($params, $oppFields);

        $row = $this->dbx->get()->row_array();

        return [
            'total_opportunities' => (int)($row['total_opportunities'] ?? 0),
            'won_customers'       => (int)($row['won_customers'] ?? 0),
            'total_revenue'       => (float)($row['total_revenue'] ?? 0),
            'total_gross_profit'  => (float)($row['total_gross_profit'] ?? 0),
        ];
    }

    private function getByLeadSource($startDate, $endDate, $params)
    {
        $costRows = $this->getCostBySource($startDate, $endDate, $params);
        $oppRows  = $this->getOpportunityBySource($startDate, $endDate, $params);

        $map = [];

        foreach ($costRows as $r) {
            $key = $r['lead_source'] ?: 'Unspecified';
            if (!isset($map[$key])) {
                $map[$key] = $this->emptySourceRow($key);
            }

            $map[$key]['cost'] += (float)$r['cost'];
            $map[$key]['direct_cost'] += (float)$r['direct_cost'];
            $map[$key]['shared_cost'] += (float)$r['shared_cost'];
        }

        foreach ($oppRows as $r) {
            $key = $r['lead_source'] ?: 'Unspecified';
            if (!isset($map[$key])) {
                $map[$key] = $this->emptySourceRow($key);
            }

            $map[$key]['opportunities'] += (int)$r['opportunities'];
            $map[$key]['won_customers'] += (int)$r['won_customers'];
            $map[$key]['revenue'] += (float)$r['revenue'];
            $map[$key]['gross_profit'] += (float)$r['gross_profit'];
        }

        foreach ($map as &$r) {
            $r['cac_per_opportunity'] = $r['opportunities'] > 0
                ? round($r['cost'] / $r['opportunities'], 2)
                : 0;

            $r['cac_per_customer'] = $r['won_customers'] > 0
                ? round($r['cost'] / $r['won_customers'], 2)
                : 0;

            $r['win_rate_pct'] = $r['opportunities'] > 0
                ? round(($r['won_customers'] / $r['opportunities']) * 100, 2)
                : 0;

            $r['roi_pct'] = $r['cost'] > 0
                ? round((($r['gross_profit'] - $r['cost']) / $r['cost']) * 100, 2)
                : 0;
        }

        unset($r);

        $rows = array_values($map);

        usort($rows, function ($a, $b) {
            return $b['cost'] <=> $a['cost'];
        });

        return $rows;
    }

    private function getCostBySource($startDate, $endDate, $params)
    {
        $this->dbx->reset_query();

        $this->dbx
            ->select("COALESCE(NULLIF(lead_source, ''), 'Unspecified') AS lead_source", false)
            ->select('COALESCE(SUM(amount_idr), 0) AS cost', false)
            ->select("COALESCE(SUM(CASE WHEN cost_scope = 'SALES' AND allocation_method = 'DIRECT' THEN amount_idr ELSE 0 END), 0) AS direct_cost", false)
            ->select("COALESCE(SUM(CASE WHEN NOT (cost_scope = 'SALES' AND allocation_method = 'DIRECT') THEN amount_idr ELSE 0 END), 0) AS shared_cost", false)
            ->from($this->tblCost)
            ->where('status', 'APPROVED')
            ->where('deleted_at IS NULL', null, false)
            ->where('cost_date >=', $startDate)
            ->where('cost_date <=', $endDate);

        $this->applyCostFilters($params);
        $this->dbx->group_by("COALESCE(NULLIF(lead_source, ''), 'Unspecified')", false);

        return $this->dbx->get()->result_array();
    }

    private function getOpportunityBySource($startDate, $endDate, $params)
    {
        if (!$this->dbx->table_exists($this->tblOpp)) {
            return [];
        }

        $fields = $this->dbx->list_fields($this->tblOpp);

        $this->dbx->reset_query();

        $leadSourceExpr = in_array('lead_source', $fields, true)
            ? "COALESCE(NULLIF(o.lead_source, ''), 'Unspecified')"
            : "'Unspecified'";

        $this->dbx
            ->select($leadSourceExpr . ' AS lead_source', false)
            ->select('COUNT(o.opportunity_id) AS opportunities', false)
            ->select('COALESCE(SUM(o.estimated_revenue), 0) AS revenue', false)
            ->from($this->tblOpp . ' o');

        if (in_array('gross_profit', $fields, true)) {
            $this->dbx->select('COALESCE(SUM(o.gross_profit), 0) AS gross_profit', false);
        } else {
            $this->dbx->select('0 AS gross_profit', false);
        }

        if (in_array('is_closed_won', $fields, true)) {
            $this->dbx->select("SUM(CASE WHEN o.is_closed_won = 1 THEN 1 ELSE 0 END) AS won_customers", false);
        } else {
            $this->dbx
                ->join($this->tblStages . ' s', 's.stage_name = o.stage', 'left')
                ->select("SUM(CASE WHEN s.is_won_stage = 1 THEN 1 ELSE 0 END) AS won_customers", false);
        }

        if (in_array('deleted_at', $fields, true)) {
            $this->dbx->where('o.deleted_at IS NULL', null, false);
        }

        $this->dbx->where('DATE(o.created_at) >=', $startDate);
        $this->dbx->where('DATE(o.created_at) <=', $endDate);

        $this->applyOpportunityFilters($params, $fields);
        $this->dbx->group_by($leadSourceExpr, false);

        return $this->dbx->get()->result_array();
    }

    private function getByCategory($startDate, $endDate, $params)
    {
        $this->dbx->reset_query();

        $this->dbx
            ->select("COALESCE(NULLIF(cost_category, ''), 'Uncategorized') AS cost_category", false)
            ->select('COUNT(*) AS total_entries', false)
            ->select('COALESCE(SUM(amount_idr), 0) AS cost', false)
            ->from($this->tblCost)
            ->where('status', 'APPROVED')
            ->where('deleted_at IS NULL', null, false)
            ->where('cost_date >=', $startDate)
            ->where('cost_date <=', $endDate);

        $this->applyCostFilters($params);

        $this->dbx
            ->group_by("COALESCE(NULLIF(cost_category, ''), 'Uncategorized')", false)
            ->order_by('cost', 'DESC');

        return $this->dbx->get()->result_array();
    }

    private function getMonthlyTrend($startDate, $endDate, $params)
    {
        $costRows = $this->getCostMonthly($startDate, $endDate, $params);
        $oppRows  = $this->getOpportunityMonthly($startDate, $endDate, $params);

        $map = [];

        foreach ($costRows as $r) {
            $month = $r['period_month'];
            if (!isset($map[$month])) {
                $map[$month] = $this->emptyMonthRow($month);
            }

            $map[$month]['cost'] += (float)$r['cost'];
            $map[$month]['direct_cost'] += (float)$r['direct_cost'];
            $map[$month]['shared_cost'] += (float)$r['shared_cost'];
        }

        foreach ($oppRows as $r) {
            $month = $r['period_month'];
            if (!isset($map[$month])) {
                $map[$month] = $this->emptyMonthRow($month);
            }

            $map[$month]['opportunities'] += (int)$r['opportunities'];
            $map[$month]['won_customers'] += (int)$r['won_customers'];
            $map[$month]['revenue'] += (float)$r['revenue'];
            $map[$month]['gross_profit'] += (float)$r['gross_profit'];
        }

        foreach ($map as &$r) {
            $r['cac_per_customer'] = $r['won_customers'] > 0
                ? round($r['cost'] / $r['won_customers'], 2)
                : 0;

            $r['roi_pct'] = $r['cost'] > 0
                ? round((($r['gross_profit'] - $r['cost']) / $r['cost']) * 100, 2)
                : 0;
        }

        unset($r);

        ksort($map);

        return array_values($map);
    }

    private function getCostMonthly($startDate, $endDate, $params)
    {
        $this->dbx->reset_query();

        $this->dbx
            ->select("COALESCE(period_month, DATE_FORMAT(cost_date, '%Y-%m')) AS period_month", false)
            ->select('COALESCE(SUM(amount_idr), 0) AS cost', false)
            ->select("COALESCE(SUM(CASE WHEN cost_scope = 'SALES' AND allocation_method = 'DIRECT' THEN amount_idr ELSE 0 END), 0) AS direct_cost", false)
            ->select("COALESCE(SUM(CASE WHEN NOT (cost_scope = 'SALES' AND allocation_method = 'DIRECT') THEN amount_idr ELSE 0 END), 0) AS shared_cost", false)
            ->from($this->tblCost)
            ->where('status', 'APPROVED')
            ->where('deleted_at IS NULL', null, false)
            ->where('cost_date >=', $startDate)
            ->where('cost_date <=', $endDate);

        $this->applyCostFilters($params);

        $this->dbx
            ->group_by("COALESCE(period_month, DATE_FORMAT(cost_date, '%Y-%m'))", false)
            ->order_by('period_month', 'ASC');

        return $this->dbx->get()->result_array();
    }

    private function getOpportunityMonthly($startDate, $endDate, $params)
    {
        if (!$this->dbx->table_exists($this->tblOpp)) {
            return [];
        }

        $fields = $this->dbx->list_fields($this->tblOpp);

        $this->dbx->reset_query();

        $this->dbx
            ->select("DATE_FORMAT(o.created_at, '%Y-%m') AS period_month", false)
            ->select('COUNT(o.opportunity_id) AS opportunities', false)
            ->select('COALESCE(SUM(o.estimated_revenue), 0) AS revenue', false)
            ->from($this->tblOpp . ' o');

        if (in_array('gross_profit', $fields, true)) {
            $this->dbx->select('COALESCE(SUM(o.gross_profit), 0) AS gross_profit', false);
        } else {
            $this->dbx->select('0 AS gross_profit', false);
        }

        if (in_array('is_closed_won', $fields, true)) {
            $this->dbx->select("SUM(CASE WHEN o.is_closed_won = 1 THEN 1 ELSE 0 END) AS won_customers", false);
        } else {
            $this->dbx
                ->join($this->tblStages . ' s', 's.stage_name = o.stage', 'left')
                ->select("SUM(CASE WHEN s.is_won_stage = 1 THEN 1 ELSE 0 END) AS won_customers", false);
        }

        if (in_array('deleted_at', $fields, true)) {
            $this->dbx->where('o.deleted_at IS NULL', null, false);
        }

        $this->dbx->where('DATE(o.created_at) >=', $startDate);
        $this->dbx->where('DATE(o.created_at) <=', $endDate);

        $this->applyOpportunityFilters($params, $fields);

        $this->dbx
            ->group_by("DATE_FORMAT(o.created_at, '%Y-%m')", false)
            ->order_by('period_month', 'ASC');

        return $this->dbx->get()->result_array();
    }

    private function applyCostFilters($params)
    {
        if (!empty($params['region_id']) && strtoupper($params['region_id']) !== 'ALL') {
            $this->dbx->where('region_id', $params['region_id']);
        }

        if (!empty($params['sales_id'])) {
            $this->dbx->where('sales_id', (int)$params['sales_id']);
        }

        if (!empty($params['service_type'])) {
            $this->dbx->where('service_type', $params['service_type']);
        }

        if (!empty($params['lead_source'])) {
            $this->dbx->where('lead_source', $params['lead_source']);
        }

        if (!empty($params['cost_category'])) {
            $this->dbx->where('cost_category', $params['cost_category']);
        }

        if (!empty($params['cost_scope'])) {
            $this->dbx->where('cost_scope', $params['cost_scope']);
        }
    }

    private function applyOpportunityFilters($params, $fields)
    {
        if (!empty($params['region_id']) && strtoupper($params['region_id']) !== 'ALL') {
            if (in_array('region_id', $fields, true)) {
                $this->dbx->where('o.region_id', $params['region_id']);
            }
        }

        if (!empty($params['sales_id'])) {
            if (in_array('owner_sales_id', $fields, true)) {
                $this->dbx->where('o.owner_sales_id', (int)$params['sales_id']);
            } elseif (in_array('sales_id', $fields, true)) {
                $this->dbx->where('o.sales_id', (int)$params['sales_id']);
            }
        }

        if (!empty($params['service_type'])) {
            if (in_array('service_type', $fields, true)) {
                $this->dbx->where('o.service_type', $params['service_type']);
            } elseif (in_array('shipment_type', $fields, true)) {
                $this->dbx->where('o.shipment_type', $params['service_type']);
            }
        }

        if (!empty($params['lead_source']) && in_array('lead_source', $fields, true)) {
            $this->dbx->where('o.lead_source', $params['lead_source']);
        }
    }

    private function emptySourceRow($source)
    {
        return [
            'lead_source' => $source,
            'cost' => 0,
            'direct_cost' => 0,
            'shared_cost' => 0,
            'opportunities' => 0,
            'won_customers' => 0,
            'revenue' => 0,
            'gross_profit' => 0,
            'cac_per_opportunity' => 0,
            'cac_per_customer' => 0,
            'win_rate_pct' => 0,
            'roi_pct' => 0,
        ];
    }

    private function emptyMonthRow($month)
    {
        return [
            'period_month' => $month,
            'cost' => 0,
            'direct_cost' => 0,
            'shared_cost' => 0,
            'opportunities' => 0,
            'won_customers' => 0,
            'revenue' => 0,
            'gross_profit' => 0,
            'cac_per_customer' => 0,
            'roi_pct' => 0,
        ];
    }

    private function firstExistingField($fields, $candidates)
    {
        foreach ($candidates as $field) {
            if (in_array($field, $fields, true)) {
                return $field;
            }
        }

        return null;
    }

    private function normalizeDate($value, $fallback)
    {
        $value = trim((string)$value);

        if ($value === '') {
            return $fallback;
        }

        $ts = strtotime($value);

        if (!$ts) {
            return $fallback;
        }

        return date('Y-m-d', $ts);
    }
}