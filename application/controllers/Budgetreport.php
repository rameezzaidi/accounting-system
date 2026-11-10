<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Budget report: budget against actual (posted vouchers, year-end closing excluded) for a range of months */
class Budgetreport extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Report_model');
	}

	private function params()
	{
		$fy = $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
		if (!$fy) { return 'No fiscal year found.'; }
		$fm = substr($fy->start_date, 0, 7); $em = substr($fy->end_date, 0, 7);
		$from = (string) $this->input->get('from'); $to = (string) $this->input->get('to');
		if (!month_range($from)) { $from = $fm; }
		if (!month_range($to)) { $to = min(date('Y-m'), $em); if ($to < $fm) { $to = $em; } }
		$from = max($from, $fm); $to = min($to, $em);
		if ($from > $to) { return 'The "from" month cannot be after the "to" month.'; }
		return array('fy' => $fy, 'from_m' => $from, 'to_m' => $to, 'from' => max(month_range($from)[0], $fy->start_date), 'to' => min(month_range($to)[1], $fy->end_date));
	}

	private function compute($p)
	{
		$idx = function ($ym) use ($p) { $a = explode('-', substr($p['fy']->start_date, 0, 7)); $b = explode('-', $ym); return ((int) $b[0] - (int) $a[0]) * 12 + ((int) $b[1] - (int) $a[1]); };
		$i1 = $idx($p['from_m']) + 1; $i2 = $idx($p['to_m']) + 1;
		$bud = array();
		foreach ($this->db->where('fiscal_year_id', $p['fy']->id)->get('budget_items')->result() as $b) {
			$s = 0.0; for ($i = $i1; $i <= $i2; $i++) { $s += (float) $b->{'m' . $i}; }
			$bud[(int) $b->account_id] = round($s, 2);
		}
		$sections = array('income' => array(), 'expense' => array());
		$tot = array('income' => array('b' => 0, 'a' => 0), 'expense' => array('b' => 0, 'a' => 0));
		// actual for the chosen months only (opening balance / earlier movement are not part of the figure)
		foreach ($this->Report_model->balances($p['fy']->id, $p['from'], $p['to'], true) as $r) {
			if (!in_array($r->account_type, array('income', 'expense'), true)) { continue; }
			$actual = $r->account_type === 'income' ? (float) $r->cr - (float) $r->dr : (float) $r->dr - (float) $r->cr;
			$b = isset($bud[(int) $r->id]) ? $bud[(int) $r->id] : 0.0;
			if (abs($actual) < 0.005 && abs($b) < 0.005) { continue; }
			$var = $r->account_type === 'income' ? $actual - $b : $b - $actual;     // positive = favourable
			$sections[$r->account_type][] = (object) array('id' => $r->id, 'code' => $r->code, 'name' => $r->name, 'budget' => round($b, 2), 'actual' => round($actual, 2),
				'variance' => round($var, 2), 'pct' => $b > 0 ? round($actual / $b * 100, 1) : null);
			$tot[$r->account_type]['b'] += $b; $tot[$r->account_type]['a'] += $actual;
		}
		return array('sections' => $sections, 'tot' => $tot);
	}

	public function index()
	{
		$this->require_perm('budget_report', 'view');
		$fy = $this->active_fy;
		$this->render('budget_report/index', array('page_title' => 'Budget Report', 'fys' => $this->Fy_model->all(), 'fy' => $fy,
			'to' => $fy ? min(date('Y-m'), substr($fy->end_date, 0, 7)) : '', 'can_export' => $this->can('budget_report', 'export')));
	}

	public function data()
	{
		$this->require_perm('budget_report', 'view');
		$p = $this->params();
		if (!is_array($p)) { $this->partial('ledger/_message', array('message' => $p)); }
		$this->partial('budget_report/_data', array('p' => $p, 'r' => $this->compute($p)));
	}

	public function export()
	{
		$this->require_perm('budget_report', 'export');
		$p = $this->params();
		if (!is_array($p)) { show_error($p, 400); }
		$r = $this->compute($p);
		$this->audit('budget_report', 'export', null, "Exported budget report ({$p['from_m']} to {$p['to_m']})");
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$out = array(array(setting('company_name')), array('Budget vs Actual - ' . $p['fy']->title), array('Months', $p['from_m'] . ' to ' . $p['to_m']), array(), array('Code', 'Account', 'Budget', 'Actual', 'Variance (+ favourable)', '% used'));
		foreach ($r['sections'] as $t => $rows) {
			$out[] = array(strtoupper($t));
			foreach ($rows as $x) { $out[] = array($x->code, $x->name, $n($x->budget), $n($x->actual), $n($x->variance), $x->pct === null ? '' : $x->pct); }
			$out[] = array('', 'Total ' . $t, $n($r['tot'][$t]['b']), $n($r['tot'][$t]['a']));
		}
		csv_out('budget_report_' . $p['from_m'] . '_' . $p['to_m'] . '.csv', $out);
	}
}
