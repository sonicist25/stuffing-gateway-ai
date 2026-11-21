<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class M_feeder_rate extends CI_Model
{
    /** @var CI_DB_query_builder */
    public $dbx;

    public function __construct()
    {
        parent::__construct();

        // kalau pakai DB per tahun, baca dari query ?tahun=2025
        $tahun = $this->input->get('tahun', TRUE);

        if ($tahun && is_numeric($tahun) && function_exists('switch_db')) {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        } else {
            $this->dbx = $this->db;
        }
    }

    /**
     * RATES SEA EXPORT (LCL + FCL) dari a_feeder_rate_detail
     */
    public function get_feeder_rates($origin = NULL, $destination = NULL, $service = 'all')
    {
        $db = $this->dbx;
        $db->from('a_feeder_rate_detail');

        if ($origin !== NULL && $origin !== '') {
            $db->where('region_id', strtoupper($origin));
        }

        if ($destination !== NULL && $destination !== '') {
            $db->like('destination', strtoupper($destination));
        }

        // filter valid >= hari ini
        $db->where('valid >=', date('Y-m-d'));

        $db->order_by('region_id');
        $db->order_by('destination');
        $db->order_by('carier');

        $query = $db->get();
        $rows  = $query->result();
			
        $lcl = [];
        $fcl = [];

        foreach ($rows as $row) {
            // normalisasi condition jadi free time friendly
            $condition = trim((string) $row->condition);
            if ($condition === 'NaN' || $condition === '-' || $condition === '0') {
                $condition = '';
            }
            if ($condition !== '' && stripos($condition, 'day') === FALSE) {
                $condition = $condition . ' days';
            }

            $free_time = $condition;
            $currency  = 'USD';

            // ===== LCL =====
            if ($service === 'all' || $service === 'lcl') {
                $lcl[] = [
                    'origin'        => strtoupper($row->region_id),
                    'destination'   => strtoupper($row->destination),
                    'rate_per_cbm'  => (max(0, (float) $row->lcl)),   // walaupun 0.00 tetap masuk
                    'min_cbm'       => (int) $row->mchgs,
                    'valid'         => $row->valid,
                    'free_time'     => $free_time,
                    'note'          => $row->remarks,
                    'currency'      => $currency,
                ];
            }

            // ===== FCL (20/40/40HC) =====
            if ($service === 'all' || $service === 'fcl') {
                if ($row->duapuluh >= 0) {
                    $fcl[] = [
                        'origin'      => strtoupper($row->region_id),
                        'destination' => strtoupper($row->destination),
                        'size'        => "20'",
                        'freight'     => (float) $row->duapuluh,
                        'carrier'     => $row->carier,
                        'scac'        => $row->scac,
                        'valid'       => $row->valid,
                        'free_time'   => $free_time,
                        'note'        => $row->remarks,
                        'currency'    => $currency,
                    ];
                }

                if ($row->empatpuluh >= 0) {
                    $fcl[] = [
                        'origin'      => strtoupper($row->region_id),
                        'destination' => strtoupper($row->destination),
                        'size'        => "40'",
                        'freight'     => (float) $row->empatpuluh,
                        'carrier'     => $row->carier,
                        'scac'        => $row->scac,
                        'valid'       => $row->valid,
                        'free_time'   => $free_time,
                        'note'        => $row->remarks,
                        'currency'    => $currency,
                    ];
                }

                if ($row->empatpuluhhc >= 0) {
                    $fcl[] = [
                        'origin'      => strtoupper($row->region_id),
                        'destination' => strtoupper($row->destination),
                        'size'        => "40' HC",
                        'freight'     => (float) $row->empatpuluhhc,
                        'carrier'     => $row->carier,
                        'scac'        => $row->scac,
                        'valid'       => $row->valid,
                        'free_time'   => $free_time,
                        'note'        => $row->remarks,
                        'currency'    => $currency,
                    ];
                }
            }
        }
		
		$temp = [];
		$unique_lcl = [];

		foreach ($lcl as $item) {
			$key = $item['origin'].'|'.$item['destination'].'|'.$item['rate_per_cbm'].'|'.$item['min_cbm'].'|'.$item['valid'];
			if (!isset($temp[$key])) {
				$temp[$key] = true;
				$unique_lcl[] = $item;
			}
		}
		$lcl = $unique_lcl;

        // limit masing-masing 5 data
        $lcl = array_slice($lcl, 0, 5);
        $fcl = array_slice($fcl, 0, 5);

        return [
            'lcl' => $lcl,
            'fcl' => $fcl,
        ];
    }

    /**
     * RATES AIRFREIGHT EXPORT dari airline_cost
     */
    public function get_air_rates($origin = NULL, $destination = NULL)
    {
        $db = $this->dbx;
        $db->from('airline_cost');

        if ($origin !== NULL && $origin !== '') {
            $origin = strtoupper($origin);
            $db->group_start();
            $db->where('origin_code', $origin);
            $db->or_like('origin', $origin);
            $db->group_end();
        }

        if ($destination !== NULL && $destination !== '') {
            $destination = strtoupper($destination);
            $db->group_start();
            $db->where('destination_code', $destination);
            $db->or_like('destination', $destination);
            $db->group_end();
        }

        // filter valid >= hari ini
        $db->where('valid >=', date('Y-m-d'));

        $db->order_by('origin_code');
        $db->order_by('destination_code');
        $db->order_by('airline_code');

        $query = $db->get();
        $rows  = $query->result();

        $air = [];
        foreach ($rows as $row) {
            $air[] = [
                'airline'           => $row->airline,
                'airline_code'      => $row->airline_code,
                'shc'               => $row->shc,
                'commodity'         => $row->commodity,
                'origin'            => $row->origin,
                'origin_code'       => $row->origin_code,
                'destination'       => $row->destination,
                'destination_code'  => $row->destination_code,
                'minimum'           => (float) $row->minimum,
                'normal'            => (float) $row->dasar,
                'rate_45'           => (float) $row->empatlima,
                'rate_100'          => (float) $row->seratus,
                'rate_300'          => (float) $row->tigaratus,
                'rate_500'          => (float) $row->limaratus,
                'rate_1000'         => (float) $row->seribu,
                'valid'             => $row->valid,
                'remarks'           => $row->remarks,
                'currency'          => 'IDR',
            ];
        }

        // limit 5 data
        //$air = array_slice($air, 0, 5);

        return $air;
    }
}