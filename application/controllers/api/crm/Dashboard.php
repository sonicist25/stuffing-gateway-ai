<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Dashboard extends RestController {

    public function __construct() {
        parent::__construct();
        $this->db = $this->load->database('master', TRUE);
    }

    /**
     * GET target vs actual per salesman per tahun
     * URL: /api/sales/target_actual?year=2026&salesman_id=90
     */
public function target_actual_get() {
    $salesman_id = $this->get('salesman_id'); // bisa kosong
    $region      = $this->get('region');      // bisa kosong
	//echo $salesman_id;
	//die();
    // Ambil target salesman per bulan
    $this->db->select('stm.month, sts.salesman_id, sts.fullname, sts.region_id,
                       sts.revenue_target, sts.profit_target, sts.management');
    $this->db->from('sales_target_salesman sts');
    $this->db->join('sales_target_monthly stm', 'stm.monthly_id = sts.monthly_id');
    if ($salesman_id) {
        $this->db->where('sts.salesman_id', $salesman_id);
    }
    if ($region) {
        $this->db->where('sts.region_id', $region);
    }
    $targets = $this->db->get()->result();

    // Ambil actual sales per bulan
   $this->db->select('salesman_id, month, SUM(actual_revenue) AS actual_revenue, SUM(actual_profit) AS actual_profit');
	$this->db->from('actual_sales');
	if ($salesman_id) {
		$this->db->where('salesman_id', $salesman_id);
	}
	if ($region) {
		$this->db->where('region_id', $region);
	}
	$this->db->group_by(['salesman_id', 'month']);
	$actuals = $this->db->get()->result();

    // Gabungkan data per salesman + bulan
    $result = [];
    foreach ($targets as $t) {
        $bulan = $t->month;
        $act = array_filter($actuals, function($a) use ($t, $bulan) {
            return $a->salesman_id == $t->salesman_id && $a->month == $bulan;
        });
        $act = reset($act);
		$result[$t->salesman_id]['user_id']   = $t->salesman_id;
        $result[$t->salesman_id]['fullname']   = $t->fullname;
        $result[$t->salesman_id]['region_id']  = $t->region_id;
        $result[$t->salesman_id]['management'] = $t->management;
        $result[$t->salesman_id]['data'][] = [
            'month'          => $bulan,
            'target_revenue' => (float)$t->revenue_target,
            'target_profit'  => (float)$t->profit_target,
            'actual_revenue' => $act ? (float)$act->actual_revenue : 0,
            'actual_profit'  => $act ? (float)$act->actual_profit : 0,
        ];
    }

    return $this->response([
        'status' => true,
        'salesman_id' => $salesman_id ?: 'ALL',
        'region' => $region ?: 'ALL',
        'salesmen' => array_values($result)
    ], RestController::HTTP_OK);
}

}