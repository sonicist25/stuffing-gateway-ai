<?php
class C_post_data_acct_per_job extends CI_Controller
{
	var $title ='Post Data Acct';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('finance/M_post_data_acct','afg_m_job_sheet','report/m_rekap_sales','reference/type_service_model','afg_m_stuffing_plan','afg_m_job_sheet'));
		$this->load->library('Fungsi');

        // Inject koneksi database aktif ke library Fungsi
        $this->fungsi->dbx = $this->afg_m_stuffing_plan->dbx;
        $this->dbx = $this->afg_m_stuffing_plan->dbx;
	}

	function index(){
		$this->fungsi->auth(50,'report Job Sheet');
		$data['title'] = $this->title;
		$data['table_title'] ='INVOICE';
		$data['menu_navigation'] ='navigation';
		$data['main_view'] ='finance/template-finance';
		$this->load->view('afg_template', $data);
	}
	
	
	function open_form() {
		$kode_ref = $this->M_post_data_acct->get_kode_ref_detail($_POST)->result();
		$option ['']='';
		if($kode_ref) {
			foreach($kode_ref as $row ){
				$option[$row->job_no]=$row->job_no;
			}
		}
		$data['option']=$option;		
 		$this->load->view('finance/v_post_data_acct_job');
	}
	
	function get_kode_ref() {	
 		$kode_ref = $this->M_post_data_acct->get_kode_ref_detail($_POST)->result();
		$option ['']='';
		if($kode_ref) {
			foreach($kode_ref as $row ){
				$option[$row->job_no]=$row->job_no;
			}
		}
		$dropdown = form_dropdown('dropdown_kode_ref',$option,'','id="dropdown_kode_ref" class="select2" style="min-width: 200px"');
 		echo json_encode(array('dropdown'=>$dropdown));
	}

	private function sumByCurrency($rows, $currencyField = 'currency', $priceField = 'unit_price', $qtyField = 'qty') {
    $usd = [];
    $idr = [];
    foreach ($rows as $row) {
        $price = floatval($row->$priceField);
        $qty   = floatval($row->$qtyField);
        if ($row->$currencyField === 'USD') {
            $usd[] = $price * $qty;
        } else {
            $idr[] = $price * $qty;
        }
    }
    return [array_sum($usd), array_sum($idr)];
}

	public function proses_post()
	{
		$data = [
			'kode_ref'     => $_POST['kode_ref'],
			'sp_detail_id' => $_POST['sp_detail_id'],
			'kurs'         => floatval($_POST['kurs']) // langsung casting
		];

		$dbx = $this->afg_m_job_sheet->dbx;
		$post_ok = body_jasaweb_jobsheet($data, $dbx);
		if ($post_ok === false) {
			return;
		}

		$data_jobsheet = $this->afg_m_job_sheet
			->get_sp_spd_by_post_jobsheet($data['kode_ref'], $data['sp_detail_id']);

		// Income
		list($incusd, $incrp) = $this->sumByCurrency(
			$this->afg_m_job_sheet->get_income_job_sheet($data['kode_ref'])
		);

		// Global Income
		$giusd = $girp = [];
		foreach ($this->afg_m_job_sheet->get_income_global(substr($data['kode_ref'],0,15)) as $row) {
			$qty = $this->afg_m_stuffing_plan->get_qty_gbl_cost(substr($data['kode_ref'],0,15),'USD');
			$htg = $this->fungsi->pecah4($qty[0],',') / $this->fungsi->pecah4($qty[1],',');
			$ttl = floatval($row->unit_price) * floatval($row->qty) * $htg;

			($row->currency === 'USD') ? $giusd[] = $ttl : $girp[] = $ttl;
		}

		$ttl_i_usd = $incusd + array_sum($giusd);
		$ttl_i_rp  = $incrp + array_sum($girp);
		$total_global_income = $data['kurs'] * $ttl_i_usd + $ttl_i_rp;

		// Costs
		list($cusd0, $crp0) = $this->sumByCurrency(
			$this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_tbl_satu',$data['kode_ref'])
		);
		list($cusd1, $crp1) = $this->sumByCurrency(
			$this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_refund_discount',$data['kode_ref'])
		);
		list($cusd2, $crp2) = $this->sumByCurrency(
			$this->afg_m_job_sheet->get_cost_job_sheet('inf_cost_refund_sc',$data['kode_ref'])
		);

		// Global Cost
		$cusd3 = $crp3 = [];
		foreach ($this->afg_m_job_sheet->get_cost_job_sheet('x_glbcost',substr($data['kode_ref'],0,15)) as $row) {
			$qty = $this->afg_m_stuffing_plan->get_qty_gbl_cost(substr($data['kode_ref'],0,15),'USD');
			$htg = $this->fungsi->pecah4($qty[0],',') / $this->fungsi->pecah4($qty[1],',');
			$ttl = floatval($row->price) * $htg;

			($row->currency === 'USD') ? $cusd3[] = $ttl : $crp3[] = $ttl;
		}

		$ttl_cost = $this->afg_m_stuffing_plan->get_total_cost($data['kode_ref']);
		$total_global_cost = $data['kurs'] * floatval($ttl_cost[0]) + floatval($ttl_cost[1]);
		$ttl_dn = $this->afg_m_stuffing_plan->ttl_debit_note($data['sp_detail_id']);

		$this->m_rekap_sales->save_profit([
			"kode_ref"       => $data['kode_ref'],
			"profit"         => $total_global_income - $total_global_cost + ($ttl_dn * $data['kurs']),
			"income"         => $total_global_income + ($ttl_dn * $data['kurs']),
			"cost"           => $total_global_cost,
			"profit_usd"     => $ttl_i_usd - $ttl_cost[0] + $ttl_dn,
			"profit_rp"      => $ttl_i_rp - $ttl_cost[1],
			"income_usd"     => $ttl_i_usd,
			"income_rp"      => $ttl_i_rp,
			"cost_usd"       => $ttl_cost[0],
			"cost_rp"        => $ttl_cost[1],
			"global_cost"    => array_sum($crp3),
			"global_cost_usd"=> array_sum($cusd3),
			"inv_agent"      => $ttl_dn,
			"cost_coloader"  => "",
			"region_id"      => $this->session->userdata('region_id'),
			"shipper_name"   => $data_jobsheet['shipper_print'],
			"sales"          => $this->fungsi->get_user_by_id($data_jobsheet['sales']),
			"destination"    => $data_jobsheet['destination'],
			"volume"         => $data_jobsheet['bl_meas'],
			"weight"         => $data_jobsheet['weight'],
			"sales_id"         => $data_jobsheet['sales'],
		]);
	}
	function proses_post_global_cost()
	{
		$data['kode_ref'] = $_POST['kode_ref'];
		$dbx = $this->afg_m_job_sheet->dbx;
		body_jasaweb_global_cost($data,$dbx);
	}
	function proses_post_global_income()
	{
		$data['kode_ref'] = $_POST['kode_ref'];
		$dbx = $this->afg_m_job_sheet->dbx;
		body_jasaweb_global_income($data,$dbx);
		
	}
}

/*
function too_excel(){	
		$this->load->library(array('Spreadsheet_Excel_Reader','fungsi'));
		$excel = new Spreadsheet_Excel_Reader('tes.xls');
		echo $excel->dump(true,true);
}
*/

