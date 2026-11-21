<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_opportunities extends CI_Model
{
    /** @var CI_DB_query_builder */
    public $dbx;

    private $tbl = 'crm_opportunities';
    private $pk  = 'opportunity_id';

    private $tbl_stage_master  = 'crm_opportunity_stages';
    private $tbl_stage_history = 'crm_opportunity_stage_history';

    public function __construct()
    {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    public function getById($id)
    {
        return $this->dbx
            ->get_where($this->tbl, [$this->pk => (int)$id])
            ->row();
    }

    public function create($data)
    {
        $now = date('Y-m-d H:i:s');

        if (empty($data['created_at'])) {
            $data['created_at'] = $now;
        }

        if (empty($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        // Default stage kalau tidak dikirim
        if (empty($data['stage'])) {
            $data['stage'] = 'New';
        }

        $stageName = trim((string)$data['stage']);
        $stageMeta = $this->getStageMeta($stageName);

        if (!$stageMeta) {
            return [
                'status'  => false,
                'message' => 'Invalid stage: ' . $stageName,
            ];
        }

        $isWon  = !empty($stageMeta['is_won_stage']) ? 1 : 0;
        $isLost = !empty($stageMeta['is_lost_stage']) ? 1 : 0;

        $data['is_closed_won']  = $isWon;
        $data['is_closed_lost'] = $isLost;

        if ($isWon || $isLost) {
            if (empty($data['closed_at'])) {
                $data['closed_at'] = $now;
            }
        } else {
            $data['closed_at'] = null;
        }

        $changedBy = $this->extractChangedBy($data);

        $this->dbx->trans_begin();

        $this->dbx->insert($this->tbl, $data);

        if ($this->dbx->affected_rows() < 1) {
            $this->dbx->trans_rollback();

            return [
                'status'  => false,
                'message' => 'Insert failed',
            ];
        }

        $id = (int)$this->dbx->insert_id();

        $this->insertStageHistory([
            'opportunity_id'   => $id,
            'stage_name'       => $stageName,
            'entered_stage_at' => $data['created_at'],
            'is_current'       => 1,
            'is_closed_won'    => $isWon,
            'is_closed_lost'   => $isLost,
            'closed_at'        => ($isWon || $isLost) ? $data['closed_at'] : null,
            'changed_by'       => $changedBy,
        ]);

        if ($this->dbx->trans_status() === false) {
            $this->dbx->trans_rollback();

            return [
                'status'  => false,
                'message' => 'Insert failed',
            ];
        }

        $this->dbx->trans_commit();

        return [
            'status'  => true,
            'message' => 'Opportunity created',
            'data'    => [
                'opportunity_id' => $id,
            ],
        ];
    }

    public function update($id, $data)
    {
        $id = (int)$id;

        if ($id <= 0) {
            return [
                'status'  => false,
                'message' => 'Invalid opportunity ID',
            ];
        }

        $old = $this->dbx
            ->where($this->pk, $id)
            ->get($this->tbl)
            ->row_array();

        if (!$old) {
            return [
                'status'  => false,
                'message' => 'Opportunity not found',
            ];
        }

        $now = date('Y-m-d H:i:s');

        if (empty($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        $changedBy = $this->extractChangedBy($data);

        $oldStage = trim((string)($old['stage'] ?? ''));
        $newStage = array_key_exists('stage', $data)
            ? trim((string)$data['stage'])
            : $oldStage;

        $stageChanged = ($newStage !== '' && $newStage !== $oldStage);

        $stageMeta = null;
        $isWon = !empty($old['is_closed_won']) ? 1 : 0;
        $isLost = !empty($old['is_closed_lost']) ? 1 : 0;
        $closedAt = $old['closed_at'] ?? null;

        if ($newStage !== '') {
            $stageMeta = $this->getStageMeta($newStage);

            if (!$stageMeta) {
                return [
                    'status'  => false,
                    'message' => 'Invalid stage: ' . $newStage,
                ];
            }

            $isWon  = !empty($stageMeta['is_won_stage']) ? 1 : 0;
            $isLost = !empty($stageMeta['is_lost_stage']) ? 1 : 0;

            $data['is_closed_won']  = $isWon;
            $data['is_closed_lost'] = $isLost;

            if ($isWon || $isLost) {
                $closedAt = !empty($old['closed_at']) ? $old['closed_at'] : $now;
                $data['closed_at'] = $closedAt;
            } else {
                $closedAt = null;
                $data['closed_at'] = null;
            }
        }

        $this->dbx->trans_begin();

        $this->dbx
            ->where($this->pk, $id)
            ->update($this->tbl, $data);

        if ($this->dbx->affected_rows() < 0) {
            $this->dbx->trans_rollback();

            return [
                'status'  => false,
                'message' => 'Update failed',
            ];
        }

        if ($stageChanged) {
            $this->closeCurrentStageHistory($id, $now);

            $this->insertStageHistory([
                'opportunity_id'   => $id,
                'stage_name'       => $newStage,
                'entered_stage_at' => $now,
                'is_current'       => 1,
                'is_closed_won'    => $isWon,
                'is_closed_lost'   => $isLost,
                'closed_at'        => ($isWon || $isLost) ? $closedAt : null,
                'changed_by'       => $changedBy,
            ]);

            $this->insertStageChangedActivity($id, $oldStage, $newStage, $changedBy);
        }

        if ($this->dbx->trans_status() === false) {
            $this->dbx->trans_rollback();

            return [
                'status'  => false,
                'message' => 'Update failed',
            ];
        }

        $this->dbx->trans_commit();

        return [
            'status'  => true,
            'message' => 'Opportunity updated',
            'data'    => [
                'opportunity_id'   => $id,
                'old_stage'        => $oldStage,
                'new_stage'        => $newStage,
                'stage_changed'    => $stageChanged,
                'is_closed_won'    => $isWon,
                'is_closed_lost'   => $isLost,
                'closed_at'        => $closedAt,
            ],
        ];
    }

    /**
     * Khusus kalau Anda mau update stage saja dari Kanban / dropdown stage.
     */
    public function changeStage($id, $newStage, $changedBy = null)
    {
        $data = [
            'stage'      => trim((string)$newStage),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($changedBy !== null) {
            $data['updated_by'] = $changedBy;
        }

        return $this->update($id, $data);
    }

    /**
     * delete: soft delete if columns exist, otherwise hard delete.
     * If your table doesn't have deleted_at/deleted_by, just hard delete.
     */
    public function delete($id, $soft = [])
    {
        if (is_array($soft) && !empty($soft)) {
            $this->dbx
                ->where($this->pk, (int)$id)
                ->update($this->tbl, $soft);

            if ($this->dbx->affected_rows() < 0) {
                return [
                    'status'  => false,
                    'message' => 'Delete failed',
                ];
            }

            return [
                'status'  => true,
                'message' => 'Opportunity deleted (soft)',
            ];
        }

        $this->dbx
            ->where($this->pk, (int)$id)
            ->delete($this->tbl);

        if ($this->dbx->affected_rows() < 1) {
            return [
                'status'  => false,
                'message' => 'Delete failed',
            ];
        }

        return [
            'status'  => true,
            'message' => 'Opportunity deleted',
        ];
    }

    public function list($params = [])
    {
        $page    = max(1, (int)($params['page'] ?? 1));
        $limit   = max(1, min(100, (int)($params['limit'] ?? 20)));
        $offset  = ($page - 1) * $limit;

        $region  = (string)($params['region'] ?? '');
        $salesId = (string)($params['sales_id'] ?? '');
        $q       = trim((string)($params['q'] ?? ''));
        $stage   = (string)($params['stage'] ?? '');

        $this->dbx->from($this->tbl);
        $this->dbx->where('deleted_at IS NULL', null, false);

        if ($region !== '') {
            $this->dbx->where('region_id', $region);
        }

        if ($salesId !== '') {
            $this->dbx->where('owner_sales_id', $salesId);
        }

        if ($stage !== '') {
            $this->dbx->where('stage', $stage);
        }

        if ($q !== '') {
            $this->dbx->group_start();
            $this->dbx->like('opportunity_no', $q);
            $this->dbx->or_like('account_name', $q);
            $this->dbx->or_like('origin', $q);
            $this->dbx->or_like('destination', $q);
            $this->dbx->or_like('incoterm', $q);
            $this->dbx->or_like('hs_code', $q);
            $this->dbx->or_like('notes', $q);
            $this->dbx->or_like('lost_reason', $q);
            $this->dbx->group_end();
        }

        $countDb = clone $this->dbx;
        $total = (int)$countDb->count_all_results('', false);

        $this->dbx->select([
            'opportunity_id',
            'opportunity_no',
            'stage',
            'lost_reason',
            'source_type',
            'lead_id',
            'id_shipper',
            'account_name',
            'region_id',
            'owner_sales_id',
            'shipment_type',
            'transport_mode',
            'sea_type',
            'incoterm',
            'service_level',
            'origin',
            'destination',
            'cargo_ready_date',
            'expected_arrival_date',
            'commodity_type',
            'weight_kg',
            'volume_cbm',
            'chargeable_weight_kg',
            'container_size',
            'hs_code',
            'currency',
            'estimated_revenue',
            'buying_price',
            'gross_profit',
            'competitor_id',
            'notes',
            'created_at',
            'updated_at',
            'closed_at',
            'is_closed_won',
            'is_closed_lost',
        ], false);

        $this->dbx->order_by('updated_at', 'DESC');

        $rows = $this->dbx
            ->limit($limit, $offset)
            ->get()
            ->result_array();

        return [
            'status' => true,
            'page'   => $page,
            'limit'  => $limit,
            'total'  => $total,
            'data'   => $rows,
        ];
    }

    private function getStageMeta($stageName)
    {
        $stageName = trim((string)$stageName);

        if ($stageName === '') {
            return null;
        }

        return $this->dbx
            ->where('stage_name', $stageName)
            ->where('is_active', 1)
            ->get($this->tbl_stage_master)
            ->row_array();
    }

    private function closeCurrentStageHistory($opportunityId, $leftAt = null)
    {
        $leftAt = $leftAt ?: date('Y-m-d H:i:s');

        $this->dbx
            ->where('opportunity_id', (int)$opportunityId)
            ->where('is_current', 1)
            ->update($this->tbl_stage_history, [
                'left_stage_at' => $leftAt,
                'is_current'    => 0,
            ]);
    }

    private function insertStageHistory($data)
    {
        $now = date('Y-m-d H:i:s');

        $insert = [
            'opportunity_id'    => (int)$data['opportunity_id'],
            'stage_name'        => trim((string)$data['stage_name']),
            'entered_stage_at'  => !empty($data['entered_stage_at']) ? $data['entered_stage_at'] : $now,
            'left_stage_at'     => $data['left_stage_at'] ?? null,
            'is_current'        => isset($data['is_current']) ? (int)$data['is_current'] : 1,
            'is_closed_won'     => isset($data['is_closed_won']) ? (int)$data['is_closed_won'] : 0,
            'is_closed_lost'    => isset($data['is_closed_lost']) ? (int)$data['is_closed_lost'] : 0,
            'closed_at'         => $data['closed_at'] ?? null,
            'changed_by'        => $data['changed_by'] ?? null,
            'created_at'        => $data['created_at'] ?? $now,
        ];

        $this->dbx->insert($this->tbl_stage_history, $insert);
    }

    private function insertStageChangedActivity($opportunityId, $oldStage, $newStage, $changedBy = null)
	{
		// Disable dulu karena struktur crm_activities existing belum pasti.
		// Stage history tetap dicatat di crm_opportunity_stage_history.
		return;
	}

    private function extractChangedBy($data)
    {
        if (isset($data['updated_by']) && $data['updated_by'] !== '') {
            return (int)$data['updated_by'];
        }

        if (isset($data['created_by']) && $data['created_by'] !== '') {
            return (int)$data['created_by'];
        }

        if (isset($data['owner_sales_id']) && $data['owner_sales_id'] !== '') {
            return (int)$data['owner_sales_id'];
        }

        return null;
    }
}