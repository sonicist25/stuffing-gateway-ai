<?php
class M_listing_job extends CI_Model
{
    public $dbx;
    public $dbr;

    private $has_source_income_id = false;
    private $has_income_source_table = false;
    private $has_income_source_id = false;
    private $has_invoice_db = false;

    /*
     * Batas aman untuk WHERE IN().
     * Jangan kirim puluhan ribu ID dalam satu SQL.
     */
    private $chunk_size = 750;

    public function __construct()
    {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        /*
         * MASTER:
         * hanya untuk metadata schema + last_query.
         *
         * REPLICA:
         * seluruh query report.
         */
        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
            $this->dbr = $this->load->database('replica', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
            $this->dbr = $this->load->database(switch_db_replica($tahun), TRUE);
        }

        /*
         * Cek capability schema sekali saja per request.
         */
        $this->has_source_income_id =
            $this->dbx->field_exists('source_income_id', 'x_finance_item');

        $this->has_income_source_table =
            $this->dbx->field_exists('income_source_table', 'x_finance_item');

        $this->has_income_source_id =
            $this->dbx->field_exists('income_source_id', 'x_finance_item');

        $this->has_invoice_db =
            $this->dbx->field_exists('invoice_db', 'inf_income_selling_price');
    }

    /**
     * ================================================================
     * TAHAP 1
     * ================================================================
     * Ambil kandidat current-unbilled untuk INVOICE maupun DEBIT NOTE.
     *
     * Query ini sengaja disusun mengikuti index:
     *
     * idx_listing_job_fast tetap membantu prefix region_id + prep_coll.
     * ditarik tidak lagi dijadikan syarat mutlak karena legacy dapat memiliki
     * ditarik=1 tetapi ship_co kosong. Direct mapping menjadi safety-net billed.
     *
     * Jangan bungkus region_id/prep_coll/ship_co dengan UPPER/TRIM/COALESCE
     * pada filter utama.
     */
    private function fetch_unbilled_candidates($region, $tahun = '', $bulan = '')
    {
        $tahun = preg_replace('/[^0-9]/', '', (string)$tahun);
        $bulan = preg_replace('/[^0-9]/', '', (string)$bulan);

        $this->dbr
            ->select(array(
                'i.id AS income_id',
                'i.kode_ref',
                'i.code_service',
                'i.item_service',
                'i.price',
                'i.currency',
                'i.prep_coll',
                'i.invoice_db',
                'i.region_id',
                'i.qty',
                'i.unit_price',
                'i.g',
                'i.ditarik',
                'i.ship_co'
            ), false)
            ->from('inf_income_selling_price i')
            ->where('i.region_id', $region)

            /*
             * Listing Job memang menampilkan P/C.
             * Jadi jangan batasi PREPAID saja. COLLECT yang belum dibuat
             * DEBIT NOTE juga harus ikut tampil.
             */
            ->where_in('i.prep_coll', array('PREPAID', 'COLLECT'))

            /*
             * CURRENT ownership utama = ship_co.
             * Jangan mensyaratkan ditarik=0 di tahap kandidat karena data
             * legacy bisa memiliki ditarik=1 tetapi ship_co kosong.
             * Row seperti itu harus tetap terlihat sebagai kandidat/anomali,
             * kecuali direct mapping di Tahap 2 membuktikan sudah billed.
             */
            ->group_start()
                ->where('i.ship_co', '')
                ->or_where('i.ship_co IS NULL', null, false)
            ->group_end()
            ->where('i.price !=', 0)
            ->not_like('i.item_service', 'TAX 1', 'after');

        /*
         * Jenis source yang termasuk tagihan belum dibuat:
         * - INVOICE
         * - DEBIT NOTE
         * - NULL / blank legacy dianggap INVOICE.
         *
         * CREDIT NOTE sengaja tidak dimasukkan ke Listing Job ini.
         */
        if ($this->has_invoice_db) {
            $this->dbr
                ->group_start()
                    ->where('i.invoice_db', 'INVOICE')
                    ->or_where('i.invoice_db', 'DEBIT NOTE')
                    ->or_where('i.invoice_db', '')
                    ->or_where('i.invoice_db IS NULL', null, false)
                ->group_end();
        }

        /*
         * Format kode_ref existing:
         * SUBSTRING(...,8,2)  = YY
         * SUBSTRING(...,10,2) = MM
         *
         * Memang bukan indexable, tetapi dieksekusi setelah index utama
         * sudah mengecilkan kandidat berdasarkan region/PREPAID/ditarik/ship_co.
         */
        if ($tahun !== '') {
            $this->dbr->where(
                'SUBSTRING(i.kode_ref, 8, 2) = ' . $this->dbr->escape($tahun),
                null,
                false
            );
        }

        if ($bulan !== '') {
            $this->dbr->where(
                'SUBSTRING(i.kode_ref, 10, 2) = ' . $this->dbr->escape($bulan),
                null,
                false
            );
        }

        /*
         * Jangan ORDER BY di database pada tahap kandidat.
         * Sorting dilakukan sekali di PHP setelah seluruh filtering selesai.
         */
        $q = $this->dbr->get();

        if ($q === false) {
            return array();
        }

        return $q->result_array();
    }

    /**
     * ================================================================
     * TAHAP 2
     * ================================================================
     * Cari kandidat yang ternyata SUDAH punya direct source mapping ke
     * invoice aktif/nyata.
     *
     * Tidak ada correlated EXISTS terhadap seluruh income table.
     * Kandidat income_id diproses batch/chunk.
     */
    private function fetch_directly_billed_income_ids($region, array $incomeIds)
    {
        $incomeIds = array_values(array_unique(array_map('intval', $incomeIds)));

        if (empty($incomeIds)) {
            return array();
        }

        $billed = array();

        foreach (array_chunk($incomeIds, $this->chunk_size) as $ids) {

            /*
             * source_income_id khusus inf_income_selling_price.
             */
            if ($this->has_source_income_id) {

                $rows = $this->dbr
                    ->select('fi.source_income_id AS income_id', false)
                    ->distinct()
                    ->from('x_finance_item fi')
                    ->join(
                        'x_finance f',
                        'f.id = fi.number AND f.region_id = fi.region_id',
                        'inner'
                    )
                    ->where('fi.region_id', $region)
                    ->where_in('f.jenis', array('INVOICE', 'DEBIT NOTE'))
                    ->where('fi.source_income_id IS NOT NULL', null, false)
                    ->where_in('fi.source_income_id', $ids)
                    ->get();

                if ($rows !== false) {
                    foreach ($rows->result_array() as $row) {
                        $id = (int)$row['income_id'];
                        if ($id > 0) {
                            $billed[$id] = true;
                        }
                    }
                }
            }

            /*
             * Generic direct mapping.
             */
            if (
                $this->has_income_source_table &&
                $this->has_income_source_id
            ) {
                $rows = $this->dbr
                    ->select('fi.income_source_id AS income_id', false)
                    ->distinct()
                    ->from('x_finance_item fi')
                    ->join(
                        'x_finance f',
                        'f.id = fi.number AND f.region_id = fi.region_id',
                        'inner'
                    )
                    ->where('fi.region_id', $region)
                    ->where_in('f.jenis', array('INVOICE', 'DEBIT NOTE'))
                    ->where('fi.income_source_table', 'inf_income_selling_price')
                    ->where('fi.income_source_id IS NOT NULL', null, false)
                    ->where_in('fi.income_source_id', $ids)
                    ->get();

                if ($rows !== false) {
                    foreach ($rows->result_array() as $row) {
                        $id = (int)$row['income_id'];
                        if ($id > 0) {
                            $billed[$id] = true;
                        }
                    }
                }
            }
        }

        return $billed;
    }

    /**
     * ================================================================
     * TAHAP 3
     * ================================================================
     * Validasi bahwa income masih memiliki shipment/job aktif.
     *
     * Job dianggap aktif bila masih ditemukan di trnstuffingplan ATAU
     * trnstuffingplandetail. Ini penting untuk child-job IMPORT yang tidak
     * selalu memiliki header exact di trnstuffingplan.
     *
     * Kasus legacy:
     * inf_income_selling_price dapat tertinggal walaupun shipment dibatalkan
     * dan row trnstuffingplan / trnstuffingplandetail sudah dihapus.
     * Income seperti ini adalah ORPHAN, bukan invoice-unbilled yang harus ditagih.
     *
     * PENTING PERFORMANCE:
     * - Jangan JOIN trnstuffingplan ke query kandidat utama.
     * - Kandidat sudah dipersempit oleh index current-state terlebih dahulu.
     * - Validasi job dilakukan batch WHERE IN() per chunk.
     */
    private function fetch_existing_job_map($region, array $kodeRefs)
    {
        $kodeRefs = array_values(array_unique(array_filter(
            array_map('strval', $kodeRefs),
            function ($v) {
                return trim($v) !== '';
            }
        )));

        if (empty($kodeRefs)) {
            return array();
        }

        $map = array();

        foreach (array_chunk($kodeRefs, $this->chunk_size) as $jobs) {

            /*
             * IMPORTANT:
             * Jangan hanya cek trnstuffingplan.
             *
             * Pada beberapa flow IMPORT, child job masih valid/aktif di
             * trnstuffingplandetail walaupun tidak mempunyai row exact dengan
             * kode_ref yang sama di trnstuffingplan. Jika hanya header yang
             * dicek, income valid akan salah dianggap ORPHAN dan hilang dari
             * Listing Job.
             *
             * Job dianggap masih valid bila ditemukan di SALAH SATU:
             * 1. trnstuffingplan
             * 2. trnstuffingplandetail
             *
             * Kasus shipment benar-benar dibatalkan tetap ter-filter karena
             * biasanya kedua row tersebut sudah tidak ada.
             */

            $qHeader = $this->dbr
                ->select('kode_ref')
                ->distinct()
                ->from('trnstuffingplan')
                ->where('region_id', $region)
                ->where_in('kode_ref', $jobs)
                ->get();

            if ($qHeader !== false) {
                foreach ($qHeader->result_array() as $row) {
                    $job = trim((string)$row['kode_ref']);

                    if ($job !== '') {
                        $map[$job] = true;
                    }
                }
            }

            $qDetail = $this->dbr
                ->select('kode_ref')
                ->distinct()
                ->from('trnstuffingplandetail')
                ->where('region_id', $region)
                ->where_in('kode_ref', $jobs)
                ->get();

            if ($qDetail !== false) {
                foreach ($qDetail->result_array() as $row) {
                    $job = trim((string)$row['kode_ref']);

                    if ($job !== '') {
                        $map[$job] = true;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * ================================================================
     * TAHAP 4
     * ================================================================
     * Ambil receive_from HANYA untuk job yang benar-benar lolos.
     *
     * Tidak GROUP BY seluruh trnstuffingplandetail.
     */
    private function fetch_receive_from_map($region, array $kodeRefs)
    {
        $kodeRefs = array_values(array_unique(array_filter(
            array_map('strval', $kodeRefs),
            function ($v) {
                return trim($v) !== '';
            }
        )));

        if (empty($kodeRefs)) {
            return array();
        }

        $map = array();

        foreach (array_chunk($kodeRefs, $this->chunk_size) as $jobs) {

            $q = $this->dbr
                ->select(
                    'kode_ref, MAX(shipper_print) AS receive_from',
                    false
                )
                ->from('trnstuffingplandetail')
                ->where('region_id', $region)
                ->where_in('kode_ref', $jobs)
                ->group_by('kode_ref')
                ->get();

            if ($q === false) {
                continue;
            }

            foreach ($q->result_array() as $row) {
                $map[(string)$row['kode_ref']] =
                    isset($row['receive_from'])
                        ? $row['receive_from']
                        : '';
            }
        }

        return $map;
    }

    /**
     * Shared engine untuk layar + PDF + Excel.
     */
    private function build_report_rows($region, $tahun = '', $bulan = '')
    {
        /*
         * 1. Kandidat berdasarkan current state.
         */
        $rows = $this->fetch_unbilled_candidates(
            $region,
            $tahun,
            $bulan
        );

        if (empty($rows)) {
            return array();
        }

        /*
         * 2. Exclude direct mapping yang membuktikan sudah billed.
         */
        $incomeIds = array();

        foreach ($rows as $row) {
            $incomeIds[] = (int)$row['income_id'];
        }

        $directlyBilled =
            $this->fetch_directly_billed_income_ids(
                $region,
                $incomeIds
            );

        if (!empty($directlyBilled)) {

            $rows = array_values(array_filter(
                $rows,
                function ($row) use ($directlyBilled) {
                    $id = (int)$row['income_id'];
                    return !isset($directlyBilled[$id]);
                }
            ));
        }

        if (empty($rows)) {
            return array();
        }

        /*
         * 3. Exclude ORPHAN income.
         *
         * Income lama bisa tertinggal walaupun shipment/job dibatalkan dan
         * trnstuffingplan sudah dihapus. Jangan ubah source menjadi BILLED;
         * cukup jangan tampilkan di LISTING JOB BELUM INVOICE.
         */
        $jobs = array();

        foreach ($rows as $row) {
            $jobs[] = (string)$row['kode_ref'];
        }

        $existingJobs =
            $this->fetch_existing_job_map($region, $jobs);

        $rows = array_values(array_filter(
            $rows,
            function ($row) use ($existingJobs) {
                $job = trim((string)$row['kode_ref']);
                return $job !== '' && isset($existingJobs[$job]);
            }
        ));

        if (empty($rows)) {
            return array();
        }

        /*
         * 4. receive_from hanya untuk job final yang masih ada.
         */
        $jobs = array();

        foreach ($rows as $row) {
            $jobs[] = (string)$row['kode_ref'];
        }

        $receiveMap =
            $this->fetch_receive_from_map($region, $jobs);

        foreach ($rows as &$row) {
            $job = (string)$row['kode_ref'];

            $row['receive_from'] =
                isset($receiveMap[$job])
                    ? $receiveMap[$job]
                    : '';
        }
        unset($row);

        /*
         * Sort final di memory.
         */
        usort(
            $rows,
            function ($a, $b) {
                $cmp = strcmp(
                    (string)$a['kode_ref'],
                    (string)$b['kode_ref']
                );

                if ($cmp !== 0) {
                    return $cmp;
                }

                return ((int)$a['income_id'])
                    <=> ((int)$b['income_id']);
            }
        );

        return $rows;
    }

    public function get_proses($data)
    {
        $region = trim((string)$this->session->userdata('region_id'));

        if ($region === '') {
            return array(
                'status' => 0,
                'bulan'  => isset($data['bulan']) ? $data['bulan'] : '',
                'rows'   => array(),
                'error'  => 'REGION_REQUIRED'
            );
        }

        $tahun = isset($data['tahun'])
            ? preg_replace('/[^0-9]/', '', (string)$data['tahun'])
            : '';

        $bulan = isset($data['bulan'])
            ? preg_replace('/[^0-9]/', '', (string)$data['bulan'])
            : '';

        $rows = $this->build_report_rows(
            $region,
            $tahun,
            $bulan
        );

        /*
         * Jangan simpan SQL monster lagi.
         * Simpan parameter report saja.
         */
        $this->save_report_params(
            $region,
            $tahun,
            $bulan
        );

        return array(
            'status' => count($rows),
            'bulan'  => isset($data['bulan']) ? $data['bulan'] : '',
            'rows'   => $rows
        );
    }

    /**
     * Simpan PARAMETER, bukan SQL kompleks.
     *
     * get_q2() akan membangun ulang report memakai engine 3 tahap yang sama.
     */
    private function save_report_params($region, $tahun, $bulan)
    {
        $userId = $this->session->userdata('user_id');

        $payload =
            'LISTING_JOB_V2|' .
            json_encode(array(
                'region' => (string)$region,
                'tahun'  => (string)$tahun,
                'bulan'  => (string)$bulan
            ));

        $qr = $this->dbx->get_where(
            'last_query',
            array('user_id' => $userId)
        );

        if ($qr->num_rows() > 0) {

            $this->dbx->update(
                'last_query',
                array('query' => $payload),
                array('user_id' => $userId)
            );

        } else {

            $this->dbx->insert(
                'last_query',
                array(
                    'user_id' => $userId,
                    'query'   => $payload
                )
            );
        }
    }

    /**
     * Export PDF / Excel.
     *
     * Untuk marker V2:
     * rebuild report memakai engine bertahap yang sama di REPLICA.
     *
     * Untuk record last_query lama yang masih berupa SELECT:
     * tetap didukung sebagai fallback dan dijalankan di REPLICA.
     */
    public function get_q2()
    {
        $data = $this->dbx->get_where(
            'last_query',
            array(
                'user_id' =>
                    $this->session->userdata('user_id')
            )
        );

        if ($data->num_rows() <= 0) {
            return false;
        }

        $stored = trim((string)$data->row()->query);

        if ($stored === '') {
            return false;
        }

        $prefix = 'LISTING_JOB_V2|';

        if (strpos($stored, $prefix) === 0) {

            $json = substr(
                $stored,
                strlen($prefix)
            );

            $params = json_decode($json, true);

            if (!is_array($params)) {
                return false;
            }

            /*
             * Region tetap wajib sama dengan session aktif.
             * Jangan percaya region yang tersimpan jika session berubah.
             */
            $region = trim(
                (string)$this->session->userdata('region_id')
            );

            if ($region === '') {
                return false;
            }

            return $this->build_report_rows(
                $region,
                isset($params['tahun'])
                    ? $params['tahun']
                    : '',
                isset($params['bulan'])
                    ? $params['bulan']
                    : ''
            );
        }

        /*
         * Backward compatibility untuk last_query lama.
         */
        if (stripos(ltrim($stored), 'SELECT') !== 0) {
            return false;
        }

        $query = $this->dbr->query($stored);

        if ($query === false) {
            return false;
        }

        return $query->result_array();
    }
}
