<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Afg_m_detail extends CI_Model { 

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
   
   function get_data_consignee($data)
   {
   	$id = $data['sp_detail_id'];
   	unset($data['sp_detail_id']);
   	$q = $this->dbx->get_where('refconsigneenotify',$data);
   	if($q->num_rows()>0) 
   	{
   		$this->dbx->update('trnstuffingplandetail',
   			array(
   				'consignee'=>$q->row()->consignee_notify_name,
   				'consignee_add'=>$q->row()->consignee_notify_address),
   			array('sp_detail_id'=>$id)
   		);
   		$this->dbx->update('tbl_detail',
   			array( 'consignee'=>$data['consignee_notify_code']),
   			array('detail_id'=>$id)
   		);
   		return $q;
   	}
   }
   
   function get_data_notify($data)
   {
   	$id = $data['sp_detail_id'];
   	unset($data['sp_detail_id']);
   	$q = $this->dbx->get_where('refconsigneenotify',$data);
   	if($q->num_rows()>0) 
   	{
   		$this->dbx->update('trnstuffingplandetail',
   			array(
   				'notify'=>$q->row()->consignee_notify_name,
   				'notify_add'=>$q->row()->consignee_notify_address),
   			array('sp_detail_id'=>$id)
   		);
   		$this->dbx->update('tbl_detail',
   			array( 'notify'=>$data['consignee_notify_code']),
   			array('detail_id'=>$id)
   		);
   		return $q;
   	}
   }
   
   function notify_selected($id)
   {
   	$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$id));
   	if($q->num_rows()>0) 
   	{
   		return $q->row()->notify;
   	}
   	else 
   	{
   		return '';
   	}
   }
   
   function consignee_selected($id)
   {
   	$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$id));
   	if($q->num_rows()>0) 
   	{
   		return $q->row()->consignee;
   	}
   	else 
   	{
   		return '';
   	}
   }

   function get_shipper($data)
   {
    $q = $this->db->get_where('mstshipper',$data);
   	if($q->num_rows()>0) 
   	{
   		return $q->row();
   	}
   	else 
   	{
   		$data = new StdClass();
   		$data->shipper_name = '';
   		$data->shipper_address = '';
   		return $data;
   	}
   }
   
function get_shipper_by_id($data)
{
    // 1. Ekstrak id_shipper secara eksplisit agar aman dari variabel ekstra di $_POST
    $id_shipper = isset($data['id_shipper']) ? $data['id_shipper'] : '';

    // 2. Ambil data shipper utama dulu
    $this->dbx->where('id_shipper', $id_shipper);
    $q = $this->dbx->get('mstshipper');
    
    if ($q->num_rows() > 0) {
        $shipper = $q->row(); // Mengambil data dasar seperti sales, cp_name, cp_email
    } else {
        // Fallback jika tidak ditemukan
        $shipper = new StdClass();
        $shipper->id_shipper      = $id_shipper;
        $shipper->shipper_name    = '';
        $shipper->shipper_address = '';
        $shipper->sales           = '';
        $shipper->cp_name         = '';
        $shipper->cp_email        = '';
    }

    // 3. Cek dan timpa dengan data dari mstshipper_address (HBL)
    $this->dbx->where('id_shipper', $id_shipper);
    $this->dbx->where('address_type', 'HBL'); // Gunakan where alih-alih like agar lebih presisi
    $this->dbx->order_by('id_address', 'DESC');
    $this->dbx->limit(1);
    $addr = $this->dbx->get('mstshipper_address');

    if ($addr->num_rows() > 0) {
        $row = $addr->row();
        
        // Gunakan (string) untuk mencegah error trim(null) di PHP versi terbaru
        if (trim((string) $row->label) !== '') {
            $shipper->shipper_name = $row->label;
        }
        
        if (trim((string) $row->address) !== '') {
            $shipper->shipper_address = $row->address;
        }
    }

    return $shipper;
}

	
	// di model (misal afg_m_detail.php)
	function save_shipper_hbl_address($id_shipper, $nama, $alamat)
	{
		if (empty($id_shipper)) return; // jaga-jaga, jangan proses kalau shipper belum dipilih

		$existing = $this->dbx->get_where('mstshipper_address', array(
			'id_shipper'   => $id_shipper,
			'address_type' => 'HBL'
		));

		$data = array(
			'label'      => $nama,
			'address'    => $alamat,
			'updated_by' => $this->session->userdata('username'), // sesuaikan dgn session Anda
			'updated_at' => date('Y-m-d H:i:s')
		);

		if ($existing->num_rows() > 0)
		{
			// UPDATE row HBL yang sudah ada (ambil yang paling baru kalau lebih dari satu)
			$row = $existing->row();
			$this->dbx->where('id_address', $row->id_address)
					   ->update('mstshipper_address', $data);
		}
		else
		{
			// INSERT baru
			$data['id_shipper']   = $id_shipper;
			$data['address_type'] = 'HBL';
			$data['created_by']   = $this->session->userdata('username');
			$data['created_at']   = date('Y-m-d H:i:s');
			$data['isActive']     = 1;
			$data['is_default']   = 0;
			$this->dbx->insert('mstshipper_address', $data);
		}
	}
   
   function save_detail($data)
   {
   	$id = $data['sp_detail_id'];
   	unset($data['sp_detail_id']);
    $job_no = $data['job_no'];
    unset($data['job_no']);

   	$this->dbx->update('tbl_detail',	
   		array(
   			'backup_date'=>$this->fungsi->jin_date_sql($data['backup_date']),
   			'bl_type'=>$data['bl_type'],
   			'as_agent'=>$data['as_agent'],
   			'payable'=>$data['payable'],
   			'as_arange'=>$data['as_arange'],
   			'cy_cy'=>strtoupper($data['cy_cy']),
   			'cfs_cfs'=>strtoupper($data['cfs_cfs']),
   			'diff_status_1'=>strtoupper($data['diff_status_1']),
   			'diff_status_2'=>strtoupper($data['diff_status_2']),
   			'port_of_loading'=>$data['port_of_loading'],
   			'port_of_discharge'=>$data['port_of_discharge'],
   			'port_of_receive'=>$data['port_of_receive'],
   			'port_of_delivery'=>$data['port_of_delivery'],
   		),
   		array('detail_id'=>$id)
   	);
   	unset($data['backup_date']);
   	unset($data['bl_type']);
   	unset($data['as_agent']);
   	unset($data['payable']);
   	unset($data['as_arange']);
   	unset($data['cy_cy']);
   	unset($data['cfs_cfs']);
   	unset($data['diff_status_1']);
   	unset($data['diff_status_2']);
   	unset($data['port_of_loading']);
   	unset($data['port_of_discharge']);
   	unset($data['port_of_receive']);
   	unset($data['port_of_delivery']);
   	
   	
   	$this->dbx->update('trnstuffingplandetail',$data,array('sp_detail_id'=>$id));
   	$this->dbx->update(
        'x_tracking_export',
        array('hbl_no' => $data['hb_l']),
        array('no_job' => $job_no)
    );
   	return array('status'=>$this->dbx->affected_rows());
   }
   
     function save_detail_awb($data)
   {
   	$id = $data['sp_detail_id'];
   	unset($data['sp_detail_id']);
   	$this->dbx->update('tbl_detail',	
   		array(
   			'backup_date'=>$this->fungsi->jin_date_sql($data['backup_date']),
   			'bl_type'=>$data['bl_type'],
   			'as_agent'=>$data['as_agent'],
   			'as_arange'=>$data['as_arange'],
   			'consignee'=>$data['consignee'],
   			'port_of_loading'=>$data['port_of_loading'],
   			'port_of_discharge'=>$data['port_of_discharge'],
   			'port_of_receive'=>$data['port_of_receive'],
   			'port_of_delivery'=>$data['port_of_delivery'],
   		),
   		array('detail_id'=>$id)
   	);
   	unset($data['backup_date']);
   	unset($data['bl_type']);
   	unset($data['as_agent']);
   	unset($data['payable']);
   	unset($data['as_arange']);
   	unset($data['cy_cy']);
   	unset($data['cfs_cfs']);
   	unset($data['diff_status_1']);
   	unset($data['diff_status_2']);
   	unset($data['port_of_loading']);
   	unset($data['port_of_discharge']);
   	unset($data['port_of_receive']);
   	unset($data['port_of_delivery']);
   	
   	$this->dbx->update('trnstuffingplandetail',$data,array('sp_detail_id'=>$id));
   	return array('status'=>$this->dbx->affected_rows());
   }
   
      function save_detail_draft($data)
   {
   	$id = $data['sp_detail_id'];
    $this->dbx->update('trnstuffingplan_booking_detail',	
   		array(
   			'as_agent'=>$data['as_agent'],
   			'container_no'=>$data['container_no'],
   			'note'=>$data['note'],
   			'consignee'=>$data['consignee'],
   			'notify'=>$data['notify'],
   			'description_of_goods'=>$data['desc_of_goods'],
   			'marking'=>$data['marking'],
   			'as_agent'=>$data['as_agent'],
   		),
   		array('booking_id'=>$id)
   	);
   	$this->dbx->update('trnstuffingplan_booking',	
   		array(
   			'payable'=>$data['payable'],
   			'movement_type'=>strtoupper($data['movement_type']),
   			'pol_bl'=>$data['pol_bl'],
   			'tp_bl'=>$data['tp_bl'],
   			'pod_bl'=>$data['pod_bl'],
   			'shipped_on_board'=>$data['shipped_on_board'],
   		),
   		array('booking_code'=>$id)
   	);
   	return array('status'=>$this->dbx->affected_rows());
   }
   
   function get_detail($id)
   {
   	$data = new stdClass(); 
   	$query=$this->dbx->get_where('tbl_detail',array('detail_id'=>$id));
   	if($query->num_rows() > 0) {
   		return $query->row();
   	}
   	else 
   	{
   		$data->no_from_shipper = '';
   		$data->manifest_no = '';
   		
   		$data->feeder_vessel='';
   		$data->feeder_vessel_eta='';
   		$data->feeder_vessel_etd='';
   		$data->feeder_vessel_eta_date='';
   		$data->feeder_vessel_etd_date='';
   		
   		$data->snd_vessel = '';
   		$data->snd_vessel_etd = '';
   		$data->snd_vessel_eta = '';
   		$data->snd_vessel_etd_date = '';
   		$data->snd_vessel_eta_date = '';
   		
   		$data->last_vessel = '';
   		$data->last_vessel_etd = '';
   		$data->last_vessel_eta = '';
   		$data->last_vessel_etd_date = '';
   		$data->last_vessel_eta_date = '';
   		
   		$data->last_snd_vessel = '';
   		$data->last_snd_vessel_etd = '';
   		$data->last_snd_vessel_eta = '';
   		$data->last_snd_vessel_etd_date = '';
   		$data->last_snd_vessel_eta_date = '';
   		
   		$data->as_agent = '';
   		$data->shipper_name = '';
   		$data->shipper_address = '';
   		$data->backup_date = '';
   		$data->bl_type = '';
   		$data->payable = '';
   		$data->as_arange = '';
   		
   		$data->cy_cy = '';
   		$data->cfs_cfs = '';
   		$data->diff_status_1 = '';
   		$data->diff_status_2 = '';
   		$data->last_count = '';

   		
   		$data->location = '';
   		$data->overtime = '';
   		$data->trucking_co = '';
   		
   		$data->port_of_loading = '';
   		$data->port_of_discharge = '';
   		$data->port_of_receive = '';
   		$data->port_of_delivery = '';
   		
   		$data->mbl = '';
   		
   		return $data;
   	}
   }
   
   function save_front($data)
   {
   	$data['snd_vessel_etd_date']=$this->fungsi->jin_date_sql($data['snd_vessel_etd_date']);
   	$data['snd_vessel_eta_date']=$this->fungsi->jin_date_sql($data['snd_vessel_eta_date']);
   	$data['last_vessel_etd_date']=$this->fungsi->jin_date_sql($data['last_vessel_etd_date']);
   	$data['last_vessel_eta_date']=$this->fungsi->jin_date_sql($data['last_vessel_eta_date']);
   	$data['last_snd_vessel_etd_date']=$this->fungsi->jin_date_sql($data['last_snd_vessel_etd_date']);
   	$data['last_snd_vessel_eta_date']=$this->fungsi->jin_date_sql($data['last_snd_vessel_eta_date']);
   	$data['feeder_vessel_etd_date']=$this->fungsi->jin_date_sql($data['feeder_vessel_etd_date']);
   	$data['feeder_vessel_eta_date']=$this->fungsi->jin_date_sql($data['feeder_vessel_eta_date']);
   	$data['region_id']=$this->session->userdata('region_id');

   	
   	
   	$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$data['detail_id']));
   	if($q->num_rows() > 0 ) 
   	{
   		$id = $data['detail_id'];
   		unset($data['detail_id']);
			$this->dbx->update('tbl_detail',$data,array('detail_id'=>$id));
		}   
		else
		{
			$this->dbx->insert('tbl_detail',$data);
		}
	
		return array('status'=>$this->dbx->affected_rows());
   }
   
function save_front_awb($data)
   {
   	$data['date_do_release']=$this->fungsi->jin_date_sql($data['date_do_release']);
   	$data['tanggal_peb']=$this->fungsi->jin_date_sql($data['tanggal_peb']);
   	$data['region_id']=$this->session->userdata('region_id');

   	$q = $this->dbx->get_where('tbl_detail',array('detail_id'=>$data['detail_id']));
   	if($q->num_rows() > 0 ) 
   	{
   		$id = $data['detail_id'];
   		unset($data['detail_id']);
			$this->dbx->update('tbl_detail',$data,array('detail_id'=>$id));
		}   
		else
		{
			$this->dbx->insert('tbl_detail',$data);
		}
	
		return array('status'=>$this->dbx->affected_rows());
   }
   
   
   
   
}