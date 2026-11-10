<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tax summary: tax charged / paid / withheld per rate for a period, with the resulting position */
class Taxsummary extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Report_model'));
	}

	private function params()
	{
		$fy = $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
		if (!$fy) { return 'No fiscal year found.'; }
		$from = (string) $this->input->get('from'); $to = (string) $this->input->get('to');
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = $fy->start_date; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to = min(date('Y-m-d'), $fy->end_date); if ($to < $fy->start_date) { $to = $fy->end_date; } }
		if ($from < $fy->start_date) { $from = $fy->start_date; }
		if ($to > $fy->end_date) { $to = $fy->end_date; }
		if ($from > $to) { return 'The "from" date cannot be after the "to" date.'; }
		return array('fy' => $fy, 'from' => $from, 'to' => $to);
	}

	private function compute($p)
	{
		$rows = $this->db->query(
			"SELECT e.tax_type, t.code, t.name, e.rate, COUNT(*) n, SUM(e.base_amount) base, SUM(e.tax_amount) tax
			 FROM tax_entries e JOIN vouchers v ON v.id = e.voucher_id AND v.status = 'posted' JOIN tax_rates t ON t.id = e.tax_rate_id
			 WHERE e.entry_date BETWEEN ? AND ? GROUP BY e.tax_type, t.code, t.name, e.rate ORDER BY e.tax_type, t.code, e.rate",
			array($p['from'], $p['to']))->result();
		$by = array('output' => array(), 'input' => array(), 'withholding' => array(), 'advance' => array());
		$tot = array('output' => 0, 'input' => 0, 'withholding' => 0, 'advance' => 0);
		$base = $tot;
		foreach ($rows as $r) { $by[$r->tax_type][] = $r; $tot[$r->tax_type] += (float) $r->tax; $base[$r->tax_type] += (float) $r->base; }

		// balances of the ledger accounts that tax is posted to, as at the end date
		$ids = array();
		foreach ($this->db->select('DISTINCT account_id', false)->get('tax_rates')->result() as $x) { $ids[(int) $x->account_id] = true; }
		$accs = array();
		foreach ($this->Report_model->balances($p['fy']->id, $p['fy']->start_date, $p['to'], true) as $b) {
			if (isset($ids[(int) $b->id])) { $accs[] = (object) array('code' => $b->code, 'name' => $b->name, 'type' => $b->account_type,
				'balance' => round((float) $b->ob + (float) $b->prior + (float) $b->dr - (float) $b->cr, 2)); }
		}
		return array('by' => $by, 'tot' => $tot, 'base' => $base, 'net_sales_tax' => round($tot['output'] - $tot['input'], 2), 'accounts' => $accs);
	}

	public function index()
	{
		$this->require_perm('tax_report', 'view');
		$fy = $this->active_fy;
		$this->render('tax_summary/index', array('page_title' => 'Tax Summary', 'fys' => $this->Fy_model->all(), 'fy' => $fy,
			'to' => $fy ? min(date('Y-m-d'), $fy->end_date) : '', 'can_export' => $this->can('tax_report', 'export')));
	}

	public function data()
	{
		$this->require_perm('tax_report', 'view');
		$p = $this->params();
		if (!is_array($p)) { $this->partial('ledger/_message', array('message' => $p)); }
		$this->partial('tax_summary/_data', array('p' => $p, 'r' => $this->compute($p)));
	}

	public function export()
	{
		$this->require_perm('tax_report', 'export');
		$p = $this->params();
		if (!is_array($p)) { show_error($p, 400); }
		$r = $this->compute($p);
		$this->audit('tax_report', 'export', null, "Exported tax summary ({$p['from']} to {$p['to']})");
		$q = function ($v) { return '"' . str_replace('"', '""', (string) $v) . '"'; };
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$types = tax_types();
		$out = $q(setting('company_name')) . "\n" . $q('Tax Summary - ' . $p['fy']->title) . "\nPeriod,{$p['from']} to {$p['to']}\n\nType,Code,Name,Rate %,Entries,Base amount,Tax amount\n";
		foreach ($r['by'] as $t => $rows) {
			foreach ($rows as $x) { $out .= implode(',', array($q($types[$t]), $x->code, $q($x->name), (float) $x->rate, $x->n, $n($x->base), $n($x->tax))) . "\n"; }
			$out .= $q('Total ' . $types[$t]) . ",,,,," . $n($r['base'][$t]) . ',' . $n($r['tot'][$t]) . "\n";
		}
		$out .= "\n" . $q('Net sales tax payable (output - input)') . ',' . $n($r['net_sales_tax']) . "\n";
		$this->output->set_content_type('text/csv', 'utf-8')->set_header('Content-Disposition: attachment; filename="tax_summary_' . $p['from'] . '_' . $p['to'] . '.csv"')->set_output("\xEF\xBB\xBF" . $out);
	}
}
