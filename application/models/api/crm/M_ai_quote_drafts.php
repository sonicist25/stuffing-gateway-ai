<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class M_ai_quote_drafts extends CI_Model
{
    /** @var CI_DB_query_builder */
    public $dbx;

    private $tbl = 'crm_ai_quote_drafts';

    public function __construct()
    {
        parent::__construct();
        $this->dbx = $this->load->database('master', true);
    }

    private function jsonEncode($value)
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? null : $json;
    }

    public function getById($draftId)
    {
        return $this->dbx
            ->where('draft_id', (int)$draftId)
            ->get($this->tbl)
            ->row_array();
    }

    public function getAccessible($draftId, $userId, $regionId, $isManagement)
    {
        $this->dbx->from($this->tbl);
        $this->dbx->where('draft_id', (int)$draftId);

        if (!$isManagement) {
            $this->dbx->where('created_by_sales_id', (int)$userId);
            $this->dbx->where('region_id', (string)$regionId);
        }

        return $this->dbx->get()->row_array();
    }

    public function findBySession($sessionId, $userId)
    {
        return $this->dbx
            ->where('session_id', trim((string)$sessionId))
            ->where('created_by_sales_id', (int)$userId)
            ->order_by('draft_id', 'DESC')
            ->limit(1)
            ->get($this->tbl)
            ->row_array();
    }

    public function saveDraft(array $payload, $userId, $regionId, $isManagement = false)
    {
        $now = date('Y-m-d H:i:s');
        $draftId = (int)($payload['draft_id'] ?? 0);
        $sessionId = trim((string)($payload['session_id'] ?? ''));
        $status = strtoupper(trim((string)($payload['status'] ?? 'WORKING')));

        if (!in_array($status, ['WORKING', 'WAITING_OPPORTUNITY', 'DISCARDED'], true)) {
            $status = 'WORKING';
        }

        $draft = is_array($payload['draft'] ?? null) ? $payload['draft'] : [];
        $messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
        $pending = is_array($payload['pending_context'] ?? null)
            ? $payload['pending_context']
            : null;

        $header = is_array($draft['header'] ?? null) ? $draft['header'] : [];
        $accountName = trim((string)($header['account_name'] ?? $payload['account_name'] ?? ''));

        $existing = null;
        if ($draftId > 0) {
            $existing = $this->getAccessible($draftId, $userId, $regionId, $isManagement);
            if (!$existing) {
                throw new RuntimeException('Draft tidak ditemukan atau tidak dapat diakses.');
            }
        } elseif ($sessionId !== '') {
            $existing = $this->findBySession($sessionId, $userId);
            if ($existing) {
                $draftId = (int)$existing['draft_id'];
            }
        }

        if ($existing && strtoupper((string)($existing['status'] ?? '')) === 'CONVERTED') {
            throw new RuntimeException('Draft ini sudah dikonversi menjadi quotation. Mulai New Chat untuk draft baru.');
        }

        $row = [
            'session_id'          => $sessionId,
            'account_name'        => $accountName !== '' ? $accountName : null,
            'region_id'           => (string)$regionId,
            'created_by_sales_id' => (int)$userId,
            'status'              => $status,
            'draft_json'          => $this->jsonEncode($draft),
            'messages_json'       => $this->jsonEncode($messages),
            'pending_context_json'=> $this->jsonEncode($pending),
            'updated_at'          => $now,
        ];

        if ($draftId > 0) {
            $this->dbx->set('version_no', 'version_no + 1', false);
            $this->dbx->where('draft_id', $draftId)->update($this->tbl, $row);

            if ($this->dbx->affected_rows() < 0) {
                throw new RuntimeException('Draft gagal diperbarui.');
            }

            return $draftId;
        }

        if ($sessionId === '') {
            throw new InvalidArgumentException('session_id is required.');
        }

        $row['version_no'] = 1;
        $row['created_at'] = $now;

        $this->dbx->insert($this->tbl, $row);
        if ($this->dbx->affected_rows() < 1) {
            throw new RuntimeException('Draft gagal disimpan.');
        }

        return (int)$this->dbx->insert_id();
    }

    public function attachOpportunity($draftId, $opportunityId)
    {
        $this->dbx
            ->where('draft_id', (int)$draftId)
            ->update($this->tbl, [
                'opportunity_id' => (int)$opportunityId,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

        return $this->dbx->affected_rows() >= 0;
    }

    public function markConverted($draftId, $opportunityId, $quoteId)
    {
        $this->dbx
            ->where('draft_id', (int)$draftId)
            ->update($this->tbl, [
                'opportunity_id' => (int)$opportunityId,
                'quote_id'       => (int)$quoteId,
                'status'         => 'CONVERTED',
                'converted_at'   => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

        return $this->dbx->affected_rows() >= 0;
    }
}
