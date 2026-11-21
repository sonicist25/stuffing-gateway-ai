<?php
class Export_feeder_rate_model extends CI_Model
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
	
	// Kolom yang boleh dipakai untuk pencarian jqGrid pada a_feeder_rate_detail.
	// Whitelist ini mencegah nama kolom hasil input user langsung masuk ke SQL.
	private $searchable_columns_feeder = array(
		'destination','country','container','freight','usd','idr','kurs','condition','remarks','valid'
	);

	// Terapkan filter pencarian jqGrid (_search/searchField/searchOper/searchString)
	// ke query builder secara aman. Dipakai oleh get_table_data() & get_table_airline().
	private function apply_grid_search($data, $allowed_columns)
	{
		$ops = array(
			'eq'=>'=', 'ne'=>'<>', 'bw'=>'LIKE', 'lt'=>'<', 'le'=>'<=', 'gt'=>'>', 'ge'=>'>=',
			'bn'=>'NOT LIKE', 'in'=>'LIKE', 'ni'=>'NOT LIKE', 'ew'=>'LIKE', 'en'=>'NOT LIKE',
			'cn'=>'LIKE', 'nc'=>'NOT LIKE'
		);

		$searchField  = isset($data['searchField']) ? $data['searchField'] : '';
		$searchOper   = isset($data['searchOper']) ? $data['searchOper'] : '';
		$searchString = isset($data['searchString']) ? $data['searchString'] : '';

		// Tolak kolom & operator yang tidak dikenal (whitelist), bukan pakai apa adanya.
		if (!in_array($searchField, $allowed_columns, true) || !isset($ops[$searchOper])) {
			return;
		}

		$sqlOp = $ops[$searchOper];

		if (in_array($searchOper, array('bw','bn'), true)) {
			$searchString .= '%';
		} elseif (in_array($searchOper, array('ew','en'), true)) {
			$searchString = '%'.$searchString;
		} elseif (in_array($searchOper, array('cn','nc','in','ni'), true)) {
			$searchString = '%'.$searchString.'%';
		}

		// $searchField sudah divalidasi lewat whitelist, $searchString di-escape oleh CI.
		$this->dbx->where("$searchField $sqlOp", $searchString);
	}

	function get_table_data($data,$limit)
	{
		if(isset($data['_search']) && $data['_search']=='true') {
			$this->apply_grid_search($data, $this->searchable_columns_feeder);
		}

		$this->dbx->where('valid >=', date('Y-m-d'));

		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$q = $this->dbx->get("a_feeder_rate_detail");
		return $q->result();
	}

	// Hitung total baris tanpa mengambil semua data (sebelumnya controller
	// memanggil get_table_data() dua kali penuh hanya untuk menghitung jumlah baris).
	function count_table_data($data)
	{
		if(isset($data['_search']) && $data['_search']=='true') {
			$this->apply_grid_search($data, $this->searchable_columns_feeder);
		}
		$this->dbx->where('valid >=', date('Y-m-d'));
		return $this->dbx->count_all_results('a_feeder_rate_detail');
	}
	
	// Kolom yang boleh dipakai untuk pencarian jqGrid pada airline_cost.
	private $searchable_columns_airline = array(
		'destination','carier','scac','valid','remarks'
	);

	function get_table_airline($data,$limit)
	{
		if(isset($data['_search']) && $data['_search']=='true') {
			$this->apply_grid_search($data, $this->searchable_columns_airline);
		}

		$this->dbx->where('valid >=', date('Y-m-d'));

		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$this->dbx->order_by($data['sidx'],$data['sord']);
		$q = $this->dbx->get("airline_cost");
		return $q->result();
	}
	
	function pilih_id($data)
	{
		$select = "select_".$data[0];
		$this->dbx->$select('id');
		$q = $this->dbx->get_where("a_feeder_rate",array('shipping_co'=>$data[1]));
		return $q->row()->id;
	}
	
	
	function manage_detail($data)
	{
		$oper = $data['oper'];
		$id=$data['id'];
		unset($data['oper']);
		unset($data['id']);
		
		if($oper!='del' && isset($data['remarks']))
		{
			$data['remarks']= strtoupper($data['remarks']);
		}
		
		if($oper=='add') 
		{
			$this->dbx->insert('a_feeder_rate_detail',$data);	
		}
		elseif($oper=='edit') 
		{
			$this->dbx->update('a_feeder_rate_detail',$data,array('id'=>$id));	
		}
		else 
		{
			$this->dbx->delete('a_feeder_rate_detail',array('id'=>$id));
		}
		
		// NOTE: key sebelumnya bertuliskan 'satus' (typo), diperbaiki jadi 'status'.
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	function manage_airline_detail($data)
	{
		$oper = $data['oper'];
		$id=$data['id'];
		unset($data['oper']);
		unset($data['id']);
		
		if($oper!='del' && isset($data['remarks']))
		{
			$data['remarks']= strtoupper($data['remarks']);
		}
		
		if($oper=='add') 
		{
			$this->dbx->insert('airline_cost',$data);	
		}
		elseif($oper=='edit') 
		{
			$this->dbx->update('airline_cost',$data,array('id'=>$id));	
		}
		else 
		{
			$this->dbx->delete('airline_cost',array('id'=>$id));
		}
		
		// NOTE: key sebelumnya bertuliskan 'satus' (typo), diperbaiki jadi 'status'.
		return array('status'=>$this->dbx->affected_rows(),'oper'=>$oper);
	}
	
	function last($data)
	{
		$this->dbx->order_by('id','desc');
		$this->dbx->limit(1);
		$this->dbx->where('shipping_co',$data['shipping_co']);
		$q = $this->dbx->get('a_feeder_rate');
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function pilih($data)
	{
		$q = $this->dbx->get_where('a_feeder_rate',array('id'=>$data['id'],'shipping_co'=>$data['shipping_co']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function next($data)
	{
		$this->dbx->limit(1);
		$this->dbx->order_by('id','asc');
		$q = $this->dbx->get_where('a_feeder_rate',array('id >'=>$data['id'],'shipping_co'=>$data['shipping_co']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}
	
	function prev($data)
	{
		$this->dbx->limit(1);
		$this->dbx->order_by('id','desc');
		$q = $this->dbx->get_where('a_feeder_rate',array('id <'=>$data['id'],'shipping_co'=>$data['shipping_co']));
		if($q->num_rows()>0) 
		{
			return $q->row();
		} 
		else 
		{
			return false; 
		}
	}

}


