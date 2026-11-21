<?php
class Quotation_model extends CI_Model
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
	
function tarik_data_selling_price_quot($data){	
		$row_misc 	= 	"";
		$row_of		=	"";
		$data_misc	=	array();
		$data_of	=	array();	
		$row_fcl 	= '';
		$data_fcl 	= '';
	
		$q1 = $this->dbx->get_where('x_finance_booking_detail', array('booking_id'=>$data['booking_id'],'region_id'=>$this->session->userdata("region_id")));
		
		if($q1->num_rows() > 0 ) 
		{
			$this->dbx->where('booking_id',$data['booking_id']);
			$this->dbx->where ('region_id',$this->session->userdata("region_id"));
			$this->dbx->delete('x_finance_booking_detail');
		}
		if($data['type_of_booking'] == 'EX') 
		{
		
			$shipper = $this->get_shipper_id($data['shipper_id']);
			$q = $this->dbx->get_where('a_selling',array(
				'dari'=>$data['sales'],
				'kepada'=>$shipper,
				'valid >='=> $this->fungsi->jin_date_sql($data['valid']), 
			));
			
			if($q->num_rows()>0) 
			{
				if($data['shipment'] == 'FCL') 
				{
                    foreach($q->result() as $row)
					{
						 $type =$data['shipment'];
						$query_misc = $this->dbx->get_where('a_selling_rate',array(
                                                  'id_selling'=>$row->id_selling,
                                                  'of_emkl'=>1,
                                                  'type'=>$type,
                                                  ));
						
						foreach($query_misc->result() as $row)
						{
							$set = $data['qty'];
							if($row->mchgs >= $set )
							{       
                                $set = $row->mchgs;
							}
							$unit_price = $row->price;
							$currency = $row->currency;
							$price = $set * $unit_price;
							$this->dbx->insert('x_finance_booking_detail', array(
								'booking_id'=>$data['booking_id'],
								'qty'=>$set,
								'item_service'=>$row->nama_service,
								'unit_price'=>$unit_price,
								'price'=>$price,
								'container'=>$row->container,
								'prep_coll'=>$row->kondisi,
								'currency'=>$currency,
								'region_id'=>$this->session->userdata("region_id"),
								"created_by"=>$this->session->userdata('fullname'),
								"created_at"=>date('Y-m-d H:i:s'),
							));
						}
						//foreach($q->result() as $row)
						
							$query = $this->dbx->get_where('a_selling_rate',array(
								//'container'=>$data['container'],
								'destination'=>$data['destination'],
								'id_selling'=>$row->id_selling,
								'of_emkl'=>0,
								'type'=>$type,
							));

							foreach($query->result() as $row)
							{
								if($row->set_m3 == 'SET' ) 
								{
									$set = '1';
								}
								else 
								{
									$set = $data['qty'];
								}
						
								if($row->mchgs >= $set ) 
								{
									$set = $row->mchgs;
								}
								$unit_price = $row->price;
								$currency = $row->currency;
								$price = $set * $unit_price;
								$this->dbx->insert('x_finance_booking_detail', array(
									'booking_id'=>$data['booking_id'],
									'qty'=>$set,
									'item_service'=>$row->nama_service,
									'unit_price'=>$unit_price,
									'price'=>$price,
									'container'=>$row->container,
									'prep_coll'=>$row->kondisi,
									'currency'=>$currency,
									'region_id'=>$this->session->userdata("region_id"),
									"created_by"=>$this->session->userdata('fullname'),
									"created_at"=>date('Y-m-d H:i:s'),
								));
							}

						
					
						$row_misc 	= 	'';
						$row_of		=	'';
						$data_misc	=	'';
						$data_of		=	'';
					
						$row_fcl 	= $query->num_rows();
						$data_fcl 	= $query->result();
					}
				}
				else
				{
					foreach($q->result() as $row)
					{ 
						$query_misc = $this->dbx->get_where('a_selling_rate',array(
                                                  'id_selling'=>$row->id_selling,
                                                  'of_emkl'=>1,
                                                  'type'=>$data['shipment'],
                                                  ));
				
						foreach($query_misc->result() as $row)
						{
							if($row->set_m3 == 'SET' ) 
							{
								$set = '1';
							}
							else 
							{
								$set = $data['qty'];
							}
						
							if($row->mchgs >= $set )
							{       
                              $set = $row->mchgs;
							}
							$unit_price = $row->price;
							$currency = $row->currency;
							$price = $set * $unit_price;
							$this->dbx->insert('x_finance_booking_detail', array(
								'booking_id'=>$data['booking_id'],
								'qty'=>$set,
								'item_service'=>$row->nama_service,
								'unit_price'=>$unit_price,
								'price'=>$price,
								'container'=>$row->container,
								'prep_coll'=>$row->kondisi,
								'currency'=>$currency,
								'region_id'=>$this->session->userdata("region_id"),
								"created_by"=>$this->session->userdata('fullname'),
								"created_at"=>date('Y-m-d H:i:s'),
							));
						}

						$query_of = $this->dbx->get_where('a_selling_rate',array(
							'id_selling'=>$row->id_selling,
							'of_emkl'=>0,
							'destination'=>$data['destination'],
							'type'=>$data['shipment'],
						));
						foreach($query_of->result() as $row_of)
						{
							if($row_of->set_m3 == 'SET' ) 
							{
								$set = '1';
							}
							else 
							{
								$set = $data['qty'];
							}
						
							if($row_of->mchgs >= $set )
							{       
							$set = $row_of->mchgs;
							}
							
							$unit_price = $row_of->price;
							$currency = $row_of->currency;
							$price = $set * $unit_price;
							$this->dbx->insert('x_finance_booking_detail', array(
								'booking_id'=>$data['booking_id'],
								'qty'=>$set,
								'item'=>$row_of->nama_service,
								'unit_price'=>$unit_price,
								'price'=>$price,
								'container'=>$row_of->container,
								'kondisi'=>$row_of->kondisi,
								'currency'=>$currency,
								'region_id'=>$this->session->userdata("region_id"),
								"created_by"=>$this->session->userdata('fullname'),
								"created_at"=>date('Y-m-d H:i:s'),
							));
						}
					}
					$row_misc 	= 	$query_misc->num_rows();
					$row_of		=	$query_of->num_rows();
					$data_misc	=	$query_misc->result();
					$data_of		=	$query_of->result();
				
					$row_fcl 	= '';
					$data_fcl 	= '';
				}
			}

			return;
		}
		else
		{
						
			$this->dbx->select('service,port_loading,pre_col,type,currency,unit_price,mchgs,container,data_id');
			$this->dbx->from('ref_import_detail');
			$this->dbx->join('ref_import','ref_import.id=ref_import_detail.data_id','left');
			$this->dbx->where('ref_import_detail.port_loading', $data['destination']);
			$this->dbx->where('ref_import.consignee_id', $data['shipper_id']);
			$this->dbx->where('ref_import.sales_id', $data['sales']);
			$q3 = $this->dbx->get();
			
			
			if($q3->num_rows() > 0 ) {
				$set = $data['qty'];
            	$unit_price = $row->unit_price;
				$unit_price = $row->unit_price;
				$currency = $row->currency;                               
						$this->dbx->insert('x_finance_booking_detail', array(
						'booking_id'=>$data['booking_id'],
						'qty'=>$set,
						'item_service'=>$row->service,
						'unit_price'=>$unit_price,
						'price'=>$set * $unit_price,
						'container'=>$row->container,
						'prep_coll'=>$row->pre_col,
						'currency'=>$currency,
						'region_id'=>$this->session->userdata("region_id"),
						"created_by"=>$this->session->userdata('fullname'),
						"created_at"=>date('Y-m-d H:i:s'),
					));
				}

				////print_r(array('job'=>$job,'mode'=>1));	
			}
			
			return array($data);////print_r(array('job'=>$job,'mode'=>2, "result"=>$data['sales']));	
		}
		
	function get_shipper_id($id){
		//return $this->dbx->get_where('mstshipper',array('id_shipper'=>$id))->row()->shipper_id;
		$q = $this->dbx->get_where('mstshipper',array('id_shipper'=>$id));
		if($q->num_rows()>0) {
			return $q->row()->shipper_name;
		}
		else 
		{
			$this->dbx->where("consignee_import_code =",$id);
			$query = $this->dbx->get('refconsigneeimport');
			if($query->num_rows()>0) {
				return $query->row()->consignee_import_id;
			}
		}
	}
}
?>