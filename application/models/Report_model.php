<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * All the numbers used by the ledger, trial balance, financial statements and dashboard.
 * Only vouchers with status = 'posted' are counted.
 * A fiscal year's opening position = opening_balances rows of that year.
 */
class Report_model extends CI_Model
{
	/**
	 * One row per posting account:
	 *   ob        opening balance of the year (Dr - Cr)
	 *   prior     posted movement inside the year BEFORE $from   (Dr - Cr)
	 *   dr / cr   movement between $from and $to
	 */
	public function balances($fy_id, $from, $to, $excl_closing = false)
	{
		$ex = $excl_closing ? ' AND v.is_closing = 0' : '';
		$sql = "SELECT a.id, a.code, a.name, a.account_type, a.cash_bank, a.parent_id,
				COALESCE(ob.debit,0) - COALESCE(ob.credit,0) AS ob,
				COALESCE(p.d,0) - COALESCE(p.c,0) AS prior,
				COALESCE(m.d,0) AS dr, COALESCE(m.c,0) AS cr
			FROM accounts a
			LEFT JOIN opening_balances ob ON ob.account_id = a.id AND ob.fiscal_year_id = ?
			LEFT JOIN (SELECT vl.account_id, SUM(vl.debit) d, SUM(vl.credit) c
				FROM voucher_lines vl JOIN vouchers v ON v.id = vl.voucher_id
				WHERE v.status = 'posted'$ex AND v.fiscal_year_id = ? AND v.voucher_date < ?
				GROUP BY vl.account_id) p ON p.account_id = a.id
			LEFT JOIN (SELECT vl.account_id, SUM(vl.debit) d, SUM(vl.credit) c
				FROM voucher_lines vl JOIN vouchers v ON v.id = vl.voucher_id
				WHERE v.status = 'posted'$ex AND v.fiscal_year_id = ? AND v.voucher_date BETWEEN ? AND ?
				GROUP BY vl.account_id) m ON m.account_id = a.id
			WHERE a.is_group = 0
			ORDER BY a.code";
		return $this->db->query($sql, array((int) $fy_id, (int) $fy_id, $from, (int) $fy_id, $from, $to))->result();
	}

	/* ------------------------------------------------------------
	 | General ledger
	 * ------------------------------------------------------------ */
	public function ledger($account_ids, $fy_id, $from, $to)
	{
		$ids = implode(',', array_map('intval', $account_ids));
		$opening = $this->db->query(
			"SELECT (SELECT COALESCE(SUM(debit - credit),0) FROM opening_balances WHERE fiscal_year_id = ? AND account_id IN ($ids))
			      + (SELECT COALESCE(SUM(vl.debit - vl.credit),0) FROM voucher_lines vl JOIN vouchers v ON v.id = vl.voucher_id
			         WHERE v.status = 'posted' AND v.fiscal_year_id = ? AND v.voucher_date < ? AND vl.account_id IN ($ids)) AS bal",
			array((int) $fy_id, (int) $fy_id, $from)
		)->row();

		$rows = $this->db->query(
			"SELECT v.id AS voucher_id, v.voucher_no, v.voucher_type, v.voucher_date, v.narration, v.reference,
			        vl.description, vl.debit, vl.credit, a.code AS account_code, a.name AS account_name
			 FROM voucher_lines vl
			 JOIN vouchers v ON v.id = vl.voucher_id
			 JOIN accounts a ON a.id = vl.account_id
			 WHERE v.status = 'posted' AND v.fiscal_year_id = ? AND vl.account_id IN ($ids)
			   AND v.voucher_date BETWEEN ? AND ?
			 ORDER BY v.voucher_date, v.id, vl.id",
			array((int) $fy_id, $from, $to)
		)->result();

		$bal = round((float) $opening->bal, 2);
		$td = 0; $tc = 0;
		foreach ($rows as $r) {
			$bal = round($bal + (float) $r->debit - (float) $r->credit, 2);
			$r->balance = $bal;
			$td += (float) $r->debit; $tc += (float) $r->credit;
		}
		return array('opening' => round((float) $opening->bal, 2), 'rows' => $rows, 'total_debit' => round($td, 2), 'total_credit' => round($tc, 2), 'closing' => $bal);
	}

	/* ------------------------------------------------------------
	 | Trial balance
	 * ------------------------------------------------------------ */
	public function trial_balance($fy_id, $from, $to)
	{
		$rows = array();
		$t = array('o_dr' => 0, 'o_cr' => 0, 'm_dr' => 0, 'm_cr' => 0, 'c_dr' => 0, 'c_cr' => 0);
		foreach ($this->balances($fy_id, $from, $to) as $r) {
			$open = (float) $r->ob + (float) $r->prior;
			$close = $open + (float) $r->dr - (float) $r->cr;
			if (abs($open) < 0.005 && abs($close) < 0.005 && (float) $r->dr == 0 && (float) $r->cr == 0) { continue; }
			$r->o_dr = $open > 0 ? $open : 0;   $r->o_cr = $open < 0 ? -$open : 0;
			$r->c_dr = $close > 0 ? $close : 0; $r->c_cr = $close < 0 ? -$close : 0;
			$rows[] = $r;
			$t['o_dr'] += $r->o_dr; $t['o_cr'] += $r->o_cr; $t['m_dr'] += $r->dr; $t['m_cr'] += $r->cr;
			$t['c_dr'] += $r->c_dr; $t['c_cr'] += $r->c_cr;
		}
		foreach ($t as $k => $v) { $t[$k] = round($v, 2); }
		return array('rows' => $rows, 'totals' => $t);
	}

	/* ------------------------------------------------------------
	 | Day book (all posted vouchers in a date range, with lines)
	 * ------------------------------------------------------------ */
	public function day_book($from, $to, $type = '')
	{
		$this->db->from('vouchers v')->where('v.status', 'posted')->where('v.voucher_date >=', $from)->where('v.voucher_date <=', $to);
		if ($type) { $this->db->where('v.voucher_type', $type); }
		$vouchers = $this->db->order_by('v.voucher_date')->order_by('v.id')->get()->result();
		if (!$vouchers) { return array(); }
		$ids = array_map(function ($v) { return (int) $v->id; }, $vouchers);
		$lines = $this->db->select('vl.*, a.code AS account_code, a.name AS account_name')->from('voucher_lines vl')
			->join('accounts a', 'a.id = vl.account_id')->where_in('vl.voucher_id', $ids)->order_by('vl.id')->get()->result();
		$map = array();
		foreach ($lines as $l) { $map[$l->voucher_id][] = $l; }
		foreach ($vouchers as $v) { $v->lines = isset($map[$v->id]) ? $map[$v->id] : array(); }
		return $vouchers;
	}

	/* ------------------------------------------------------------
	 | Income statement (period movement)
	 * ------------------------------------------------------------ */
	public function income_statement($fy_id, $from, $to)
	{
		$income = array(); $expense = array(); $ti = 0; $te = 0;
		foreach ($this->balances($fy_id, $from, $to, true) as $r) {
			if ($r->account_type === 'income') {
				$amt = round((float) $r->cr - (float) $r->dr, 2);
				if ($amt != 0) { $r->amount = $amt; $income[] = $r; $ti += $amt; }
			} elseif ($r->account_type === 'expense') {
				$amt = round((float) $r->dr - (float) $r->cr, 2);
				if ($amt != 0) { $r->amount = $amt; $expense[] = $r; $te += $amt; }
			}
		}
		return array('income' => $income, 'expense' => $expense, 'total_income' => round($ti, 2),
			'total_expense' => round($te, 2), 'net' => round($ti - $te, 2));
	}

	/* ------------------------------------------------------------
	 | Statement of Expenditure: expense accounts grouped by their parent,
	 | this period / cumulative from the start of the year / month by month
	 * ------------------------------------------------------------ */
	public function soe($fy, $from, $to)
	{
		$names = array();
		foreach ($this->db->get('accounts')->result() as $a) { $names[(int) $a->id] = $a; }

		// month columns of the period
		$months = array();
		$t = strtotime(date('Y-m-01', strtotime($from))); $end = strtotime($to);
		while ($t <= $end && count($months) < 24) { $months[date('Y-m', $t)] = date('M y', $t); $t = strtotime('+1 month', $t); }

		$monthly = array();
		$q = $this->db->query(
			"SELECT vl.account_id, DATE_FORMAT(v.voucher_date,'%Y-%m') ym, SUM(vl.debit - vl.credit) amt
			 FROM voucher_lines vl JOIN vouchers v ON v.id = vl.voucher_id JOIN accounts a ON a.id = vl.account_id
			 WHERE v.status = 'posted' AND v.is_closing = 0 AND v.fiscal_year_id = ? AND a.account_type = 'expense'
			   AND v.voucher_date BETWEEN ? AND ? GROUP BY vl.account_id, ym", array((int) $fy->id, $from, $to))->result();
		foreach ($q as $r) { $monthly[(int) $r->account_id][$r->ym] = round((float) $r->amt, 2); }

		$groups = array(); $tot = array('period' => 0, 'cum' => 0, 'months' => array());
		foreach ($this->balances($fy->id, $from, $to, true) as $r) {
			if ($r->account_type !== 'expense') { continue; }
			$period = round((float) $r->dr - (float) $r->cr, 2);
			$cum = round((float) $r->prior + (float) $r->dr - (float) $r->cr, 2);
			if (abs($period) < 0.005 && abs($cum) < 0.005) { continue; }
			$pid = $r->parent_id ? (int) $r->parent_id : 0;
			if (!isset($groups[$pid])) {
				$g = isset($names[$pid]) ? $names[$pid] : null;
				$groups[$pid] = (object) array('code' => $g ? $g->code : '', 'name' => $g ? $g->name : 'Expenses', 'rows' => array(), 'period' => 0, 'cum' => 0, 'months' => array());
			}
			$r->period = $period; $r->cum = $cum;
			$r->months = isset($monthly[(int) $r->id]) ? $monthly[(int) $r->id] : array();
			$groups[$pid]->rows[] = $r;
			$groups[$pid]->period += $period; $groups[$pid]->cum += $cum;
			$tot['period'] += $period; $tot['cum'] += $cum;
			foreach ($r->months as $ym => $amt) {
				$groups[$pid]->months[$ym] = (isset($groups[$pid]->months[$ym]) ? $groups[$pid]->months[$ym] : 0) + $amt;
				$tot['months'][$ym] = (isset($tot['months'][$ym]) ? $tot['months'][$ym] : 0) + $amt;
			}
		}
		uasort($groups, function ($a, $b) { return strcmp($a->code, $b->code); });
		$tot['period'] = round($tot['period'], 2); $tot['cum'] = round($tot['cum'], 2);
		return array('groups' => array_values($groups), 'totals' => $tot, 'months' => $months);
	}

	/* ------------------------------------------------------------
	 | Balance sheet as at a date (inside one fiscal year)
	 * ------------------------------------------------------------ */
	public function balance_sheet($fy, $asat)
	{
		$assets = array(); $liab = array(); $equity = array();
		$ta = 0; $tl = 0; $te = 0; $profit = 0;
		foreach ($this->balances($fy->id, $fy->start_date, $asat) as $r) {
			$net = round((float) $r->ob + (float) $r->prior + (float) $r->dr - (float) $r->cr, 2);   // Dr - Cr
			switch ($r->account_type) {
				case 'asset':     if ($net != 0) { $r->amount = $net;  $assets[] = $r; $ta += $net; } break;
				case 'liability': if ($net != 0) { $r->amount = -$net; $liab[]   = $r; $tl -= $net; } break;
				case 'equity':    if ($net != 0) { $r->amount = -$net; $equity[] = $r; $te -= $net; } break;
				case 'income':    $profit -= $net; break;   // income is credit => adds to profit
				case 'expense':   $profit -= $net; break;   // expense is debit  => reduces profit
			}
		}
		$profit = round($profit, 2);
		$total_le = round($tl + $te + $profit, 2);
		return array('assets' => $assets, 'liabilities' => $liab, 'equity' => $equity,
			'total_assets' => round($ta, 2), 'total_liabilities' => round($tl, 2), 'total_equity' => round($te, 2),
			'profit' => $profit, 'total_le' => $total_le, 'difference' => round($ta - $total_le, 2));
	}

	/* ------------------------------------------------------------
	 | Dashboard
	 * ------------------------------------------------------------ */
	public function dashboard($fy)
	{
		$today = date('Y-m-d');
		$to = ($today > $fy->end_date) ? $fy->end_date : (($today < $fy->start_date) ? $fy->start_date : $today);
		$cash = array(); $cash_total = 0; $income = 0; $expense = 0; $assets = 0; $liab = 0;
		foreach ($this->balances($fy->id, $fy->start_date, $to, true) as $r) {
			$net = (float) $r->ob + (float) $r->prior + (float) $r->dr - (float) $r->cr;
			if ($r->cash_bank !== 'none') { $cash[] = (object) array('code' => $r->code, 'name' => $r->name, 'type' => $r->cash_bank, 'balance' => round($net, 2)); $cash_total += $net; }
			if ($r->account_type === 'income')  { $income  += (float) $r->cr - (float) $r->dr; }
			if ($r->account_type === 'expense') { $expense += (float) $r->dr - (float) $r->cr; }
			if ($r->account_type === 'asset')     { $assets += $net; }
			if ($r->account_type === 'liability') { $liab   -= $net; }
		}

		$counts = array();
		foreach ($this->db->query("SELECT voucher_type, COUNT(*) n FROM vouchers WHERE fiscal_year_id = ? AND status = 'posted' GROUP BY voucher_type", array((int) $fy->id))->result() as $r) {
			$counts[$r->voucher_type] = (int) $r->n;
		}

		// monthly income vs expense for the fiscal year
		$months = array(); $ts = strtotime($fy->start_date);
		for ($i = 0; $i < 12; $i++) {
			$m = date('Y-m', strtotime("+$i month", $ts));
			$months[$m] = array('label' => date('M y', strtotime($m . '-01')), 'income' => 0, 'expense' => 0);
		}
		$rows = $this->db->query(
			"SELECT DATE_FORMAT(v.voucher_date,'%Y-%m') ym, a.account_type, SUM(vl.debit) d, SUM(vl.credit) c
			 FROM voucher_lines vl JOIN vouchers v ON v.id = vl.voucher_id JOIN accounts a ON a.id = vl.account_id
			 WHERE v.status = 'posted' AND v.is_closing = 0 AND v.fiscal_year_id = ? AND a.account_type IN ('income','expense')
			 GROUP BY ym, a.account_type", array((int) $fy->id))->result();
		foreach ($rows as $r) {
			if (!isset($months[$r->ym])) { continue; }
			if ($r->account_type === 'income')  { $months[$r->ym]['income']  += (float) $r->c - (float) $r->d; }
			if ($r->account_type === 'expense') { $months[$r->ym]['expense'] += (float) $r->d - (float) $r->c; }
		}

		return array('cash' => $cash, 'cash_total' => round($cash_total, 2), 'income' => round($income, 2), 'expense' => round($expense, 2),
			'profit' => round($income - $expense, 2), 'assets' => round($assets, 2), 'liabilities' => round($liab, 2),
			'counts' => $counts, 'months' => array_values($months), 'as_at' => $to);
	}
}
