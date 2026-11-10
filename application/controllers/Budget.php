<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Budget: a monthly amount for every income and expense account of a fiscal year */
class Budget extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Account_model');
	}

	private function fy_from_get()
	{
		return $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
	}

	public function index()
	{
		$this->require_perm('budget', 'view');
		$fy = $this->fy_from_get();
		$accounts = array(); $items = array(); $labels = array();
		if ($fy) {
			foreach ($this->Account_model->posting(false) as $a) { if (in_array($a->account_type, array('income', 'expense'), true)) { $accounts[] = $a; } }
			foreach ($this->db->where('fiscal_year_id', $fy->id)->get('budget_items')->result() as $b) { $items[(int) $b->account_id] = $b; }
			$start = strtotime(substr($fy->start_date, 0, 7) . '-01');
			for ($i = 0; $i < 12; $i++) { $labels[] = date('M y', strtotime("+$i month", $start)); }
		}
		$this->render('budget/index', array('page_title' => 'Budget', 'fys' => $this->Fy_model->all(), 'fy' => $fy, 'accounts' => $accounts, 'items' => $items,
			'labels' => $labels, 'can_edit' => $this->can('budget', 'edit') && $fy && !(int) $fy->is_closed));
	}

	public function save()
	{
		$this->post_only();
		$this->require_perm('budget', 'edit');
		$fy = $this->Fy_model->get((int) $this->input->post('fy_id'));
		if (!$fy) { $this->fail('Fiscal year not found.'); }
		if ((int) $fy->is_closed) { $this->fail("Fiscal year {$fy->title} is closed. Its budget cannot be changed."); }
		$post = json_decode((string) $this->input->post('payload'), true);
		if (!is_array($post)) { $this->fail('Nothing to save.'); }

		$valid = array();
		foreach ($this->Account_model->posting(false) as $a) { if (in_array($a->account_type, array('income', 'expense'), true)) { $valid[(int) $a->id] = $a; } }
		$existing = array();
		foreach ($this->db->where('fiscal_year_id', $fy->id)->get('budget_items')->result() as $b) { $existing[(int) $b->account_id] = $b; }

		$changes = array(); $saved = 0;
		$this->db->trans_begin();
		foreach ($post as $aid => $months) {
			$aid = (int) $aid;
			if (!isset($valid[$aid]) || !is_array($months)) { continue; }
			$vals = array(); $sum = 0.0;
			for ($i = 1; $i <= 12; $i++) {
				$v = isset($months[$i]) ? round((float) str_replace(',', '', (string) $months[$i]), 2) : 0.0;
				if ($v < 0) { $this->db->trans_rollback(); $this->fail("Negative amount for {$valid[$aid]->code}. Budgets cannot be negative."); }
				$vals['m' . $i] = $v; $sum += $v;
			}
			$had = isset($existing[$aid]);
			if ($sum == 0 && !$had) { continue; }
			if ($had) {
				$same = true; foreach ($vals as $k => $v) { if (abs((float) $existing[$aid]->$k - $v) > 0.004) { $same = false; } }
				if ($same) { continue; }
			}
			$old = $had ? array_sum(array_map(function ($i) use ($existing, $aid) { return (float) $existing[$aid]->{'m' . $i}; }, range(1, 12))) : 0;
			if ($sum == 0) { $this->db->where('id', $existing[$aid]->id)->delete('budget_items'); }
			elseif ($had) { $this->db->where('id', $existing[$aid]->id)->update('budget_items', $vals); }
			else { $this->db->insert('budget_items', array_merge(array('fiscal_year_id' => $fy->id, 'account_id' => $aid), $vals)); }
			$changes[] = $valid[$aid]->code . ': ' . number_format($old, 2) . ' -> ' . number_format($sum, 2);
			$saved++;
		}
		if ($this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save the budget. Please try again.'); }
		$this->db->trans_commit();
		if ($saved) { $this->audit('budget', 'update', $fy->id, "Budget {$fy->title}: $saved account(s) changed", null, array('changes' => $changes)); }
		$this->ok($saved ? "Budget saved ($saved account" . ($saved > 1 ? 's' : '') . ' changed).' : 'No changes to save.');
	}
}
