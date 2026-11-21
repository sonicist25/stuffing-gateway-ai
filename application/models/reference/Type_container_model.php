<?php
class Type_container_model extends CI_Model
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
	
	function getListContainer(){
		$this->dbx->select ('*');
		return $this->dbx->get('reftypecontainer');
	}
	
	// Kolom yang boleh dicari lewat jqGrid search box (whitelist mencegah
	// nama kolom hasil input user langsung dipakai di SQL).
	private $searchable_columns = array('typecontainer_id','typecontainer_name');

	function getDatareftypecontainer($data,$limit){
		if(isset($data['_search']) && $data['_search']=='true') {
			$ops = array(
            	'eq'=>'=', 'ne'=>'<>', 'bw'=>'LIKE', 'lt'=>'<', 'le'=>'<=', 'gt'=>'>', 'ge'=>'>=',
            	'bn'=>'NOT LIKE', 'in'=>'LIKE', 'ni'=>'NOT LIKE', 'ew'=>'LIKE', 'en'=>'NOT LIKE',
            	'cn'=>'LIKE', 'nc'=>'NOT LIKE'
        	);

			$searchField  = isset($data['searchField']) ? $data['searchField'] : '';
			$searchOper   = isset($data['searchOper']) ? $data['searchOper'] : '';
			$searchString = isset($data['searchString']) ? $data['searchString'] : '';

			if (in_array($searchField, $this->searchable_columns, true) && isset($ops[$searchOper])) {
				$sqlOp = $ops[$searchOper];

				if (in_array($searchOper, array('bw','bn'), true)) {
					$searchString .= '%';
				} elseif (in_array($searchOper, array('ew','en'), true)) {
					$searchString = '%'.$searchString;
				} elseif (in_array($searchOper, array('cn','nc','in','ni'), true)) {
					$searchString = '%'.$searchString.'%';
				}

				// $searchField sudah divalidasi lewat whitelist di atas,
				// $searchString di-escape otomatis oleh query builder CI.
				$this->dbx->where("$searchField $sqlOp", $searchString);
			}
		}
		if($limit==true) {
			$this->dbx->limit($data['rows'],$data['start']);
		}
		$q = $this->dbx->get("reftypecontainer")->result();
		return $q;
	}
	
	function insert_type_container($wh_name){
		$this->dbx->insert('reftypecontainer', array('typecontainer_name'=>strtoupper($wh_name)));
	}
	
	function update_type_container($wh_name, $id){
		$this->dbx->where('typecontainer_id', $id);
		$this->dbx->update('reftypecontainer', array('typecontainer_name'=>strtoupper($wh_name)));
	}
	
	function delete_type_container($id){
		$this->dbx->where('typecontainer_id',$id);
		$this->dbx->delete('reftypecontainer');
	}
	
}	
