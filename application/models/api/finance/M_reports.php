<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_reports extends MY_Model
{
    private $company_id = 1;

    // =========================
    // Helpers
    // =========================
    private function _valid_date($s, $default=null)
    {
        $s = trim((string)$s);
        if ($s === '') return $default;
        return preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $s) ? $s : $default;
    }

    private function _float($v){ return (float)$v; }

    // =========================
    // General Ledger
    // =========================
    public function general_ledger($f)
    {
        $date_from  = $this->_valid_date($f['date_from'] ?? null, date('Y-m-01'));
        $date_to    = $this->_valid_date($f['date_to'] ?? null, date('Y-m-t'));
        $account_id = !empty($f['account_id']) ? (int)$f['account_id'] : null;
        $party_id   = !empty($f['party_id']) ? (int)$f['party_id'] : null;

        if (!$account_id) {
            return [
                'opening_balance' => 0,
                'rows' => [],
                'summary' => ['debit'=>0,'credit'=>0,'closing'=>0],
                'filters' => compact('date_from','date_to','account_id','party_id')
            ];
        }

        // Opening balance (sum debit-credit before date_from)
        $this->erp->select('COALESCE(SUM(debit - credit),0) AS ob', false)
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('region_id', $this->auth_region())
            ->where('status', 'POSTED')
            ->where('account_id', $account_id)
            ->where('posting_date <', $date_from);
        if ($party_id) $this->erp->where('party_id', $party_id);
        $opening = (float)($this->erp->get()->row()->ob ?? 0);

        // Rows in range
        $this->erp->select("
            gl.posting_date, gl.voucher_type, gl.voucher_no,
            gl.debit, gl.credit, gl.party_id,
            a.account_code, a.account_name,
            p.party_name
        ", false)
        ->from('gl_entries gl')
        ->join('accounts a','a.account_id = gl.account_id','left')
        ->join('parties p','p.party_id = gl.party_id','left')
        ->where('gl.company_id', $this->company_id)
        ->where('gl.region_id', $this->auth_region())
        ->where('gl.status', 'POSTED')
        ->where('gl.account_id', $account_id)
        ->where('gl.posting_date >=', $date_from)
        ->where('gl.posting_date <=', $date_to)
        ->order_by('gl.posting_date','ASC')
        ->order_by('gl.gl_id','ASC');

        if ($party_id) $this->erp->where('gl.party_id', $party_id);

        $rows = $this->erp->get()->result_array();

        $running = $opening;
        $sumD = 0.0; $sumC = 0.0;

        foreach ($rows as &$r) {
            $d = (float)$r['debit'];
            $c = (float)$r['credit'];
            $sumD += $d; $sumC += $c;
            $running += ($d - $c);
            $r['running_balance'] = $running;
        }
        unset($r);

        return [
            'opening_balance' => $opening,
            'rows' => $rows,
            'summary' => [
                'debit' => $sumD,
                'credit' => $sumC,
                'closing' => $opening + ($sumD - $sumC),
            ],
            'filters' => compact('date_from','date_to','account_id','party_id')
        ];
    }

    // =========================
    // Trial Balance (opening + movement + closing)
    // =========================
    public function trial_balance($f)
    {
        $date_from = $this->_valid_date($f['date_from'] ?? null, date('Y-m-01'));
        $date_to   = $this->_valid_date($f['date_to'] ?? null, date('Y-m-t'));
        $show_zero = (int)($f['show_zero'] ?? 0); // optional

        if (strtotime($date_to) < strtotime($date_from)) {
            // swap, biar tidak bikin FE error
            $tmp = $date_from; $date_from = $date_to; $date_to = $tmp;
        }

        $opening_end = date('Y-m-d', strtotime($date_from.' -1 day'));

        // Opening per account (<= opening_end)
        $ob = $this->erp->select('account_id, COALESCE(SUM(debit-credit),0) AS opening', false)
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('region_id', $this->auth_region())
            ->where('status', 'POSTED')
            ->where('posting_date <=', $opening_end)
            ->group_by('account_id')
            ->get()->result_array();

        $obMap = [];
        foreach ($ob as $r) $obMap[(int)$r['account_id']] = (float)$r['opening'];

        // Movement in range
        $mov = $this->erp->select('account_id, COALESCE(SUM(debit),0) AS debit, COALESCE(SUM(credit),0) AS credit', false)
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('region_id', $this->auth_region())
            ->where('status', 'POSTED')
            ->where('posting_date >=', $date_from)
            ->where('posting_date <=', $date_to)
            ->group_by('account_id')
            ->get()->result_array();

        $movMap = [];
        foreach ($mov as $r) {
            $aid = (int)$r['account_id'];
            $movMap[$aid] = ['debit'=>(float)$r['debit'], 'credit'=>(float)$r['credit']];
        }

        // Accounts master
        $acc = $this->erp->select('account_id, account_code, account_name, root_type, is_group, is_postable')
            ->from('accounts')
            ->where('company_id', $this->company_id)
            ->where('region_id', $this->auth_region())
            ->where('is_active', 1)
            ->order_by('root_type','ASC')
            ->order_by('account_code','ASC')
            ->get()->result_array();

        $rows = [];
        $tot = ['opening'=>0,'debit'=>0,'credit'=>0,'closing'=>0];

        foreach ($acc as $a) {
            $aid = (int)$a['account_id'];

            // TB: tampilkan akun postable saja (MVP, sesuai versi kamu)
            if ((int)$a['is_postable'] !== 1) continue;

            $opening = $obMap[$aid] ?? 0.0;
            $debit   = $movMap[$aid]['debit'] ?? 0.0;
            $credit  = $movMap[$aid]['credit'] ?? 0.0;
            $closing = $opening + ($debit - $credit);

            if (!$show_zero) {
                if (abs($opening) < 0.01 && abs($debit) < 0.01 && abs($credit) < 0.01 && abs($closing) < 0.01) continue;
            }

            $rows[] = [
                'account_id'   => $aid,
                'account_code' => $a['account_code'],
                'account_name' => $a['account_name'],
                'root_type'    => $a['root_type'],
                'opening'      => $opening,
                'debit'        => $debit,
                'credit'       => $credit,
                'closing'      => $closing
            ];

            $tot['opening'] += $opening;
            $tot['debit']   += $debit;
            $tot['credit']  += $credit;
            $tot['closing'] += $closing;
        }

        // movement totals from GL (lebih akurat)
        $mvTot = $this->erp->select('COALESCE(SUM(debit),0) AS dr, COALESCE(SUM(credit),0) AS cr', false)
            ->from('gl_entries')
            ->where('company_id', $this->company_id)
            ->where('region_id', $this->auth_region())
            ->where('status', 'POSTED')
            ->where('posting_date >=', $date_from)
            ->where('posting_date <=', $date_to)
            ->get()->row_array();

        $tot['debit']  = (float)($mvTot['dr'] ?? $tot['debit']);
        $tot['credit'] = (float)($mvTot['cr'] ?? $tot['credit']);

        return [
            'rows' => $rows,
            'totals' => [
                'opening' => (float)$tot['opening'],
                'debit'   => (float)$tot['debit'],
                'credit'  => (float)$tot['credit'],
                'closing' => (float)$tot['closing'],
            ],
            'filters' => compact('date_from','date_to','opening_end')
        ];
    }

    // =========================
    // Profit & Loss
    // =========================
    public function profit_loss($f)
    {
        $date_from = $this->_valid_date($f['date_from'] ?? null, date('Y-m-01'));
        $date_to   = $this->_valid_date($f['date_to'] ?? null, date('Y-m-t'));

        $rows = $this->erp->select("
            a.account_id, a.account_code, a.account_name, a.root_type,
            COALESCE(SUM(g.debit),0) AS debit,
            COALESCE(SUM(g.credit),0) AS credit
        ", false)
        ->from('accounts a')
        ->join('gl_entries g', "g.account_id = a.account_id
            AND g.company_id = a.company_id
            AND g.status = 'POSTED'
            AND g.posting_date >= ".$this->erp->escape($date_from)."
            AND g.posting_date <= ".$this->erp->escape($date_to)."
        ", 'left', false)
        ->where('a.company_id', $this->company_id)
        ->where('a.region_id', $this->auth_region())
        ->where('a.is_active', 1)
        ->where_in('a.root_type', ['Income','Expense'])
        ->where('a.is_postable', 1)
        ->group_by('a.account_id')
        ->order_by('a.root_type','ASC')
        ->order_by('a.account_code','ASC')
        ->get()->result_array();

        $income = [];
        $expense = [];
        $totIncome = 0.0;
        $totExpense = 0.0;

        foreach ($rows as $r) {
            $d = (float)$r['debit'];
            $c = (float)$r['credit'];

            if ($r['root_type'] === 'Income') {
                $net = $c - $d;
                if (abs($net) < 0.01) continue;
                $income[] = [
                    'account_id' => (int)$r['account_id'],
                    'account_code' => $r['account_code'],
                    'account_name' => $r['account_name'],
                    'amount' => (float)$net
                ];
                $totIncome += $net;
            } else {
                $net = $d - $c;
                if (abs($net) < 0.01) continue;
                $expense[] = [
                    'account_id' => (int)$r['account_id'],
                    'account_code' => $r['account_code'],
                    'account_name' => $r['account_name'],
                    'amount' => (float)$net
                ];
                $totExpense += $net;
            }
        }

        $netProfit = $totIncome - $totExpense;

        return [
            'income' => $income,
            'expense' => $expense,
            'totals' => [
                'total_income' => (float)$totIncome,
                'total_expense' => (float)$totExpense,
                'net_profit' => (float)$netProfit
            ],
            'filters' => compact('date_from','date_to')
        ];
    }

    // =========================
    // Balance Sheet
    // =========================
    public function balance_sheet($f)
    {
        // support FE yang mungkin kirim date_to (fallback)
        $as_of = $this->_valid_date($f['as_of'] ?? null, null);
        if (!$as_of) $as_of = $this->_valid_date($f['date_to'] ?? null, date('Y-m-d'));

        $year = (int)date('Y', strtotime($as_of));
        $fy_start = $year . '-01-01';

        // cek FY closing SUBMITTED
        $closing = $this->erp->select('closing_id,status,closing_date,je_id,net_profit')
            ->from('year_end_closings')
            ->where([
                'company_id' => $this->company_id,
                'fiscal_year'=> $year,
                'status'     => 'SUBMITTED'
            ])->get()->row_array();

        $is_closed = $closing ? true : false;

        // Closing balance per account up to as_of
        $rows = $this->erp->select("
            a.account_id, a.account_code, a.account_name, a.root_type,
            COALESCE(SUM(g.debit),0) AS debit,
            COALESCE(SUM(g.credit),0) AS credit
        ", false)
        ->from('accounts a')
        ->join('gl_entries g', "g.account_id = a.account_id
            AND g.company_id = a.company_id
            AND g.status = 'POSTED'
            AND g.posting_date <= ".$this->erp->escape($as_of)."
        ", 'left', false)
        ->where('a.company_id', $this->company_id)
        ->where('a.region_id', $this->auth_region())
        ->where('a.is_active', 1)
        ->where_in('a.root_type', ['Asset','Liability','Equity'])
        ->where('a.is_postable', 1)
        ->group_by('a.account_id')
        ->order_by('a.root_type','ASC')
        ->order_by('a.account_code','ASC')
        ->get()->result_array();

        $assets = [];
        $liabilities = [];
        $equity = [];
        $totA = 0.0; $totL = 0.0; $totE = 0.0;

        foreach ($rows as $r) {
            $d = (float)$r['debit'];
            $c = (float)$r['credit'];

            if ($r['root_type'] === 'Asset') {
                $bal = $d - $c;
                if (abs($bal) < 0.01) continue;
                $assets[] = [
                    'account_id' => (int)$r['account_id'],
                    'account_code' => $r['account_code'],
                    'account_name' => $r['account_name'],
                    'amount' => (float)$bal
                ];
                $totA += $bal;
            } elseif ($r['root_type'] === 'Liability') {
                $bal = $c - $d;
                if (abs($bal) < 0.01) continue;
                $liabilities[] = [
                    'account_id' => (int)$r['account_id'],
                    'account_code' => $r['account_code'],
                    'account_name' => $r['account_name'],
                    'amount' => (float)$bal
                ];
                $totL += $bal;
            } else { // Equity
                $bal = $c - $d;
                if (abs($bal) < 0.01) continue;
                $equity[] = [
                    'account_id' => (int)$r['account_id'],
                    'account_code' => $r['account_code'],
                    'account_name' => $r['account_name'],
                    'amount' => (float)$bal
                ];
                $totE += $bal;
            }
        }

        // FY belum closed => preview current year earnings
        $cy_profit = 0.0;
        if (!$is_closed) {
            $cy_profit = $this->_net_profit_range($fy_start, $as_of);
            if (abs($cy_profit) >= 0.01) {
                $equity[] = [
                    'account_id' => 0,
                    'account_code' => '',
                    'account_name' => 'Current Year Earnings (Preview)',
                    'amount' => (float)$cy_profit
                ];
                $totE += $cy_profit;
            }
        }

        return [
            'as_of' => $as_of,
            'fiscal_year' => $year,
            'is_closed' => $is_closed,
            'closing' => $closing ?: null,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totals' => [
                'total_assets' => (float)$totA,
                'total_liabilities' => (float)$totL,
                'total_equity' => (float)$totE,
                'check' => (float)($totA - ($totL + $totE))
            ],
            'meta' => [
                'current_year_profit_preview' => (float)$cy_profit,
                'preview_range' => [$fy_start, $as_of]
            ]
        ];
    }

    // helper: net profit dalam range (Income - Expense)
    private function _net_profit_range($date_from, $date_to)
    {
        $row = $this->erp->select("
            COALESCE(SUM(CASE WHEN a.root_type='Income' THEN (g.credit - g.debit) ELSE 0 END),0) AS inc,
            COALESCE(SUM(CASE WHEN a.root_type='Expense' THEN (g.debit - g.credit) ELSE 0 END),0) AS exp
        ", false)
        ->from('gl_entries g')
        ->join('accounts a','a.account_id=g.account_id','inner')
        ->where('g.company_id', $this->company_id)
        ->where('g.status', 'POSTED')
        ->where('g.posting_date >=', $date_from)
        ->where('g.posting_date <=', $date_to)
        ->get()->row_array();

        $inc = (float)($row['inc'] ?? 0);
        $exp = (float)($row['exp'] ?? 0);
        return $inc - $exp;
    }
}