<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_shipment_sync_model extends CI_Model
{
    protected $table = 'tv_shipment_syncs';

    public function __construct()
    {
        parent::__construct();
    }

    public function find_by_id($id)
    {
        return $this->db
            ->from($this->table)
            ->where('id', (int)$id)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function find_by_tracking_number($trackingNumber)
    {
        $trackingNumber = trim((string)$trackingNumber);
        if ($trackingNumber === '') {
            return null;
        }

        return $this->db
            ->from($this->table)
            ->where('tracking_number', $trackingNumber)
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function create_sync(array $data)
    {
        $now = date('Y-m-d H:i:s');

        if (!isset($data['created_at'])) {
            $data['created_at'] = $now;
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        $this->db->insert($this->table, $data);

        if (!$this->db->affected_rows()) {
            return false;
        }

        return (int)$this->db->insert_id();
    }

    public function update_sync($id, array $data)
    {
        if (empty($data)) {
            return true;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', (int)$id);
        return $this->db->update($this->table, $data);
    }

    public function ensure_sync(array $data)
	{
		$trackingNumber = trim((string)($data['tracking_number'] ?? ''));
		if ($trackingNumber === '') {
			return false;
		}

		$existing = $this->find_by_tracking_number($trackingNumber);
		$now = date('Y-m-d H:i:s');

		if ($existing) {
			$update = $data;

			// Jangan pernah reset field operasional cron dari payload booking biasa.
			unset($update['created_at']);

			$existingStatus = (string)($existing['sync_status'] ?? '');
			$existingNext   = $existing['next_poll_at'] ?? null;
			$newHbl         = trim((string)($data['hbl'] ?? ''));
			$existingHbl    = trim((string)($existing['hbl'] ?? ''));

			$hasHbl = ($newHbl !== '' || $existingHbl !== '');

			// Kalau row sudah pernah aktif / sudah jalan / sudah selesai,
			// jangan overwrite status dan jadwal polling dari payload booking.
			if (in_array($existingStatus, [
				'BC_SENT',
				'ATTR_PATCHED',
				'TRACKING_ACTIVE',
				'DELIVERED',
				'COMPLETED',
				'STOPPED',
				'ERROR',
			], true)) {
				unset($update['sync_status']);
				unset($update['next_poll_at']);
				unset($update['last_error']);
			}

			// Case penting:
			// Row lama masih NEW, lalu sekarang HBL sudah ada.
			// Ini harus langsung diaktifkan supaya cron bisa ambil.
			if ($existingStatus === 'NEW' && $hasHbl) {
				$update['sync_status']  = 'TRACKING_ACTIVE';
				$update['next_poll_at'] = $now;
				$update['last_error']   = null;
			}

			// Safety:
			// Kalau status sudah eligible tapi next_poll_at kosong, masukkan antrean.
			if (
				$hasHbl &&
				empty($existingNext) &&
				in_array($existingStatus, ['BC_SENT', 'ATTR_PATCHED', 'TRACKING_ACTIVE'], true)
			) {
				$update['next_poll_at'] = $now;
			}

			$this->update_sync($existing['id'], $update);
			return (int)$existing['id'];
		}

		// New row.
		if (!empty($data['hbl'])) {
			$data['sync_status']  = 'TRACKING_ACTIVE';
			$data['next_poll_at'] = $now;
			$data['last_error']   = null;
		}

		return $this->create_sync($data);
	}

    public function get_due_shipments($limit = 100, array $shipperIds = [])
    {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 100;
        }

        $this->db->from($this->table);

		$this->db->where_in('sync_status', [
			'NEW',
			'BC_SENT',
			'ATTR_PATCHED',
			'TRACKING_ACTIVE',
		]);

		// Jangan ambil row tanpa HBL, karena track_gocomet butuh HBL.
		$this->db->where('hbl IS NOT NULL', null, false);
		$this->db->where("TRIM(hbl) <>", "");

		$this->db->group_start();
		$this->db->where('next_poll_at IS NULL', null, false);
		$this->db->or_where('next_poll_at <=', date('Y-m-d H:i:s'));
		$this->db->group_end();

		if (!empty($shipperIds)) {
			$shipperIds = array_map('intval', $shipperIds);
			$this->db->where_in('shipper_id', $shipperIds);
		}

		$this->db->order_by('next_poll_at IS NULL', 'DESC', false);
		$this->db->order_by('next_poll_at', 'ASC');
		$this->db->order_by('id', 'ASC');
		$this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    public function mark_bc_sent($id)
    {
        return $this->update_sync($id, [
            'sync_status'   => 'BC_SENT',
            'last_event_at' => date('Y-m-d H:i:s'),
            'last_error'    => null,
        ]);
    }

    public function mark_attr_patched($id, $hash)
    {
        return $this->update_sync($id, [
            'sync_status'     => 'ATTR_PATCHED',
            'patch_status'    => 'SUCCESS',
            'last_patch_hash' => $hash,
            'last_patch_at'   => date('Y-m-d H:i:s'),
            'last_error'      => null,
        ]);
    }

    public function mark_tracking_active($id, $nextPollAt = null)
    {
        return $this->update_sync($id, [
            'sync_status'       => 'TRACKING_ACTIVE',
            'next_poll_at'      => $nextPollAt,
            'last_mixed_poll_at'=> date('Y-m-d H:i:s'),
        ]);
    }

    public function set_next_poll($id, $nextPollAt)
    {
        return $this->update_sync($id, [
            'next_poll_at'       => $nextPollAt,
            'last_mixed_poll_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function mark_completed($id)
    {
        return $this->update_sync($id, [
            'sync_status'  => 'COMPLETED',
            'stopped_at'   => date('Y-m-d H:i:s'),
            'next_poll_at' => null,
            'last_error'   => null,
        ]);
    }

    public function mark_delivered($id, $mixedFetched = false)
    {
        $data = [
            'sync_status'  => 'DELIVERED',
            'delivered_at' => date('Y-m-d H:i:s'),
            'next_poll_at' => null,
            'last_error'   => null,
        ];
        if ($mixedFetched) {
            $data['last_mixed_poll_at'] = date('Y-m-d H:i:s');
        }
        return $this->update_sync($id, $data);
    }

    public function mark_stopped($id, $message = null)
    {
        return $this->update_sync($id, [
            'sync_status'  => 'STOPPED',
            'stopped_at'   => date('Y-m-d H:i:s'),
            'next_poll_at' => null,
            'last_error'   => $message,
        ]);
    }

    public function mark_patch_failed($id, $message = null)
    {
        return $this->update_sync($id, [
            'patch_status' => 'FAILED',
            'last_error'   => $message,
        ]);
    }

    /**
     * Error fatal/permanen.
     * Pakai hanya untuk kasus yang memang harus mematikan row.
     */
    public function save_last_error($id, $message)
    {
        return $this->update_sync($id, [
            'sync_status' => 'ERROR',
            'last_error'  => $message,
        ]);
    }

    /**
     * Error sementara / retryable.
     * Row tetap aktif dan dijadwalkan retry lagi.
     */
    public function save_retryable_error($id, $message, $nextPollAt, $mixedFetched = false)
    {
        $data = [
            'sync_status'  => 'TRACKING_ACTIVE',
            'next_poll_at' => $nextPollAt,
            'last_error'   => $message,
        ];
        if ($mixedFetched) {
            $data['last_mixed_poll_at'] = date('Y-m-d H:i:s');
        }
        return $this->update_sync($id, $data);
    }

    public function clear_last_error($id)
    {
        return $this->update_sync($id, [
            'last_error' => null,
        ]);
    }
}