<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Application extends CI_Controller {	
	
	public function __construct() {
		parent::__construct();
		$this->load->model('model');
		$this->fungsi->get_define();
		$this->fungsi->restrict();
		$this->fungsi->dbx = $this->model->dbx;
		$this->dbx = $this->model->dbx;
	}
	
public function get_data()
{
    $form = $this->input->post('form', true);
    if ($form === null || $form === '') {
        $form = $this->input->get('form', true);
    }

    // wajib: kalau form kosong, balas JSON error
    if (!$form) {
        return $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(['status' => 0, 'message' => 'form is required']));
    }

    // === DB CHANGE: handle khusus, jangan lanjut ke bawah ===
    if ($form === 'DB-CHANGE') {
        $db = $this->input->post('db', true);
        if ($db === null || $db === '') $db = $this->input->get('db', true);

        // set session tahun/db
        $this->session->set_userdata('db', $db);

        // PENTING: pastikan session benar2 tersimpan sebelum response
        session_write_close();

        return $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(['status' => 1, 'db' => $db]));
    }

    // === selain DB-CHANGE, lanjut seperti biasa ===
    $value = '';

    if ($form === 'INCOME') {
        $id_sd = $this->input->get('id_sd', true);
        $id_sp = $this->input->get('id_sp', true);

        $lock = $this->model->get_where([
            'table' => TRN_INC_HDR,
            'fld'   => '*',
            'cond'  => ['id_sd' => $id_sd]
        ]);

        if ($lock->num_rows() == 0) {
            $lock = $this->model->insert_data([
                'table' => TRN_INC_HDR,
                'data'  => ['id_sd' => $id_sd, 'id_sp' => $id_sp],
                'get'   => ['id_sd' => $id_sd, 'id_sp' => $id_sp]
            ]);
        }

        $value = [
            'intensiv_tidak_diproses' => $lock->row()->intensiv_tidak_diproses,
            'tarik_insentiv_per_doc'  => $lock->row()->tarik_insentiv_per_doc
        ];

        return $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($value));
    }

    // contoh branch lain: pastikan outputnya juga konsisten (json_encode kalau array/object)
    if ($form === 'BILL OF LADING') {
        $value = $this->model->get_bill($this->input->post(NULL, true) ?: $this->input->get(NULL, true));
        return $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($value));
    }
		
		if($_GET['form']=='HEADER') :
			$value = $this->get_data_header($_GET);
		endif;
		
		if($_GET['form']=='TABLE') :
   		$value = $this->get_data_table($_GET);
		endif;
		
		if($_GET['form']=='REGION') :
   		$value = $this->select_region($_GET);
		endif;
		
		if($_GET['form']=='DB-CHANGE') :
   		$value = $this->select_db($_GET);
		endif;
		
		if($_GET['form']=='SPNUM') :
   		$value = $this->spnum($_GET);
		endif;
		
		if($_GET['form']=='LIST') :
   		$value = $this->get_list($_GET);
		endif;
		
		if($_GET['form']=='TABLE FINANCE') :
   		$value = $this->get_data_table_finance($_GET);
		endif;
}

	
	private function get_data_table_finance($data) {
		$hal = isset($data['page'])?$data['page']:1; 
		$batas = isset($data['rows'])?$data['rows']:50;
		$sidx = isset($data['sidx'])?$data['sidx']:'';	 	
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$data['start']=$start;		
		$q = $this->model->get_data_table_finance($data,$limit=false);
		$query = $this->model->get_data_table_finance($data,$limit=true); 		
		$hitung = count($q); 		
		if( $hitung > 0 ) : $total_hal = ceil($hitung/$batas);
		else : $total_hal = 0;
		endif;	 
		if ($hal > $total_hal) $hal=$total_hal;	 
		$value = new stdClass();	 
		$value->page = $hal;
		$value->total = $total_hal;
		$value->records = $hitung;
		$i=0;
		foreach($query as $row) {
			$job=$this->model->get_job_type($row->kode_ref);
			$cst=$this->model->get_customer($job,$row->shipper,$row->consignee);
			$value->rows[$i]['id']=$row->id_sd;
		 	$value->rows[$i]['cell']=array(
		 			$this->model->get_blue(FNC_HDR,array("job_no"=>$row->kode_ref.$row->num,"region_id"=>REGID,"jenis"=>"INVOICE")),
		 			$row->kode_ref.$row->num,									
		    		$this->fungsi->jin_date_str($row->stuffing_date),
		    		$row->shipper_print,
					$row->destination,
					$row->carrier,
					$row->vessel.' '.$row->voyage,
					$row->id_sp,									
					$row->kurs,									
					$row->sales,									
					$cst['id'],
					$cst['code'],
					$cst['address'],
					'',																																			
					$row->no_pack_qty.' '.strtoupper($row->no_pack_unit),
					$row->weight,
					$row->bl_meas,						
					$row->etd_jkt,						
					$row->port_loading,						
					$row->transhipment_port,						
					$row->hb_l,						
					$row->mother_bl,
					$job						
			);
		   $i++;
		}
		return json_encode($value);
	}
	
	private function get_list($data){
		$query= new stdClass();
		if($_GET['name']=='marketing') :
			$query=$this->model->get_data_sales()->result();
		elseif($_GET['name']=='prepared') :
			$query=$this->model->get_data_prepared()->result();
		elseif($_GET['name']=='level') :
			$level = $this->fungsi->get_data_level();
			$object = (object)$level;
			$query = $this->model->get_data_sales()->result();	
		endif;
		
		//print_r($query);
		exit();
		
		$key=$data['key'];
		$val=$data['val'];
		$opt['option']['']='';
		foreach ($query as $row) {	
			$opt['option'][$row->$key]=$row->$val;
		}
		if($data['opt']=='false') :
			return form_dropdown($data['name'], $opt['option'], '', 'id="'.$data['name'].'"');
		else :
			return json_encode(form_dropdown($data['name'], $opt['option'], '', 'id="'.$data['name'].'"'));
		endif;
	}
	
	private function spnum ($data) {
		$value = $this->model->spnum($data);
		return json_encode($value);
	}
	
	private function get_data_header ($data) {
		$value = $this->model->get_data_header($data);
		return json_encode($value);
	}
	
	private function get_data_table($data) {
		$hal = isset($data['page'])?$data['page']:''; 
		$batas = isset($data['rows'])?$data['rows']:'';
		$sidx = isset($data['sidx'])?$data['sidx']:'';
		$sord = isset($data['sord'])?$data['sord']:'';
		$search = isset($data['_search'])?$data['_search']:'false';
		
	 	$start = $batas*$hal - $batas;
		$start = ($start<0)?0:$start;
		$data['start']=$start;
				
		if($data['table']=='TRN_STF_PLN_DET') : $data['sidx']='num'; $data['sord']='asc'; endif;		
		$q = $this->model->get_data_table($data,$limit=false);
		$query = $this->model->get_data_table($data,$limit=true);
		$hitung = count($q);	 
		if( $hitung > 0 ) : $total_hal = ceil($hitung/$batas);
		else : $total_hal = 0;
		endif;	 
		if ($hal > $total_hal) $hal=$total_hal;	 
		$value = new stdClass();	 
		$value->page = $hal;
		$value->total = $total_hal;
		$value->records = $hitung;	
		
		////print_r(array($data,$q,$query));
		//exit();
		
		$m=array();$n=array();$h=array();$cell=array();		
		if($hitung>=1) :
			foreach($query as $row => $key){ $m[]=$key; }
			foreach($m[0] as $r => $k) {$n[]=$r;}
			$id=$n[0];
			if($data['table']!='TRN_STF_PLN') :
				unset($n[0]);
			endif;
			foreach($n as $r => $k) { $h[]=$k; }
			$i=0;
			foreach($query as $row) {
				for($z=0;$z<=count($h)-1;$z++) {
					$x=$h[$z];
					$cell[$z]=$row->$x;
					if($cell[$z]=='null') $cell[$z]='';
				}
				$value->rows[$i]['id']=$row->$id;		
		   	$value->rows[$i]['cell']= $cell;
				$i++;
			}
		endif;
		return json_encode($value);
	}
	
	private function select_region($data) {
		$this->session->unset_userdata('region_id');
		$this->session->set_userdata('region_id',$data['region_id']);
		return json_encode(array('region_id'=>$data['region_id']));
	}
	
	private function select_db($data) {
		$thn = isset($_GET['db']) ? $_GET['db'] : date('Y');
		$this->session->set_userdata('db', $thn); // tidak perlu unset dahulu
		$this->session->sess_regenerate(TRUE);
		$year = $this->fungsi->option_year($thn);
		echo json_encode([
			'db' => $thn,
			'status' => 1,
			'opt' => $year['opt'],
			'db_ext' => $this->dbx->database
		]);
	}

	
	function manage_data () {
		if(!isset($_POST['table'])||!isset($_POST['oper'])) exit();
		$value = $this->model->manage_data($_POST);
		echo json_encode($value);
	}
	
	function actifity() {
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'mst_log_activity';
		$data['title'] = 'log activity user';
		$data['user'] = $this->fungsi->get_data_object(REF_USR,array('user_id','fullname'),TRUE);
		$this->load->view('afg_template',$data);
	}

}