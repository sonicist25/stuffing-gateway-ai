<?php
class Afg_c_table_stuffing extends CI_Controller
{
	var $title = 'Schedules';
	public function __construct()
	{
		parent::__construct();
		$this->fungsi->restrict();
		$this->load->model(array('afg_m_table_stuffing'));
	}
	
	function index()
	{
		//$this->fungsi->auth(60,'DO Import');
		$data = array('title'=>'Table Stuffing',
							'menu_navigation'=>'navigation',
							'main_view'=>'table_stuffing/afg_v_table_index',
						);				
		$this->load->view('afg_template', $data);
	}
	
	function get_table()
	{
		$d = str_replace("t=","",$_SERVER['QUERY_STRING']);
		$date = date_create($d);
		date_modify($date, '-1 week');
		$post=array('n'=>$d,'w'=>date_format($date, 'Ymd'));
		$value=$this->afg_m_table_stuffing->get_data($post);
		$data = array('title'=>'Table Stuffing',
							'menu_navigation'=>'navigation',
							'main_view'=>'table_stuffing/afg_v_table_index',
							'value'=>$value,
						);				
		$this->load->view('afg_template', $data);
	}
	
	function get_data() {
		$d = $this->fungsi->jin_date_sql($_POST['date']);
		$date = date_create($d);
		date_modify($date, '-1 week');
		$data=array('n'=>$d,'w'=>date_format($date, 'Ymd'));
		
		$w=$this->fungsi->jin_date_str($data["w"]);
		$n=$this->fungsi->jin_date_str($data["n"]);
		
		$value=$this->afg_m_table_stuffing->get_data_test($data);
		
		
		if($value->num_rows()>0) {
			foreach($value->result() as $row)
			{
				$q[]=array(
					$row->destination,
					$row->agen_penagihan,
				);

			}
			
			$q = array_map("unserialize", array_unique(array_map("serialize", $q)));
			$q = array_values($q);
			
			foreach($q as $row)
			{
				$v[]=array(
					'destination'=>$row[0],
					'agent'=>$row[1],
					'detail'=>$this->afg_m_table_stuffing->get_data_detail_by_sp_id($row[0],$row[1],$data),
				);
			}

			

			echo json_encode(array('interval'=>'Data Stuffing From '.$w.' to '.$n,'value'=>$v));
			
		} else {
			
			echo json_encode(array('interval'=>'Data Stuffing From '.$w.' to '.$n, 'value'=>""));
			
		}
		
		}
	
}