<?php
class Afg_c_trucking extends CI_Controller
{
	var $title = 'Track Import';
	public function __construct(){
		parent::__construct();
		$this->fungsi->restrict();
	//	$this->fungsi->delete_trucking();
		$this->load->model(array('afg_m_export','afg_m_countries','afg_m_cities','afg_m_tracking','pricelist/booking_model'));
		$this->fungsi->dbx = $this->afg_m_tracking->dbx;
		$this->dbx = $this->afg_m_tracking->dbx;
	}
	
	function index(){
		$regionName = $this->session->userdata('region_name');
		$this->session->unset_userdata('country_id');
		$data['title'] = $this->title;
		$data['h1_title'] = 'Stuffing Plan';
		$data['table_title'] = 'List Shipment Request';
		$data['menu_navigation'] = 'navigation';
		$data['main_view'] = 'trucking/form_trucking';
		$data['authentic_level'] = $this->fungsi->authentic_level(array(0,'1','2','3',4));		
		$data['regionName'] = $regionName;			
		$data['readonly'] = 'disabled="disabled"';			
		$data['style'] = 'stuffing_plan';			
		$data['level'] = $this->session->userdata('level_id');			
		$data['region_id'] = $this->session->userdata('region_id');					
		$this->load->view('afg_template', $data);
	}
	
	public function open_trucking_table_export()
		{
			// Ambil input
			$job_no             = $this->input->post('job_no');
			$kodeRef            = $this->input->post('kodeRef');
			$etd                = $this->input->post('etd');
			$eta                = $this->input->post('eta');
			$consol_destination = $this->input->post('consol_destination');
			$connecting 		= $this->input->post('connecting');
			$hbl_no             = $this->input->post('bl');
			$shipper            = $this->input->post('shipper');
			$scac               = $this->input->post('scac');
			$container_number   = $this->input->post('container');
			$pod                = $this->input->post('pod');
			// Data tracked/connected
			$tracked   = $this->afg_m_tracking->get_tracked($job_no);
			$connected = $this->afg_m_tracking->get_connected($job_no);

			// POD = Consol Destination -> shipment DIRECT (tanpa transhipment)
			// Dicek ulang di server, jangan hanya percaya flag dari client
			$is_direct = $this->is_direct_shipment($pod, $consol_destination);
			if ($is_direct) {
				$connecting = '';
			}

			// Update x_tracking_export
			$isi = [
				'etd'                => $etd,
				'eta'                => $eta,
				'consol_destination' => $consol_destination,
				'container_no' => $container_number,
				'shipper' => $shipper,
				'hbl_no' => $hbl_no,
			];
			if ($is_direct) {
				// Direct: kosongkan connecting lama supaya form tidak muncul transhipment lagi
				$isi['connecting'] = '';
			} elseif (!empty($connecting)) {
				// Jika connecting tidak kosong, tambahkan ke array update
				$isi['connecting'] = $connecting;
			}
			$this->dbx->where('no_job', $kodeRef);
			$this->dbx->update('x_tracking_export', $isi);

			// Data utama
			$data = $this->afg_m_tracking->find($kodeRef, $job_no)->row();

			// Fallback: kalau POD tidak terkirim dari grid, pakai destination dari DB
			if (!$is_direct && trim((string)$pod) === '' && !empty($data->destination)) {
				$is_direct = $this->is_direct_shipment($data->destination, $consol_destination);
			}

			$data->tracked         = $tracked;
			$data->connected       = $is_direct ? 0 : $connected;
			$data->is_direct       = $is_direct ? 1 : 0;
			$data->pod             = $pod;
			if ($is_direct) {
				$data->connecting  = '';
			}
			$data->message         = '';
			$data->tracking_result = '';
			$data->code            = '';
			$data->scac            = $scac;

			$this->load->view('trucking/form_track_export', $data);
		}


	function open_trucking_table_import(){
		$isi['etd']= $_POST['etd'];
		$isi['eta']= $_POST['eta'];
		$isi['atd']= $_POST['atd'];
		$isi['ata']= $_POST['ata'];
		// Validasi stripping_date
		if (!empty($_POST['stripping_date']) && $_POST['stripping_date'] !== '00000000' && $_POST['stripping_date'] !== 'null') {
			$isi['stripping_date'] = $_POST['stripping_date'];
		}
		$isi['warehouse']= $_POST['warehouse'];
		$this->dbx->where('no_job',$_POST['kodeRef']);
		$this->dbx->update('x_tracking',$isi);
		$data = $this->afg_m_tracking->find($_POST['kodeRef'],$_POST['job_no'])->result()[0];
		$tracked = $this->afg_m_tracking->get_tracked($_POST['job_no']);
		$data->tracked=$tracked;
		//$data->connected=$connected;
		$data->message = '';
        $data->tracking_result = '';
        $data->code = '';
		$hbl_no = $data->hbl_no;
		$hbl_agent = $data->hbl_agent;
		//$shipper = $this->input->post('shipper');
		//print_r ($data);
		//die();
		$this->load->view('trucking/form_track_import', $data);
	}
	
	function get_tracking(){
		$hal = isset($_POST['page'])?$_POST['page']:1; 
		$batas = isset($_POST['rows'])?$_POST['rows']:10;
		$sidx = isset($_POST['sidx'])?$_POST['sidx']:'';
		$sord = isset($_POST['sord'])?$_POST['sord']:'';
				
		$start = $batas * $hal - $batas;
		$start = ($start<0)?0:$start;			
		$_POST['start'] = $start;
		
		$q = $this->afg_m_tracking->tampil_semua_tracking($_POST,false);
		$query = $this->afg_m_tracking->tampil_semua_tracking($_POST,true);

		$hitung = count($q);	 
		if( $hitung > 0 ) {
		    $total_hal = ceil($hitung/$batas);
		} else {
		    $total_hal = 0;
		}	 
		if ($hal > $total_hal) $hal=$total_hal;
		
		$data = new stdClass();	 
		$data->page = $hal;
		$data->total = $total_hal;
		$data->records = $hitung;
		$i=0;
		foreach($q->result() as $row) {
		    $data->rows[$i]['id']=$row->id_tracking;
		    $data->rows[$i]['cell']=array(
		    		$row->id_shipper,
		    		$row->id_request,
		    		$row->id_agent,
		    		$row->mbl_no,
		    		$row->hbl_no, 
		    		$row->port_of_destination, 
		    		$row->feeder_name, 
		    		$this->fungsi->jin_date_str($row->etd),
		    		$this->fungsi->jin_date_str($row->atd),
		    		$this->fungsi->jin_date_str($row->eta), 
		    		$this->fungsi->jin_date_str($row->ata), 
		    		$row->shipper, 
		    		$row->consignee,
		    		$row->destination, 
		    		$row->pickup_do, 
		    		$row->pickup_cargo, 
		    		$row->container_no, 
		    		$row->remarks,
		    		$row->sp_detail_id,
		    		);
		    $i++;
		}
		echo json_encode($data);
	}

	function manage_tracking_import(){
		$mode = $this->input->post('oper');
		$id = $this->input->post('id');
						
		$data = array (
			'id_shipper' 					=> $this->input->post('id_shipper'),
			'id_request' 					=> $this->input->post('id_request'),
			'id_agent' 						=> $this->input->post('id_agent'),
			'warehouse' 					=> $this->input->post('warehouse'),
			'mbl_no' 						=> $this->input->post('mbl_no'),
			'hbl_agent' 					=> $this->input->post('hbl_agent'),
			'hbl_no' 						=> $this->input->post('hbl_no'),
			'feeder_name' 					=> $this->input->post('feeder_name'),
			'connecting_name' 				=> $this->input->post('connecting_name'),
			'etd' 							=> $this->input->post('etd'),	
			'atd' 							=> $this->input->post('atd'),	
			'eta' 							=> $this->input->post('eta'),
			'ata'        					=> $this->input->post('ata'),
			'shipper' 						=> $this->input->post('shipper'),
			'consignee'						=> $this->input->post('consignee'),
			'destination' 					=> $this->input->post('destination'),
			'pickup_do' 					=> $this->input->post('pickup_do'),
			'pickup_cargo'					=> $this->input->post('pickup_cargo'),
			'container_no'					=> strtoupper($this->input->post('container_no')),
			'remarks' 						=> $this->input->post('remarks'),
			'sp_detail_id' 					=> $this->input->post('sp_detail_id'),
			'prepared' 				=> $this->input->post('prepared'),
			'no_job' 				=> $this->input->post('no_job')
		);
		
		if($mode == 'add')
		{
			$data['shipper']=$this->get_data($data['no_job'],'shipper');
			$data['consignee']=$this->get_data($data['no_job'],'consignee');
			$status = $this->afg_m_tracking->add_import($data);
			echo json_encode(array('status'=>$status,'mode'=>'add'));
		}
	}
	
	function manage_tracking_export()
{
    $mode = $this->input->post('oper');

    $make_json = function ($prefix, $keys) {
        $out = [];

        foreach ($keys as $idx => $defaultKey) {
            $no = $idx + 1;

            $attr = $this->input->post($prefix . '_attr_' . $no);
            $val  = $this->input->post($prefix . '_val_' . $no);

            $attr = trim((string)($attr !== null && $attr !== '' ? $attr : $defaultKey));
            $val  = trim((string)$val);

            if ($val === '') {
                $out[$attr] = null;
            } else {
                $out[$attr] = $val;
            }
        }

        return $out;
    };

    $pickup_cargo = $make_json('pigo', [
        'Pickup Cargo'
    ]);

    $dpol = $make_json('dpol', [
        'Actual Time of Departure (ATD)',
        'Vessel Name'
    ]);

    $atp = $make_json('atp', [
        'Arrival at Transhipment Port',
        'Transhipment Port'
    ]);

    $dft = $make_json('dtp', [
        'Departure from Transhipment Port',
        'Container No'
    ]);

    $atd = $make_json('atd', [
        'Actual Time of Arrival (ATA)',
        'Vessel/Voyage'
    ]);

    $pickup_do = $make_json('pdo', [
        'Pickup DO'
    ]);

    $cargo_delivery = $make_json('cd', [
        'Cargo delivered'
    ]);

    // Shipment direct: tidak ada data transhipment yang disimpan
    $is_direct = (int)$this->input->post('is_direct') === 1;
    if ($is_direct) {
        $atp = [
            'Arrival at Transhipment Port' => null,
            'Transhipment Port'            => null
        ];
        $dft = [
            'Departure from Transhipment Port' => null,
            'Container No'                     => null
        ];
    }

    $data = array(
        'id_shipper'       => $this->input->post('id_shipper'),
        'id_request'       => $this->input->post('id_request'),
        'id_agent'         => $this->input->post('agent_code'),
        'mbl_no'           => $this->input->post('mbl_no'),
        'hbl_no'           => $this->input->post('hbl_no'),
        'feeder_name'      => $this->input->post('feeder_name'),

        'etd'              => $this->fungsi->jin_date_sql($this->input->post('etd')),
        'eta'              => $this->fungsi->jin_date_sql($this->input->post('eta')),

        'pickup_cargo'     => json_encode($pickup_cargo),
        'dpol'             => json_encode($dpol),
        'atp'              => json_encode($atp),
        'dft'              => json_encode($dft),
        'atd'              => json_encode($atd),
        'pickup_do'        => json_encode($pickup_do),
        'cargo_delivery'   => json_encode($cargo_delivery),

        'connecting'       => $is_direct ? '' : $this->input->post('connecting'),
        'consol_destination' => $this->input->post('consol_destination'),

        'shipper'          => $this->input->post('shipper'),
        'consignee'        => $this->input->post('consignee'),
        'destination'      => $this->input->post('destination'),
        'container_no'     => $this->input->post('container_no'),
        'remarks'          => $this->input->post('remarks'),
        'sp_detail_id'     => $this->input->post('sp_detail_id'),
        'packaging'        => $this->input->post('packaging'),
        'prepared'         => $this->input->post('prepared'),
        'no_job'           => $this->input->post('no_job')
    );

    if ($mode == 'add') {
        $data['shipper']   = $this->get_data($data['no_job'], 'shipper');
        $data['consignee'] = $this->get_data($data['no_job'], 'consignee');

        $status = $this->afg_m_tracking->add_export($data);

        echo json_encode(array(
            'status' => $status,
            'mode'   => 'add',
            'data'   => array(
                'dpol'           => $dpol,
                'atp'            => $atp,
                'dft'            => $dft,
                'atd'            => $atd,
                'pickup_do'      => $pickup_do,
                'cargo_delivery' => $cargo_delivery
            )
        ));
        return;
    }

    echo json_encode(array(
        'status' => 0,
        'message' => 'Invalid mode'
    ));
}

	/**
	 * Shipment dianggap DIRECT kalau POD sama dengan Consol Destination.
	 * Perbandingan case-insensitive dan spasi dirapikan.
	 */
	private function is_direct_shipment($pod, $consol_destination)
	{
		$norm = function ($v) {
			return strtoupper(trim(preg_replace('/\s+/', ' ', strip_tags((string)$v))));
		};

		$p = $norm($pod);
		$c = $norm($consol_destination);

		return $p !== '' && $p === $c;
	}

	function get_data($id,$con){
		$q= $this->dbx->get_where("trnstuffingplandetail",array('kode_ref'=>$id));
		$code = substr($q->row()->code,4,3);
		if($con=='shipper') {
			if($code=='100' || $code=='101' ||$code=='102' ||$code=='401' ||$code=='200' || $code=='400' ||$code=='402' ||$code=='500' || $code=='700' || $code=='900') {
				return $q->row()->shipper_print;	
			} else {
				return $q->row()->consignee;
			}
		} else {
			if($code=='100' || $code=='101' ||$code=='102' ||$code=='401' ||$code=='200' || $code=='400' ||$code=='402' ||$code=='500' || $code=='700' || $code=='900') {
				return $q->row()->consignee_add;	
			} else {
				return $q->row()->shipper_import;
			}		
		}
	}

}
