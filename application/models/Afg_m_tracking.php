<?php
class Afg_m_tracking extends CI_Model
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
   function get_tracked($code){
	    
		$this->dbx->where('kode_ref',$code);
		$data =$this->dbx->get('trnstuffingplan')->row('id_gocomet');
		if($data) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	  function get_connected($code){
		$this->dbx->where('kode_ref',$code);
		$query =$this->dbx->get('trnstuffingplan');
		$via = $query->row('transhipment_port');
		$pod = $query->row('destination');
		if($query) {
			if ($via == $pod)
			{
			return 1;
			}else {
			return 0;	
			}
		} else {
			return 0;	
		}
	}

   function find($code,$job_no)
   {
		$region = $this->session->userdata('region_id');
		$data = Array();
		$filter = array('no_job'=>$code, 'region_id' => $region);
		$queryexp =$this->dbx->get_where('x_tracking_export',$filter);
		$queryimp =$this->dbx->get_where('x_tracking',$filter);
		if($queryexp !== FALSE && $queryexp->num_rows() > 0) {
			$data =$this->dbx->get_where('x_tracking_export',$filter);
		}else if($queryimp !== FALSE && $queryimp->num_rows() > 0){
			$data =$this->dbx->get_where('x_tracking',$filter);
		}else{
			$this->dbx->select(
				array(
					'trnstuffingplan.kode_ref as kode_ref',
					'trnstuffingplan.eta as eta',
					'trnstuffingplandetail.kode_ref as no_job',
					'trnstuffingplandetail.shipper as id_shipper',
					'trnstuffingplan.agen_penagihan as id_agent',
					'trnstuffingplan.mother_bl as mbl_no',
					'trnstuffingplan.transhipment_port as connecting',
					'trnstuffingplan.destination as consol_destination',
					'trnstuffingplandetail.hb_l as hbl_no',
					'trnstuffingplandetail.doc as hbl_agent',
					'trnstuffingplandetail.destination as destination',
					'CONCAT(trnstuffingplan.vessel, " ", trnstuffingplan.voyage) as feeder_name',
					'trnstuffingplan.etd_jkt as etd',
					'trnstuffingplan.atd as atd_pol',
					'trnstuffingplan.ata as ata_des',
					'concat("") as atd',
					'concat("") as ata',
					'concat("") as pickup_do',
					'concat("") as pickup_cargo',
					'concat("") as remarks',
					'concat("") as dpol',
					'concat("") as atp',
					'concat("") as dft',
					'concat("") as cargo_delivery',
					'trnstuffingplandetail.shipper_print as shipper',
					'trnstuffingplandetail.consignee_add as consignee',
					'trnstuffingplandetail.consignee as shipper_import',
					'trnstuffingplan.cont_seal_no as container_no',
					'trnstuffingplandetail.sp_detail_id as sp_detail_id',	
					'trnstuffingplandetail.no_of_pack as packaging',	
					'trnstuffingplan.prepared as prepared',
					'trnstuffingplan.warehouse as warehouse',
					'tbl_detail.no_from_shipper as id_request',
				)
			);
			$this->dbx->join('tbl_detail', 'tbl_detail.detail_id = trnstuffingplandetail.sp_detail_id', 'left');
			$this->dbx->join('trnstuffingplan','trnstuffingplan.sp_id=trnstuffingplandetail.sp_id','left');
			$this->dbx->where('trnstuffingplan.region_id',$region);
			$this->dbx->where('trnstuffingplandetail.kode_ref', $code);
			$this->dbx->order_by('trnstuffingplandetail.num','asc');
			$data =$this->dbx->get('trnstuffingplandetail');
		}
		return $data;
   }
   
	function tampil_semua_tracking($data,$limit){
		/*
		$q =$this->dbx->query("select * from x_tracking");
		return $q;
		*/
		
		$region = $this->session->userdata('region_id');
		$session= $this->session->userdata('user_id');
		
		//$this->dbx->select('sp_id,kode_ref,closing_date_time,etd_jkt,vessel,voyage,port_loading,transhipment_port,destination,prepared',false);
		
		if($data['_search']=='true') {
			$searchField = $data['searchField'];
			$searchString = $data['searchString'];
      		$searchOper  =  $data['searchOper'];
			
			$ops = array(
            	'eq'=>'=', //equal
            	'cn'=>'LIKE', // contains
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
		
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		
		$this->dbx->order_by($data['sidx'],$data['sord']);
		
		return$this->dbx->get('x_tracking');
	}
	
	
	function add_export($data)
{
    $data['region_id'] = $this->session->userdata('region_id');

    // cek apakah job ini sudah ada
    $exists = $this->dbx
        ->where('no_job', $data['no_job'])
        ->count_all_results('x_tracking_export');

    if ($exists > 0) {

        $updateData = array(
			'mbl_no'           => $data['mbl_no'],
			'hbl_no'           => $data['hbl_no'],
			'feeder_name'      => $data['feeder_name'],
			'etd'              => $data['etd'],
			'eta'              => $data['eta'],
			'pickup_cargo'     => $data['pickup_cargo'],
			'dpol'             => $data['dpol'],
			'atp'              => $data['atp'],
			'dft'              => $data['dft'],
			'atd'              => $data['atd'],
			'pickup_do'        => $data['pickup_do'],
			'cargo_delivery'   => $data['cargo_delivery'],
			'connecting'       => $data['connecting'],
			'consol_destination' => $data['consol_destination'],
			'container_no'     => $data['container_no'],
			'remarks'          => $data['remarks'],
			'updated_by'       => $this->session->userdata('fullname'),
			'updated_at'       => date('Y-m-d H:i:s')
		);

        $updateData['updated_by'] = $this->session->userdata('fullname');
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->dbx->where('hbl_no', $data['hbl_no']);
        $this->dbx->update('x_tracking_export', $updateData);

    } else {

        $data['created_by'] = $this->session->userdata('fullname');
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->dbx->insert('x_tracking_export', $data);
    }

    return ($this->dbx->affected_rows() > 0) ? '1' : '0';
}

	function add_import($data){
		$data['region_id']=$this->session->userdata('region_id');
		$q =$this->dbx->get_where('x_tracking',array('hbl_no'=>$data['hbl_no']));
		if($q->num_rows() > 0) {
			$data['updated_by']=$this->session->userdata('fullname');
			$data['updated_at']=date('Y-m-d H:i:s');
			$this->dbx->where('hbl_no',$data['hbl_no']);
			$this->dbx->update('x_tracking',$data);
		} else {
			$data['created_by']=$this->session->userdata('fullname');
			$data['created_at']=date('Y-m-d H:i:s');
			$this->dbx->insert('x_tracking',$data);
		}
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function update($data,$id){
		$this->dbx->where('id_tracking',$id);
		$this->dbx->update('x_tracking',$data);
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function delete($id){
		$this->dbx->where('id_tracking',$id);
		$this->dbx->delete('x_tracking');
		if($this->dbx->affected_rows() > 0) {
			return '1';
		} else {
			return '0';	
		}
	}
	
	function get_request_by_hbl_no_shipper($hbl){
   	$this->dbx->select('id_gocomet');
   	$this->dbx->join('trnstuffingplandetail','trnstuffingplandetail.sp_id=trnstuffingplan.sp_id');
   	$this->dbx->where('trnstuffingplandetail.hb_l', $hbl);
   	$this->dbx->where('trnstuffingplan.region_id', $this->session->userdata('region_id'));
   	//$this->dbx->group_start();
   //$this->dbx->where('backup_date IS NULL OR backup_date = ""');
    //$this->dbx->group_end();
    $query =$this->dbx->get('trnstuffingplan');
    //print_r ($this->dbx->last_query());
   // die();
    if ($query->num_rows() > 0) {
    return $query->row()->id_gocomet ;
    } else {
    return 'empty' ;
    }
  }
   function get_request_tracking_export($hbl_no){
    $query =$this->dbx->get_where('x_tracking_export',array('hbl_no' => $hbl_no));
    return $query->result();
  }
  // JOIN 3 TABEL UNTUK AMBIL DATA BERDASARKAN HBL
    public function get_detail_by_hbl($hbl_no)
    {
       $this->dbx->select("
            d.kode_ref          AS no_job,
            d.shipper           AS id_shipper,
            t.no_from_shipper   AS id_request,
            d.no_of_pack        AS packaging,
            p.agen_penagihan    AS id_agent,
            p.mother_bl         AS mbl_no,
            d.hb_l              AS hbl_no,
            t.feeder_vessel     AS feeder_name,
            p.etd_jkt           AS etd,
            p.atd		        AS atd,
            p.eta               AS eta,
            p.ata               AS ata,
            d.shipper_print     AS shipper,
            p.transhipment_port AS connecting,
            p.destination       AS consol_destination,
            d.consignee_add     AS consignee,
            d.destination       AS destination,
            p.cont_seal_no      AS container_no,
            d.sp_detail_id      AS sp_detail_id,
            d.region_id         AS region_id
        ");
       $this->dbx->from('trnstuffingplandetail d');
       $this->dbx->join('trnstuffingplan p', 'p.sp_id = d.sp_id', 'left');
       $this->dbx->join('tbl_detail t', 't.detail_id = d.sp_detail_id', 'left');
       $this->dbx->where('d.hb_l', $hbl_no);
       $this->dbx->limit(1);

        return$this->dbx->get()->row_array();
    }
	private function normalize_dmy($val)
	{
		if ($val === null || $val === '') return null;
		$val = trim($val);

		// 20251027 → 27/10/2025
		if (preg_match('/^\d{8}$/', $val)) {
			$y = substr($val, 0, 4);
			$m = substr($val, 4, 2);
			$d = substr($val, 6, 2);
			return $d . '/' . $m . '/' . $y;
		}

		// 2025-10-27 → 27/10/2025
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
			[$y, $m, $d] = explode('-', $val);
			return $d . '/' . $m . '/' . $y;
		}

		// kalau sudah 27/10/2025 atau format lain, biarkan apa adanya
		return $val;
	}

	public function upsert_tracking_export($row)
	{
		$now  = date('Y-m-d H:i:s');
		$user = $this->session->userdata('fullname');

		// Aman dari warning
		$isHK = (isset($row['format_type']) && $row['format_type'] === 'HK');

		// --- Normalisasi tanggal utama ke D/M/Y ---
		$etd = isset($row['etd']) ? $this->normalize_dmy($row['etd']) : null;
		$atd = isset($row['atd']) ? $this->normalize_dmy($row['atd']) : null;
		$eta = isset($row['eta']) ? $this->normalize_dmy($row['eta']) : null;
		$ata = isset($row['ata']) ? $this->normalize_dmy($row['ata']) : null;

		// Tambahan dari Excel HK (kalau ada)
		$atd_ts_port_raw     = $row['atd_ts_port']     ?? null; // ATD T/S PORT dari Excel
		$container_trans_raw = $row['container_trans'] ?? null; // NO CONTAINER TRANSHIPMENT (kalau ada)
		$connecting_excel_raw = $row['connecting_excel'] ?? null; // NO CONTAINER TRANSHIPMENT (kalau ada)

		// --- kalau nilainya "DIRECT" -> paksa jadi null, selain itu normalisasi tanggal / isi apa adanya ---
		if ($atd_ts_port_raw && strtoupper(trim($atd_ts_port_raw)) !== 'DIRECT') {
			$atd_ts_port = $this->normalize_dmy($atd_ts_port_raw);
		} else {
			$atd_ts_port = null;
		}
		if ($connecting_excel_raw && strtoupper(trim($connecting_excel_raw)) !== 'DIRECT') {
			$connecting_excel = $connecting_excel_raw;
		} else {
			$connecting_excel = null;
		}

		if ($container_trans_raw && strtoupper(trim($container_trans_raw)) !== 'DIRECT') {
			$container_trans = $container_trans_raw;
		} else {
			$container_trans = null;
		}
		$atd_ts_port     = isset($row['atd_ts_port'])     ? $this->normalize_dmy($row['atd_ts_port'])     : null; // ATD T/S PORT
		$ata_destination = isset($row['ata_destination']) ? $this->normalize_dmy($row['ata_destination']) : null; // ATA DESTINATION
		$collect_do      = isset($row['collect_do'])      ? $this->normalize_dmy($row['collect_do'])      : null;
		$collect_cargo   = isset($row['collect_cargo'])   ? $this->normalize_dmy($row['collect_cargo'])   : null;

		$feeder_name      = $row['feeder_name']      ?? null;
		$connecting_db    = $row['connecting_db']    ?? null;
		$container_no_db  = $row['container_no']  ?? null;

		// ====================================================
		//  JSON FIELDS SESUAI FORMAT YANG KAMU MAU
		// ====================================================

		// 1) Pickup Cargo selalu null
		$pickup_cargo = json_encode([
			"Pickup Cargo" => null
		]);

		// 2) DPOL
		// HK  : ATD ambil dari kolom ATD (yang sudah di-normalize), vessel = feeder_name
		// JOB : ATD ambil dari ETD
		$dpol = json_encode([
			"Actual Time of Departure (ATD)" => $isHK ? $atd : $etd,
			"Vessel Name"                    => $feeder_name
		]);

		// 3) ATP (Arrival at Transhipment Port)
		// HK  : Arrival = ATA (tanggal transhipment), Transhipment Port = connecting (db)
		// JOB : null semua
		$atp = json_encode([
			"Arrival at Transhipment Port" => $isHK ? $ata : null,
			"Transhipment Port"            => $isHK ? $connecting_db : null
		]);

		// 4) DFT (Departure from T/S port + container)
		// Departure from Transhipment Port : null (belum dipakai)
		// Container No = dari DB
		$dft = json_encode([
			"Departure from Transhipment Port" => $isHK ? $atd_ts_port : null,
			"Container No"                     => $container_trans
		]);

		// 5) ATD (di DB: kolom atd, tapi JSON berisi info arrival destination)
		// HK  : ATA DESTINATION & CONNECTING VESSEL dari Excel
		// JOB : ATA langsung dari eta, vessel/voyage null
		$atd_json = json_encode([
			"Actual Time of Arrival (ATA)" => $isHK ? $ata_destination : null,
			"Vessel/Voyage"                => $isHK ? $connecting_excel : null
		]);

		// 6) Pickup DO
		$pickup_do_json = json_encode([
			"Pickup DO" => $collect_do
		]);

		// 7) Cargo delivered
		$cargo_delivery_json = json_encode([
			"Cargo delivered" => $collect_cargo
		]);

		// ====================================================
		//  DATA UTAMA UNTUK TABEL x_tracking_export
		// ====================================================
		$data = [
			'no_job'             => $row['no_job']     ?? null,
			'id_shipper'         => $row['id_shipper'] ?? null,
			'id_request'         => $row['id_request'] ?? null,
			'packaging'          => $row['packaging']  ?? null,
			'id_agent'           => $row['id_agent']   ?? null,
			'mbl_no'             => $row['mbl_no']     ?? null,
			'hbl_no'             => $row['hbl_no']     ?? null,
			'feeder_name'        => $feeder_name,
			'etd'                => $etd,
			'eta'                => $eta,
			'ata'                => $ata,
			'shipper'            => $row['shipper']    ?? null,
			'connecting'         => $connecting_db ?? $connecting_excel ?? null,
			'consol_destination' => $row['consol_destination'] ?? $row['destination'] ?? null,
			'consignee'          => $row['consignee']  ?? null,
			'destination'        => $row['destination'] ?? null,
			'container_no'       => $container_no_db ?? null,
			'remarks'            => $row['remarks']    ?? null,
			'sp_detail_id'       => $row['sp_detail_id'] ?? null,
			'region_id'          => $row['region_id']    ?? null,

			// JSON fields
			'pickup_cargo'   => $pickup_cargo,
			'dpol'           => $dpol,
			'atp'            => $atp,
			'dft'            => $dft,
			'atd'            => $atd_json,
			'pickup_do'      => $pickup_do_json,
			'cargo_delivery' => $cargo_delivery_json,

			'prepared'   => 0,
			'created_by' => $user,
			'created_at' => $now,
			'updated_by' => $user,
			'updated_at' => $now,
		];

		// UPSERT by hbl_no
		$this->dbx->where('hbl_no', $row['hbl_no']);
		$this->dbx->where('region_id', $this->session->userdata('region_id'));
		$exists =$this->dbx->count_all_results('x_tracking_export');

		if ($exists) {
			$this->dbx->where('hbl_no', $row['hbl_no']);
			$this->dbx->update('x_tracking_export', $data);
		} else {
			$this->dbx->insert('x_tracking_export', $data);
		}
	}


}
?>