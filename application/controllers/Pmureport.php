<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Project statement: budget, income and spending of one project, by account and as a transaction list */
class Pmureport extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Project_model');
	}

	private function compute($pid, $from, $to)
	{
		$sql = "SELECT v.id vid, v.voucher_no, v.voucher_date, v.narration, a.id aid, a.code, a.name, a.account_type, vl.debit, vl.credit
			FROM voucher_lines vl JOIN vouchers v ON v.id = vl.voucher_id AND v.status = 'posted' JOIN accounts a ON a.id = vl.account_id
			WHERE v.project_id = ? AND a.account_type IN ('income','expense')";
		$par = array((int) $pid);
		if ($from !== '') { $sql .= ' AND v.voucher_date >= ?'; $par[] = $from; }
		if ($to !== '')   { $sql .= ' AND v.voucher_date <= ?'; $par[] = $to; }
		$rows = $this->db->query($sql . ' ORDER BY v.voucher_date, v.id, vl.id', $par)->result();
		$by = array('income' => array(), 'expense' => array()); $tot = array('income' => 0.0, 'expense' => 0.0);
		foreach ($rows as $r) {
			$amt = $r->account_type === 'income' ? (float) $r->credit - (float) $r->debit : (float) $r->debit - (float) $r->credit;
			$r->amount = round($amt, 2);
			if (!isset($by[$r->account_type][$r->aid])) { $by[$r->account_type][$r->aid] = (object) array('code' => $r->code, 'name' => $r->name, 'amount' => 0.0); }
			$by[$r->account_type][$r->aid]->amount += $amt;
			$tot[$r->account_type] += $amt;
		}
		return array('rows' => $rows, 'by' => $by, 'tot' => $tot);
	}

	private function dates()
	{
		$f = (string) $this->input->get('from'); $t = (string) $this->input->get('to');
		return array(preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) ? $f : '', preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) ? $t : '');
	}

	public function index()
	{
		$this->require_perm('pmu_report', 'view');
		$this->render('pmu_report/index', array('page_title' => 'Project Statement', 'projects' => $this->db->order_by('code')->get('projects')->result(),
			'sel' => (int) $this->input->get('project'), 'can_export' => $this->can('pmu_report', 'export')));
	}

	public function data()
	{
		$this->require_perm('pmu_report', 'view');
		$p = $this->Project_model->get($this->input->get('project'));
		if (!$p) { $this->partial('ledger/_message', array('message' => 'Select a project.')); }
		list($from, $to) = $this->dates();
		if ($from !== '' && $to !== '' && $from > $to) { $this->partial('ledger/_message', array('message' => 'The "from" date cannot be after the "to" date.')); }
		$this->partial('pmu_report/_data', array('p' => $p, 'from' => $from, 'to' => $to, 'r' => $this->compute($p->id, $from, $to)));
	}

	public function export()
	{
		$this->require_perm('pmu_report', 'export');
		$p = $this->Project_model->get($this->input->get('project'));
		if (!$p) { show_error('Select a project.', 400); }
		list($from, $to) = $this->dates();
		$r = $this->compute($p->id, $from, $to);
		$this->audit('pmu_report', 'export', $p->id, "Exported project statement {$p->code}");
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$out = array(array(setting('company_name')), array('Project statement', $p->code . ' ' . $p->name), array('Period', ($from ?: 'start') . ' to ' . ($to ?: 'date')), array('Project budget', $n($p->budget)), array(),
			array('Date', 'Voucher', 'Account', 'Type', 'Narration', 'Amount'));
		foreach ($r['rows'] as $x) { $out[] = array($x->voucher_date, $x->voucher_no, $x->code . ' ' . $x->name, $x->account_type, $x->narration, $n($x->amount)); }
		$out[] = array(); $out[] = array('Total income', '', '', '', '', $n($r['tot']['income'])); $out[] = array('Total spent', '', '', '', '', $n($r['tot']['expense']));
		$out[] = array('Balance (income - spent)', '', '', '', '', $n($r['tot']['income'] - $r['tot']['expense']));
		csv_out('project_' . $p->code . '.csv', $out);
	}
}
