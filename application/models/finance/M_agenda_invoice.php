<?php
class M_agenda_invoice extends CI_Model
{	
 public $dbx;
  public $dbr;

    public function __construct() {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
            $this->dbr = $this->load->database('replica', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
            $this->dbr = $this->load->database(switch_db_replica($tahun), TRUE);
        }
    }
   
   private function _build_base_query($params)
    {
        $this->dbr->select(array(
            'x_finance.id as id',
            'x_finance.job_no as job_no',
            'x_finance.number as number',
            'x_finance.date as date',
            'x_finance.kepada as kepada',
            'x_finance.kepada_kode as kepada_kode',
            'x_finance.tgl_bayar as tgl_bayar', 
            'x_finance.region_id as region_id', 
        ));

        // FILTER PENCARIAN jqGrid
        if (!empty($params['_search']) && $params['_search'] == 'true') {
            $searchField  = isset($params['searchField']) ? $params['searchField'] : '';
            $searchString = isset($params['searchString']) ? $params['searchString'] : '';
            $searchOper   = isset($params['searchOper']) ? $params['searchOper'] : '';

            $ops = array(
                'eq'=>'=',
                'ne'=>'<>',
                'bw'=>'LIKE',
                'lt'=>'<',
                'le'=>'<=',
                'gt'=>'>',
                'ge'=>'>=',
                'bn'=>'NOT LIKE',
                'in'=>'LIKE',
                'ni'=>'NOT LIKE',
                'ew'=>'LIKE',
                'en'=>'NOT LIKE',
                'cn'=>'LIKE',
                'nc'=>'NOT LIKE'
            );

            if (isset($ops[$searchOper])) {
                $sqlOp = $ops[$searchOper];

                if($searchOper == 'eq') $searchString = $searchString;
                if($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
                if($searchOper == 'ew' || $searchOper == 'en') $searchString = '%'.$searchString;
                if(in_array($searchOper, array('cn','nc','in','ni'))) $searchString = '%'.$searchString.'%';

                if($searchField == 'shipper' ) $searchField = "kepada_kode";	
                if($searchField == 'colum' )  $searchField = "number";	
                if($searchField == 'date' )   $searchString = $this->fungsi->jin_date_sql($searchString);	

                $where = "$searchField $sqlOp ".$this->dbr->escape($searchString);                     
                $this->dbr->where($where); 
            }
        }

        // FILTER JENIS (INVOICE/CN)
        if (isset($params['jenis']) && $params['jenis'] == '1') {
            $this->dbr->where('x_finance.jenis !=','CREDIT NOTE');
        } else {
            $this->dbr->where('x_finance.jenis','CREDIT NOTE');
        }

        // FILTER KEPADA / KODE
        if (!empty($params['kepada'])) {
            $this->dbr->where('x_finance.kepada', $params['kepada']);
        }

        if (!empty($params['kepada']) && !empty($params['kode'])) {
            $this->dbr->where('x_finance.kepada', $params['kepada']);
            $this->dbr->where('x_finance.kepada_kode', $params['kode']);
        }

        // FILTER TANGGAL
        if (!empty($params['from_date'])) {
            $this->dbr->where('date >=', $this->fungsi->jin_date_sql($params['from_date']));
        }
        if (!empty($params['to_date'])) {
            $this->dbr->where('date <=', $this->fungsi->jin_date_sql($params['to_date']));
        }

        // REGION
        $this->dbr->where('region_id',$this->session->userdata("region_id"));

        // SORTING
        $sidx = isset($params['sidx']) ? $params['sidx'] : 'date';
        $sord = isset($params['sord']) ? $params['sord'] : 'asc';

        if($sidx == "colum")   { $sidx = "number"; }
        if($sidx == "bl_no")   { $sidx = "job_no"; }
        if($sidx == "shipper") { $sidx = "kepada_kode"; }
        if($sidx == "usd" || $sidx == "rp") {
            $sidx = "currency"; // meski di x_finance tidak ada, biarkan konsisten
        }

        $this->dbr->order_by($sidx, $sord);
        $this->dbr->from('x_finance');
    }

    // Dipakai untuk hitung total records (grid)
    public function count_filtered($params)
    {
        $this->_build_base_query($params);
        return $this->dbr->count_all_results();
    }

    // Dipakai untuk data grid dengan paginasi
    public function get_paginated($params)
    {
        $this->_build_base_query($params);

        if (isset($params['rows']) && isset($params['start'])) {
            $this->dbr->limit((int)$params['rows'], (int)$params['start']);
        }

        return $this->dbr->get()->result();
    }

    // Dipakai untuk export FULL (tanpa limit)
    public function get_all_for_export($params)
    {
        $this->_build_base_query($params);
        return $this->dbr->get()->result_array();
    }

    // === FUNGSI LAINNYA (tetap, seperti versi lama) ===

    public function get_total($id, $cur)
    {
        $total = [];
        $q = $this->dbr->get_where('x_finance_item', array('number'=>$id,'currency'=>$cur));
        if($q->num_rows() > 0) {
            foreach ($q->result() as $row){
                $total[] = $row->price;
                // kalau mau qty * price:
                // $total[] = $row->qty * $row->price;
            }
            return array_sum($total);
        } else {
            return "";
        }
    }
   
    public function get_data_item($id)
    {
        $this->dbr->order_by('number','asc');
        $data = $this->dbr->get_where('x_finance_item', array('number'=>$id), 1, 0);
        return $data;
    }

    function get_name($code,$kpd,$number)
	{
		if($kpd=="AGENT") {
			$sa = $this->dbr->get_where("refagentcompany",array("agent_company_code"=>$code));
			if($sa->num_rows() > 0 ) {
				return $sa->row()->agent_company_name;
			} else {
				return "";
			}
		}
		if($kpd=="WAREHOUSE") {
			$sc = $this->dbr->get_where("refwarehouse",array("warehouse_code"=>$code));
			if($sc->num_rows() > 0 ) {
				return $sc->row()->warehouse_name;
			} else {
				return "";
			}
		}
		if($kpd=="COLOADER") {
			$sc = $this->dbr->get_where("refshippingcompany",array("shipping_company_code"=>$code));
			if($sc->num_rows() > 0 ) {
				return $sc->row()->shipping_company_name;
			} else {
				return "";
			}
		}
		if($kpd=="SHIPPER") {
			$ss = $this->dbr->get_where("x_invoice",array("number"=>$number,"region_id"=>$this->session->userdata('region_id')));
			if($ss->num_rows() > 0 ) {
				return $ss->row()->shipper_name;
			} 
			else {
				return "";
			}
		}
		if($kpd=="CONSIGNEE") {
			$scn = $this->dbr->get_where("refconsigneeimport",array("consignee_import_code"=>$code));
			if($scn->num_rows() > 0 ) {
				return $code;
			} else {
				return "";
			}
		}
		if($kpd=="") {
			return $code;
		}
	}
}