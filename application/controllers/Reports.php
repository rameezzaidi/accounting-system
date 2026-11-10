<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Accounts reports (trial balance, day book) and financial reports (income statement, balance sheet) */
class Reports extends MY_Controller
{
	/** name => [group, permission module, title, filter mode] */
	private $defs = array(
		'trial_balance'    => array('accounts',  'rep_accounts',  'Trial Balance',    'range'),
		'day_book'         => array('accounts',  'rep_accounts',  'Day Book',         'range'),
		'income_statement' => array('financial', 'rep_financial', 'Income Statement', 'range'),
		'balance_sheet'    => array('financial', 'rep_financial', 'Balance Sheet',    'asat'),
	);

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Report_model'));
	}

	public function accounts($which = 'trial_balance') { $this->page('accounts', $which); }
	public function financial($which = 'income_statement') { $this->page('financial', $which); }

	private function page($group, $which)
	{
		if (!isset($this->defs[$which]) || $this->defs[$which][0] !== $group) { show_404(); }
		$perm = $this->defs[$which][1];
		$this->require_perm($perm, 'view');
		$tabs = array();
		foreach ($this->defs as $k => $d) { if ($d[0] === $group && $this->can($d[1], 'view')) { $tabs[$k] = $d[2]; } }
		$fy = $this->active_fy;
		$this->render('reports/page', array(
			'page_title' => ($group === 'accounts' ? 'Accounts Report' : 'Financial Report') . ' - ' . $this->defs[$which][2],
			'group' => $group, 'which' => $which, 'title' => $this->defs[$which][2], 'mode' => $this->defs[$which][3],
			'tabs' => $tabs, 'fys' => $this->Fy_model->all(), 'fy' => $fy, 'can_export' => $this->can($perm, 'export'),
			'to' => $fy ? min(date('Y-m-d'), $fy->end_date) : '',
		));
	}

	/** read + clamp filters */
	private function params($name)
	{
		$fy = $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
		if (!$fy) { return 'No fiscal year found.'; }
		$from = (string) $this->input->get('from'); $to = (string) $this->input->get('to');
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = $fy->start_date; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to = min(date('Y-m-d'), $fy->end_date); if ($to < $fy->start_date) { $to = $fy->end_date; } }
		if ($from < $fy->start_date) { $from = $fy->start_date; }
		if ($to > $fy->end_date) { $to = $fy->end_date; }
		if ($this->defs[$name][3] === 'asat') { $from = $fy->start_date; }
		if ($from > $to) { return 'The "from" date cannot be after the "to" date.'; }
		return array('fy' => $fy, 'from' => $from, 'to' => $to, 'type' => (string) $this->input->get('type'));
	}

	private function compute($name, $p)
	{
		switch ($name) {
			case 'trial_balance':    return $this->Report_model->trial_balance($p['fy']->id, $p['from'], $p['to']);
			case 'day_book':         return $this->Report_model->day_book($p['from'], $p['to'], in_array($p['type'], array('JV', 'PV', 'RV', 'CV'), true) ? $p['type'] : '');
			case 'income_statement': return $this->Report_model->income_statement($p['fy']->id, $p['from'], $p['to']);
			case 'balance_sheet':    return $this->Report_model->balance_sheet($p['fy'], $p['to']);
		}
		return null;
	}

	public function data($name = '')
	{
		if (!isset($this->defs[$name])) { show_404(); }
		$this->require_perm($this->defs[$name][1], 'view');
		$p = $this->params($name);
		if (!is_array($p)) { $this->partial('ledger/_message', array('message' => $p)); }
		$this->partial('reports/_' . $name, array('p' => $p, 'r' => $this->compute($name, $p), 'title' => $this->defs[$name][2]));
	}

	public function export($name = '')
	{
		if (!isset($this->defs[$name])) { show_404(); }
		$this->require_perm($this->defs[$name][1], 'export');
		$p = $this->params($name);
		if (!is_array($p)) { show_error($p, 400); }
		$r = $this->compute($name, $p);
		$this->audit('reports', 'export', $name, "Exported {$this->defs[$name][2]} ({$p['from']} to {$p['to']})");

		$q = function ($v) { return '"' . str_replace('"', '""', (string) $v) . '"'; };
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$out = $q(setting('company_name')) . "\n" . $q($this->defs[$name][2] . ' - ' . $p['fy']->title) . "\nPeriod,{$p['from']} to {$p['to']}\n\n";

		if ($name === 'trial_balance') {
			$out .= "Code,Account,Type,Opening Dr,Opening Cr,Movement Dr,Movement Cr,Closing Dr,Closing Cr\n";
			foreach ($r['rows'] as $x) { $out .= implode(',', array($x->code, $q($x->name), $x->account_type, $n($x->o_dr), $n($x->o_cr), $n($x->dr), $n($x->cr), $n($x->c_dr), $n($x->c_cr))) . "\n"; }
			$t = $r['totals'];
			$out .= implode(',', array('', 'TOTAL', '', $n($t['o_dr']), $n($t['o_cr']), $n($t['m_dr']), $n($t['m_cr']), $n($t['c_dr']), $n($t['c_cr']))) . "\n";
		} elseif ($name === 'day_book') {
			$out .= "Date,Voucher,Type,Account,Narration,Debit,Credit\n";
			foreach ($r as $v) { foreach ($v->lines as $l) {
				$out .= implode(',', array($v->voucher_date, $q($v->voucher_no), $v->voucher_type, $q($l->account_code . ' ' . $l->account_name),
					$q($l->description ?: $v->narration), (float) $l->debit > 0 ? $n($l->debit) : '', (float) $l->credit > 0 ? $n($l->credit) : '')) . "\n";
			} }
		} elseif ($name === 'income_statement') {
			$out .= "Section,Code,Account,Amount\n";
			foreach ($r['income'] as $x) { $out .= implode(',', array('Income', $x->code, $q($x->name), $n($x->amount))) . "\n"; }
			$out .= 'Income,,Total income,' . $n($r['total_income']) . "\n";
			foreach ($r['expense'] as $x) { $out .= implode(',', array('Expense', $x->code, $q($x->name), $n($x->amount))) . "\n"; }
			$out .= 'Expense,,Total expense,' . $n($r['total_expense']) . "\n";
			$out .= ($r['net'] >= 0 ? 'Net profit' : 'Net loss') . ',,,' . $n(abs($r['net'])) . "\n";
		} else {
			$out .= "Section,Code,Account,Amount\n";
			foreach (array('assets' => 'Assets', 'liabilities' => 'Liabilities', 'equity' => 'Equity') as $k => $lbl) {
				foreach ($r[$k] as $x) { $out .= implode(',', array($lbl, $x->code, $q($x->name), $n($x->amount))) . "\n"; }
			}
			$out .= 'Equity,,Current period profit / (loss),' . $n($r['profit']) . "\n";
			$out .= 'Total assets,,,' . $n($r['total_assets']) . "\nTotal liabilities and equity,,," . $n($r['total_le']) . "\n";
		}
		$this->output->set_content_type('text/csv')->set_header('Content-Disposition: attachment; filename="' . $name . '.csv"');
		$this->output->set_output($out);
		$this->output->_display();
		exit;
	}
}
