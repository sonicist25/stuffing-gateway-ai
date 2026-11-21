<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transvoyant_attribute_service
{
    protected $CI;
    protected $cfg;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->helper('transvoyant');
        $this->CI->load->library('Transvoyant_client');

        $this->CI->load->model('Transvoyant_shipment_sync_model');
        $this->CI->load->model('Transvoyant_attribute_log_model');

        $this->CI->config->load('transvoyant');

        $this->cfg = $this->CI->config->item('transvoyant');

        if (!is_array($this->cfg)) {
            $this->cfg = [];
        }
    }


    /**
     * =========================================================
     * BUILD PAYLOAD DARI tv_shipment_syncs
     * =========================================================
     */
    public function build_payload_from_sync(array $syncRow)
    {
        $customerReferenceNumber = tv_base_tracking_number(
            $syncRow['customer_reference_no']
                ?? $syncRow['tracking_number']
                ?? null
        );

        $payload = [
            'customerReferenceNumber' => $customerReferenceNumber,
            'carrier' => trim(
                (string)($syncRow['carrier'] ?? '')
            ),
        ];

        $optionalMap = [
            'carrierSCAC'   => 'carrier_scac',
            'bol'           => 'mbl',
            'vesselName'    => 'vessel_name',
            'voyage'        => 'voyage',
            'pol'           => 'pol',
            'pod'           => 'pod',
            'container'     => 'container_no',
            'containerType' => 'container_type',

            // IMPORTANT:
            // Firmenich / Transvoyant meminta case seperti ini.
            'houseAwb'      => 'hbl',
        ];

        foreach ($optionalMap as $apiField => $dbField) {

            $value = $syncRow[$dbField] ?? null;

            $value = is_string($value)
                ? trim($value)
                : $value;

            if ($value !== null && $value !== '') {
                $payload[$apiField] = $value;
            }
        }

        return $payload;
    }


    /**
     * =========================================================
     * BUILD PAYLOAD DARI BOOKING
     * =========================================================
     */
    public function build_payload_from_booking(
        array $postData = [],
        array $bookingResult = []
    ) {
        $tracking = null;

        $candidates = [
            $postData['si_number'] ?? null,
            $postData['customer_reference_no'] ?? null,
            $postData['shipment_no'] ?? null,

            $bookingResult['si_number'] ?? null,
            $bookingResult['customer_reference_no'] ?? null,
            $bookingResult['shipment_no'] ?? null,
        ];

        foreach ($candidates as $candidate) {

            $candidate = trim(
                (string)$candidate
            );

            if ($candidate !== '') {

                $tracking = tv_base_tracking_number(
                    $candidate
                );

                break;
            }
        }

        $payload = [
            'customerReferenceNumber' => $tracking,
            'carrier' => trim(
                (string)($postData['carrier'] ?? '')
            ),
        ];

        $map = [
            'carrierSCAC'   => 'carrier_scac',
            'bol'           => 'mbl',
            'vesselName'    => 'vessel_name',
            'voyage'        => 'voyage',
            'pol'           => 'pol',
            'pod'           => 'pod',
            'container'     => 'container_no',
            'containerType' => 'container_type',

            // Case-sensitive sesuai requirement Firmenich.
            'houseAwb'      => 'hbl',
        ];

        foreach ($map as $apiField => $postField) {

            $value = $postData[$postField] ?? null;

            $value = is_string($value)
                ? trim($value)
                : $value;

            if ($value !== null && $value !== '') {
                $payload[$apiField] = $value;
            }
        }

        return $payload;
    }


    /**
     * =========================================================
     * CLEAN OPTIONAL VALUE
     * =========================================================
     *
     * Field dari cron kadang berisi:
     *
     * NULL
     * N/A
     * -
     * 0
     *
     * Nilai seperti itu dianggap kosong.
     */
    protected function clean_optional_value($value)
    {
        $value = trim(
            (string)$value
        );

        if ($value === '') {
            return null;
        }

        $upper = strtoupper($value);

        $invalid = [
            'NULL',
            'N/A',
            'NA',
            'NONE',
            '-',
            '--',
            '0',
            'TBA',
            'TBC',
        ];

        if (in_array($upper, $invalid, true)) {
            return null;
        }

        return $value;
    }


    /**
     * =========================================================
     * DATABASE UNTUK x_tracking_export
     * =========================================================
     *
     * Aplikasi Gateway sering memakai $this->dbx untuk database
     * operational stuffing.
     *
     * Kalau dbx tersedia, gunakan dbx.
     * Kalau tidak, fallback ke default db.
     */
    protected function get_tracking_db()
    {
        if (
            isset($this->CI->dbx)
            && is_object($this->CI->dbx)
        ) {
            return $this->CI->dbx;
        }

        if (
            !isset($this->CI->db)
            || !is_object($this->CI->db)
        ) {
            $this->CI->load->database();
        }

        return $this->CI->db;
    }


    /**
     * =========================================================
     * AMBIL x_tracking_export
     * =========================================================
     *
     * HBL adalah key paling aman karena x_tracking_export
     * memiliki unique index hbl_no.
     */
    protected function get_tracking_export_row(
        array $syncRow
    ) {
        $hbl = $this->clean_optional_value(
            $syncRow['hbl'] ?? null
        );

        if (!$hbl) {
            return null;
        }

        $db = $this->get_tracking_db();

        $db->from('x_tracking_export');
        $db->where('hbl_no', $hbl);

        if (!empty($syncRow['shipper_id'])) {
            $db->where(
                'id_shipper',
                (string)$syncRow['shipper_id']
            );
        }

        $db->order_by(
            'id_tracking',
            'DESC'
        );

        return $db
            ->limit(1)
            ->get()
            ->row_array();
    }


    /**
     * =========================================================
     * DECODE JSON DFT / ATD / DLL
     * =========================================================
     */
    protected function decode_tracking_json($raw)
    {
        if (is_array($raw)) {
            return $raw;
        }

        $raw = trim(
            (string)$raw
        );

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode(
            $raw,
            true
        );

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }


    /**
     * =========================================================
     * CARI KEY PERTAMA YANG TERSEDIA DI JSON
     * =========================================================
     */
    protected function get_first_json_value(
        array $data,
        array $keys
    ) {
        foreach ($keys as $key) {

            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $this->clean_optional_value(
                $data[$key]
            );

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }


    /**
     * =========================================================
     * PARSE CONTAINER
     * =========================================================
     *
     * Contoh:
     *
     * FCIU7318009 / 0082279
     *
     * menjadi:
     *
     * container = FCIU7318009
     *
     *
     * Contoh:
     *
     * BSIU2624702 / 25H0588707 / 20 GP
     *
     * menjadi:
     *
     * container      = BSIU2624702
     * container_type = 20GP
     */
    protected function parse_container_info($raw)
    {
        $raw = trim(
            (string)$raw
        );

        if ($raw === '') {
            return [
                'container'      => null,
                'container_type' => null,
                'raw'            => null,
            ];
        }

        $parts = preg_split(
            '/\s*\/\s*/',
            $raw
        );

        $parts = array_values(
            array_filter(
                array_map(
                    'trim',
                    $parts
                ),
                function ($value) {
                    return $value !== '';
                }
            )
        );

        $container = null;

        if (!empty($parts[0])) {

            $container = strtoupper(
                preg_replace(
                    '/\s+/',
                    '',
                    $parts[0]
                )
            );
        }

        $containerType = null;

        /*
         * Cari:
         *
         * 20 GP
         * 20GP
         * 40 HC
         * 40HC
         * 40 HQ
         * dst.
         */
        if (
            preg_match(
                '/\b(20|40|45)\s*(GP|HC|HQ|DC|RF|OT|FR)\b/i',
                $raw,
                $m
            )
        ) {
            $containerType = strtoupper(
                $m[1] . $m[2]
            );
        }

        return [
            'container'      => $container,
            'container_type' => $containerType,
            'raw'            => $raw,
        ];
    }


    /**
     * =========================================================
     * PARSE VESSEL + VOYAGE
     * =========================================================
     *
     * Supported:
     *
     * INCRES V. 072N
     * HMM PLATINUM v.098E
     * CMA CGM KRYPTON v.0BDNRE1MA
     * TBN 805N
     * HUI WAN 306 260904
     * TIANJIN VOYAGER 2607N
     */
    protected function parse_vessel_voyage($raw)
    {
        $raw = trim(
            (string)$raw
        );

        if ($raw === '') {
            return [
                'vessel_name' => null,
                'voyage'      => null,
                'raw'         => null,
            ];
        }

        /*
         * Normalize multiple spaces.
         */
        $raw = preg_replace(
            '/\s+/',
            ' ',
            $raw
        );


        /**
         * -----------------------------------------------------
         * FORMAT EXPLICIT V / V.
         * -----------------------------------------------------
         *
         * INCRES V. 072N
         * HMM PLATINUM v.098E
         * CMA CGM KRYPTON v.0BDNRE1MA
         */
        if (
            preg_match(
                '/^(.*?)\s+[vV]\.?\s*(.+)$/u',
                $raw,
                $m
            )
        ) {
            return [
                'vessel_name' => trim($m[1]),
                'voyage'      => trim($m[2]),
                'raw'         => $raw,
            ];
        }


        /**
         * -----------------------------------------------------
         * FORMAT TANPA V.
         * -----------------------------------------------------
         *
         * TBN 805N
         * BELAWAN 2608N
         * TIANJIN VOYAGER 2607N
         * HUI WAN 306 260904
         *
         * Token terakhir dianggap voyage HANYA jika
         * mengandung angka.
         */
        if (
            preg_match(
                '/^(.*?)\s+([A-Z0-9\-\/]*\d[A-Z0-9\-\/]*)$/i',
                $raw,
                $m
            )
        ) {
            return [
                'vessel_name' => trim($m[1]),
                'voyage'      => trim($m[2]),
                'raw'         => $raw,
            ];
        }


        /**
         * Format tidak bisa dipastikan.
         *
         * Jangan menebak voyage.
         */
        return [
            'vessel_name' => $raw,
            'voyage'      => null,
            'raw'         => $raw,
        ];
    }


    /**
     * =========================================================
     * BUILD SECOND LEG
     * =========================================================
     *
     * Sumber:
     *
     * container:
     * x_tracking_export.dft -> Container No
     *
     * vessel/voyage:
     * x_tracking_export.dft / atd -> Vessel/Voyage
     *
     * optional:
     * 2nd_mbl_no
     * liner2nd
     * 2nd_hbl_no
     *
     *
     * CATATAN:
     *
     * 2nd_hbl_no TIDAK mengganti houseAwb.
     *
     * houseAwb harus tetap Firmenich shipment HBL.
     */
    protected function build_second_leg(array $syncRow)
{
    $row = $this->get_tracking_export_row($syncRow);

    if (!$row) {
        return null;
    }

    /*
     * =========================================================
     * BASIC ROUTING DATA
     * =========================================================
     */
    $connecting = $this->clean_optional_value(
        $row['connecting'] ?? null
    );

    $destination = $this->clean_optional_value(
        $row['destination'] ?? null
    );


    /*
     * =========================================================
     * DETECT FALSE SECOND LEG
     * =========================================================
     *
     * Ada shipment yang field `connecting` terisi,
     * tetapi sebenarnya connecting tersebut adalah FINAL POD.
     *
     * Contoh sync 666:
     *
     * connecting  = PORT KELANG
     * destination = PORT KELANG
     *
     * Shipment seperti ini SINGLE LEG.
     *
     * IMPORTANT:
     * Jangan bandingkan dengan consol_destination.
     *
     * Karena shipment transshipment yang valid dapat mempunyai:
     *
     * connecting          = SINGAPORE
     * consol_destination  = SINGAPORE
     * destination         = PENANG
     *
     * dan itu memang shipment 2 leg.
     */
    $normalizeLocation = function ($value) {
        $value = strtoupper(
            trim((string)$value)
        );

        /*
         * Samakan:
         *
         * PORT KELANG
         * PORT-KELANG
         * PORT  KELANG
         *
         * menjadi:
         *
         * PORTKELANG
         */
        return preg_replace(
            '/[^A-Z0-9]+/',
            '',
            $value
        );
    };

    $connectingNorm  = $normalizeLocation($connecting);
    $destinationNorm = $normalizeLocation($destination);

    /*
     * Connecting sama dengan final destination:
     * tidak ada second leg.
     */
    if (
        $connectingNorm !== ''
        && $destinationNorm !== ''
        && $connectingNorm === $destinationNorm
    ) {
        return null;
    }


    /*
     * =========================================================
     * TRACKING JSON
     * =========================================================
     */
    $dft = $this->decode_tracking_json(
        $row['dft'] ?? null
    );

    $atd = $this->decode_tracking_json(
        $row['atd'] ?? null
    );


    /*
     * =========================================================
     * SECOND CONTAINER
     * =========================================================
     */
    $containerRaw = $this->get_first_json_value(
        $dft,
        [
            'Container No',
            'Container No.',
            'Container Number',
            'Container',
        ]
    );


    /*
     * Shipment benar-benar memiliki connecting port yang
     * berbeda dengan destination, tetapi second container
     * belum diterima dari cron.
     *
     * Jangan close patch_status.
     * Cron berikutnya masih harus mencoba lagi.
     */
    if (!$containerRaw) {

        if ($connecting) {

            return [
                'valid'       => false,
                'pending'     => true,
                'reason'      => 'SECOND_LEG_CONTAINER_PENDING',

                'source'      => 'x_tracking_export',
                'id_tracking' => $row['id_tracking'] ?? null,

                'connecting'  => $connecting,

                'second_mbl'  => $this->clean_optional_value(
                    $row['2nd_mbl_no'] ?? null
                ),

                'second_hbl'  => $this->clean_optional_value(
                    $row['2nd_hbl_no'] ?? null
                ),

                'carrier'     => $this->clean_optional_value(
                    $row['liner2nd'] ?? null
                ),

                'dft'         => $dft,
                'atd'         => $atd,
            ];
        }

        /*
         * Tidak ada connecting dan tidak ada second container.
         * Berarti direct / single-leg shipment.
         */
        return null;
    }


    /*
     * =========================================================
     * PARSE SECOND CONTAINER
     * =========================================================
     */
    $containerInfo = $this->parse_container_info(
        $containerRaw
    );

    if (empty($containerInfo['container'])) {

        return [
            'valid'         => false,
            'pending'       => true,
            'reason'        => 'SECOND_LEG_CONTAINER_INVALID',

            'source'        => 'x_tracking_export',
            'id_tracking'   => $row['id_tracking'] ?? null,

            'connecting'    => $connecting,
            'raw_container' => $containerRaw,

            'dft'           => $dft,
            'atd'           => $atd,
        ];
    }


    /*
     * =========================================================
     * SECOND VESSEL / VOYAGE
     * =========================================================
     *
     * Prioritas:
     *
     * 1. DFT
     * 2. ATD
     *
     * Beberapa cron menyimpan:
     *
     * "Vessel/Voyage":"YUAN XIANG FA ZHAN 017N"
     */
    $vesselVoyageRaw = $this->get_first_json_value(
        $dft,
        [
            'Vessel/Voyage',
            'Vessel / Voyage',
            'Vessel Voyage',
        ]
    );

    if (!$vesselVoyageRaw) {

        $vesselVoyageRaw = $this->get_first_json_value(
            $atd,
            [
                'Vessel/Voyage',
                'Vessel / Voyage',
                'Vessel Voyage',
            ]
        );
    }


    /*
     * =========================================================
     * FALLBACK:
     * VESSEL DAN VOYAGE DISIMPAN TERPISAH
     * =========================================================
     */
    if (!$vesselVoyageRaw) {

        $secondVessel = $this->get_first_json_value(
            $atd,
            [
                'Vessel Name',
                'Vessel',
            ]
        );

        $secondVoyage = $this->get_first_json_value(
            $atd,
            [
                'Voyage',
                'Voyage No',
                'Voyage No.',
            ]
        );

        if ($secondVessel && $secondVoyage) {

            $vesselVoyage = [
                'vessel_name' => $secondVessel,
                'voyage'      => $secondVoyage,
                'raw'         => $secondVessel . ' ' . $secondVoyage,
            ];

        } else {

            $vesselVoyage = [
                'vessel_name' => null,
                'voyage'      => null,
                'raw'         => null,
            ];
        }

    } else {

        $vesselVoyage = $this->parse_vessel_voyage(
            $vesselVoyageRaw
        );
    }


    /*
     * =========================================================
     * SECOND VESSEL / VOYAGE BELUM TERSEDIA
     * =========================================================
     *
     * Jangan menggunakan vessel/voyage dari first leg.
     *
     * Second container sudah ada, tetapi second vessel harus
     * benar-benar berasal dari second leg.
     */
    if (
        empty($vesselVoyage['vessel_name'])
        || empty($vesselVoyage['voyage'])
    ) {

        return [
            'valid'          => false,
            'pending'        => true,
            'reason'         => 'SECOND_LEG_VESSEL_VOYAGE_PENDING',

            'container'      => $containerInfo['container'],
            'container_type' => $containerInfo['container_type'],

            'raw_container'  => $containerRaw,
            'raw_vessel'     => $vesselVoyageRaw,

            'source'         => 'x_tracking_export',
            'id_tracking'    => $row['id_tracking'] ?? null,

            'connecting'     => $connecting,

            'second_mbl'     => $this->clean_optional_value(
                $row['2nd_mbl_no'] ?? null
            ),

            'second_hbl'     => $this->clean_optional_value(
                $row['2nd_hbl_no'] ?? null
            ),

            'carrier'        => $this->clean_optional_value(
                $row['liner2nd'] ?? null
            ),

            'dft'            => $dft,
            'atd'            => $atd,
        ];
    }


    /*
     * =========================================================
     * OPTIONAL SECOND LEG DATA
     * =========================================================
     */
    $secondMbl = $this->clean_optional_value(
        $row['2nd_mbl_no'] ?? null
    );

    $secondHbl = $this->clean_optional_value(
        $row['2nd_hbl_no'] ?? null
    );

    $secondCarrier = $this->clean_optional_value(
        $row['liner2nd'] ?? null
    );


    /*
     * =========================================================
     * FIRST CONTAINER
     * =========================================================
     *
     * Hanya untuk debug / metadata:
     * apakah container berubah pada second leg.
     */
    $firstContainerInfo = $this->parse_container_info(
        $syncRow['container_no'] ?? ''
    );


    /*
     * =========================================================
     * SECOND LEG VALID
     * =========================================================
     */
    return [
        'valid'             => true,
        'pending'           => false,

        'container'         => $containerInfo['container'],
        'container_type'    => $containerInfo['container_type'],

        'vessel_name'       => $vesselVoyage['vessel_name'],
        'voyage'            => $vesselVoyage['voyage'],

        /*
         * Conditional data dari cron.
         */
        'bol'               => $secondMbl,
        'second_hbl'        => $secondHbl,
        'carrier'           => $secondCarrier,

        /*
         * Debug / metadata.
         */
        'first_container'   => $firstContainerInfo['container'],

        'container_changed' =>
            !empty($firstContainerInfo['container'])
            && strtoupper(
                $firstContainerInfo['container']
            )
            !== strtoupper(
                $containerInfo['container']
            ),

        'source'            => 'x_tracking_export',

        'id_tracking'       => $row['id_tracking'] ?? null,

        'connecting'        => $connecting,

        'dft'               => $dft,
        'atd'               => $atd,
    ];
}

    /**
     * =========================================================
     * SHOULD PATCH
     * =========================================================
     *
     * Hash seluruh payload.
     *
     * Karena container / vessel / voyage berbeda untuk leg 2,
     * hash leg 1 dan leg 2 otomatis berbeda.
     */
    public function should_patch(
        $syncId,
        array $payload
    ) {
        $hash = tv_hash_payload(
            $payload
        );

        $existing =
            $this->CI
                ->Transvoyant_attribute_log_model
                ->find_last_success_by_hash(
                    $syncId,
                    $hash
                );

        if ($existing) {
            return [
                false,
                $hash,
                'Payload hash sama, patch tidak perlu'
            ];
        }

        return [
            true,
            $hash,
            'Payload berubah atau belum pernah patch'
        ];
    }


    /**
     * =========================================================
     * PATCH SATU PAYLOAD
     * =========================================================
     */
    protected function patch_single_payload(
        $syncId,
        $bookingId,
        array $payload
    ) {
        list(
            $shouldPatch,
            $hash,
            $reason
        ) = $this->should_patch(
            $syncId,
            $payload
        );


        /**
         * Sudah pernah sukses.
         */
        if (!$shouldPatch) {

            return [
                'success'      => true,
                'skipped'      => true,
                'message'      => $reason,
                'payload_hash' => $hash,
                'payload'      => $payload,
            ];
        }


        /**
         * Simpan request log.
         */
        $logId =
            $this->CI
                ->Transvoyant_attribute_log_model
                ->insert_log([
                    'sync_id' =>
                        $syncId,

                    'booking_id' =>
                        $bookingId,

                    'customer_reference_no' =>
                        $payload[
                            'customerReferenceNumber'
                        ],

                    'payload_hash' =>
                        $hash,

                    'request_payload' =>
                        json_encode(
                            $payload,
                            JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                        ),

                    'status' =>
                        'PENDING',
                ]);


        /**
         * Kirim ke Transvoyant.
         */
        $result =
            $this->CI
                ->transvoyant_client
                ->patch_attributes(
                    $payload
                );


        /**
         * Update log.
         */
        $this->CI
            ->Transvoyant_attribute_log_model
            ->update_log(
                $logId,
                [
                    'response_http_code' =>
                        $result[
                            'http_code'
                        ] ?? null,

                    'response_body' =>
                        $result['body']
                        ?? (
                            $result['message']
                            ?? null
                        ),

                    'status' =>
                        !empty(
                            $result['success']
                        )
                        ? 'SUCCESS'
                        : 'FAILED',

                    'sent_at' =>
                        !empty(
                            $result['success']
                        )
                        ? date(
                            'Y-m-d H:i:s'
                        )
                        : null,
                ]
            );


        $result['log_id'] =
            $logId;

        $result['payload_hash'] =
            $hash;

        $result['payload'] =
            $payload;


        return $result;
    }


    /**
     * =========================================================
     * PATCH ATTRIBUTE
     * =========================================================
     *
     * Direct shipment:
     *
     *   1 shipment = 1 payload
     *
     *
     * Shipment dengan second leg:
     *
     *   1 shipment = 2 payload
     *
     *
     * Leg 2:
     *
     * container  -> DFT Container No
     * vessel     -> ATD Vessel/Voyage
     * voyage     -> ATD Vessel/Voyage
     *
     * Optional:
     *
     * bol        -> 2nd_mbl_no jika tersedia
     * carrier    -> liner2nd jika tersedia
     *
     *
     * houseAwb SELALU HBL Firmenich shipment asli.
     */
    public function patch_if_changed(
        array $syncRow
    ) {
        $syncId = (int)(
            $syncRow['id'] ?? 0
        );

        $bookingId =
            $syncRow['booking_id']
            ?? null;


        /**
         * =====================================================
         * LEG 1
         * =====================================================
         */
        $leg1Payload =
            $this->build_payload_from_sync(
                $syncRow
            );


        if (
            empty(
                $leg1Payload[
                    'customerReferenceNumber'
                ]
            )
        ) {
            return [
                'success' =>
                    false,

                'message' =>
                    'customerReferenceNumber kosong',
            ];
        }


        if (
            empty(
                $leg1Payload['carrier']
            )
        ) {
            return [
                'success' =>
                    false,

                'message' =>
                    'carrier kosong',
            ];
        }


        /**
         * -----------------------------------------------------
         * NORMALISASI CONTAINER LEG 1
         * -----------------------------------------------------
         *
         * FCIU7318009 / 0082279
         *
         * menjadi:
         *
         * FCIU7318009
         */
        if (
            !empty(
                $leg1Payload['container']
            )
        ) {
            $firstContainer =
                $this->parse_container_info(
                    $leg1Payload['container']
                );

            if (
                !empty(
                    $firstContainer[
                        'container'
                    ]
                )
            ) {
                $leg1Payload['container'] =
                    $firstContainer[
                        'container'
                    ];
            }


            /**
             * Hanya isi containerType dari parser kalau
             * sebelumnya belum tersedia.
             */
            if (
                empty(
                    $leg1Payload[
                        'containerType'
                    ]
                )
                && !empty(
                    $firstContainer[
                        'container_type'
                    ]
                )
            ) {
                $leg1Payload[
                    'containerType'
                ] =
                    $firstContainer[
                        'container_type'
                    ];
            }
        }


        $payloads = [];

        $payloads[] = [
            'leg'     => 1,
            'payload' => $leg1Payload,
        ];


        /**
         * =====================================================
         * DETECT SECOND LEG
         * =====================================================
         */
        $secondLeg =
            $this->build_second_leg(
                $syncRow
            );


        $secondLegPending = false;


        /**
         * Shipment connecting sudah diketahui tetapi data
         * second leg belum lengkap.
         *
         * Kita tetap boleh mengirim leg 1.
         *
         * Tetapi JANGAN mark attribute selesai, supaya
         * cron berikutnya dapat mencoba second leg lagi.
         */
        if (
            is_array($secondLeg)
            && !empty(
                $secondLeg['pending']
            )
        ) {
            $secondLegPending = true;
        }


        /**
         * =====================================================
         * BUILD LEG 2
         * =====================================================
         */
        if (
            is_array($secondLeg)
            && !empty(
                $secondLeg['valid']
            )
        ) {
            /**
             * Clone shipment-level attributes dari leg 1.
             */
            $leg2Payload =
                $leg1Payload;


            /**
             * -------------------------------------------------
             * CONTAINER
             * -------------------------------------------------
             */
            $leg2Payload['container'] =
                $secondLeg['container'];


            /**
             * -------------------------------------------------
             * VESSEL
             * -------------------------------------------------
             */
            $leg2Payload['vesselName'] =
                $secondLeg['vessel_name'];


            /**
             * -------------------------------------------------
             * VOYAGE
             * -------------------------------------------------
             */
            $leg2Payload['voyage'] =
                $secondLeg['voyage'];


            /**
             * -------------------------------------------------
             * CONTAINER TYPE
             * -------------------------------------------------
             *
             * Jangan mewarisi containerType leg 1 jika
             * containerType leg 2 tidak diketahui.
             */
            if (
                !empty(
                    $secondLeg[
                        'container_type'
                    ]
                )
            ) {
                $leg2Payload[
                    'containerType'
                ] =
                    $secondLeg[
                        'container_type'
                    ];

            } else {

                unset(
                    $leg2Payload[
                        'containerType'
                    ]
                );
            }


            /**
             * -------------------------------------------------
             * SECOND MBL
             * -------------------------------------------------
             *
             * Conditional:
             *
             * kalau 2nd_mbl_no valid:
             *     pakai second MBL
             *
             * kalau kosong:
             *     tetap pakai BOL leg 1
             */
            if (
                !empty(
                    $secondLeg['bol']
                )
            ) {
                $leg2Payload['bol'] =
                    $secondLeg['bol'];
            }


            /**
             * -------------------------------------------------
             * SECOND CARRIER
             * -------------------------------------------------
             *
             * Conditional:
             *
             * liner2nd tersedia:
             *     carrier = liner2nd
             *
             * liner2nd kosong:
             *     carrier tetap carrier leg 1
             */
            if (
                !empty(
                    $secondLeg['carrier']
                )
            ) {
                $oldCarrier = trim(
                    (string)(
                        $leg2Payload[
                            'carrier'
                        ] ?? ''
                    )
                );

                $newCarrier = trim(
                    (string)$secondLeg[
                        'carrier'
                    ]
                );


                $leg2Payload['carrier'] =
                    $newCarrier;


                /**
                 * Jika carrier berubah, carrierSCAC leg 1
                 * tidak boleh ikut karena bisa salah.
                 *
                 * Lebih aman tidak mengirim SCAC daripada
                 * mengirim SCAC milik carrier yang salah.
                 */
                if (
                    strcasecmp(
                        $oldCarrier,
                        $newCarrier
                    ) !== 0
                ) {
                    unset(
                        $leg2Payload[
                            'carrierSCAC'
                        ]
                    );
                }
            }


            /**
             * -------------------------------------------------
             * HOUSE AWB
             * -------------------------------------------------
             *
             * JANGAN diubah menggunakan 2nd_hbl_no.
             *
             * houseAwb tetap:
             *
             * JMXZLO-GCL2606563
             * JCNSHA-GCL2606585
             * dll.
             */


            $payloads[] = [
                'leg' =>
                    2,

                'payload' =>
                    $leg2Payload,

                'source' => [
                    'id_tracking' =>
                        $secondLeg[
                            'id_tracking'
                        ] ?? null,

                    'second_hbl' =>
                        $secondLeg[
                            'second_hbl'
                        ] ?? null,

                    'connecting' =>
                        $secondLeg[
                            'connecting'
                        ] ?? null,
                ],
            ];
        }


        /**
         * =====================================================
         * SEND PAYLOADS
         * =====================================================
         */
        $results = [];

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $lastHash = null;

        $errors = [];


        foreach ($payloads as $item) {

            $leg =
                (int)$item['leg'];

            $payload =
                $item['payload'];


            $result =
                $this->patch_single_payload(
                    $syncId,
                    $bookingId,
                    $payload
                );


            $result['leg'] =
                $leg;


            if (
                isset(
                    $item['source']
                )
            ) {
                $result['source'] =
                    $item['source'];
            }


            $results[] =
                $result;


            if (
                !empty(
                    $result[
                        'payload_hash'
                    ]
                )
            ) {
                $lastHash =
                    $result[
                        'payload_hash'
                    ];
            }


            /**
             * Request gagal.
             */
            if (
                empty(
                    $result['success']
                )
            ) {
                $failed++;


                $errors[] =
                    'LEG '
                    . $leg
                    . ': '
                    . (
                        $result['body']
                        ?? $result['message']
                        ?? 'Patch attribute gagal'
                    );


                continue;
            }


            /**
             * Sudah pernah dikirim.
             */
            if (
                !empty(
                    $result['skipped']
                )
            ) {
                $skipped++;

            } else {

                $sent++;
            }
        }


        /**
         * =====================================================
         * FINAL STATUS
         * =====================================================
         */
        $complete = false;


        /**
         * Ada request Transvoyant yang gagal.
         */
        if ($failed > 0) {

            $errorMessage =
                implode(
                    ' | ',
                    array_unique(
                        $errors
                    )
                );


            $this->CI
                ->Transvoyant_shipment_sync_model
                ->mark_patch_failed(
                    $syncId,
                    $errorMessage
                );


        /**
         * Second leg diketahui akan ada tetapi data cron
         * belum lengkap.
         *
         * IMPORTANT:
         *
         * JANGAN mark_attr_patched().
         *
         * Dengan begitu status masih dapat dicoba lagi
         * ketika cron sudah memasukkan:
         *
         * DFT Container No
         * ATD Vessel/Voyage
         */
        } elseif ($secondLegPending) {

            $complete = false;


        /**
         * Semua payload berhasil / sudah pernah dikirim.
         */
        } else {

            $complete = true;


            if (!empty($lastHash)) {

                $this->CI
                    ->Transvoyant_shipment_sync_model
                    ->mark_attr_patched(
                        $syncId,
                        $lastHash
                    );
            }
        }


        /**
         * =====================================================
         * RESPONSE
         * =====================================================
         */
        return [
            'success' =>
                ($failed === 0),

            'complete' =>
                $complete,

            'sync_id' =>
                $syncId,

            'leg_count' =>
                count($payloads),

            'payload_count' =>
                count($payloads),

            'sent' =>
                $sent,

            'skipped' =>
                $skipped,

            'failed' =>
                $failed,

            'pending_second_leg' =>
                $secondLegPending,

            'second_leg' =>
                $secondLeg,

            'results' =>
                $results,
        ];
    }
}