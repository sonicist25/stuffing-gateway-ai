<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_sync extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->dbx = $this->load->database('master', TRUE);
    }

    public function lead_converted($p)
    {
        $leadId    = isset($p['lead_id']) ? (int)$p['lead_id'] : 0;
        $idShipper = isset($p['id_shipper']) ? (int)$p['id_shipper'] : 0;

        $convAt = !empty($p['converted_at']) ? (string)$p['converted_at'] : date('Y-m-d H:i:s');
        $source = !empty($p['source_system']) ? trim((string)$p['source_system']) : null;
        $meta   = isset($p['meta']) ? $p['meta'] : null;

        if ($leadId <= 0) return ['status'=>false,'message'=>'lead_id is required'];
        if ($idShipper <= 0) return ['status'=>false,'message'=>'id_shipper is required'];

        $lead = $this->dbx->where('id_lead', $leadId)->get('crm_leads')->row_array();
        if (!$lead) return ['status'=>false,'message'=>'Lead not found'];

        // idempotent
        if (($lead['conversion_status'] ?? '') === 'CONVERTED' && (int)($lead['customer_id_shipper'] ?? 0) > 0) {
            return [
                'status'=>true,
                'message'=>'Already converted',
                'lead_id'=>$leadId,
                'id_shipper'=>(int)$lead['customer_id_shipper']
            ];
        }

        $data = [
            'conversion_status'   => 'CONVERTED',
            'customer_id_shipper' => $idShipper,
            'converted_at'        => $convAt,
            'conversion_source'   => $source,
            'status'              => 'Converted', // optional, kalau kamu pakai status ini
        ];

        if ($meta !== null) {
            $data['conversion_meta'] = json_encode($meta); // JSON column / LONGTEXT juga aman
        }

        $this->dbx->where('id_lead', $leadId)->update('crm_leads', $data);

        return [
            'status'=>true,
            'message'=>'Lead linked to customer',
            'lead_id'=>$leadId,
            'id_shipper'=>$idShipper
        ];
    }
}
