<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Graphs: charts of the books for a fiscal year */
class Graphs extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Report_model', 'Supplier_model'));
	}

	public function index()
	{
		$this->require_perm('graphs', 'view');
		$this->render('graphs/index', array('page_title' => 'Graphs', 'fys' => $this->Fy_model->all(), 'fy' => $this->active_fy,
			'scripts' => array('assets/vendor/chart.min.js', 'assets/js/graphs.js')));
	}

	public function data()
	{
		$this->require_perm('graphs', 'view');
		$fy = $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
		if (!$fy) { $this->json(array('status' => false, 'message' => 'No fiscal year found.')); }
		$to = min(date('Y-m-d'), $fy->end_date); if ($to < $fy->start_date) { $to = $fy->start_date; }

		$dash = $this->Report_model->dashboard($fy);
		$inc = array(); $exp = array(); $cash = array();
		foreach ($this->Report_model->balances($fy->id, $fy->start_date, $fy->end_date, true) as $b) {
			if ($b->account_type === 'income')  { $v = (float) $b->cr - (float) $b->dr; if ($v > 0.004) { $inc[] = array($b->code . ' ' . $b->name, round($v, 2)); } }
			if ($b->account_type === 'expense') { $v = (float) $b->dr - (float) $b->cr; if ($v > 0.004) { $exp[] = array($b->code . ' ' . $b->name, round($v, 2)); } }
		}
		foreach ($this->Report_model->balances($fy->id, $fy->start_date, $to, true) as $b) {
			if ($b->cash_bank !== 'none') { $cash[] = array($b->code . ' ' . $b->name, round((float) $b->ob + (float) $b->prior + (float) $b->dr - (float) $b->cr, 2)); }
		}
		$top = function ($list) { usort($list, function ($a, $b) { return $b[1] <=> $a[1]; }); $o = array_slice($list, 0, 7); $rest = 0; foreach (array_slice($list, 7) as $x) { $rest += $x[1]; } if ($rest > 0) { $o[] = array('Others', round($rest, 2)); } return $o; };

		// budget vs actual per month (expense side)
		$bud = array_fill(0, 12, 0.0);
		foreach ($this->db->query("SELECT b.* FROM budget_items b JOIN accounts a ON a.id = b.account_id WHERE b.fiscal_year_id = ? AND a.account_type = 'expense'", array($fy->id))->result() as $b) {
			for ($i = 1; $i <= 12; $i++) { $bud[$i - 1] += (float) $b->{'m' . $i}; }
		}
		$budinc = array_fill(0, 12, 0.0);
		foreach ($this->db->query("SELECT b.* FROM budget_items b JOIN accounts a ON a.id = b.account_id WHERE b.fiscal_year_id = ? AND a.account_type = 'income'", array($fy->id))->result() as $b) {
			for ($i = 1; $i <= 12; $i++) { $budinc[$i - 1] += (float) $b->{'m' . $i}; }
		}

		// payables per supplier
		$pay = array();
		foreach ($this->Supplier_model->listing() as $s) { $bal = round((float) $s->billed - (float) $s->paid, 2); if ($bal > 0.004) { $pay[] = array($s->name, $bal); } }

		// projects
		$this->load->model('Project_model');
		$proj = array();
		foreach ($this->db->order_by('code')->get('projects')->result() as $p) { $t = $this->Project_model->totals($p->id); $proj[] = array('label' => $p->code, 'budget' => (float) $p->budget, 'spent' => $t['expense'], 'income' => $t['income']); }

		// payroll by month
		$pr = array();
		foreach ($this->db->query("SELECT r.period, SUM(r.gross) g, SUM(r.net) n FROM payroll_runs r JOIN vouchers v ON v.id = r.voucher_id AND v.status = 'posted'
			WHERE r.period BETWEEN ? AND ? GROUP BY r.period ORDER BY r.period", array(substr($fy->start_date, 0, 7), substr($fy->end_date, 0, 7)))->result() as $r) {
			$pr[] = array('label' => date('M y', strtotime($r->period . '-01')), 'gross' => (float) $r->g, 'net' => (float) $r->n);
		}

		$this->json(array('status' => true, 'fy' => $fy->title, 'months' => $dash['months'], 'income' => $top($inc), 'expense' => $top($exp), 'cash' => $cash,
			'budget_exp' => array_map(function ($v) { return round($v, 2); }, $bud), 'budget_inc' => array_map(function ($v) { return round($v, 2); }, $budinc),
			'payables' => $top($pay), 'projects' => $proj, 'payroll' => $pr));
	}
}
