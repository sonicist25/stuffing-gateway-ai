<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_sales_funnel extends CI_Model
{
    /** @var CI_DB_query_builder */
    public $dbx;

    private $tblOpp     = 'crm_opportunities';
    private $tblStages  = 'crm_opportunity_stages';
    private $tblHistory = 'crm_opportunity_stage_history';

    public function __construct()
    {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    public function dashboard($params = [])
    {
        $periodStart = $this->normalizeDate($params['period_start'] ?? null, date('Y-01-01'));
        $periodEnd   = $this->normalizeDate($params['period_end'] ?? null, date('Y-m-d'));

        $startDt = $periodStart . ' 00:00:00';
        $endDt   = $periodEnd . ' 23:59:59';

        $granularity = $params['granularity'] ?? $params['period_mode'] ?? 'month';

        $stages = $this->getStages();

        $snapshotMap   = $this->getSnapshotStageMap($startDt, $endDt, $params);
        $avgDaysMap    = $this->getAvgDaysMap($startDt, $endDt, $params);
        $enteredMap    = $this->getEnteredStageMap($startDt, $endDt, $params);
        $lostAfterMap  = $this->getLostAfterStageMap($startDt, $endDt, $params);

        $stageRows = [];
        $totalOpportunities = 0;
        $totalPipelineValue = 0;
        $wonCount = 0;
        $lostCount = 0;

        $normalStages = [];

        foreach ($stages as $s) {
            $isLost = (int)($s['is_lost_stage'] ?? 0) === 1;

            if (!$isLost) {
                $normalStages[] = $s;
            }
        }

        $nextStageMap = $this->buildNextStageMap($normalStages);

        foreach ($stages as $s) {
            $stageName = $s['stage_name'];

            $count = (int)($snapshotMap[$stageName]['count_opportunities'] ?? 0);
            $value = (float)($snapshotMap[$stageName]['sum_estimated_revenue'] ?? 0);

            $enteredCount = (int)($enteredMap[$stageName] ?? 0);
            $nextStage = $nextStageMap[$stageName] ?? null;

            $nextEnteredCount = 0;
            $conversionToNextPct = null;

            if ($nextStage !== null && $enteredCount > 0) {
                $nextEnteredCount = $this->getMovedToNextCount(
                    $stageName,
                    $nextStage,
                    $startDt,
                    $endDt,
                    $params
                );

                $conversionToNextPct = round(($nextEnteredCount / $enteredCount) * 100, 2);
            }

            $lostAfterCount = (int)($lostAfterMap[$stageName] ?? 0);
            $dropOffPct = null;

            if ($enteredCount > 0) {
                $dropOffPct = round(($lostAfterCount / $enteredCount) * 100, 2);
            }

            $avgDays = $avgDaysMap[$stageName] ?? null;

            if ((int)($s['is_won_stage'] ?? 0) === 1) {
                $wonCount += $count;
            } elseif ((int)($s['is_lost_stage'] ?? 0) === 1) {
                $lostCount += $count;
            }

            $totalOpportunities += $count;
            $totalPipelineValue += $value;

            $stageRows[] = [
                'stage_name'            => $stageName,
                'stage_order'           => (int)$s['stage_order'],
                'stage_tone'            => $s['stage_tone'] ?? 'default',
                'is_won_stage'          => (int)($s['is_won_stage'] ?? 0),
                'is_lost_stage'         => (int)($s['is_lost_stage'] ?? 0),

                'count_opportunities'   => $count,
                'sum_estimated_revenue' => $value,

                'avg_days_in_stage'     => $avgDays === null ? null : round((float)$avgDays, 2),
                'entered_stage_count'   => $enteredCount,
                'next_stage_name'       => $nextStage,
                'moved_to_next_count'   => $nextEnteredCount,
                'conversion_to_next_pct'=> $conversionToNextPct,

                'lost_after_stage_count'=> $lostAfterCount,
                'drop_off_pct'          => $dropOffPct,
            ];
        }

        $openCount = max(0, $totalOpportunities - $wonCount - $lostCount);

        $winRatePct = $totalOpportunities > 0
            ? round(($wonCount / $totalOpportunities) * 100, 2)
            : 0;

        $lostRatePct = $totalOpportunities > 0
            ? round(($lostCount / $totalOpportunities) * 100, 2)
            : 0;

        $velocityDays = $this->getVelocityDays($startDt, $endDt, $params);

        return [
            'status' => true,
            'data'   => [
                'period' => [
                    'start'       => $periodStart,
                    'end'         => $periodEnd,
                    'granularity' => $granularity,
                ],
                'filters' => [
                    'region_id'    => $params['region_id'] ?? null,
                    'sales_id'     => $params['sales_id'] ?? null,
                    'mode'         => $params['mode'] ?? null,
                    'service_type' => $params['service_type'] ?? null,
                    'industry'     => $params['industry'] ?? null,
                    'lead_source'  => $params['lead_source'] ?? null,
                ],
                'summary' => [
                    'total_opportunities'  => $totalOpportunities,
                    'open_count'           => $openCount,
                    'won_count'            => $wonCount,
                    'lost_count'           => $lostCount,
                    'win_rate_pct'         => $winRatePct,
                    'lost_rate_pct'        => $lostRatePct,
                    'total_pipeline_value' => $totalPipelineValue,
                    'velocity_days'        => $velocityDays,
                ],
                'stages' => $stageRows,
            ],
        ];
    }

    private function getStages()
    {
        return $this->dbx
            ->select([
                'stage_name',
                'stage_order',
                'stage_tone',
                'is_won_stage',
                'is_lost_stage',
            ])
            ->from($this->tblStages)
            ->where('is_active', 1)
            ->order_by('stage_order', 'ASC')
            ->get()
            ->result_array();
    }

    private function getSnapshotStageMap($startDt, $endDt, $params)
    {
        $this->dbx->reset_query();

        $this->dbx
            ->select('o.stage AS stage_name', false)
            ->select('COUNT(o.opportunity_id) AS count_opportunities', false)
            ->select('COALESCE(SUM(o.estimated_revenue), 0) AS sum_estimated_revenue', false)
            ->from($this->tblOpp . ' o');

        $this->applyOpportunityFilters($params, 'o');
        $this->dbx->where('o.created_at >=', $startDt);
        $this->dbx->where('o.created_at <=', $endDt);
        $this->dbx->group_by('o.stage');

        $rows = $this->dbx->get()->result_array();

        $map = [];

        foreach ($rows as $r) {
            $map[$r['stage_name']] = [
                'count_opportunities'   => (int)$r['count_opportunities'],
                'sum_estimated_revenue' => (float)$r['sum_estimated_revenue'],
            ];
        }

        return $map;
    }

    private function getAvgDaysMap($startDt, $endDt, $params)
    {
        $this->dbx->reset_query();

        $this->dbx
            ->select('h.stage_name', false)
            ->select('AVG(TIMESTAMPDIFF(HOUR, h.entered_stage_at, COALESCE(h.left_stage_at, NOW())) / 24) AS avg_days', false)
            ->from($this->tblHistory . ' h')
            ->join($this->tblOpp . ' o', 'o.opportunity_id = h.opportunity_id', 'inner');

        $this->applyOpportunityFilters($params, 'o');
        $this->dbx->where('h.entered_stage_at >=', $startDt);
        $this->dbx->where('h.entered_stage_at <=', $endDt);
        $this->dbx->group_by('h.stage_name');

        $rows = $this->dbx->get()->result_array();

        $map = [];

        foreach ($rows as $r) {
            $map[$r['stage_name']] = $r['avg_days'] === null ? null : (float)$r['avg_days'];
        }

        return $map;
    }

    private function getEnteredStageMap($startDt, $endDt, $params)
    {
        $this->dbx->reset_query();

        $this->dbx
            ->select('h.stage_name', false)
            ->select('COUNT(DISTINCT h.opportunity_id) AS entered_count', false)
            ->from($this->tblHistory . ' h')
            ->join($this->tblOpp . ' o', 'o.opportunity_id = h.opportunity_id', 'inner');

        $this->applyOpportunityFilters($params, 'o');
        $this->dbx->where('h.entered_stage_at >=', $startDt);
        $this->dbx->where('h.entered_stage_at <=', $endDt);
        $this->dbx->group_by('h.stage_name');

        $rows = $this->dbx->get()->result_array();

        $map = [];

        foreach ($rows as $r) {
            $map[$r['stage_name']] = (int)$r['entered_count'];
        }

        return $map;
    }

    private function getLostAfterStageMap($startDt, $endDt, $params)
    {
        $this->dbx->reset_query();

        /**
         * Logic:
         * jumlah opportunity yang masuk stage X dalam periode,
         * lalu akhirnya berada di lost stage.
         */
        $this->dbx
            ->select('h.stage_name', false)
            ->select('COUNT(DISTINCT h.opportunity_id) AS lost_count', false)
            ->from($this->tblHistory . ' h')
            ->join($this->tblOpp . ' o', 'o.opportunity_id = h.opportunity_id', 'inner')
            ->join($this->tblStages . ' s', 's.stage_name = o.stage AND s.is_lost_stage = 1', 'inner');

        $this->applyOpportunityFilters($params, 'o');
        $this->dbx->where('h.entered_stage_at >=', $startDt);
        $this->dbx->where('h.entered_stage_at <=', $endDt);
        $this->dbx->group_by('h.stage_name');

        $rows = $this->dbx->get()->result_array();

        $map = [];

        foreach ($rows as $r) {
            $map[$r['stage_name']] = (int)$r['lost_count'];
        }

        return $map;
    }

    private function getMovedToNextCount($stageName, $nextStageName, $startDt, $endDt, $params)
    {
        $this->dbx->reset_query();

        $this->dbx
            ->select('COUNT(DISTINCT h1.opportunity_id) AS moved_count', false)
            ->from($this->tblHistory . ' h1')
            ->join(
                $this->tblHistory . ' h2',
                'h2.opportunity_id = h1.opportunity_id 
                 AND h2.stage_name = ' . $this->dbx->escape($nextStageName) . '
                 AND h2.entered_stage_at > h1.entered_stage_at',
                'inner',
                false
            )
            ->join($this->tblOpp . ' o', 'o.opportunity_id = h1.opportunity_id', 'inner');

        $this->applyOpportunityFilters($params, 'o');
        $this->dbx->where('h1.stage_name', $stageName);
        $this->dbx->where('h1.entered_stage_at >=', $startDt);
        $this->dbx->where('h1.entered_stage_at <=', $endDt);

        $row = $this->dbx->get()->row_array();

        return (int)($row['moved_count'] ?? 0);
    }

    private function getVelocityDays($startDt, $endDt, $params)
    {
        $this->dbx->reset_query();

        /**
         * Velocity:
         * rata-rata hari dari created_at sampai closed_at
         * untuk opportunity yang won.
         */
        $this->dbx
            ->select('AVG(TIMESTAMPDIFF(HOUR, o.created_at, o.closed_at) / 24) AS velocity_days', false)
            ->from($this->tblOpp . ' o')
            ->join($this->tblStages . ' s', 's.stage_name = o.stage AND s.is_won_stage = 1', 'inner');

        $this->applyOpportunityFilters($params, 'o');
        $this->dbx->where('o.closed_at IS NOT NULL', null, false);
        $this->dbx->where('o.closed_at >=', $startDt);
        $this->dbx->where('o.closed_at <=', $endDt);

        $row = $this->dbx->get()->row_array();

        if (!$row || $row['velocity_days'] === null) {
            return null;
        }

        return round((float)$row['velocity_days'], 2);
    }

    private function buildNextStageMap($normalStages)
    {
        $map = [];

        $count = count($normalStages);

        for ($i = 0; $i < $count; $i++) {
            $current = $normalStages[$i]['stage_name'];
            $next = null;

            if (isset($normalStages[$i + 1])) {
                $next = $normalStages[$i + 1]['stage_name'];
            }

            $map[$current] = $next;
        }

        return $map;
    }

    private function applyOpportunityFilters($params, $alias = 'o')
    {
        $fields = $this->getOpportunityFields();

        if (in_array('deleted_at', $fields, true)) {
            $this->dbx->where($alias . '.deleted_at IS NULL', null, false);
        }

        if (!empty($params['region_id']) && in_array('region_id', $fields, true)) {
            $this->dbx->where($alias . '.region_id', $params['region_id']);
        }

        if (!empty($params['sales_id']) && in_array('owner_sales_id', $fields, true)) {
            $this->dbx->where($alias . '.owner_sales_id', (int)$params['sales_id']);
        }

        /**
         * Di schema opportunity existing Anda namanya shipment_type.
         * Tapi dari FE boleh kirim service_type.
         */
        if (!empty($params['service_type'])) {
            if (in_array('service_type', $fields, true)) {
                $this->dbx->where($alias . '.service_type', $params['service_type']);
            } elseif (in_array('shipment_type', $fields, true)) {
                $this->dbx->where($alias . '.shipment_type', $params['service_type']);
            }
        }

        if (!empty($params['industry']) && in_array('industry', $fields, true)) {
            $this->dbx->where($alias . '.industry', $params['industry']);
        }

        if (!empty($params['lead_source']) && in_array('lead_source', $fields, true)) {
            $this->dbx->where($alias . '.lead_source', $params['lead_source']);
        }

        if (!empty($params['stage']) && in_array('stage', $fields, true)) {
            $this->dbx->where($alias . '.stage', $params['stage']);
        }
    }

    private function getOpportunityFields()
    {
        static $fields = null;

        if ($fields === null) {
            $fields = $this->dbx->list_fields($this->tblOpp);
        }

        return $fields;
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