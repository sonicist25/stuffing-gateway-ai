<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_import extends CI_Model {

    /**
     * MENGAMBIL LIST UTAMA FCL & LCL UNTUK HALAMAN PRICING IMPORT (REACT)
     */
    public function get_grouped_rates() {
        
        // ==========================================
        // 1. GROUPING FCL RATES
        // ==========================================
        $this->db->where('is_active', 1);
        $this->db->order_by('pol_name', 'ASC');
        $fcl_raw = $this->db->get('import_ocean_freight_fcl_guidance')->result();
        
        $grouped_fcl = [];
        foreach ($fcl_raw as $row) {
            // Gunakan nama pelabuhan sebagai rute utama
            $route = trim($row->pol_name) . ' (' . trim($row->pol_code) . ') ➝ JAKARTA (IDJKT)';
            
            $grouped_fcl[$route][] = [
                'id'              => $row->id,
                'rate_type'       => 'fcl',
                'liner_name'      => $row->carrier ?: 'TBA',
                'carrier_scac'    => $row->carrier_scac,
                'agent_name'      => $row->agent_name,
                'agent_code'      => $row->agent_code,
                'cargo_type'      => 'GENCO', // Default standard
                'validity_to'     => $row->period_month, // Kolom tgl expired terbaru
                'prices'          => [
                    '20'   => $row->price_20,
                    '40'   => $row->price_40,
                    '40HC' => $row->price_40hq
                ],
                'allin'           => [
                    '20'   => $row->allin_20,
                    '40'   => $row->allin_40,
                    '40HC' => $row->allin_40hq
                ],
                'surcharges'      => [
                    'LSS 20' => $row->lss_20, 'LSS 40' => $row->lss_40, 'LSS 40HC' => $row->lss_40hq,
                    'BAF 20' => $row->baf_20, 'BAF 40' => $row->baf_40, 'BAF 40HC' => $row->baf_40hq,
                ]
            ];
        }

        // ==========================================
        // 2. GROUPING LCL RATES
        // ==========================================
        $this->db->where('is_active', 1);
        $this->db->order_by('pol_name', 'ASC');
        $lcl_raw = $this->db->get('import_ocean_freight_lcl_guidance')->result();
        
        $grouped_lcl = [];
        foreach ($lcl_raw as $row) {
            $route = trim($row->pol_name) . ' (' . trim($row->pol_code) . ') ➝ JAKARTA (IDJKT)';

            $grouped_lcl[$route][] = [
                'id'                => $row->id,
                'rate_type'         => 'lcl',
                'liner_name'        => $row->agent_name ?: $row->pol_display_raw,
                'agent_name'        => $row->agent_name,
                'agent_code'        => $row->agent_code,
                'cargo_type'        => 'LCL',
                'validity_to'       => $row->period_month,
                'min_lcl_price'     => $row->of_per_cbm,
                'surcharges'        => [
                    'LSS' => $row->lss_per_cbm,
                    'GRI' => $row->gri_per_cbm,
                    'PSS' => $row->pss_per_cbm,
                    'EFS' => $row->efs_per_cbm,
                    'FCS' => $row->fcs_per_cbm,
                    'ETS' => $row->ets_per_cbm
                ],
                'remarks'           => $row->special_note,
                'pol_display_raw'   => $row->pol_display_raw
            ];
        }

        return [
            'fcl' => $grouped_fcl,
            'lcl' => $grouped_lcl
        ];
    }

    /**
     * MENGAMBIL DETAIL MODAL (DESTINATION CHARGES + ORIGIN CHARGES + REBATES)
     */
    public function get_rate_details($type, $rate_id) {
        $header = null;
        $origin_name = '';
        $agent_name = '';

        if ($type === 'fcl') {
            $header = $this->db->get_where('import_ocean_freight_fcl_guidance', ['id' => $rate_id])->row();
            if($header) {
                $origin_name = $header->pol_name;
                $agent_name = $header->agent_name;
            }
        } else {
            $header = $this->db->get_where('import_ocean_freight_lcl_guidance', ['id' => $rate_id])->row();
            if($header) {
                $origin_name = $header->pol_name;
                $agent_name = $header->agent_name;
            }
        }

        if (!$header) return null;

        // Cari Agent ID di master Local Charges menggunakan relasi Origin dan Agent
        $this->db->group_start();
        $this->db->like('origin_name', $origin_name);
        $this->db->or_like('agent_name', $agent_name);
        $this->db->group_end();
        $this->db->where('is_active', 1);
        $agent_link = $this->db->get('import_destination_charge_agents')->row();

        $local_charges = [];
        $origin_charges = [];

        if ($agent_link) {
            // Ambil Destination Local Charges (Tabel Items)
            $items = $this->db->order_by('sort_order', 'ASC')
                              ->get_where('import_destination_charge_items', ['agent_id' => $agent_link->agent_id])
                              ->result();
            
            foreach ($items as $item) {
                $local_charges[] = [
                    'component_name' => $item->item_name,
                    'unit_basis'     => $item->direct_unit_raw ?: '-',
                    'currency'       => $item->direct_currency,
                    'price_20'       => $item->direct_amount,
                    'price_wm'       => $item->direct_amount,
                    'raw_text'       => $item->direct_raw_text
                ];
            }

            // Ambil Origin Extra Charges (Jika Ada)
            $extras = $this->db->order_by('sort_order', 'ASC')
                               ->get_where('import_destination_charge_extra_items', ['agent_id' => $agent_link->agent_id])
                               ->result();
                               
            foreach ($extras as $ext) {
                $origin_charges[$ext->charge_group][] = [
                    'component_name' => $ext->item_name ?: $ext->remarks,
                    'unit_basis'     => $ext->unit_raw ?: '-',
                    'currency'       => $ext->currency,
                    'price_20'       => $ext->amount,
                    'price_wm'       => $ext->amount,
                    'raw_text'       => $ext->raw_text ?: $ext->remarks
                ];
            }
        }

        return [
            'header'         => $header,
            'local_charges'  => $local_charges,
            'origin_charges' => $origin_charges,
            'rebate_info'    => $agent_link // Sisipkan info rebate
        ];
    }
}