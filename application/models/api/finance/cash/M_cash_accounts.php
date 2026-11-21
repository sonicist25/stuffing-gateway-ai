<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_cash_accounts extends MY_Model
{
  private $company_id = 1;

  public function list($p)
{
    $limit = (int)($p['limit'] ?? 200);
    if ($limit <= 0) $limit = 200;
    if ($limit > 500) $limit = 500;

    $search = trim((string)($p['search'] ?? ''));

    $this->erp->select("
        account_id,
        account_code,
        account_name,
        root_type,
        region_id
    ", false)
        ->from('accounts')
        ->where('company_id', $this->company_id)
        ->where('is_active', 1)
        ->where('is_postable', 1)
        ->where('root_type', 'Asset');

    /**
     * Penting:
     * Filter region supaya COA antar cabang tidak double/campur.
     */
    $this->apply_region_filter('region_id');

    /**
     * Batasi hanya cash/bank account.
     * Jangan semua Asset, karena Asset juga berisi AR, deposit, PPN input, fixed asset, dll.
     *
     * Sesuaikan kode parent COA Anda:
     * 1100 = Cash & Bank
     * 1101/1102/dst = akun kas/bank postable
     */
    $this->erp->group_start()
        ->like('account_code', '110', 'after')
        ->or_like('account_name', 'Bank')
        ->or_like('account_name', 'Cash')
        ->or_like('account_name', 'Kas')
        ->or_like('account_name', 'Mandiri')
        ->or_like('account_name', 'Maybank')
    ->group_end();

    if ($search !== '') {
        $this->erp->group_start()
            ->like('account_code', $search)
            ->or_like('account_name', $search)
        ->group_end();
    }

    $this->erp->order_by('account_code', 'ASC');
    $this->erp->order_by('account_id', 'ASC');
    $this->erp->limit($limit);

    $rows = $this->erp->get()->result_array();

    /**
     * Safety dedup.
     * Kalau masih ada duplicate account_code di region yang sama,
     * tampilkan satu saja.
     */
    $seen = [];
    $out = [];

    foreach ($rows as $r) {
        $key = trim((string)$r['region_id']) . '|' . trim((string)$r['account_code']);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;

        $r['account_id'] = (int)$r['account_id'];
        $r['label'] = $r['account_code'] . ' - ' . $r['account_name'];

        $out[] = $r;
    }

    return $out;
}

private function _active_region()
{
    $region = '';

    if ($this->session->userdata('region_id')) {
        $region = trim((string)$this->session->userdata('region_id'));
    }

    if ($region === '' && $this->session->userdata('region')) {
        $region = trim((string)$this->session->userdata('region'));
    }

    if ($region === '' && method_exists($this, 'auth_region')) {
        $region = trim((string)$this->auth_region());
    }

    return $region;
}
}