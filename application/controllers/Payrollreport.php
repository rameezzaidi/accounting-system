<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Payroll report: runs, totals per employee and per department for a range of months (void runs excluded) */
class Payrollreport extends MY_Controller
{
	private function params()
	{
		$from = (string) $this->input->get('from'); $to = (string) $this->input->get('to');
		if (!month_range($from)) { $from = date('Y') . '-01'; }
		if (!month_range($to)) { $to = date('Y-m'); }
		if ($from > $to) { return 'The "from" month cannot be after the "to" month.'; }
		return array('from' => $from, 'to' => $to);
	}

	private function compute($p)
	{
		$base = "FROM payroll_lines l JOIN payroll_runs r ON r.id = l.run_id JOIN vouchers v ON v.id = r.voucher_id AND v.status = 'posted' JOIN employees e ON e.id = l.employee_id WHERE r.period BETWEEN ? AND ?";
		$par = array($p['from'], $p['to']);
		$runs = $this->db->query("SELECT r.*, v.voucher_no, (SELECT COUNT(*) FROM payroll_lines x WHERE x.run_id = r.id) emps, pj.code project_code
			FROM payroll_runs r JOIN vouchers v ON v.id = r.voucher_id AND v.status = 'posted' LEFT JOIN projects pj ON pj.id = r.project_id WHERE r.period BETWEEN ? AND ? ORDER BY r.period, r.id", $par)->result();
		$emps = $this->db->query("SELECT e.emp_code, e.name, e.department, COUNT(DISTINCT r.id) months, SUM(l.basic) basic, SUM(l.allowances) allowances, SUM(l.bonus) bonus, SUM(l.gross) gross, SUM(l.deductions) deductions, SUM(l.net) net $base GROUP BY e.id ORDER BY e.name", $par)->result();
		$dept = $this->db->query("SELECT COALESCE(NULLIF(e.department,''),'(none)') department, COUNT(DISTINCT e.id) emps, SUM(l.gross) gross, SUM(l.deductions) deductions, SUM(l.net) net $base GROUP BY department ORDER BY department", $par)->result();
		$tot = array('gross' => 0, 'deductions' => 0, 'net' => 0, 'paid' => 0);
		foreach ($runs as $r) { $tot['gross'] += (float) $r->gross; $tot['deductions'] += (float) $r->deductions; $tot['net'] += (float) $r->net; if ($r->pay_voucher_id) { $tot['paid'] += (float) $r->net; } }
		return array('runs' => $runs, 'emps' => $emps, 'dept' => $dept, 'tot' => $tot);
	}

	public function index()
	{
		$this->require_perm('payroll_report', 'view');
		$this->render('payroll_report/index', array('page_title' => 'Payroll Report', 'from' => date('Y') . '-01', 'to' => date('Y-m'), 'can_export' => $this->can('payroll_report', 'export')));
	}

	public function data()
	{
		$this->require_perm('payroll_report', 'view');
		$p = $this->params();
		if (!is_array($p)) { $this->partial('ledger/_message', array('message' => $p)); }
		$this->partial('payroll_report/_data', array('p' => $p, 'r' => $this->compute($p)));
	}

	public function export()
	{
		$this->require_perm('payroll_report', 'export');
		$p = $this->params();
		if (!is_array($p)) { show_error($p, 400); }
		$r = $this->compute($p);
		$this->audit('payroll_report', 'export', null, "Exported payroll report ({$p['from']} to {$p['to']})");
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$out = array(array(setting('company_name')), array('Payroll Report', $p['from'] . ' to ' . $p['to']), array(), array('Month', 'Voucher', 'Employees', 'Gross', 'Deductions', 'Net', 'Paid on'));
		foreach ($r['runs'] as $x) { $out[] = array($x->period, $x->voucher_no, $x->emps, $n($x->gross), $n($x->deductions), $n($x->net), $x->paid_date); }
		$out[] = array('Total', '', '', $n($r['tot']['gross']), $n($r['tot']['deductions']), $n($r['tot']['net']));
		$out[] = array(); $out[] = array('Code', 'Employee', 'Department', 'Months', 'Basic', 'Allowances', 'Bonus', 'Gross', 'Deductions', 'Net');
		foreach ($r['emps'] as $x) { $out[] = array($x->emp_code, $x->name, $x->department, $x->months, $n($x->basic), $n($x->allowances), $n($x->bonus), $n($x->gross), $n($x->deductions), $n($x->net)); }
		csv_out('payroll_report_' . $p['from'] . '_' . $p['to'] . '.csv', $out);
	}
}
