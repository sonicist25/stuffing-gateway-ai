<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class M_accounts extends MY_Model
{
    private $company_id = 1;

    private $root_types = [
        'Asset',
        'Liability',
        'Equity',
        'Income',
        'Expense',
        'Other Income & Expenses',
    ];

    private $settlement_roles = [
        'NONE',
        'LOCAL_CASH',
        'CENTRAL_BANK',
        'CENTRAL_BANK_LEGACY',
        'CENTRAL_BANK_LEGACY_INACTIVE',
        'AR_CLEARING_HO',
        'AP_CLEARING_HO',
        'AR_AP_CLEARING_HO',
        'CUSTOMER_DEPOSIT',
        'VENDOR_ADVANCE',
    ];

    /**
     * GET / accounts list.
     * Compatible with old calls: list($q)
     * Also supports list(['q' => '', 'root_type' => '', ...]) for future FE filters.
     */
    public function list($q = null)
    {
        $p = is_array($q) ? $q : ['q' => $q];
        $q = trim((string)($p['q'] ?? $p['search'] ?? ''));
        $includeInactive = !empty($p['include_inactive']);
        $rootType = trim((string)($p['root_type'] ?? ''));
        $accountType = trim((string)($p['account_type'] ?? ''));
        $settlementRole = strtoupper(trim((string)($p['settlement_role'] ?? '')));
        $postableOnly = isset($p['postable_only']) ? ((int)$p['postable_only'] === 1) : false;
        $limit = (int)($p['limit'] ?? 0);

        $this->erp->select($this->_account_select('a'))
            ->from('accounts a')
            ->where('a.company_id', $this->company_id);

        $this->_apply_region_filter('a');

        if (!$includeInactive) {
            $this->erp->where('a.is_active', 1);
        }

        if ($postableOnly) {
            $this->erp->where('a.is_postable', 1);
            $this->erp->where('a.is_group', 0);
        }

        if ($rootType !== '') {
            $this->erp->where('a.root_type', $rootType);
        }

        if ($accountType !== '' && $this->_has_col('accounts', 'account_type')) {
            $this->erp->where('a.account_type', $accountType);
        }

        if ($settlementRole !== '' && $this->_has_col('accounts', 'settlement_role')) {
            $this->erp->where('a.settlement_role', $settlementRole);
        }

        if ($q !== '') {
            $this->erp->group_start()
                ->like('a.account_code', $q)
                ->or_like('a.account_name', $q);

            if ($this->_has_col('accounts', 'external_account_code')) {
                $this->erp->or_like('a.external_account_code', $q);
            }

            if ($this->_has_col('accounts', 'external_account_name')) {
                $this->erp->or_like('a.external_account_name', $q);
            }

            $this->erp->group_end();
        }

        $this->erp->order_by('a.root_type', 'ASC')
            ->order_by('a.account_code', 'ASC')
            ->order_by('a.account_id', 'ASC');

        if ($limit > 0) {
            if ($limit > 500) $limit = 500;
            $this->erp->limit($limit);
        }

        $rows = $this->erp->get()->result_array();
        return $this->_normalize_rows($rows);
    }

    public function create($p)
    {
        $accountCode = trim((string)($p['account_code'] ?? ''));
        $accountName = trim((string)($p['account_name'] ?? ''));
        $rootType = trim((string)($p['root_type'] ?? ''));
        $regionId = $this->_active_region();

        $errs = [];
        if ($accountCode === '') $errs['account_code'] = 'required';
        if ($accountName === '') $errs['account_name'] = 'required';
        if ($rootType === '') $errs['root_type'] = 'required';
        if ($rootType !== '' && !in_array($rootType, $this->root_types, true)) $errs['root_type'] = 'invalid';
        if ($regionId === '') $errs['region_id'] = 'required';

        if ($errs) {
            return ['ok' => false, 'http' => 422, 'message' => 'Validation error', 'errors' => $errs];
        }

        $exists = $this->erp->select('account_id')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('region_id', $regionId)
            ->where('account_code', $accountCode)
            ->limit(1)
            ->get()
            ->row_array();

        if ($exists) {
            return [
                'ok'      => false,
                'http'    => 409,
                'message' => 'Account code already exists in this region',
                'data'    => ['account_id' => (int)$exists['account_id'], 'region_id' => $regionId],
            ];
        }

        $parentId = !empty($p['parent_id']) ? (int)$p['parent_id'] : null;
        if ($parentId) {
            $parent = $this->_get_same_region_account($parentId, $regionId);
            if (!$parent) {
                return ['ok' => false, 'http' => 422, 'message' => 'INVALID_PARENT_ACCOUNT'];
            }
        }

        $isGroup = !empty($p['is_group']) ? 1 : 0;
        $isPostable = isset($p['is_postable']) ? ((int)$p['is_postable'] ? 1 : 0) : 1;
        if ($isGroup) $isPostable = 0;

        $accountCurrency = $this->_normalize_currency($p['account_currency'] ?? $p['currency'] ?? 'IDR');
        $settlementRole = $this->_normalize_settlement_role($p['settlement_role'] ?? 'NONE');
        $accountType = trim((string)($p['account_type'] ?? ''));
        if ($accountType === '') {
            $accountType = $this->_infer_account_type($accountCode, $accountName, $rootType, $settlementRole);
        }

        $data = [
            'company_id'             => $this->company_id,
            'parent_id'              => $parentId,
            'account_code'           => $accountCode,
            'account_name'           => $accountName,
            'root_type'              => $rootType,
            'account_type'           => $accountType !== '' ? $accountType : null,
            'account_currency'       => $accountCurrency,
            'settlement_role'        => $settlementRole,
            'external_system'        => $this->_null_if_empty($p['external_system'] ?? null),
            'external_account_code'  => $this->_null_if_empty($p['external_account_code'] ?? null),
            'external_account_name'  => $this->_null_if_empty($p['external_account_name'] ?? null),
            'is_central_bank'        => !empty($p['is_central_bank']) ? 1 : 0,
            'is_group'               => $isGroup,
            'is_postable'            => $isPostable,
            'is_active'              => 1,
            'region_id'              => $regionId,
        ];

        $this->_inject_audit_if_exists($data, true);
        $data = $this->_filter_columns('accounts', $data);

        $this->erp->insert('accounts', $data);

        if ($this->erp->affected_rows() <= 0) {
            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error',
                'data'    => ['db_error' => $this->erp->error(), 'last_query' => $this->erp->last_query()],
            ];
        }

        $id = (int)$this->erp->insert_id();

        return ['ok' => true, 'message' => 'Created', 'data' => $this->get($id)];
    }

    public function update($id, $p)
    {
        $id = (int)$id;
        $row = $this->get($id);

        if (!$row) {
            return ['ok' => false, 'http' => 404, 'message' => 'Account not found'];
        }

        $data = [];
        $regionId = (string)$row['region_id'];

        if (isset($p['account_name'])) {
            $name = trim((string)$p['account_name']);
            if ($name === '') return ['ok' => false, 'http' => 422, 'message' => 'Validation error', 'errors' => ['account_name' => 'required']];
            $data['account_name'] = $name;
        }

        if (isset($p['root_type'])) {
            $rt = trim((string)$p['root_type']);
            if (!in_array($rt, $this->root_types, true)) {
                return ['ok' => false, 'http' => 422, 'message' => 'Validation error', 'errors' => ['root_type' => 'invalid']];
            }
            $data['root_type'] = $rt;
        }

        if (array_key_exists('parent_id', $p)) {
            $parentId = !empty($p['parent_id']) ? (int)$p['parent_id'] : null;
            if ($parentId && $parentId === $id) {
                return ['ok' => false, 'http' => 422, 'message' => 'PARENT_CANNOT_BE_SELF'];
            }
            if ($parentId && !$this->_get_same_region_account($parentId, $regionId)) {
                return ['ok' => false, 'http' => 422, 'message' => 'INVALID_PARENT_ACCOUNT'];
            }
            $data['parent_id'] = $parentId;
        }

        if (array_key_exists('account_type', $p)) {
            $data['account_type'] = $this->_null_if_empty($p['account_type']);
        }

        if (array_key_exists('account_currency', $p) || array_key_exists('currency', $p)) {
            $data['account_currency'] = $this->_normalize_currency($p['account_currency'] ?? $p['currency'] ?? 'IDR');
        }

        if (array_key_exists('settlement_role', $p)) {
            $data['settlement_role'] = $this->_normalize_settlement_role($p['settlement_role']);
        }

        foreach (['external_system', 'external_account_code', 'external_account_name'] as $k) {
            if (array_key_exists($k, $p)) {
                $data[$k] = $this->_null_if_empty($p[$k]);
            }
        }

        if (array_key_exists('is_central_bank', $p)) {
            $data['is_central_bank'] = (int)$p['is_central_bank'] ? 1 : 0;
        }

        if (array_key_exists('is_group', $p)) {
            $data['is_group'] = (int)$p['is_group'] ? 1 : 0;
        }

        if (array_key_exists('is_postable', $p)) {
            $data['is_postable'] = (int)$p['is_postable'] ? 1 : 0;
        }

        if (array_key_exists('is_active', $p)) {
            $data['is_active'] = (int)$p['is_active'] ? 1 : 0;
        }

        $newIsGroup = array_key_exists('is_group', $data) ? (int)$data['is_group'] : (int)$row['is_group'];
        if ($newIsGroup === 1) {
            $data['is_postable'] = 0;
        }

        if (empty($data)) {
            return ['ok' => true, 'message' => 'No changes', 'data' => $row];
        }

        $this->_inject_audit_if_exists($data, false);
        $data = $this->_filter_columns('accounts', $data);

        $this->erp->where('account_id', $id)
            ->where('company_id', $this->company_id)
            ->where('region_id', $regionId)
            ->update('accounts', $data);

        if ($this->erp->trans_status() === false) {
            return [
                'ok'      => false,
                'http'    => 500,
                'message' => 'DB error',
                'data'    => ['db_error' => $this->erp->error(), 'last_query' => $this->erp->last_query()],
            ];
        }

        return ['ok' => true, 'message' => 'Updated', 'data' => $this->get($id)];
    }

    public function get($id)
    {
        $id = (int)$id;

        $this->erp->select($this->_account_select('a'))
            ->from('accounts a')
            ->where('a.account_id', $id)
            ->where('a.company_id', $this->company_id);

        $this->_apply_region_filter('a');

        $row = $this->erp->get()->row_array();
        if (!$row) return null;

        return $this->_normalize_row($row);
    }

    public function tree($p = [])
    {
        $withBalance = !empty($p['with_balance']);
        $asOf = !empty($p['as_of']) ? $p['as_of'] : date('Y-m-d');
        $includeInactive = !empty($p['include_inactive']);

        $this->erp->select($this->_account_select('a'))
            ->from('accounts a')
            ->where('a.company_id', $this->company_id);

        if (!$includeInactive) {
            $this->erp->where('a.is_active', 1);
        }

        $this->_apply_region_filter('a');

        $rows = $this->erp->order_by('a.root_type', 'ASC')
            ->order_by('a.account_code', 'ASC')
            ->order_by('a.account_id', 'ASC')
            ->get()
            ->result_array();

        $rows = $this->_normalize_rows($rows);
        $balMap = [];

        if ($withBalance) {
            $this->erp->select('gl.account_id, COALESCE(SUM(gl.debit),0) - COALESCE(SUM(gl.credit),0) AS balance', false)
                ->from('gl_entries gl')
                ->where('gl.company_id', $this->company_id)
                ->where('gl.status', 'POSTED')
                ->where('gl.posting_date <=', $asOf)
                ->group_by('gl.account_id');

            $this->_apply_region_filter('gl');

            $bals = $this->erp->get()->result_array();

            foreach ($bals as $b) {
                $balMap[(int)$b['account_id']] = (float)$b['balance'];
            }
        }

        $byId = [];

        foreach ($rows as $r) {
            $r['children'] = [];
            $r['balance'] = $withBalance ? ($balMap[(int)$r['account_id']] ?? 0.0) : 0.0;
            $byId[(int)$r['account_id']] = $r;
        }

        $roots = [];

        foreach ($byId as $id => &$node) {
            $pid = !empty($node['parent_id']) ? (int)$node['parent_id'] : 0;

            if ($pid && isset($byId[$pid])) {
                $byId[$pid]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }

        unset($node);

        $grouped = [
            'Asset' => [],
            'Liability' => [],
            'Equity' => [],
            'Income' => [],
            'Expense' => [],
            'Other Income & Expenses' => [],
        ];

        foreach ($roots as $n) {
            $rt = $n['root_type'] ?: 'Asset';
            if (!isset($grouped[$rt])) $grouped[$rt] = [];
            $grouped[$rt][] = $n;
        }

        return $grouped;
    }

    /**
     * One-time helper endpoint can call this after schema migration.
     * It marks central bank ledgers as legacy/inactive and creates HO clearing ledgers per region.
     */
    public function sync_ho_clearing_accounts($p = [])
    {
        $regions = $p['regions'] ?? [];
        if (!is_array($regions)) $regions = [];

        if (!$regions) {
            $regions = $this->erp->select('DISTINCT region_id', false)
                ->from('accounts')
                ->where('company_id', $this->company_id)
                ->where('region_id IS NOT NULL', null, false)
                ->where('region_id <>', '')
                ->order_by('region_id', 'ASC')
                ->get()
                ->result_array();
            $regions = array_values(array_filter(array_map(function($r) {
                return trim((string)$r['region_id']);
            }, $regions)));
        }

        $this->erp->trans_begin();

        if ($this->_has_col('accounts', 'settlement_role')) {
            $bankData = [
                'settlement_role'  => 'CENTRAL_BANK_LEGACY_INACTIVE',
                'account_type'     => 'Bank',
                'external_system'  => 'ERPNext',
                'is_central_bank'  => 1,
                'is_active'        => 0,
            ];
            $bankData = $this->_filter_columns('accounts', $bankData);

            $this->erp->where('company_id', $this->company_id)
                ->where_in('account_code', ['1270', '1271'])
                ->update('accounts', $bankData);
        }

        $created = [];

        foreach ($regions as $regionId) {
            $regionId = trim((string)$regionId);
            if ($regionId === '') continue;

            $created[] = $this->_ensure_account([
                'region_id'        => $regionId,
                'parent_code'      => '1300',
                'account_code'     => '1150',
                'account_name'     => 'Due From Head Office / Bank Clearing Pusat',
                'root_type'        => 'Asset',
                'account_type'     => 'Current Asset',
                'account_currency' => 'IDR',
                'settlement_role'  => 'AR_CLEARING_HO',
            ]);

            $created[] = $this->_ensure_account([
                'region_id'        => $regionId,
                'parent_code'      => '2100',
                'account_code'     => '2155',
                'account_name'     => 'Due To Head Office / Central Payment Clearing',
                'root_type'        => 'Liability',
                'account_type'     => 'Current Liability',
                'account_currency' => 'IDR',
                'settlement_role'  => 'AP_CLEARING_HO',
            ]);

            if (!empty($p['with_usd'])) {
                $created[] = $this->_ensure_account([
                    'region_id'        => $regionId,
                    'parent_code'      => '1300',
                    'account_code'     => '1151',
                    'account_name'     => 'Due From Head Office USD / Bank Clearing Pusat',
                    'root_type'        => 'Asset',
                    'account_type'     => 'Current Asset',
                    'account_currency' => 'USD',
                    'settlement_role'  => 'AR_CLEARING_HO',
                ]);

                $created[] = $this->_ensure_account([
                    'region_id'        => $regionId,
                    'parent_code'      => '2100',
                    'account_code'     => '2156',
                    'account_name'     => 'Due To Head Office USD / Central Payment Clearing',
                    'root_type'        => 'Liability',
                    'account_type'     => 'Current Liability',
                    'account_currency' => 'USD',
                    'settlement_role'  => 'AP_CLEARING_HO',
                ]);
            }
        }

        if ($this->erp->trans_status() === false) {
            $err = $this->erp->error();
            $this->erp->trans_rollback();
            return ['ok' => false, 'http' => 500, 'message' => 'DB error', 'data' => ['db_error' => $err]];
        }

        $this->erp->trans_commit();

        return ['ok' => true, 'message' => 'OK', 'data' => ['rows' => $created]];
    }

    private function _ensure_account($p)
    {
        $regionId = trim((string)$p['region_id']);
        $code = trim((string)$p['account_code']);

        $exists = $this->erp->select('account_id')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('region_id', $regionId)
            ->where('account_code', $code)
            ->limit(1)
            ->get()
            ->row_array();

        if ($exists) {
            return ['region_id' => $regionId, 'account_code' => $code, 'action' => 'exists', 'account_id' => (int)$exists['account_id']];
        }

        $parent = $this->erp->select('account_id')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('region_id', $regionId)
            ->where('account_code', trim((string)$p['parent_code']))
            ->where('is_group', 1)
            ->limit(1)
            ->get()
            ->row_array();

        $data = [
            'company_id'             => $this->company_id,
            'parent_id'              => $parent ? (int)$parent['account_id'] : null,
            'account_code'           => $code,
            'account_name'           => trim((string)$p['account_name']),
            'root_type'              => trim((string)$p['root_type']),
            'account_type'           => trim((string)$p['account_type']),
            'account_currency'       => $this->_normalize_currency($p['account_currency'] ?? 'IDR'),
            'settlement_role'        => $this->_normalize_settlement_role($p['settlement_role'] ?? 'NONE'),
            'is_central_bank'        => 0,
            'is_group'               => 0,
            'is_postable'            => 1,
            'is_active'              => 1,
            'region_id'              => $regionId,
        ];

        $this->_inject_audit_if_exists($data, true);
        $this->erp->insert('accounts', $this->_filter_columns('accounts', $data));
        $id = (int)$this->erp->insert_id();

        return ['region_id' => $regionId, 'account_code' => $code, 'action' => 'created', 'account_id' => $id];
    }

    private function _get_same_region_account($id, $regionId)
    {
        return $this->erp->from('accounts')
            ->where('account_id', (int)$id)
            ->where('company_id', $this->company_id)
            ->where('region_id', $regionId)
            ->limit(1)
            ->get()
            ->row_array();
    }

    private function _account_select($alias = 'a')
    {
        $base = [
            'account_id',
            'company_id',
            'parent_id',
            'account_code',
            'account_name',
            'root_type',
            'is_group',
            'is_postable',
            'is_active',
            'created_at',
            'region_id',
        ];

        $optional = [
            'account_type',
            'account_currency',
            'settlement_role',
            'external_system',
            'external_account_code',
            'external_account_name',
            'is_central_bank',
            'updated_at',
            'created_by',
            'updated_by',
        ];

        $cols = [];
        foreach ($base as $c) {
            $cols[] = $alias . '.' . $c;
        }
        foreach ($optional as $c) {
            if ($this->_has_col('accounts', $c)) {
                $cols[] = $alias . '.' . $c;
            }
        }

        return implode(',', $cols);
    }

    private function _normalize_row($r)
    {
        $r['account_id'] = (int)$r['account_id'];
        $r['company_id'] = (int)$r['company_id'];
        $r['parent_id'] = !empty($r['parent_id']) ? (int)$r['parent_id'] : null;
        $r['is_group'] = (int)$r['is_group'];
        $r['is_postable'] = (int)$r['is_postable'];
        $r['is_active'] = (int)$r['is_active'];
        if (isset($r['is_central_bank'])) $r['is_central_bank'] = (int)$r['is_central_bank'];
        if (!isset($r['settlement_role']) || $r['settlement_role'] === null || $r['settlement_role'] === '') $r['settlement_role'] = 'NONE';
        if (!isset($r['account_currency']) || $r['account_currency'] === null || $r['account_currency'] === '') $r['account_currency'] = 'IDR';
        $r['label'] = $r['account_code'] . ' - ' . $r['account_name'];
        return $r;
    }

    private function _normalize_rows($rows)
    {
        foreach ($rows as &$r) {
            $r = $this->_normalize_row($r);
        }
        unset($r);
        return $rows;
    }

    private function _active_region()
    {
        $region = '';

        if ($this->session && $this->session->userdata('region_id')) {
            $region = trim((string)$this->session->userdata('region_id'));
        }

        if ($region === '' && $this->session && $this->session->userdata('region')) {
            $region = trim((string)$this->session->userdata('region'));
        }

        if ($region === '' && method_exists($this, 'auth_region')) {
            $region = trim((string)$this->auth_region());
        }

        return $region;
    }

    private function _apply_region_filter($alias = null)
    {
        $region = $this->_active_region();
        if ($region === '') {
            // Safer than leaking all branch COA when auth/session region is empty.
            $this->erp->where('1 = 0', null, false);
            return;
        }

        $col = $alias ? $alias . '.region_id' : 'region_id';
        $this->erp->where($col, $region);
    }

    private function _normalize_currency($currency)
    {
        $c = strtoupper(trim((string)$currency));
        if ($c === '' || $c === 'RP' || $c === 'RP.' || $c === 'RUPIAH') return 'IDR';
        return $c;
    }

    private function _normalize_settlement_role($role)
    {
        $role = strtoupper(trim((string)$role));
        if ($role === '') $role = 'NONE';
        if (!in_array($role, $this->settlement_roles, true)) return 'NONE';
        return $role;
    }

    private function _infer_account_type($code, $name, $rootType, $settlementRole)
    {
        $code = trim((string)$code);
        $n = strtoupper((string)$name);

        if (in_array($settlementRole, ['CENTRAL_BANK', 'CENTRAL_BANK_LEGACY', 'CENTRAL_BANK_LEGACY_INACTIVE'], true)) return 'Bank';
        if (in_array($settlementRole, ['AR_CLEARING_HO', 'AR_AP_CLEARING_HO'], true)) return 'Current Asset';
        if ($settlementRole === 'AP_CLEARING_HO') return 'Current Liability';
        if ($settlementRole === 'LOCAL_CASH') return 'Cash';

        if ($rootType === 'Asset') {
            if (strpos($n, 'MANDIRI') !== false || strpos($n, 'BANK') !== false) return 'Bank';
            if (strpos($n, 'CASH') !== false || strpos($n, 'KAS') !== false || strpos($code, '112') === 0) return 'Cash';
            if (strpos($code, '131') === 0 || strpos($n, 'RECEIVABLE') !== false) return 'Receivable';
            if (strpos($n, 'PPN') !== false || strpos($n, 'PPH') !== false || strpos($n, 'TAX') !== false) return 'Tax';
            return 'Current Asset';
        }

        if ($rootType === 'Liability') {
            if (strpos($code, '211') === 0 || strpos($n, 'PAYABLE') !== false) return 'Payable';
            if (strpos($n, 'PPN') !== false || strpos($n, 'PPH') !== false || strpos($n, 'TAX') !== false) return 'Tax';
            return 'Current Liability';
        }

        if ($rootType === 'Income') return 'Income Account';
        if ($rootType === 'Expense') return 'Expense Account';
        if ($rootType === 'Equity') return 'Equity';

        return null;
    }

    private function _null_if_empty($v)
    {
        $v = trim((string)$v);
        return $v === '' ? null : $v;
    }

    private function _filter_columns($table, $data)
    {
        if (!$this->erp->table_exists($table)) return [];
        $fields = $this->erp->list_fields($table);
        $allowed = array_flip($fields);
        $out = [];
        foreach ($data as $k => $v) {
            if (isset($allowed[$k])) $out[$k] = $v;
        }
        return $out;
    }

    private function _has_col($table, $col)
    {
        static $cache = [];
        $key = $table . '.' . $col;
        if (array_key_exists($key, $cache)) return $cache[$key];
        if (!$this->erp->table_exists($table)) return $cache[$key] = false;
        return $cache[$key] = in_array($col, $this->erp->list_fields($table), true);
    }

    private function _inject_audit_if_exists(&$data, $isCreate = false)
    {
        $now = date('Y-m-d H:i:s');
        $user = null;
        if (method_exists($this, 'auth_username')) {
            $user = $this->auth_username();
        }
        if (!$user && $this->session) {
            $user = $this->session->userdata('username') ?: $this->session->userdata('fullname');
        }

        if ($isCreate && $this->_has_col('accounts', 'created_by')) $data['created_by'] = $user;
        if ($this->_has_col('accounts', 'updated_by')) $data['updated_by'] = $user;
        if ($this->_has_col('accounts', 'updated_at')) $data['updated_at'] = $now;
    }
}
