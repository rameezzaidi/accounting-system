<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** General ledger: statement of any account (or a whole group) for a period */
class Ledger extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model', 'Report_model'));
	}

	/** read + clamp the filter values, returns array or error string */
	private function params()
	{
		$fy = $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
		if (!$fy) { return 'No fiscal year found.'; }
		$acc = $this->Account_model->get((int) $this->input->get('account'));
		if (!$acc) { return 'Select an account to view its ledger.'; }

		$from = (string) $this->input->get('from'); $to = (string) $this->input->get('to');
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = $fy->start_date; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to = min(date('Y-m-d'), $fy->end_date); if ($to < $fy->start_date) { $to = $fy->end_date; } }
		if ($from < $fy->start_date) { $from = $fy->start_date; }
		if ($to > $fy->end_date) { $to = $fy->end_date; }
		if ($from > $to) { return 'The "from" date cannot be after the "to" date.'; }

		$ids = (int) $acc->is_group ? $this->Account_model->descendant_ids($acc->id) : array((int) $acc->id);
		return array('fy' => $fy, 'acc' => $acc, 'from' => $from, 'to' => $to, 'ids' => $ids);
	}

	public function index()
	{
		$this->require_perm('ledger', 'view');
		$fy = $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
		$accounts = array();
		foreach ($this->Account_model->tree() as $a) { $accounts[] = $a; }
		$sel = (int) $this->input->get('account');
		$this->render('ledger/index', array(
			'page_title' => 'General Ledger', 'fys' => $this->Fy_model->all(), 'fy' => $fy, 'accounts' => $accounts, 'sel' => $sel,
			'from' => $fy ? $fy->start_date : '', 'to' => $fy ? min(date('Y-m-d'), $fy->end_date) : '',
		));
	}

	public function grid()
	{
		$this->require_perm('ledger', 'view');
		$p = $this->params();
		if (!is_array($p)) { $this->partial('ledger/_message', array('message' => $p)); }
		$data = $this->Report_model->ledger($p['ids'], $p['fy']->id, $p['from'], $p['to']);
		$this->partial('ledger/_grid', array('p' => $p, 'l' => $data, 'is_group' => (int) $p['acc']->is_group === 1));
	}

	public function export()
	{
		$this->require_perm('ledger', 'export');
		$p = $this->params();
		if (!is_array($p)) { show_error($p, 400); }
		$l = $this->Report_model->ledger($p['ids'], $p['fy']->id, $p['from'], $p['to']);
		$this->audit('ledger', 'export', $p['acc']->id, "Exported ledger of {$p['acc']->code} {$p['acc']->name} ({$p['from']} to {$p['to']})");

		$q = function ($v) { return '"' . str_replace('"', '""', (string) $v) . '"'; };
		$out = "General Ledger\nAccount," . $q($p['acc']->code . ' ' . $p['acc']->name) . "\nPeriod,{$p['from']} to {$p['to']}\n\n";
		$out .= "Date,Voucher,Account,Narration,Debit,Credit,Balance\n";
		$out .= ',,,Opening balance,,,' . $this->signed($l['opening']) . "\n";
		foreach ($l['rows'] as $r) {
			$out .= implode(',', array($r->voucher_date, $q($r->voucher_no), $q($r->account_code . ' ' . $r->account_name),
				$q($r->description !== null && $r->description !== '' ? $r->description : $r->narration),
				$r->debit > 0 ? number_format($r->debit, 2, '.', '') : '', $r->credit > 0 ? number_format($r->credit, 2, '.', '') : '', $this->signed($r->balance))) . "\n";
		}
		$out .= ',,,Total,' . number_format($l['total_debit'], 2, '.', '') . ',' . number_format($l['total_credit'], 2, '.', '') . ',' . $this->signed($l['closing']) . "\n";

		$this->output->set_content_type('text/csv')->set_header('Content-Disposition: attachment; filename="ledger_' . preg_replace('/[^A-Za-z0-9]/', '', $p['acc']->code) . '.csv"');
		$this->output->set_output($out);
		$this->output->_display();
		exit;
	}

	private function signed($n) { return number_format(abs($n), 2, '.', '') . ($n >= 0 ? ' Dr' : ' Cr'); }
}
