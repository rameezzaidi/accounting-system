<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Year-end closing.
 *  close():  posts one closing journal (income + expense accounts -> retained earnings),
 *            carries every balance-sheet balance into the next year's opening balances,
 *            marks the year closed (no more entries / edits / voids).
 *  reopen(): voids the closing journal, removes the carried opening balances, reopens the year.
 */
class Closing_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Report_model', 'Fy_model', 'Voucher_model'));
	}

	public function next_fy($fy)
	{
		return $this->db->where('start_date', date('Y-m-d', strtotime($fy->end_date . ' +1 day')))->get('fiscal_years')->row();
	}

	public function re_accounts()
	{
		return $this->db->where(array('account_type' => 'equity', 'is_group' => 0, 'is_active' => 1))->order_by('code')->get('accounts')->result();
	}

	/** everything the closing screen needs, and the reasons closing is not possible */
	public function plan($fy)
	{
		$lines = array(); $inc = 0; $exp = 0; $profit = 0;
		foreach ($this->Report_model->balances($fy->id, $fy->start_date, $fy->end_date, true) as $r) {
			if ($r->account_type !== 'income' && $r->account_type !== 'expense') { continue; }
			$net = round((float) $r->ob + (float) $r->prior + (float) $r->dr - (float) $r->cr, 2);   // Dr - Cr
			if (abs($net) < 0.005) { continue; }
			if ($r->account_type === 'income') { $inc -= $net; } else { $exp += $net; }
			$profit -= $net;
			$lines[] = array('account_id' => (int) $r->id, 'code' => $r->code, 'name' => $r->name, 'type' => $r->account_type,
				'debit' => $net < 0 ? -$net : 0, 'credit' => $net > 0 ? $net : 0);
		}

		$blockers = array();
		if ((int) $fy->is_closed) { $blockers[] = 'This fiscal year is already closed.'; }
		foreach ($this->db->where('end_date <', $fy->start_date)->where('is_closed', 0)->get('fiscal_years')->result() as $e) {
			if ($this->Fy_model->has_data($e->id)) { $blockers[] = "Close the earlier year {$e->title} first."; }
		}
		$next = $this->next_fy($fy);
		if ($next && (int) $next->is_closed) { $blockers[] = "The next year {$next->title} is already closed."; }
		if (!$this->re_accounts()) { $blockers[] = 'Add an active equity account (for example Retained Earnings) in the Chart of Accounts first.'; }

		$next_ob = $next ? $this->db->where('fiscal_year_id', $next->id)->count_all_results('opening_balances') : 0;
		$s = date('Y-m-d', strtotime($fy->end_date . ' +1 day'));
		$e = date('Y-m-d', strtotime($s . ' +1 year -1 day'));
		return array('lines' => $lines, 'income' => round($inc, 2), 'expense' => round($exp, 2), 'profit' => round($profit, 2),
			'blockers' => $blockers, 'next' => $next, 'next_ob' => $next_ob,
			'next_title' => $next ? $next->title : 'FY ' . date('Y', strtotime($s)) . '-' . date('y', strtotime($e)),
			'next_start' => $s, 'next_end' => $e);
	}

	private function abort(&$err, $msg)
	{
		$this->db->trans_rollback();
		$err = $msg;
		return false;
	}

	public function close($fy, $re_id, $replace, $user_id, &$err)
	{
		$plan = $this->plan($fy);
		if ($plan['blockers']) { $err = implode(' ', $plan['blockers']); return false; }
		$re = $this->db->where('id', (int) $re_id)->get('accounts')->row();
		if (!$re || $re->account_type !== 'equity' || (int) $re->is_group || !(int) $re->is_active) { $err = 'Select an active equity account for the profit / loss.'; return false; }
		if ($plan['next'] && $plan['next_ob'] > 0 && !$replace) {
			$err = "{$plan['next']->title} already has opening balances. Tick 'replace' to overwrite them with the carried-forward balances.";
			return false;
		}

		$this->db->trans_begin();

		// 1. closing journal
		$vid = null;
		if ($plan['lines']) {
			$rows = array();
			foreach ($plan['lines'] as $l) { $rows[] = array('account_id' => $l['account_id'], 'description' => 'Year-end closing', 'debit' => $l['debit'], 'credit' => $l['credit']); }
			if ($plan['profit'] > 0)      { $rows[] = array('account_id' => (int) $re->id, 'description' => 'Net profit for ' . $fy->title, 'debit' => 0, 'credit' => $plan['profit']); }
			elseif ($plan['profit'] < 0)  { $rows[] = array('account_id' => (int) $re->id, 'description' => 'Net loss for ' . $fy->title, 'debit' => -$plan['profit'], 'credit' => 0); }
			list($clean, $total, $e) = $this->Voucher_model->clean_lines($rows);
			if ($e) { return $this->abort($err, $e); }
			$vid = $this->Voucher_model->create(array('type' => 'JV', 'date' => $fy->end_date, 'reference' => 'CLOSING',
				'narration' => 'Year-end closing of ' . $fy->title . ': income and expense accounts transferred to ' . $re->name, 'user_id' => $user_id), $clean, $total, $fy);
			if (!$vid) { return $this->abort($err, 'Could not post the closing journal.'); }
			$this->db->where('id', $vid)->update('vouchers', array('is_closing' => 1));
		}

		// 2. next fiscal year
		$next = $plan['next'];
		if (!$next) {
			if ($this->Fy_model->overlaps($plan['next_start'], $plan['next_end'])) { return $this->abort($err, 'The next fiscal year cannot be created because its dates overlap another year.'); }
			$title = $plan['next_title'];
			if ($this->Fy_model->title_exists($title)) { return $this->abort($err, "A fiscal year titled '$title' already exists with different dates. Rename it in Settings > Fiscal Years."); }
			$this->db->insert('fiscal_years', array('title' => $title, 'start_date' => $plan['next_start'], 'end_date' => $plan['next_end'], 'is_active' => 0, 'is_closed' => 0, 'created_at' => date('Y-m-d H:i:s')));
			$next = $this->Fy_model->get($this->db->insert_id());
		}

		// 3. carry balance sheet balances forward
		$this->db->where('fiscal_year_id', $next->id)->delete('opening_balances');
		$ob = array(); $td = 0; $tc = 0;
		foreach ($this->Report_model->balances($fy->id, $fy->start_date, $fy->end_date, false) as $r) {
			if (!in_array($r->account_type, array('asset', 'liability', 'equity'), true)) { continue; }
			$net = round((float) $r->ob + (float) $r->prior + (float) $r->dr - (float) $r->cr, 2);
			if (abs($net) < 0.005) { continue; }
			$ob[] = array('fiscal_year_id' => (int) $next->id, 'account_id' => (int) $r->id, 'debit' => $net > 0 ? $net : 0, 'credit' => $net < 0 ? -$net : 0);
			if ($net > 0) { $td += $net; } else { $tc -= $net; }
		}
		if (abs($td - $tc) > 0.004) {
			return $this->abort($err, 'The books of this year are out of balance by ' . number_format(abs($td - $tc), 2) . '. Check the opening balances of ' . $fy->title . ' before closing.');
		}
		if ($ob) { $this->db->insert_batch('opening_balances', $ob); }

		// 4. mark closed
		$this->db->where('id', $fy->id)->update('fiscal_years', array('is_closed' => 1, 'closed_at' => date('Y-m-d H:i:s'), 'closed_by' => $user_id,
			'closing_voucher_id' => $vid, 'carried_to_id' => (int) $next->id));
		$this->db->where('id', $next->id)->update('fiscal_years', array('carried_from_id' => (int) $fy->id));
		if ((int) $fy->is_active) { $this->Fy_model->set_active($next->id); }

		if ($this->db->trans_status() === false) { return $this->abort($err, 'Could not close the year. Nothing was changed.'); }
		$this->db->trans_commit();
		return array('voucher_id' => $vid, 'next' => $next, 'profit' => $plan['profit'], 'accounts_carried' => count($ob), 'lines' => count($plan['lines']));
	}

	public function reopen($fy, $reason, $user_id, &$err)
	{
		if (!(int) $fy->is_closed) { $err = 'This fiscal year is not closed.'; return false; }
		$next = $fy->carried_to_id ? $this->Fy_model->get($fy->carried_to_id) : null;
		if ($next && (int) $next->is_closed) { $err = "The next year {$next->title} is closed. Reopen it first."; return false; }

		$this->db->trans_begin();
		if ($fy->closing_voucher_id) {
			$this->Voucher_model->void($fy->closing_voucher_id, mb_substr('Fiscal year reopened: ' . $reason, 0, 255), $user_id);
		}
		if ($next) {
			$this->db->where('fiscal_year_id', $next->id)->delete('opening_balances');
			$this->db->where('id', $next->id)->update('fiscal_years', array('carried_from_id' => null));
		}
		$this->db->where('id', $fy->id)->update('fiscal_years', array('is_closed' => 0, 'closed_at' => null, 'closed_by' => null, 'closing_voucher_id' => null, 'carried_to_id' => null));
		if ($this->db->trans_status() === false) { $this->db->trans_rollback(); $err = 'Could not reopen the year.'; return false; }
		$this->db->trans_commit();
		return true;
	}
}
