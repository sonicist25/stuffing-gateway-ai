<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Activities extends RestController {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('crm_auth');
        $this->load->model('api/crm/M_activities', 'm_act');
		$this->load->model('api/crm/M_acquisition_costs', 'mcost');
    }

    // POST /api/crm/activities
    public function index_post()
{
    try {
        $u = $this->crm_auth->require_user();

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) $payload = [];

        $attachments = $payload['attachments'] ?? [];

        $entityType = (string)($payload['entity_type'] ?? '');
        $entityId   = (int)($payload['entity_id'] ?? 0);

        $allowed = ['lead','customer','opportunity','quotation','task'];

        if (!in_array($entityType, $allowed, true)) {
            return $this->response([
                'status'  => false,
                'message' => 'Invalid entity_type'
            ], 400);
        }

        if ($entityId <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'entity_id is required'
            ], 400);
        }

        $regionId = (string)($payload['region_id'] ?? $u['region_id']);

        $ownerSalesId = isset($payload['owner_sales_id'])
            ? (int)$payload['owner_sales_id']
            : (int)$u['user_id'];

        // Non-management: owner harus dirinya
        if ((int)$u['management'] !== 1) {
            $ownerSalesId = (int)$u['user_id'];
            $regionId = (string)$u['region_id'];
        }

        $insert = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'region_id' => $regionId,
            'owner_sales_id' => $ownerSalesId,

            'action_type' => (string)($payload['action_type'] ?? 'FOLLOWUP'),
            'channel' => (string)($payload['channel'] ?? 'WA'),
            'subject' => isset($payload['subject']) ? (string)$payload['subject'] : null,
            'outcome' => isset($payload['outcome']) ? (string)$payload['outcome'] : null,
            'note' => isset($payload['note']) ? (string)$payload['note'] : null,
            'attachment_count' => (int)($payload['attachment_count'] ?? 0),

            'created_by_sales_id' => (int)$u['user_id'],
            'activity_at' => !empty($payload['activity_at'])
                ? $payload['activity_at']
                : date('Y-m-d H:i:s'),

            'next_action_type' => !empty($payload['next_action_type'])
                ? (string)$payload['next_action_type']
                : null,
            'next_action_due_at' => !empty($payload['next_action_due_at'])
                ? $payload['next_action_due_at']
                : null,
            'next_action_note' => !empty($payload['next_action_note'])
                ? (string)$payload['next_action_note']
                : null,
        ];

        $id = $this->m_act->create($insert);

        if (empty($id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to create activity'
            ], 500);
        }

        /**
         * Mapping related entity untuk CAC direct cost.
         */
        $leadId = null;
        $opportunityId = null;

        if ($entityType === 'lead') {
            $leadId = $entityId;
        }

        if ($entityType === 'opportunity') {
            $opportunityId = $entityId;
        }

        if (!empty($payload['lead_id'])) {
            $leadId = (int)$payload['lead_id'];
        }

        if (!empty($payload['opportunity_id'])) {
            $opportunityId = (int)$payload['opportunity_id'];
        }

        /**
         * Upsert direct acquisition cost dari Sales Activity.
         */
        $costRes = $this->mcost->upsertFromSalesActivity((int)$id, [
            'has_acquisition_cost' => !empty($payload['has_acquisition_cost']) ? 1 : 0,

            'cost_category'        => $payload['cost_category'] ?? null,
            'cost_amount'          => $payload['cost_amount'] ?? 0,
            'cost_currency'        => $payload['cost_currency'] ?? 'IDR',
            'cost_rate_to_idr'     => $payload['cost_rate_to_idr'] ?? 1,
            'cost_description'     => $payload['cost_description'] ?? null,

            'activity_date'        => !empty($insert['activity_at'])
                ? date('Y-m-d', strtotime($insert['activity_at']))
                : date('Y-m-d'),

            'region_id'            => $regionId,
            'sales_id'             => $ownerSalesId,

            'lead_id'              => $leadId,
            'opportunity_id'       => $opportunityId,

            'service_type'         => $payload['service_type'] ?? $payload['shipment_type'] ?? null,
            'lead_source'          => $payload['lead_source'] ?? null,
            'industry'             => $payload['industry'] ?? null,

            'cost_status' => $payload['cost_status'] ?? 'APPROVED',

            'created_by'           => (int)$u['user_id'],
            'updated_by'           => (int)$u['user_id'],
        ]);

        if (!empty($attachments) && is_array($attachments)) {
            $this->m_act->insert_attachments($id, $attachments);
        }

        return $this->response([
            'status'      => true,
            'message'     => 'Activity logged',
            'activity_id' => $id,
            'cost'        => $costRes,
        ], RestController::HTTP_OK);

    } catch (Exception $e) {
        return $this->response([
            'status'  => false,
            'message' => $e->getMessage(),
        ], RestController::HTTP_UNAUTHORIZED);
    }
}
	public function upload_post()
	{
		try {
			$u = $this->crm_auth->require_user();

			if (empty($_FILES['file'])) {
				return $this->response([
					'status'  => false,
					'message' => 'Tidak ada file yang dikirim'
				], 400);
			}

			$file = $_FILES['file'];

			// Validasi extension
			$allowed_ext = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx','txt'];
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

			if (!in_array($ext, $allowed_ext)) {
				return $this->response([
					'status'  => false,
					'message' => 'Filetype tidak diizinkan: '.$ext
				], 400);
			}

			// Tentukan folder tujuan
			$upload_path = FCPATH.'public/uploads/activities/';
			if (!is_dir($upload_path)) {
				mkdir($upload_path, 0777, true);
			}

			// Nama file unik
			$newName = uniqid().'.'.$ext;
			$target  = $upload_path.$newName;

			if (!move_uploaded_file($file['tmp_name'], $target)) {
				return $this->response([
					'status'  => false,
					'message' => 'Gagal memindahkan file'
				], 500);
			}

			// Buat URL file
			$file_url = base_url('public/uploads/activities/'.$newName);

			return $this->response([
				'status'   => true,
				'file'     => [
					'file_url'  => $file_url,
					'filename'  => $file['name'],
					'mime_type' => $file['type'],
					'file_size' => $file['size'],
				]
			], 200);


		} catch (Exception $e) {
			if (empty($_FILES['file'])) {
        return $this->response([
            'status' => false,
            'message' => 'Tidak ada file yang dikirim',
        ], 400);
    }

    // Debug: echo MIME dan nama file
    return $this->response([
        'status' => true,
        'filename' => $_FILES['file']['name'],
        'mime_type' => $_FILES['file']['type'],
        'tmp_name' => $_FILES['file']['tmp_name'],
        'size' => $_FILES['file']['size'],
    ], 200);

		}
	}
}
