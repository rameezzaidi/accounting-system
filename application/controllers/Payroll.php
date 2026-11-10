<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payroll.
 *   run      Dr salary expense (gross)   Cr payroll deductions payable (deductions)   Cr salaries payable (net)   (JV, optional project)
 *   payment  Dr salaries payable (net)   Cr cash / bank                                                          (PV)
 */
class Payroll extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model', 'Voucher_model', 'Project_model'));
	}

	private function run_row($id)
	{
		return $this->db->select('r.*, v.voucher_no, v.status vstatus, pv.voucher_no pay_no, p.code project_code, p.name project_name')->from('payroll_runs r')
			->join('vouchers v', 'v.id = r.voucher_id')->join('vouchers pv', 'pv.id = r.pay_voucher_id', 'left')->join('projects p', 'p.id = r.project_id', 'left')
			->where('r.id', (int) $id)->get()->row();
	}

	public function index()
	{
		$this->require_perm('payroll', 'view');
		$rows = $this->db->query("SELECT r.*, v.voucher_no, v.status vstatus, (SELECT COUNT(*) FROM payroll_lines l WHERE l.run_id = r.id) emps
			FROM payroll_runs r JOIN vouchers v ON v.id = r.voucher_id ORDER BY r.period DESC, r.id DESC")->result();
		$this->render('payroll/index', array('page_title' => 'Payroll', 'rows' => $rows));
	}

	/* ---------------- new run ---------------- */
	public function newrun()
	{
		$this->require_perm('payroll', 'run');
		$exp = $pay = $ded = array();
		foreach ($this->Account_model->posting(true) as $a) {
			if ($a->account_type === 'expense') { $exp[] = $a; }
			if ($a->account_type === 'liability') { $pay[] = $a; }
		}
		$code = function ($k) { $r = $this->default_account($k); return $r ? (int) $r->id : 0; };
		$emps = $this->db->where('status', 'active')->order_by('name')->get('employees')->result();
		$this->render('payroll/new', array('page_title' => 'New payroll run', 'expenses' => $exp, 'liabilities' => $pay, 'emps' => $emps,
			'def' => array('expense' => $code('acc_salary_exp'), 'payable' => $code('acc_salary_payable'), 'deduction' => $code('acc_payroll_ded'))));
	}

	public function save()
	{
		$this->post_only();
		$this->require_perm('payroll', 'run');
		$period = (string) $this->input->post('period');
		$date = (string) $this->input->post('run_date');
		$exp = (int) $this->input->post('expense_account_id'); $pay = (int) $this->input->post('payable_account_id'); $ded = (int) $this->input->post('deduction_account_id');
		$pid = (int) $this->input->post('project_id');
		$notes = mb_substr(trim((string) $this->input->post('notes')), 0, 255);
		$items = json_decode((string) $this->input->post('payload'), true);

		$errors = array();
		if (!month_range($period)) { $errors['period'] = 'Select the salary month.'; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) { $errors['run_date'] = 'Select a valid date.'; }
		foreach (array('expense_account_id' => array($exp, 'expense'), 'payable_account_id' => array($pay, 'liability'), 'deduction_account_id' => array($ded, 'liability')) as $k => $x) {
			$a = $this->Account_model->get($x[0]);
			if (!$a || (int) $a->is_group || !(int) $a->is_active || $a->account_type !== $x[1]) { $errors[$k] = 'Select an active ' . $x[1] . ' account.'; }
		}
		if (!$errors && $pay === $ded) { $errors['deduction_account_id'] = 'Must be different from the salaries payable account.'; }
		if ($pid && !$this->Project_model->usable($pid)) { $errors['project_id'] = 'Select an active project.'; }
		if (!is_array($items) || !$items) { $this->fail('Select at least one employee.', $errors); }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }
		if ($this->db->query("SELECT COUNT(*) n FROM payroll_runs r JOIN vouchers v ON v.id = r.voucher_id AND v.status = 'posted' WHERE r.period = ?", array($period))->row()->n > 0) {
			$this->fail("A payroll run for $period already exists. Void it first to run again.", array('period' => 'Already run for this month.'));
		}
		$fy = $this->fy_or_fail($date, 'run_date');

		$lines = array(); $G = $D = $N = 0.0; $seen = array();
		foreach ($items as $eid => $it) {
			$eid = (int) $eid;
			$e = $this->db->where('id', $eid)->get('employees')->row();
			if (!$e || $e->status !== 'active' || isset($seen[$eid])) { continue; }
			$seen[$eid] = 1;
			$bonus = round((float) str_replace(',', '', isset($it['bonus']) ? $it['bonus'] : 0), 2);
			$dd = round((float) str_replace(',', '', isset($it['deductions']) ? $it['deductions'] : 0), 2);
			$gross = round((float) $e->basic_salary + (float) $e->allowances + $bonus, 2);
			if ($bonus < 0 || $dd < 0) { $this->fail("{$e->name}: bonus / deductions cannot be negative."); }
			if ($dd > $gross) { $this->fail("{$e->name}: deductions exceed the gross salary."); }
			if ($gross <= 0) { $this->fail("{$e->name}: gross salary is zero."); }
			$lines[] = array('employee_id' => $eid, 'basic' => $e->basic_salary, 'allowances' => $e->allowances, 'bonus' => $bonus, 'gross' => $gross, 'deductions' => $dd, 'net' => round($gross - $dd, 2),
				'remarks' => isset($it['remarks']) ? mb_substr(trim($it['remarks']), 0, 255) : null);
			$G += $gross; $D += $dd; $N += $gross - $dd;
		}
		if (!$lines) { $this->fail('Select at least one active employee.'); }
		$G = round($G, 2); $D = round($D, 2); $N = round($N, 2);

		$narr = 'Salaries for ' . date('F Y', strtotime($period . '-01')) . ' (' . count($lines) . ' employees)';
		$in = array(array('account_id' => $exp, 'debit' => $G, 'credit' => 0, 'description' => $narr));
		if ($D > 0) { $in[] = array('account_id' => $ded, 'debit' => 0, 'credit' => $D, 'description' => $narr . ' - deductions'); }
		if ($N > 0) { $in[] = array('account_id' => $pay, 'debit' => 0, 'credit' => $N, 'description' => $narr . ' - net payable'); }
		list($vl, $total, $err) = $this->Voucher_model->clean_lines($in);
		if ($err) { $this->fail($err); }

		$this->db->trans_begin();
		$vid = $this->Voucher_model->create(array('type' => 'JV', 'date' => $date, 'reference' => 'PAY-' . $period, 'narration' => $narr, 'user_id' => $this->user->id, 'project_id' => $pid), $vl, $total, $fy);
		if ($vid) {
			$this->db->insert('payroll_runs', array('period' => $period, 'run_date' => $date, 'expense_account_id' => $exp, 'payable_account_id' => $pay, 'deduction_account_id' => $ded,
				'project_id' => $pid ?: null, 'gross' => $G, 'deductions' => $D, 'net' => $N, 'voucher_id' => $vid, 'notes' => $notes !== '' ? $notes : null,
				'created_by' => $this->user->id, 'created_at' => date('Y-m-d H:i:s')));
			$rid = $this->db->insert_id();
			foreach ($lines as &$l) { $l['run_id'] = $rid; }
			unset($l);
			$this->db->insert_batch('payroll_lines', $lines);
		}
		if (!$vid || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save the payroll. Please try again.'); }
		$this->db->trans_commit();
		$new = $this->Voucher_model->get($vid);
		$this->audit('payroll', 'run', $rid, "Payroll $period: {$new->voucher_no}, gross " . number_format($G, 2) . ", net " . number_format($N, 2) . ', ' . count($lines) . ' employees', null, $this->Voucher_model->snapshot($new));
		$this->ok("Payroll posted ({$new->voucher_no}).", array('redirect' => site_url('payroll/view/' . $rid)));
	}

	/* ---------------- view / pay / void ---------------- */
	public function view($id = 0)
	{
		$this->require_perm('payroll', 'view');
		$r = $this->run_row($id);
		if (!$r) { show_404(); }
		$lines = $this->db->select('l.*, e.emp_code, e.name, e.designation, e.department')->from('payroll_lines l')->join('employees e', 'e.id = l.employee_id')->where('l.run_id', $r->id)->order_by('e.name')->get()->result();
		$this->render('payroll/view', array('page_title' => 'Payroll ' . $r->period, 'r' => $r, 'lines' => $lines));
	}

	public function payform($id = 0)
	{
		$this->require_perm('payroll', 'pay');
		$r = $this->run_row($id);
		if (!$r || $r->vstatus !== 'posted' || $r->pay_voucher_id) { show_404(); }
		$this->partial('payroll/_payform', array('r' => $r, 'cash' => $this->Account_model->posting(true, true)));
	}

	public function pay()
	{
		$this->post_only();
		$this->require_perm('payroll', 'pay');
		$r = $this->run_row($this->input->post('id'));
		if (!$r || $r->vstatus !== 'posted') { $this->fail('Payroll run not found or void.'); }
		if ($r->pay_voucher_id) { $this->fail('Salaries of this run are already paid.'); }
		$date = (string) $this->input->post('paid_date');
		$cash = (int) $this->input->post('cash_account');
		$ca = $this->Account_model->get($cash);
		if (!$ca || $ca->cash_bank === 'none' || !(int) $ca->is_active) { $this->fail('Please fix the highlighted fields.', array('cash_account' => 'Select the cash / bank account.')); }
		if ($date < $r->run_date) { $this->fail('Please fix the highlighted fields.', array('paid_date' => 'Cannot be before the payroll date.')); }
		$fy = $this->fy_or_fail($date, 'paid_date');
		if ((float) $r->net <= 0) { $this->fail('Net payable is zero. Nothing to pay.'); }
		$narr = 'Salary payment for ' . date('F Y', strtotime($r->period . '-01'));
		list($vl, $total, $err) = $this->Voucher_model->clean_lines(array(
			array('account_id' => $r->payable_account_id, 'debit' => (float) $r->net, 'credit' => 0, 'description' => $narr),
			array('account_id' => $cash, 'debit' => 0, 'credit' => (float) $r->net, 'description' => $narr)));
		if ($err) { $this->fail($err); }
		$this->db->trans_begin();
		$vid = $this->Voucher_model->create(array('type' => 'PV', 'date' => $date, 'reference' => 'PAY-' . $r->period, 'narration' => $narr, 'user_id' => $this->user->id, 'project_id' => $r->project_id), $vl, $total, $fy);
		if ($vid) { $this->db->where('id', $r->id)->update('payroll_runs', array('pay_voucher_id' => $vid, 'paid_date' => $date)); }
		if (!$vid || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();
		$new = $this->Voucher_model->get($vid);
		$this->audit('payroll', 'pay', $r->id, "Paid salaries {$r->period}: {$new->voucher_no} for " . number_format((float) $r->net, 2), null, $this->Voucher_model->snapshot($new));
		$this->ok("Salaries paid ({$new->voucher_no}).", array('reload' => true));
	}

	public function voidpay($id = 0)
	{
		$this->post_only();
		$this->require_perm('payroll', 'void');
		$r = $this->run_row($id);
		if (!$r || !$r->pay_voucher_id) { $this->fail('No payment to void.'); }
		$reason = trim((string) $this->input->post('reason'));
		if ($reason === '') { $this->fail('Please enter a reason.'); }
		$v = $this->Voucher_model->get($r->pay_voucher_id);
		$fy = $this->Fy_model->get($v->fiscal_year_id);
		if ($fy && (int) $fy->is_closed) { $this->fail('The payment belongs to a closed fiscal year.'); }
		if ($this->Voucher_model->has_reconciled($v->id)) { $this->fail('The payment is bank-reconciled. Undo the reconciliation first.'); }
		$this->db->trans_begin();
		$this->Voucher_model->void($v->id, $reason, $this->user->id);
		$this->db->where('id', $r->id)->update('payroll_runs', array('pay_voucher_id' => null, 'paid_date' => null));
		if ($this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not void. Please try again.'); }
		$this->db->trans_commit();
		$this->audit('payroll', 'void', $r->id, "Voided salary payment {$v->voucher_no} of {$r->period}. Reason: $reason", $this->Voucher_model->snapshot($v), array('status' => 'void', 'reason' => $reason));
		$this->ok('Salary payment voided.', array('reload' => true));
	}

	public function voidrun($id = 0)
	{
		$this->post_only();
		$this->require_perm('payroll', 'void');
		$r = $this->run_row($id);
		if (!$r || $r->vstatus !== 'posted') { $this->fail('Payroll run not found or already void.'); }
		if ($r->pay_voucher_id) { $this->fail('Salaries are already paid. Void the payment first.'); }
		$this->do_void((int) $r->voucher_id, trim((string) $this->input->post('reason')), 'payroll', true);
	}

	public function payslip($run_id = 0, $line_id = 0)
	{
		$this->require_perm('payroll', 'print');
		$r = $this->run_row($run_id);
		if (!$r) { show_404(); }
		$this->db->select('l.*, e.emp_code, e.name, e.designation, e.department')->from('payroll_lines l')->join('employees e', 'e.id = l.employee_id')->where('l.run_id', $r->id);
		if ((int) $line_id) { $this->db->where('l.id', (int) $line_id); }
		$lines = $this->db->order_by('e.name')->get()->result();
		if (!$lines) { show_404(); }
		$this->audit('payroll', 'print', $r->id, 'Printed payslip(s) for ' . $r->period . ' (' . count($lines) . ')');
		$this->load->view('payroll/payslip', array('r' => $r, 'lines' => $lines));
	}
}
