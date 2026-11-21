<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notifikasi booking baru (CRM + email).
 * Dipisah dari controller agar bisa dipakai Instant_booking maupun Booking_v2,
 * dan supaya kegagalan email tidak menggagalkan booking.
 */
#[\AllowDynamicProperties]
class Booking_notifier
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('booking_api', TRUE);
    }

    /**
     * @param array $ctx keys: booking_id, booking_code, house_bl, id_shipper,
     *                   header (array), detail (array), links (array), file_other (string)
     */
    public function notify_new_booking(array $ctx)
    {
        try {
            return $this->send($ctx);
        } catch (Throwable $e) {
            log_message('error', 'Booking_notifier: ' . $e->getMessage());
            return false;
        }
    }

    protected function cfg($key)
    {
        return $this->CI->config->item($key, 'booking_api');
    }

    protected function send(array $ctx)
    {
        // Tetap memakai koneksi default seperti kode lama (mstshipper, refsales, crm_notifications).
        if (!isset($this->CI->db)) {
            $this->CI->load->database();
        }
        $db = $this->CI->db;

        $result = $db->select('s.shipper_name, s.id_sales, r.user_id AS sales_user_id, r.email AS sales_email, r.management AS is_management')
            ->from('mstshipper s')
            ->join('refsales r', 's.id_sales = r.id', 'left')
            ->where('s.id_shipper', (int) $ctx['id_shipper'])
            ->get()->row_array();

        if (!$result) {
            return false;
        }

        $header = $ctx['header'];
        $detail = $ctx['detail'];
        $links  = $ctx['links'] ?? [];
        $code   = $ctx['booking_code'];
        $hbl    = $ctx['house_bl'];
        $shipperName = $result['shipper_name'] ?: 'Unknown Shipper';

        // Penerima
        if ((int) $result['id_sales'] === (int) $this->cfg('notify_overseas_sales_id')) {
            $to = $this->cfg('notify_overseas_emails');
        } elseif ((int) $result['is_management'] === 1) {
            $to = [$this->cfg('notify_management_email')];
        } else {
            $to = [!empty($result['sales_email']) ? $result['sales_email'] : $this->cfg('notify_fallback_email')];
        }

        // CRM
        if (!empty($result['sales_user_id'])) {
            $db->insert('crm_notifications', [
                'user_id'    => $result['sales_user_id'],
                'title'      => 'New Online Booking',
                'body'       => "New Booking [{$code}] to {$header['destination_city']} received from {$shipperName}",
                'link'       => '/app/bookings/view/' . (int) $ctx['booking_id'],
                'ref_type'   => 'BOOKING',
                'ref_id'     => (int) $ctx['booking_id'],
                'severity'   => 'HIGH',
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $apiKey = $this->cfg('elastic_email_api_key');
        if (!$apiKey) {
            log_message('error', 'Booking_notifier: ELASTIC_EMAIL_API_KEY belum diset, email dilewati.');
            return false;
        }

        $e = function ($v) {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        };

        $linkText = '-';
        if ($links) {
            $linkText = implode(', ', array_map(function ($l) {
                return $l['bc_no'] . ($l['hbl'] !== '' ? " (HBL {$l['hbl']})" : '');
            }, $links));
        }

        $rows = [
            'Booking Number'      => $code,
            'HBL'                 => $hbl,
            'Shipper'             => $shipperName,
            'Linked BC (coload)'  => $linkText,
            'Consignee'           => $detail['consignee_name'] ?: '-',
            'Port of Loading'     => $header['origin_city'] ?: '-',
            'Port of Destination' => $header['destination_city'] ?: '-',
            'ETD'                 => $header['expect_etd'] ?: '-',
            'Qty / Package'       => $detail['quantity'] . ' ' . $detail['package_type'],
            'Weight / Volume'     => $detail['weight'] . ' KGs / ' . $detail['volume'] . ' M3',
            'PEB'                 => $detail['peb_number'] ?? '-',
        ];

        $tr = '';
        $i = 0;
        foreach ($rows as $label => $value) {
            $bg = ($i++ % 2 === 0) ? " style='background-color:#f9f9f9;'" : '';
            $tr .= "<tr{$bg}><td style='width:40%;'><strong>{$e($label)}</strong></td><td>: {$e($value)}</td></tr>";
        }

        $html = "<div style='font-family:Arial,sans-serif;padding:20px;color:#333;'>"
              . "<h2 style='color:#0f4fd8;'>New Online Booking Alert</h2>"
              . "<p>Telah diterima booking baru via <strong>Gateway Customer Portal</strong>.</p>"
              . "<table border='0' cellpadding='6' cellspacing='0' style='width:100%;max-width:600px;border:1px solid #ddd;'>{$tr}</table>"
              . "<p>Silakan dicek dan ditindaklanjuti pada sistem CRM.</p></div>";

        $payload = [
            'Recipients' => ['To' => array_values($to)],
            'Content'    => [
                'Body'    => [['ContentType' => 'HTML', 'Charset' => 'utf-8', 'Content' => $html]],
                'From'    => $this->cfg('elastic_email_sender'),
                'Subject' => "New Online Booking: {$shipperName} [{$code}] - HBL {$hbl} - to {$header['destination_city']}",
            ],
        ];

        $fileOther = (string) ($ctx['file_other'] ?? '');
        if ($fileOther !== '' && preg_match('/^[a-f0-9]{32}\.pdf$/', $fileOther)) {
            $path = APPPATH . 'third_party/file/si_documents/' . $fileOther;
            if (is_file($path)) {
                $payload['Content']['Attachments'] = [[
                    'BinaryContent' => base64_encode(file_get_contents($path)),
                    'Name'          => 'SI_Document_' . $code . '.pdf',
                    'ContentType'   => 'application/pdf',
                ]];
            }
        }

        $ch = curl_init('https://api.elasticemail.com/v4/emails/transactional');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-ElasticEmail-ApiKey: ' . $apiKey],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            log_message('error', "Booking_notifier: ElasticEmail HTTP {$status} untuk {$code}");
            return false;
        }
        return true;
    }
}
