<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Banking report: opening, receipts, payments and closing of every cash / bank account, plus reconciliation status */
class Bankreport extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Bank_model', 'Report_model'));
	}

	private function compute($p)
	{
		$rows = array(); $tot = array('opening' => 0, 'receipts' => 0, 'payments' => 0, 'closing' => 0);
		foreach ($this->Report_model->balances($p['fy']->id, $p['from'], $p['to'], true) as $b) {
			if ($b->cash_bank === 'none') { continue; }
			$open = round((float) $b->ob + (float) $b->prior, 2);
			if (!$open && !(float) $b->dr && !(float) $b->cr) { continue; }
			$x = (object) array('id' => $b->id, 'code' => $b->code, 'name' => $b->name, 'kind' => $b->cash_bank, 'opening' => $open, 'receipts' => (float) $b->dr, 'payments' => (float) $b->cr);
			$x->closing = round($open + $x->receipts - $x->payments, 2);
			$x->last = $b->cash_bank === 'bank' ? $this->Bank_model->last_recon($b->id) : null;
			$x->open_items = $b->cash_bank === 'bank' ? $this->Bank_model->uncleared_count($b->id, $p['fy'], $p['to']) : null;
			foreach ($tot as $k => $_) { $tot[$k] += $x->$k; }
			$rows[] = $x;
		}
		return array('rows' => $rows, 'tot' => $tot);
	}

	public function index()
	{
		$this->require_perm('bank_report', 'view');
		$fy = $this->active_fy;
		$this->render('bank_report/index', array('page_title' => 'Banking Report', 'fys' => $this->Fy_model->all(), 'fy' => $fy,
			'to' => $fy ? min(date('Y-m-d'), $fy->end_date) : '', 'can_export' => $this->can('bank_report', 'export')));
	}

	public function data()
	{
		$this->require_perm('bank_report', 'view');
		$p = $this->period_params();
		if (!is_array($p)) { $this->partial('ledger/_message', array('message' => $p)); }
		$this->partial('bank_report/_data', array('p' => $p, 'r' => $this->compute($p)));
	}

	public function export()
	{
		$this->require_perm('bank_report', 'export');
		$p = $this->period_params();
		if (!is_array($p)) { show_error($p, 400); }
		$r = $this->compute($p);
		$this->audit('bank_report', 'export', null, "Exported banking report ({$p['from']} to {$p['to']})");
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$out = array(array(setting('company_name')), array('Banking Report', $p['from'] . ' to ' . $p['to']), array(), array('Code', 'Account', 'Type', 'Opening', 'Receipts', 'Payments', 'Closing', 'Last reconciled', 'Uncleared items'));
		foreach ($r['rows'] as $x) { $out[] = array($x->code, $x->name, $x->kind, $n($x->opening), $n($x->receipts), $n($x->payments), $n($x->closing), $x->last ? $x->last->statement_date : '', $x->open_items === null ? '' : $x->open_items); }
		$out[] = array('', 'Total', '', $n($r['tot']['opening']), $n($r['tot']['receipts']), $n($r['tot']['payments']), $n($r['tot']['closing']));
		csv_out('banking_report_' . $p['from'] . '_' . $p['to'] . '.csv', $out);
	}
}
