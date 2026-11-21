<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Import_rates extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Pengecekan Session Anda...
        if (empty($this->session->userdata('region_id'))) { 
            if ($this->input->is_ajax_request()) {
                header('HTTP/1.1 401 Unauthorized');
                echo json_encode(['status' => 'expired', 'message' => 'Session expired']);
                exit;
            } else {
                redirect('');
            }
        }
    }

    // ==========================================
    // MEMUAT HALAMAN & TEMPLATE
    // ==========================================
    public function index() {
        $data['title'] = "Import Rates Editor";

        // SESUAIKAN DENGAN FORMAT TEMPLATE APLIKASI ANDA:
        // Opsi A (Standar):
        // $this->load->view('layout/header', $data);
        // $this->load->view('layout/sidebar');
        $data['main_view'] = 'pricelist/import_rates_editor_view';
        $this->load->view('afg_template', $data);
        // $this->load->view('layout/footer');

        // Opsi B (Jika menggunakan library template khusus):
        // $this->template->load('template_utama', 'import_rates_editor_view', $data);
    }

    // ==========================================
    // DROPDOWN SELECT2 AJAX
    // ==========================================
    public function get_port_select2() {
        $q = $this->input->get('q'); 
        if (!empty($q)) {
            $this->db->group_start()->like('name', $q)->or_like('code', $q)->group_end();
        }
        $ports = $this->db->where('is_active', 'TRUE')->order_by('name', 'ASC')->limit(50)->get('tableName')->result();
        $result = [];
        foreach($ports as $p) { $result[] = ['id' => $p->code, 'text' => $p->name . ' (' . $p->code . ')']; }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function get_agent_select2() {
        $q = $this->input->get('q'); 
        if (!empty($q)) {
            $this->db->group_start()->like('agent_company_name', $q)->or_like('agent_company_code', $q)->group_end();
        }
        $agents = $this->db->order_by('agent_company_name', 'ASC')->limit(50)->get('refagentpenagihan')->result();
        $result = [];
        foreach($agents as $a) { $result[] = ['id' => $a->agent_company_code, 'text' => $a->agent_company_name . ' (' . $a->agent_company_code . ')']; }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function get_carrier_select2() {
        $q = $this->input->get('q'); 
        if (!empty($q)) {
            $this->db->group_start()->like('name', $q)->or_like('code', $q)->group_end();
        }
        // Mengasumsikan tabel SCAC bernama 'ref_scac' dengan kolom 'code' dan 'name'
        $carriers = $this->db->order_by('name', 'ASC')->limit(50)->get('ref_scac')->result();
        $result = [];
        foreach($carriers as $c) { $result[] = ['id' => $c->code, 'text' => $c->name . ' (' . $c->code . ')']; }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    public function get_agent_origin_select2() {
        $q = $this->input->get('q'); 
        if (!empty($q)) {
            $this->db->group_start()->like('origin_name', $q)->or_like('agent_name', $q)->group_end();
        }
        $agents = $this->db->where('is_active', 1)->order_by('origin_name', 'ASC')->limit(50)->get('import_destination_charge_agents')->result();
        $result = [];
        foreach($agents as $a) {
            $text = ($a->origin_name ?: 'UNKNOWN') . ' - ' . ($a->agent_name ?: 'UNKNOWN');
            $result[] = ['id' => $a->agent_id, 'text' => $text]; 
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    // ==========================================
    // DATA PROVIDER UNTUK jqGrid (GET JSON)
    // ==========================================
    public function get_fcl() {
        $this->db->select('f.*, CONCAT(f.pol_name, " (", f.pol_code, ")") as pol_display, CONCAT(f.agent_name, " (", f.agent_code, ")") as agent_display, f.carrier_scac as carrier_display');
        $this->db->from('import_ocean_freight_fcl_guidance f');
        $this->db->join('ref_scac s', 's.code = f.carrier_scac', 'left');
        $this->db->where('f.is_active', 1);
        $data = $this->db->get()->result();
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    public function get_lcl() {
        $this->db->select('l.*, CONCAT(l.pol_name, " (", l.pol_code, ")") as pol_display, CONCAT(l.agent_name, " (", l.agent_code, ")") as agent_display');
        $this->db->from('import_ocean_freight_lcl_guidance l');
        $this->db->where('l.is_active', 1);
        $data = $this->db->get()->result();
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    public function get_local_charge() {
        $this->db->select('i.charge_id as id, i.agent_id, a.origin_name, a.agent_name, i.item_name, i.coloader_currency, i.coloader_amount, i.direct_currency, i.direct_amount');
        $this->db->from('import_destination_charge_items i');
        $this->db->join('import_destination_charge_agents a', 'a.agent_id = i.agent_id', 'left');
        $data = $this->db->get()->result();
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    public function get_origin_charge() {
        $this->db->select('e.extra_item_id as id, e.agent_id, a.origin_name, a.agent_name, e.charge_group, e.item_name, e.currency, e.amount, e.raw_text');
        $this->db->from('import_destination_charge_extra_items e');
        $this->db->join('import_destination_charge_agents a', 'a.agent_id = e.agent_id', 'left');
        $data = $this->db->get()->result();
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    public function get_agent_rebate() {
        $this->db->select('agent_id as id, origin_name, agent_name, rebate_nominate_text, rebate_nominate_amount, rebate_freehand_text, rebate_freehand_amount');
        $this->db->from('import_destination_charge_agents');
        $this->db->where('is_active', 1);
        $data = $this->db->get()->result();
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    // ==========================================
    // FUNGSI BANTUAN EXTRACT CODE
    // ==========================================
    private function _extract_code_name($input, $type = 'port') {
        $code = $input; $name = '';
        if (preg_match('/^(.*?)\s*\((.*?)\)$/', trim($input), $matches)) {
            $name = trim($matches[1]); $code = trim($matches[2]);
        } else {
            if ($type == 'port') {
                $row = $this->db->select('name')->where('code', $code)->get('tableName')->row();
                $name = $row ? $row->name : '';
            } elseif ($type == 'carrier') {
                $row = $this->db->select('name')->where('code', $code)->get('ref_scac')->row();
                $name = $row ? $row->name : '';
            } else {
                $row = $this->db->select('agent_company_name')->where('agent_company_code', $code)->get('refagentpenagihan')->row();
                $name = $row ? $row->agent_company_name : '';
            }
        }
        return ['code' => $code, 'name' => $name];
    }

    // ==========================================
    // CRUD HANDLERS
    // ==========================================
    public function crud_fcl() {
        $oper = $this->input->post('oper'); $id = $this->input->post('id');
        $pol = $this->_extract_code_name($this->input->post('pol_display'), 'port');
        $agent = $this->_extract_code_name($this->input->post('agent_display'), 'agent');
        $carrier = $this->_extract_code_name($this->input->post('carrier_display'), 'carrier');
        
        $data = [
            'pol_name' => $pol['name'], 'pol_code' => $pol['code'],
            'agent_name' => $agent['name'], 'agent_code' => $agent['code'],
            'carrier' => $carrier['name'], 'carrier_scac' => $carrier['code'],
            'price_20' => $this->input->post('price_20'), 'price_40' => $this->input->post('price_40'), 'price_40hq' => $this->input->post('price_40hq'),
            'lss_20' => $this->input->post('lss_20'), 'lss_40' => $this->input->post('lss_40'), 'lss_40hq' => $this->input->post('lss_40hq'),
            'baf_20' => $this->input->post('baf_20'), 'baf_40' => $this->input->post('baf_40'), 'baf_40hq' => $this->input->post('baf_40hq'),
            'allin_20' => $this->input->post('allin_20'), 'allin_40' => $this->input->post('allin_40'), 'allin_40hq' => $this->input->post('allin_40hq'),
            'period_month' => $this->input->post('period_month')
        ];
        
        if ($oper == 'add') $this->db->insert('import_ocean_freight_fcl_guidance', $data);
        elseif ($oper == 'edit') $this->db->where('id', $id)->update('import_ocean_freight_fcl_guidance', $data);
        elseif ($oper == 'del') $this->db->where('id', $id)->update('import_ocean_freight_fcl_guidance', ['is_active' => 0]);
    }

    public function crud_lcl() {
        $oper = $this->input->post('oper'); $id = $this->input->post('id');
        $pol = $this->_extract_code_name($this->input->post('pol_display'), 'port');
        $agent = $this->_extract_code_name($this->input->post('agent_display'), 'agent');
        
        $data = [
            'pol_display_raw'=> $this->input->post('pol_display_raw'),
            'pol_name' => $pol['name'], 'pol_code' => $pol['code'],
            'agent_name' => $agent['name'], 'agent_code' => $agent['code'],
            'of_per_cbm' => $this->input->post('of_per_cbm'), 'lss_per_cbm' => $this->input->post('lss_per_cbm'),
            'gri_per_cbm' => $this->input->post('gri_per_cbm'), 'pss_per_cbm' => $this->input->post('pss_per_cbm'),
            'efs_per_cbm' => $this->input->post('efs_per_cbm'), 'fcs_per_cbm' => $this->input->post('fcs_per_cbm'),
            'ets_per_cbm' => $this->input->post('ets_per_cbm'), 'special_note' => $this->input->post('special_note'),
            'period_month' => $this->input->post('period_month')
        ];
        if ($oper == 'add') $this->db->insert('import_ocean_freight_lcl_guidance', $data);
        elseif ($oper == 'edit') $this->db->where('id', $id)->update('import_ocean_freight_lcl_guidance', $data);
        elseif ($oper == 'del') $this->db->where('id', $id)->update('import_ocean_freight_lcl_guidance', ['is_active' => 0]);
    }

    public function crud_local_charge() {
        $oper = $this->input->post('oper'); $id = $this->input->post('id');
        $data = [
            'agent_id' => $this->input->post('agent_id'), 'item_name' => $this->input->post('item_name'),
            'coloader_currency' => $this->input->post('coloader_currency'), 'coloader_amount' => $this->input->post('coloader_amount'),
            'direct_currency' => $this->input->post('direct_currency'), 'direct_amount' => $this->input->post('direct_amount')
        ];
        if ($oper == 'add') $this->db->insert('import_destination_charge_items', $data);
        elseif ($oper == 'edit') $this->db->where('charge_id', $id)->update('import_destination_charge_items', $data);
        elseif ($oper == 'del') $this->db->where('charge_id', $id)->delete('import_destination_charge_items');
    }

    public function crud_origin_charge() {
        $oper = $this->input->post('oper'); $id = $this->input->post('id');
        $data = [
            'agent_id' => $this->input->post('agent_id'), 'charge_group' => $this->input->post('charge_group'),
            'item_name' => $this->input->post('item_name'), 'currency' => $this->input->post('currency'),
            'amount' => $this->input->post('amount'), 'raw_text' => $this->input->post('raw_text')
        ];
        if ($oper == 'add') $this->db->insert('import_destination_charge_extra_items', $data);
        elseif ($oper == 'edit') $this->db->where('extra_item_id', $id)->update('import_destination_charge_extra_items', $data);
        elseif ($oper == 'del') $this->db->where('extra_item_id', $id)->delete('import_destination_charge_extra_items');
    }

    public function crud_agent_rebate() {
        $oper = $this->input->post('oper'); $id = $this->input->post('id');
        $data = [
            'origin_name' => $this->input->post('origin_name'), 'agent_name' => $this->input->post('agent_name'),
            'rebate_nominate_text' => $this->input->post('rebate_nominate_text'), 'rebate_nominate_amount' => $this->input->post('rebate_nominate_amount'),
            'rebate_freehand_text' => $this->input->post('rebate_freehand_text'), 'rebate_freehand_amount' => $this->input->post('rebate_freehand_amount')
        ];
        if ($oper == 'add') $this->db->insert('import_destination_charge_agents', $data);
        elseif ($oper == 'edit') $this->db->where('agent_id', $id)->update('import_destination_charge_agents', $data);
        elseif ($oper == 'del') $this->db->where('agent_id', $id)->update('import_destination_charge_agents', ['is_active' => 0]);
    }

    // ==========================================
    // EXPORT
    // ==========================================
    public function export_fcl() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Import_FCL_Rates_' . date('Ymd') . '.csv');
        $output = fopen('php://output', 'w');
        
        fputcsv($output, array('POL Name', 'POL Code', 'Carrier Name', 'Carrier SCAC', 'Agent Name', 'Agent Code', 'Price 20', 'Price 40', 'Price 40HQ', 'All In 20', 'All In 40', 'All In 40HQ', 'Period'));
        $data = $this->db->where('is_active', 1)->get('import_ocean_freight_fcl_guidance')->result_array();
        
        foreach ($data as $row) {
            fputcsv($output, array($row['pol_name'], $row['pol_code'], $row['carrier'], $row['carrier_scac'], $row['agent_name'], $row['agent_code'], $row['price_20'], $row['price_40'], $row['price_40hq'], $row['allin_20'], $row['allin_40'], $row['allin_40hq'], $row['period_month']));
        }
        fclose($output);
    }

    public function export_lcl() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Import_LCL_Rates_' . date('Ymd') . '.csv');
        $output = fopen('php://output', 'w');
        
        fputcsv($output, array('POL Name', 'Agent', 'O/F per CBM', 'O/F (< 1 CBM)', 'O/F (>= 2 CBM)', 'LSS per CBM', 'GRI per CBM', 'Period'));
        $data = $this->db->where('is_active', 1)->get('import_ocean_freight_lcl_guidance')->result_array(); //[cite: 13]
        
        foreach ($data as $row) {
            // Asumsi of_under_1_cbm dan of_over_2_cbm sudah ditambahkan ke database
            fputcsv($output, array($row['pol_name'], $row['agent_name'], $row['of_per_cbm'], $row['of_under_1_cbm'] ?? 0, $row['of_over_2_cbm'] ?? 0, $row['lss_per_cbm'], $row['gri_per_cbm'], $row['period_label']));
        }
        fclose($output);
    }

    public function export_local_charge() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Import_Local_Charges_' . date('Ymd') . '.csv');
        $output = fopen('php://output', 'w');
        
        fputcsv($output, array('Origin - Agent', 'Item Name', 'Coloader Currency', 'Coloader Amount', 'Direct Currency', 'Direct Amount'));
        
        $this->db->select('CONCAT(IFNULL(a.origin_name,""), " - ", IFNULL(a.agent_name,"")) as agent_origin_name, i.item_name, i.coloader_currency, i.coloader_amount, i.direct_currency, i.direct_amount');
        $this->db->from('import_destination_charge_items i');
        $this->db->join('import_destination_charge_agents a', 'a.agent_id = i.agent_id', 'left');
        $data = $this->db->get()->result_array();
        
        foreach ($data as $row) {
            fputcsv($output, array($row['agent_origin_name'], $row['item_name'], $row['coloader_currency'], $row['coloader_amount'], $row['direct_currency'], $row['direct_amount']));
        }
        fclose($output);
    }
}