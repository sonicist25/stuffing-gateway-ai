<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_fiscal_years extends MY_Model
{
    private $company_id = 1;

    public function list_years()
    {
        $region_id = (string)$this->auth_region();

        $this->erp->from('fiscal_years')
            ->where('company_id', $this->company_id);

        $this->apply_region_filter('region_id');

        $rows = $this->erp->order_by('year', 'DESC')
            ->get()
            ->result_array();

        // auto seed beberapa tahun kalau kosong per region
        if (!$rows) {
            $y = (int)date('Y');

            for ($i = 0; $i < 3; $i++) {
                $yr = $y - $i;

                $this->erp->insert('fiscal_years', [
                    'company_id'  => $this->company_id,
                    'region_id'   => $region_id,
                    'year'        => $yr,
                    'start_date'  => $yr . '-01-01',
                    'end_date'    => $yr . '-12-31',
                    'is_closed'   => 0
                ]);
            }

            $this->erp->from('fiscal_years')
                ->where('company_id', $this->company_id);

            $this->apply_region_filter('region_id');

            $rows = $this->erp->order_by('year', 'DESC')
                ->get()
                ->result_array();
        }

        // attach closing info per region
        foreach ($rows as &$r) {
            $this->erp->select('closing_id, status, closing_date, je_id, net_profit')
                ->from('year_end_closings')
                ->where('company_id', $this->company_id)
                ->where('fiscal_year', $r['year']);

            $this->apply_region_filter('region_id');

            $close = $this->erp->order_by('closing_id', 'DESC')
                ->get()
                ->row_array();

            $r['closing'] = $close ?: null;
        }

        unset($r);

        return $rows;
    }
}