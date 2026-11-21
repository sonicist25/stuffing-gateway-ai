<?php
#[\AllowDynamicProperties]
class M_invoice_api extends CI_Model
{
    /** @var CI_DB_query_builder */
    protected $dbx;

    public function __construct()
    {
        parent::__construct();

        // Dipertahankan sesuai struktur database aplikasi saat ini.
        $tahun = '2026';

        if ($tahun === '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', true);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), true);
        }
    }

    public function not_found_response()
    {
        return [
            'status'  => 404,
            'error'   => true,
            'message' => 'Data tidak ditemukan',
            'data'    => [],
        ];
    }

    /**
     * Ambil invoice per shipper dan rentang tanggal invoice (bukan tanggal ETD).
     * Tanpa filter tetap memakai database 2026 seperti implementasi sebelumnya.
     * Dengan filter, database per tahun diambil memakai switch_db($year).
     * Struktur data.error / data.message / data.data[] dipertahankan.
     */
    public function get_list_invoice($shipperIds, $dateFrom = '', $dateTo = '')
    {
        $shipperIds = $this->normalizeShipperIds($shipperIds);
        if (empty($shipperIds)) {
            return [
                'error' => false,
                'message' => 'No invoice found',
                'data' => [],
            ];
        }

        $dateFrom = trim((string) $dateFrom);
        $dateTo = trim((string) $dateTo);
        $startYear = $dateFrom !== '' ? (int) substr($dateFrom, 0, 4)
            : ($dateTo !== '' ? (int) substr($dateTo, 0, 4) : 2026);
        $endYear = $dateTo !== '' ? (int) substr($dateTo, 0, 4) : $startYear;
        if ($endYear < $startYear || $endYear - $startYear > 5) {
            throw new InvalidArgumentException('Rentang tahun invoice tidak valid.');
        }

        $allRows = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $dbx = ($year === 2026)
                ? $this->dbx
                : $this->load->database(switch_db((string) $year), true);
            $rows = $this->fetchInvoicesForYear($dbx, $year, $shipperIds, $dateFrom, $dateTo);
            foreach ($rows as $row) {
                $allRows[] = $row;
            }
        }

        usort($allRows, static function ($a, $b) {
            $dateA = preg_replace('/[^0-9]/', '', (string) ($a['invoice_date'] ?? ''));
            $dateB = preg_replace('/[^0-9]/', '', (string) ($b['invoice_date'] ?? ''));
            $dateCompare = strcmp(substr($dateB, 0, 8), substr($dateA, 0, 8));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return (int) $b['id'] <=> (int) $a['id'];
        });

        return [
            'error' => false,
            'message' => empty($allRows) ? 'No invoice found' : 'Successfully retrieved invoice list',
            'data' => $allRows,
        ];
    }

    /** @param CI_DB_query_builder $dbx */
    private function fetchInvoicesForYear($dbx, $year, $shipperIds, $dateFrom, $dateTo)
    {
        $dbx->select([
            'x_finance.id AS id',
            'x_finance.job_no AS job_no',
            'x_finance.jenis AS jenis',
            'x_finance.kepada_kode AS kepada_kode',
            'x_finance.group AS `group`',
            'x_finance.se_exportir AS si_shipper',
            'x_finance.number AS number',
            'x_finance.date AS date',
            'x_finance.status AS status',
            'x_finance.currency AS currency',
            'x_finance.grand_total AS grand_total',
            'x_finance.region_id AS region_id',
            'trnstuffingplandetail.shipper AS id_shipper',
            // Jenis service berasal dari master job, BUKAN x_finance.jenis (jenis invoice).
            'trnstuffingplan.type_of_trans AS type_of_trans',
            'trnstuffingplandetail.booking_code AS booking_code',
            'trnstuffingplandetail.note AS note',
            'trnstuffingplandetail.destination AS destination',
            'trnstuffingplandetail.hb_l AS bl',
            'x_invoice.shipper_name AS shipper_name',
            'x_invoice.shipper_address AS shipper_address',
            'x_invoice.nama AS nama',
            'x_invoice.alamat AS alamat',
        ], false)
            ->from('trnstuffingplandetail')
            ->join('x_finance', 'x_finance.job_no = trnstuffingplandetail.kode_ref', 'inner')
            ->join('trnstuffingplan', 'trnstuffingplan.sp_id = trnstuffingplandetail.sp_id', 'left')
            ->join('x_invoice', 'x_invoice.number = x_finance.number', 'left')
            ->where_in('trnstuffingplandetail.shipper', $shipperIds);

        // x_finance.date pada instalasi lama dapat berupa YYYYMMDD atau DATE/DATETIME.
        // Normalisasi ke YYYYMMDD membuat kedua format dapat difilter tanpa mengganti tipe kolom.
        $normalizedDate = "REPLACE(LEFT(CAST(x_finance.date AS CHAR), 10), '-', '')";
        if ($dateFrom !== '') {
            $dbx->where($normalizedDate . ' >= ' . $dbx->escape(str_replace('-', '', $dateFrom)), null, false);
        }
        if ($dateTo !== '') {
            $dbx->where($normalizedDate . ' <= ' . $dbx->escape(str_replace('-', '', $dateTo)), null, false);
        }

        $query = $dbx->order_by('x_finance.date', 'DESC')
            ->order_by('x_finance.id', 'DESC')
            ->get();
        if (!$query) {
            $error = $dbx->error();
            throw new RuntimeException('Query daftar invoice gagal: ' . ($error['message'] ?? 'unknown error'));
        }

        // Pastikan satu invoice dihitung sekali walaupun join ke x_invoice/detail terduplikasi.
        $uniqueInvoices = [];
        foreach ($query->result() as $row) {
            $key = (string) $row->id;
            if (!isset($uniqueInvoices[$key])) {
                $uniqueInvoices[$key] = $row;
            }
        }
        $invoiceRows = array_values($uniqueInvoices);
        $shipmentMaps = $this->getFirmenichShipmentMaps($dbx, $invoiceRows);
        $rows = [];

        foreach ($invoiceRows as $row) {
            $shipperId = (int) $row->id_shipper;
            $billTo = !empty($row->nama) ? $row->nama : $row->shipper_name;
            $alamat = !empty($row->alamat) ? $row->alamat : $row->shipper_address;
            $bookingKey = $shipperId . '|' . trim((string) $row->booking_code);
            $jobKey = $shipperId . '|' . trim((string) $row->job_no);
            $shipmentNumber = '';

            if (in_array($shipperId, [4407, 6412], true)) {
                $shipmentNumber = $shipmentMaps['tracking'][$jobKey]
                    ?? $shipmentMaps['booking'][$bookingKey]
                    ?? '';
            }

            // Gunakan job_no internal untuk URL PDF, bukan shipment number pelanggan.
            $downloadParams = [
                'id'             => $row->id,
                'kode_ref'       => $row->job_no,
                'jenis'          => $row->jenis,
                'invoice_date'   => $row->date,
                'kepada'         => $billTo,
                'si_number'      => $row->si_shipper,
                'region_id'      => $row->region_id,
                'invoice_number' => $row->number,
                'kepada_pajak'   => $alamat,
                'X-API-KEY'      => 'gateway-fms',
            ];

            $rows[] = [
                'id'              => (int) $row->id,
                'invoice_year'    => (int) $year,
                'id_shipper'      => $shipperId,
                'type_of_trans'   => trim((string) ($row->type_of_trans ?? '')),
                'job_no'          => $row->job_no,
                'shipment_number' => $shipmentNumber,
                'jenis'           => $row->jenis,
                'kepada'          => $row->note,
                'si_number'       => $row->si_shipper,
                'hbl'             => $row->bl,
                'invoice_number'  => $row->number,
                'invoice_date'    => $row->date,
                'booking_code'    => $row->booking_code,
                'destination'     => $row->destination,
                'status'          => $this->normalizeInvoiceStatus($row->status),
                'aging'           => $this->calculateAging($row->date),
                'grand_total'     => ($row->grand_total === null || $row->grand_total === '') ? null : (float) $row->grand_total,
                'currency'        => $this->normalizeInvoiceCurrency($row->currency),
                'currency_inferred' => $this->isInvoiceCurrencyMissing($row->currency),
                'bill_to'         => $billTo,
                'alamat'          => $alamat,
                'url'             => 'https://gateway-cl.com/finance/c_download_invoice/draft?'
                    . http_build_query($downloadParams, '', '&', PHP_QUERY_RFC3986),
            ];
        }
        return $rows;
    }

    /**
     * Firmenich (4407/6412): shipment_no = nomor SI/customer shipment mereka.
     * Prioritas: x_tracking_export.id_request (sesuai report Firmenich),
     * lalu trnstuffingplan_booking.si_number sebagai fallback.
     * Tidak mengambil HBL, BL, atau nomor job sebagai fallback.
     * Mapping di-batch, bukan query tambahan per invoice (N+1).
     */
    private function getFirmenichShipmentMaps($dbx, $invoiceRows)
    {
        $bookingCodes = [];
        $jobNos = [];
        foreach ($invoiceRows as $row) {
            if (!in_array((int) $row->id_shipper, [4407, 6412], true)) {
                continue;
            }
            $bookingCode = trim((string) $row->booking_code);
            $jobNo = trim((string) $row->job_no);
            if ($bookingCode !== '') {
                $bookingCodes[$bookingCode] = $bookingCode;
            }
            if ($jobNo !== '') {
                $jobNos[$jobNo] = $jobNo;
            }
        }

        $maps = ['booking' => [], 'tracking' => []];
        foreach (array_chunk(array_values($bookingCodes), 300) as $chunk) {
            $query = $dbx->select('booking_code, shipper_id, si_number')
                ->from('trnstuffingplan_booking')
                ->where_in('shipper_id', [4407, 6412])
                ->where_in('booking_code', $chunk)
                ->order_by('id', 'DESC')
                ->get();
            if (!$query) {
                $error = $dbx->error();
                throw new RuntimeException('Query SI booking gagal: ' . ($error['message'] ?? 'unknown error'));
            }
            foreach ($query->result() as $booking) {
                $number = $this->cleanFirmenichShipmentNumber($booking->si_number);
                $key = (int) $booking->shipper_id . '|' . trim((string) $booking->booking_code);
                if ($number !== '' && !isset($maps['booking'][$key])) {
                    $maps['booking'][$key] = $number;
                }
            }
        }

        foreach (array_chunk(array_values($jobNos), 300) as $chunk) {
            $query = $dbx->select('no_job, id_shipper, id_request')
                ->from('x_tracking_export')
                ->where_in('id_shipper', [4407, 6412])
                ->where_in('no_job', $chunk)
                ->get();
            if (!$query) {
                $error = $dbx->error();
                throw new RuntimeException('Query fallback SI tracking gagal: ' . ($error['message'] ?? 'unknown error'));
            }
            foreach ($query->result() as $tracking) {
                $number = $this->cleanFirmenichShipmentNumber($tracking->id_request);
                $key = (int) $tracking->id_shipper . '|' . trim((string) $tracking->no_job);
                if ($number !== '' && $number !== '0' && !isset($maps['tracking'][$key])) {
                    $maps['tracking'][$key] = $number;
                }
            }
        }

        return $maps;
    }

    /** Ambil nomor saja jika source id_request berisi teks SHIPMENT / NO PO. */
    private function cleanFirmenichShipmentNumber($raw)
    {
        $value = trim((string) $raw);
        if ($value !== '' && preg_match('/\bSHIPMENT(?:\s+(?:NO|NUMBER))?\s*[:#=\-]?\s*([0-9]{5,15})\b/i', $value, $matches)) {
            return $matches[1];
        }
        return $value;
    }

    private function normalizeShipperIds($shipperIds)
    {
        if (!is_array($shipperIds)) {
            $shipperIds = [$shipperIds];
        }

        $normalized = [];
        foreach ($shipperIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    /**
     * Pada instalasi GCL ini, x_finance.currency dapat kosong walau
     * grand_total invoice dicatat dalam rupiah. Default IDR hanya
     * dipakai bila currency kosong/tidak diisi; USD/SGD/dll dipertahankan.
     * Periksa sampel invoice PDF sebelum menerapkan asumsi ini secara luas.
     */
    private function isInvoiceCurrencyMissing($currency)
    {
        $value = strtoupper(trim((string) $currency));
        return in_array($value, ['', '0', 'NULL', 'N/A', '-', 'NONE'], true);
    }

    private function normalizeInvoiceCurrency($currency)
    {
        $value = strtoupper(trim((string) $currency));
        if ($this->isInvoiceCurrencyMissing($value)) {
            return 'IDR';
        }
        if (in_array($value, ['RP', 'RUPIAH', 'INDONESIAN RUPIAH'], true)) {
            return 'IDR';
        }
        if (in_array($value, ['US$', 'US DOLLAR', 'DOLLAR USD'], true)) {
            return 'USD';
        }
        return $value;
    }

    private function normalizeInvoiceStatus($status)
    {
        $status = trim((string) $status);
        $lower = strtolower($status);

        if ($lower === 'paid') {
            return 'paid';
        }

        if ($lower === 'unpaid') {
            return 'unpaid';
        }

        return $status;
    }

    private function calculateAging($rawDate)
    {
        $rawDate = trim((string) $rawDate);
        if ($rawDate === '') {
            return 0;
        }

        $date = false;
        foreach (['!Ymd', '!Y-m-d', '!Y-m-d H:i:s'] as $format) {
            $date = DateTime::createFromFormat($format, $rawDate);
            if ($date instanceof DateTime) {
                break;
            }
        }

        if (!$date instanceof DateTime) {
            $timestamp = strtotime($rawDate);
            if ($timestamp === false) {
                return 0;
            }
            $date = (new DateTime())->setTimestamp($timestamp);
        }

        return (int) (new DateTime('today'))->diff($date)->days;
    }

    public function get_kepada_kode($username)
    {
        $this->dbx->select('shipper_id');
        $this->dbx->from('mstshipper');
        $this->dbx->where('shipper_name', $username);
        $query = $this->dbx->get();
        return $query->row('shipper_id');
    }

    public function get_data_invoice($id)
    {
        $q = $this->dbx->get_where('x_finance', ['id' => (int) $id]);
        if ($q->num_rows() <= 0) {
            return $this->not_found_response();
        }

        $finance = $q->row();
        $body = [
            'error'           => false,
            'message'         => 'Successfully retrieved invoice data',
            'invoice_no'      => $finance->number,
            'job_console_ref' => substr($finance->job_no, 0, 15),
        ];

        // Default kurs mencegah undefined variable ketika kepada bukan SHIPPER/CONSIGNEE.
        $kurs = 1.0;

        if ($finance->kepada === 'SHIPPER' || $finance->kepada === 'CONSIGNEE') {
            $body['ocean_freight'] = 'PREPAID';

            $mssrs = $this->dbx
                ->get_where('x_invoice', ['number' => $body['invoice_no']])
                ->row();

            if ($mssrs) {
                $body['mssrs'] = trim((string) $mssrs->shipper_name)
                    . "\n"
                    . trim((string) $mssrs->shipper_address);
                $body['job_console_customer'] = $mssrs->shipper_name;
            }

            $headerInvDB = $this->dbx
                ->select([
                    'trnstuffingplandetail.no_of_pack AS party',
                    'trnstuffingplandetail.weight AS gwt',
                    'trnstuffingplandetail.bl_meas AS cbm',
                    'tbl_detail.no_from_shipper AS si_shipper',
                    'CONCAT(trnstuffingplan.vessel, " ", trnstuffingplan.voyage) AS vessel',
                    'trnstuffingplan.tanggal_job AS date',
                    'DATE_FORMAT(x_finance.date, "%d-%m-%Y") AS etd',
                    'trnstuffingplan.port_loading AS pol',
                    'trnstuffingplandetail.hb_l AS bl_number',
                    'trnstuffingplandetail.kode_ref AS mbl_number',
                    'trnstuffingplan.kurs AS kurs',
                ], false)
                ->from('trnstuffingplandetail')
                ->join(
                    'x_finance',
                    'x_finance.job_no = trnstuffingplandetail.kode_ref',
                    'left'
                )
                ->join(
                    'trnstuffingplan',
                    'trnstuffingplan.sp_id = trnstuffingplandetail.sp_id',
                    'left'
                )
                ->join(
                    'tbl_detail',
                    'tbl_detail.sp_id = trnstuffingplandetail.sp_id',
                    'left'
                )
                ->where('trnstuffingplandetail.kode_ref', $finance->job_no)
                ->limit(1)
                ->get();

            if ($headerInvDB->num_rows() > 0) {
                $header = $headerInvDB->row_array();
                $kurs = !empty($header['kurs']) ? (float) $header['kurs'] : 1.0;
                $body['header'] = array_merge($header, $body);
            }
        }

        $itemsQuery = $this->dbx
            ->select([
                "IF((x_finance_item.jenis = 'DEBIT NOTE' AND x_finance_item.bl_no != '') OR x_finance.kepada = 'WAREHOUSE', CONCAT(x_finance_item.item, ' ', x_finance_item.bl_no), x_finance_item.item) AS detail_of_charge",
                'x_finance_item.qty AS qty',
                "IF(x_finance_item.currency = 'USD', x_finance_item.unit_price * " . (float) $kurs . ", x_finance_item.unit_price) AS rate",
                'IF(x_finance_item.anak_job IS NOT NULL, x_finance_item.anak_job, x_finance_item.kode_ref) AS ref_number',
            ], false)
            ->from('x_finance_item')
            ->join('x_finance', 'x_finance.id = x_finance_item.number', 'left')
            ->where('x_finance_item.number', $finance->id)
            ->get();

        $items = [];
        foreach ($itemsQuery->result_array() as $res) {
            $items[] = [
                'detail_of_charge' => $res['detail_of_charge'],
                'qty'              => (float) $res['qty'],
                'rate'             => (float) $res['rate'],
                'ref_number'       => $res['ref_number'],
            ];
        }

        $body['items'] = $items;
        $body['error'] = false;
        $body['message'] = 'Successfully get invoice';

        return $body;
    }

    public function get_all($invoice_id)
    {
        $query = $this->dbx
            ->select('number, date, jenis, currency, tgl_bayar, grand_total, status')
            ->from('x_finance')
            ->where('number', $invoice_id)
            ->get();

        $result = $query->result_array();

        foreach ($result as &$row) {
            if (!empty($row['date'])) {
                $row['date'] = date('Y-m-d', strtotime($row['date']));
            }
            if (isset($row['tgl_bayar']) && $row['tgl_bayar'] === '0000-00-00') {
                $row['tgl_bayar'] = null;
            }
        }
        unset($row);

        return $result;
    }
}
