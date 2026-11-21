<?php
class M_list_invoice extends CI_Model
{
    public $dbx;

    public function __construct()
    {
        parent::__construct();

        $tahun = $this->session->userdata('db');

        if ($tahun == '' || !is_numeric($tahun)) {
            $this->dbx = $this->load->database('master', TRUE);
        } else {
            $this->dbx = $this->load->database(switch_db($tahun), TRUE);
        }
    }

    function get_table_inv($post, $limit)
    {
        $this->dbx->select([
            'x_finance.id as id',
            'x_finance.number as number',
            'x_finance.group as group',
            'x_finance.date as date',
            'x_finance.job_no as job_no',
            'x_finance.kepada as kepada',
            'x_finance.kepada_kode as kepada_kode',
            'x_finance.currency as currency',
            'x_finance.kurs as kurs',
            'x_finance.status as status',
            'x_finance.grand_total as grand_total',
            'x_finance.ppn as nilai_ppn',
            'x_finance.tarif_ppn as tarif_ppn',
            'x_finance.fin_proses as fin_proses',
            'x_finance.region_id as region_id',
            'x_invoice.shipper_name as shipper_name',
            'x_invoice.number as number_finance',
        ]);

        $this->dbx->join(
            'x_invoice',
            'x_invoice.number = x_finance.number AND x_invoice.region_id = x_finance.region_id',
            'left'
        );

        if (!empty($post['filters'])) {
            $filters = json_decode($post['filters'], true);

            if (isset($filters['rules']) && is_array($filters['rules'])) {
                foreach ($filters['rules'] as $rule) {
                    $field = isset($rule['field']) ? $rule['field'] : '';
                    $op    = isset($rule['op']) ? $rule['op'] : 'eq';
                    $data  = isset($rule['data']) ? $rule['data'] : '';

                    if ($field == 'fin_proses') $field = 'x_finance.fin_proses';
                    if ($field == 'number') $field = 'x_finance.number';

                    if ($field === '') {
                        continue;
                    }

                    $opsMap = [
                        'eq' => '=', 'ne' => '<>', 'lt' => '<', 'le' => '<=',
                        'gt' => '>', 'ge' => '>=', 'cn' => 'LIKE', 'nc' => 'NOT LIKE'
                    ];

                    $sqlOp = isset($opsMap[$op]) ? $opsMap[$op] : '=';

                    if ($sqlOp == 'LIKE' || $sqlOp == 'NOT LIKE') {
                        $data = "%{$data}%";
                    }

                    $this->dbx->where("{$field} {$sqlOp}", $data);
                }
            }
        } elseif (isset($post['_search']) && $post['_search'] == 'true') {
            $searchField  = isset($post['searchField']) ? $post['searchField'] : '';
            $searchString = isset($post['searchString']) ? $post['searchString'] : '';
            $searchOper   = isset($post['searchOper']) ? $post['searchOper'] : 'eq';

            if ($searchField == 'number') $searchField = 'x_finance.number';

            if ($searchField !== '') {
                $opsMap = [
                    'eq' => '=', 'ne' => '<>', 'bw' => 'LIKE', 'lt' => '<', 'le' => '<=',
                    'gt' => '>', 'ge' => '>=', 'bn' => 'NOT LIKE', 'in' => 'LIKE',
                    'ni' => 'NOT LIKE', 'ew' => 'LIKE', 'en' => 'NOT LIKE',
                    'cn' => 'LIKE', 'nc' => 'NOT LIKE'
                ];

                $sqlOp = isset($opsMap[$searchOper]) ? $opsMap[$searchOper] : '=';

                if ($searchOper == 'bw' || $searchOper == 'bn') $searchString .= '%';
                if ($searchOper == 'ew' || $searchOper == 'en') $searchString = '%' . $searchString;
                if (in_array($searchOper, ['cn', 'nc', 'in', 'ni'], true)) $searchString = '%' . $searchString . '%';

                $this->dbx->where("{$searchField} {$sqlOp}", $searchString);
            }
        }

        if ($limit == true) {
            $rows  = isset($post['rows']) ? (int)$post['rows'] : 20;
            $start = isset($post['start']) ? (int)$post['start'] : 0;
            $this->dbx->limit($rows, $start);
        }

        if (!empty($post['sidx'])) {
            $sort = !empty($post['sord']) ? $post['sord'] : 'asc';
            $this->dbx->order_by($post['sidx'], $sort);
        }

        return $this->dbx->get_where('x_finance', [
            'x_finance.region_id' => $this->session->userdata('region_id')
        ])->result();
    }

    /**
     * jqGrid dapat mengirim id tunggal maupun "1,2,3" saat multiselect.
     */
    private function parse_ids($raw)
    {
        $ids = is_array($raw) ? $raw : explode(',', (string)$raw);
        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return $id > 0;
        })));

        return $ids;
    }

    private function get_finance_row($id)
    {
        $id     = (int)$id;
        $region = trim((string)$this->session->userdata('region_id'));

        if ($id <= 0 || $region === '') {
            return null;
        }

        return $this->dbx
            ->where('id', $id)
            ->where('region_id', $region)
            ->limit(1)
            ->get('x_finance')
            ->row_array();
    }

    private function get_finance_rows(array $ids)
    {
        $region = trim((string)$this->session->userdata('region_id'));

        if ($region === '' || empty($ids)) {
            return [];
        }

        $rows = $this->dbx
            ->where('region_id', $region)
            ->where_in('id', $ids)
            ->get('x_finance')
            ->result_array();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }

        return $map;
    }

    /**
     * Update source berdasarkan exact ID hasil direct mapping.
     *
     * Tidak pernah memakai LIKE kode_ref / prefix job.
     * Untuk tabel yang memiliki ownership number (ship_co/dn_no), row dengan
     * ownership NON-KOSONG ke invoice lain tidak disentuh.
     */
    private function release_source_ids($table, array $ids, $invoiceNo, $region)
    {
        $allowed = [
            'inf_income_selling_price',
            'inf_income_from_agent',
            'inf_income_from_coloader',
            'x_glbincome',
        ];

        if (!in_array($table, $allowed, true) || !$this->dbx->table_exists($table)) {
            return true;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return true;
        }

        $update = [];
        $refCol = null;

        if ($this->dbx->field_exists('ditarik', $table)) {
            $update['ditarik'] = 0;
        }

        if ($table === 'inf_income_selling_price') {
            $refCol = 'ship_co';
            $update['ship_co'] = '';

            if ($this->dbx->field_exists('billing_status', $table)) {
                $update['billing_status'] = 'UNBILLED';
            }
            if ($this->dbx->field_exists('billed_at', $table)) {
                $update['billed_at'] = null;
            }
            if ($this->dbx->field_exists('billed_by', $table)) {
                $update['billed_by'] = null;
            }
        } elseif ($table === 'inf_income_from_agent' || $table === 'inf_income_from_coloader') {
            if ($this->dbx->field_exists('dn_no', $table)) {
                $refCol = 'dn_no';
                $update['dn_no'] = '';
            }

            if ($this->dbx->field_exists('billing_status', $table)) {
                $update['billing_status'] = 'UNBILLED';
            }
            if ($this->dbx->field_exists('billed_at', $table)) {
                $update['billed_at'] = null;
            }
            if ($this->dbx->field_exists('billed_by', $table)) {
                $update['billed_by'] = null;
            }
        }

        if (empty($update)) {
            return true;
        }

        $this->dbx->where_in('id', $ids);

        if ($this->dbx->field_exists('region_id', $table)) {
            $this->dbx->where('region_id', $region);
        }

        /*
         * Direct mapping boleh memperbaiki row ownership kosong yang inconsistent,
         * tetapi jangan pernah mengosongkan row yang sekarang dimiliki invoice lain.
         */
        if ($refCol !== null) {
            $this->dbx->group_start();
            $this->dbx->where($refCol, $invoiceNo);
            $this->dbx->or_where($refCol, '');
            $this->dbx->or_where($refCol . ' IS NULL', null, false);
            $this->dbx->group_end();
        }

        $this->dbx->update($table, $update);

        return $this->dbx->trans_status() !== false;
    }

    /**
     * Release ownership suatu invoice secara aman.
     *
     * Urutan:
     * 1. direct mapping source_income_id / income_source_table+income_source_id;
     * 2. exact ownership fallback ship_co/dn_no = invoice number;
     * 3. clear direct mapping di x_finance_item agar source benar-benar UNBILLED.
     *
     * Tidak menggunakan kode_ref LIKE, prefix, group-wide reset, atau payment
     * pada source sebagai bukti status PAID.
     */
    private function release_finance_sources(array $finance)
    {
        $financeId = (int)($finance['id'] ?? 0);
        $number    = trim((string)($finance['number'] ?? ''));
        $region    = trim((string)($finance['region_id'] ?? ''));

        if ($financeId <= 0 || $number === '' || $region === '') {
            return false;
        }

        $byTable = [];

        if ($this->dbx->table_exists('x_finance_item')) {
            $select = ['id'];

            if ($this->dbx->field_exists('source_income_id', 'x_finance_item')) {
                $select[] = 'source_income_id';
            }
            if ($this->dbx->field_exists('income_source_table', 'x_finance_item')) {
                $select[] = 'income_source_table';
            }
            if ($this->dbx->field_exists('income_source_id', 'x_finance_item')) {
                $select[] = 'income_source_id';
            }

            $items = $this->dbx
                ->select($select)
                ->where('number', $financeId)
                ->where('region_id', $region)
                ->get('x_finance_item')
                ->result_array();

            foreach ($items as $item) {
                /* Legacy/current dedicated mapping untuk selling price. */
                if (isset($item['source_income_id'])) {
                    $sourceIncomeId = (int)$item['source_income_id'];
                    if ($sourceIncomeId > 0) {
                        $byTable['inf_income_selling_price'][] = $sourceIncomeId;
                    }
                }

                /* Generic direct mapping. */
                if (isset($item['income_source_table'], $item['income_source_id'])) {
                    $table = trim((string)$item['income_source_table']);
                    $id    = (int)$item['income_source_id'];

                    if ($table !== '' && $id > 0) {
                        $byTable[$table][] = $id;
                    }
                }
            }
        }

        foreach ($byTable as $table => $ids) {
            if (!$this->release_source_ids($table, $ids, $number, $region)) {
                return false;
            }
        }

        /*
         * Legacy exact ownership fallback.
         * Ini aman karena hanya source yang CURRENTLY menunjuk invoice ini.
         */
        if ($this->dbx->table_exists('inf_income_selling_price')) {
            $update = [
                'ditarik' => 0,
                'ship_co' => '',
            ];

            if ($this->dbx->field_exists('billing_status', 'inf_income_selling_price')) {
                $update['billing_status'] = 'UNBILLED';
            }
            if ($this->dbx->field_exists('billed_at', 'inf_income_selling_price')) {
                $update['billed_at'] = null;
            }
            if ($this->dbx->field_exists('billed_by', 'inf_income_selling_price')) {
                $update['billed_by'] = null;
            }

            $this->dbx
                ->where('region_id', $region)
                ->where('ship_co', $number)
                ->update('inf_income_selling_price', $update);
        }

        foreach (['inf_income_from_agent', 'inf_income_from_coloader'] as $table) {
            if (!$this->dbx->table_exists($table) || !$this->dbx->field_exists('dn_no', $table)) {
                continue;
            }

            $update = ['dn_no' => ''];

            if ($this->dbx->field_exists('ditarik', $table)) {
                $update['ditarik'] = 0;
            }
            if ($this->dbx->field_exists('billing_status', $table)) {
                $update['billing_status'] = 'UNBILLED';
            }
            if ($this->dbx->field_exists('billed_at', $table)) {
                $update['billed_at'] = null;
            }
            if ($this->dbx->field_exists('billed_by', $table)) {
                $update['billed_by'] = null;
            }

            $this->dbx
                ->where('region_id', $region)
                ->where('dn_no', $number)
                ->update($table, $update);
        }

        /*
         * Sangat penting untuk update_data():
         * source sudah UNBILLED, maka mapping lama juga harus dilepas.
         * Kalau mapping dibiarkan, report direct-mapping masih menganggap billed.
         */
        if ($this->dbx->table_exists('x_finance_item')) {
            $mappingReset = [];

            if ($this->dbx->field_exists('source_income_id', 'x_finance_item')) {
                $mappingReset['source_income_id'] = null;
            }
            if ($this->dbx->field_exists('income_source_table', 'x_finance_item')) {
                $mappingReset['income_source_table'] = null;
            }
            if ($this->dbx->field_exists('income_source_id', 'x_finance_item')) {
                $mappingReset['income_source_id'] = null;
            }

            if (!empty($mappingReset)) {
                $this->dbx
                    ->where('number', $financeId)
                    ->where('region_id', $region)
                    ->update('x_finance_item', $mappingReset);
            }
        }

        return $this->dbx->trans_status() !== false;
    }

    /**
     * Delete invoice dari jqGrid.
     *
     * - Mendukung single/multi id.
     * - PAID invoice immutable: bila satu saja PAID, seluruh batch ditolak.
     * - Release source exact sebelum header dihapus.
     */
    function manage_data($data)
    {
        $oper = strtolower(trim((string)($data['oper'] ?? '')));

        if ($oper !== 'del') {
            return 0;
        }

        $region = trim((string)$this->session->userdata('region_id'));
        $ids    = $this->parse_ids($data['id'] ?? '');

        if ($region === '' || empty($ids)) {
            return 0;
        }

        $financeRows = $this->get_finance_rows($ids);

        /* Semua ID wajib valid di region session. */
        if (count($financeRows) !== count($ids)) {
            log_message('error', '[LIST_INVOICE] Delete ditolak: finance id tidak lengkap / beda region.');
            return 0;
        }

        /* Pre-check sebelum ada perubahan apa pun. */
        foreach ($ids as $id) {
            $finance = $financeRows[$id];

            if (strtolower(trim((string)($finance['status'] ?? ''))) === 'paid') {
                log_message(
                    'error',
                    '[LIST_INVOICE] Delete ditolak karena PAID: ' .
                    trim((string)($finance['number'] ?? ''))
                );
                return 0;
            }
        }

        $this->dbx->trans_begin();

        foreach ($ids as $id) {
            $finance = $financeRows[$id];

            if (!$this->release_finance_sources($finance)) {
                $this->dbx->trans_rollback();
                return 0;
            }

            if ($this->dbx->table_exists('x_finance_delete')) {
                $this->dbx->insert('x_finance_delete', [
                    'kode_ref'       => (string)($finance['job_no'] ?? ''),
                    'deleted_by'     => (string)$this->session->userdata('fullname'),
                    'tanggal_delete' => date('Y-m-d H:i:s'),
                    'region_id'      => $region,
                    'number'         => (string)($finance['number'] ?? ''),
                ]);
            }

            /*
             * Header delete. Existing BEFORE DELETE trigger boleh tetap ada;
             * karena source sudah dilepas exact, trigger seharusnya no-op terhadap
             * inf_income_selling_price.
             */
            $this->dbx
                ->where('id', $id)
                ->where('region_id', $region)
                ->delete('x_finance');

            /* Fallback untuk yearly DB yang FK/trigger-nya belum seragam. */
            $this->dbx
                ->where('number', $id)
                ->where('region_id', $region)
                ->delete('x_finance_item');

            $this->dbx
                ->where('number', (string)($finance['number'] ?? ''))
                ->where('region_id', $region)
                ->delete('x_invoice');

            if ($this->dbx->trans_status() === false) {
                $this->dbx->trans_rollback();
                return 0;
            }
        }

        if ($this->dbx->trans_status() === false) {
            $this->dbx->trans_rollback();
            return 0;
        }

        $this->dbx->trans_commit();
        return 1;
    }

    function get_kode_ref($id)
    {
        $row = $this->get_finance_row($id);
        return $row ? (string)$row['job_no'] : '';
    }

    function get_number($id)
    {
        $row = $this->get_finance_row($id);
        return $row ? (string)$row['number'] : '';
    }

    function get_kepada($id)
    {
        $row = $this->get_finance_row($id);
        return $row ? (string)$row['kepada'] : '';
    }

    function update_finance($id)
    {
        $finance = $this->get_finance_row($id);

        if (!$finance) {
            return ['status' => 0, 'message' => 'FINANCE_NOT_FOUND'];
        }

        if (strtolower(trim((string)($finance['status'] ?? ''))) === 'paid') {
            return [
                'status'  => 0,
                'message' => 'PAID_INVOICE_CANNOT_UPDATE',
            ];
        }

        $this->dbx
            ->where('id', (int)$finance['id'])
            ->where('region_id', (string)$finance['region_id'])
            ->update('x_finance', ['fin_proses' => '9']);

        return ['status' => $this->dbx->affected_rows()];
    }

    /**
     * Release source invoice tanpa menghapus header.
     * Biasanya dipakai sebelum invoice unpaid direvisi/rebuild.
     *
     * PAID selalu ditolak berdasarkan x_finance.status.
     */
    function update_data($data)
    {
        $id      = (int)($data['id'] ?? 0);
        $finance = $this->get_finance_row($id);

        if (!$finance) {
            return [
                'status'  => 0,
                'message' => 'FINANCE_NOT_FOUND',
            ];
        }

        if (strtolower(trim((string)($finance['status'] ?? ''))) === 'paid') {
            return [
                'status'  => 0,
                'message' => 'PAID_INVOICE_CANNOT_RELEASE',
            ];
        }

        $this->dbx->trans_begin();

        $ok = $this->release_finance_sources($finance);

        if (!$ok || $this->dbx->trans_status() === false) {
            $this->dbx->trans_rollback();

            return [
                'status'  => 0,
                'message' => 'RELEASE_SOURCE_FAILED',
            ];
        }

        $this->dbx->trans_commit();

        return [
            'status'     => 1,
            'message'    => 'SOURCE_RELEASED',
            'finance_id' => (int)$finance['id'],
            'invoice_no' => (string)$finance['number'],
        ];
    }
}
?>
