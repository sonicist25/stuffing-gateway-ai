<?php
class M_official_receipt extends CI_Model
{	
  public $dbx;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }
   
   function get_data_OF($data) {
   	if($data['receives']=='SHIPPER') {
   		return $this->dbx->get_where('mstshipper',array('shipper_id'=>$data['code']))->row()->shipper_name;
   	}
   }

	function get_table_satu($post,$limit){
		if($post['_search']=='true') {
			$searchField = $post['searchField'];
			$searchString = $post['searchString'];
      	$searchOper  =  $post['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'ne'=>'<>',//not equal
            	'bw'=>'LIKE', //begins with
            	'lt'=>'<', //less than
            	'le'=>'<=',//less than or equal
            	'gt'=>'>', //greater than
            	'ge'=>'>=',//greater than or equal 
            	'bn'=>'NOT LIKE', //doesn't begin with
            	'in'=>'LIKE', //is in
            	'ni'=>'NOT LIKE', //is not in
            	'ew'=>'LIKE', //ends with
            	'en'=>'NOT LIKE', //doesn't end with
            	'cn'=>'LIKE', // contains
            	'nc'=>'NOT LIKE'  //doesn't contain
        	);

      	foreach ($ops as $key=>$value){
 				if($searchOper==$key) {
            	$ops = $value;
          	}
     		} 
            
       	if($searchOper == 'eq' ) $searchString = $searchString;
       	if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
       	if($searchOper == 'ew' || $searchOper == 'en' ) $searchString = '%'.$searchString;
      	if($searchOper == 'cn' || $searchOper == 'nc' || $searchOper == 'in' || $searchOper == 'ni') $searchString = '%'.$searchString.'%';
				
      	$where = "$searchField $ops '$searchString' ";                     
			$this->dbx->where($where); 				            
		}
		
		$this->dbx->order_by($post['sidx'], $post['sord']);
		if($limit==true) {
			$this->dbx->limit($post['rows'],$post['start']);	
		}
		
		return $this->dbx->get_where('x_official_receipt',array(
		//	'id !='=>'0',
			'region_id'=>$this->session->userdata('region_id'),
		))->result();
	}
	
	function get_content($post){
		if($post['mode']=='pilih') {
			$data = $this->dbx->get_where('x_official_receipt',array(
				'id'=>$post['id'],
				'region_id'=>$this->session->userdata('region_id'),
			))->row();
			if($data) {
				$data->kurs = $this->fungsi->pecah($data->kurs, ' ');
				$data->date;
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->job_no = '';
				return $data;
			}
		}
		elseif($post['mode']=='prev') {
			$this->dbx->order_by('id','desc');
			$data = $this->dbx->get_where('x_official_receipt',array(
				'id <'=>$post['id'],
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->kurs = $this->fungsi->pecah($data->kurs, ' ');
				$data->date;
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->job_no = '';
				return $data;
			}
		}
		elseif($post['mode']=='next') {
			$this->dbx->order_by('id','asc');
			$data = $this->dbx->get_where('x_official_receipt',array(
				'id >'=>$post['id'],
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->kurs = $this->fungsi->pecah($data->kurs, ' ');
				$data->date;
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->job_no = '';
				return $data;
			}
		}
		elseif($post['mode']=='last') {
			$this->dbx->order_by('id','desc');
			$data = $this->dbx->get_where('x_official_receipt',array(
				'region_id'=>$this->session->userdata('region_id'),
			),1,false)->row();
			if($data) {
				$data->kurs = $this->fungsi->pecah($data->kurs, ' ');
				$data->date;
				$data->id_last = $this->get_id('max');
				$data->id_first = $this->get_id('min');
				return $data;
			} else {
				$data = new stdClass();
				$data->job_no = '';
				return $data;
			}
		}
	}
	
	function get_id($mode) {
		$select = 'select_'.$mode;
		$this->dbx->$select('id');
		$query = $this->dbx->get_where('x_official_receipt',array(
			'region_id'=>$this->session->userdata('region_id'),
		))->row();
		return $query->id;
	}
	
	function manage_data($data){
		$region = $this->session->userdata('region_id');
		if($data['oper']=='add') {
			unset($data['oper']);
			unset($data['id']);
			$this->dbx->insert('x_official_receipt',$data);
		} elseif($data['oper']=='edit'){
			unset($data['oper']);
			$this->dbx->where('id',$data['id']);
			$this->dbx->where('region_id',$region);
			$this->dbx->update('x_official_receipt',$data);
		} elseif($data['oper']=='del') {
			$this->dbx->where('id',$data['id']);
			$this->dbx->where('region_id',$region);
			$this->dbx->delete('x_official_receipt');
		}
		if($this->dbx->affected_rows() > 0) {
			return 1;
		} else {
			return 0;
		}
	}


}
?>