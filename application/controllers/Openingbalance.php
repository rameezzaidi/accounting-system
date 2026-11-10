<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Opening balances of every posting account for a fiscal year */
class Openingbalance extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model'));
	}

	public function index()
	{
		$this->require_perm('opening_balance', 'view');
		$fys = $this->Fy_model->all();
		$fy_id = (int) $this->input->get('fy') ?: ($this->active_fy ? (int) $this->active_fy->id : 0);
		$fy = $fy_id ? $this->Fy_model->get($fy_id) : null;

		$rows = array();
		if ($fy) {
			$ob = array();
			foreach ($this->db->where('fiscal_year_id', $fy->id)->get('opening_balances')->result() as $r) { $ob[(int) $r->account_id] = $r; }
			foreach ($this->Account_model->tree() as $a) {
				if ((int) $a->is_group === 0 && !(int) $a->is_active && !isset($ob[(int) $a->id])) { continue; }
				$a->debit  = isset($ob[(int) $a->id]) ? (float) $ob[(int) $a->id]->debit : 0;
				$a->credit = isset($ob[(int) $a->id]) ? (float) $ob[(int) $a->id]->credit : 0;
				$rows[] = $a;
			}
		}
		$this->render('opening_balance/index', array(
			'page_title' => 'Opening Balance', 'fys' => $fys, 'fy' => $fy, 'rows' => $rows,
			'can_save' => $this->can('opening_balance', 'save') && $fy && !(int) $fy->is_closed,
			'scripts' => array('assets/js/opening_balance.js'),
		));
	}

	public function save()
	{
		$this->post_only();
		$this->require_perm('opening_balance', 'save');
		$fy = $this->Fy_model->get((int) $this->input->post('fy_id'));
		if (!$fy) { $this->fail('Fiscal year not found.'); }
		if ((int) $fy->is_closed) { $this->fail('This fiscal year is closed. Opening balances cannot be changed.'); }

		$ids = (array) $this->input->post('account_id');
		$dr  = (array) $this->input->post('debit');
		$cr  = (array) $this->input->post('credit');

		$posting = array();
		foreach ($this->Account_model->posting(false) as $a) { $posting[(int) $a->id] = $a; }

		$rows = array(); $td = 0; $tc = 0;
		foreach ($ids as $i => $aid) {
			$aid = (int) $aid;
			$d = round((float) str_replace(',', '', isset($dr[$i]) ? $dr[$i] : 0), 2);
			$c = round((float) str_replace(',', '', isset($cr[$i]) ? $cr[$i] : 0), 2);
			if ($d == 0 && $c == 0) { continue; }
			if (!isset($posting[$aid])) { $this->fail('Invalid account in the list.'); }
			if ($d < 0 || $c < 0) { $this->fail("{$posting[$aid]->code}: amounts cannot be negative."); }
			if ($d > 0 && $c > 0) { $this->fail("{$posting[$aid]->code} {$posting[$aid]->name}: enter either debit or credit, not both."); }
			$rows[$aid] = array('fiscal_year_id' => (int) $fy->id, 'account_id' => $aid, 'debit' => $d, 'credit' => $c);
			$td += $d; $tc += $c;
		}
		$td = round($td, 2); $tc = round($tc, 2);
		if (abs($td - $tc) > 0.004) {
			$this->fail('Opening balances are not balanced. Debit ' . number_format($td, 2) . ' / Credit ' . number_format($tc, 2) . ' (difference ' . number_format(abs($td - $tc), 2) . ').');
		}

		$before = $this->db->where('fiscal_year_id', $fy->id)->count_all_results('opening_balances');
		$this->db->trans_start();
		$this->db->where('fiscal_year_id', $fy->id)->delete('opening_balances');
		if ($rows) { $this->db->insert_batch('opening_balances', array_values($rows)); }
		$this->db->trans_complete();
		if (!$this->db->trans_status()) { $this->fail('Could not save. Please try again.'); }

		$this->audit('opening_balance', 'save', $fy->id, "Saved opening balances for {$fy->title}: " . count($rows) . ' accounts, total ' . number_format($td, 2),
			array('accounts' => $before), array('accounts' => count($rows), 'total_debit' => $td, 'total_credit' => $tc));
		$this->ok('Opening balances saved (' . count($rows) . ' accounts).');
	}
}
