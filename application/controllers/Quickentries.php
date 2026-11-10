<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Quick entries: one-line payment / receipt / contra vouchers.
 *   PV  Payment : Dr paid-to account      / Cr cash-bank
 *   RV  Receipt : Dr cash-bank            / Cr received-from account
 *   CV  Contra  : Dr transfer-to (bank/cash) / Cr transfer-from (bank/cash)
 */
class Quickentries extends MY_Controller
{
	const PER_PAGE = 15;

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model', 'Voucher_model'));
	}

	private function filters()
	{
		$type = (string) $this->input->get('type');
		return array(
			'types'  => in_array($type, array('PV', 'RV', 'CV'), true) ? array($type) : array('PV', 'RV', 'CV'),
			'q'      => trim((string) $this->input->get('q')),
			'status' => (string) $this->input->get('status'),
			'from'   => (string) $this->input->get('from'),
			'to'     => (string) $this->input->get('to'),
		);
	}

	public function index()
	{
		$this->require_perm('quick_entries', 'view');
		$this->render('quick_entries/index', array('page_title' => 'Quick Entries'));
	}

	public function grid()
	{
		$this->require_perm('quick_entries', 'view');
		$f = $this->filters();
		$page = max(1, (int) $this->input->get('page'));
		$rows = $this->Voucher_model->search($f, self::PER_PAGE, ($page - 1) * self::PER_PAGE);
		// show the "other side" account of each voucher in the list
		foreach ($rows as $r) {
			$r->lines = $this->db->select('vl.*, a.code AS account_code, a.name AS account_name, a.cash_bank')->from('voucher_lines vl')
				->join('accounts a', 'a.id = vl.account_id')->where('vl.voucher_id', $r->id)->get()->result();
		}
		$this->partial('quick_entries/_grid', array('rows' => $rows, 'total' => $this->Voucher_model->count($f), 'page' => $page, 'per' => self::PER_PAGE));
	}

	public function form($type = 'PV')
	{
		$this->require_perm('quick_entries', 'add');
		if (!in_array($type, array('PV', 'RV', 'CV'), true)) { show_404(); }
		$this->partial('quick_entries/_form', array(
			'type' => $type,
			'cash_accounts' => $this->Account_model->posting(true, true),
			'all_accounts' => $this->Account_model->posting(true),
		));
	}

	public function save()
	{
		$this->post_only();
		$this->require_perm('quick_entries', 'add');
		$type = (string) $this->input->post('voucher_type');
		if (!in_array($type, array('PV', 'RV', 'CV'), true)) { $this->fail('Invalid entry type.'); }

		$date = (string) $this->input->post('voucher_date');
		$main = (int) $this->input->post('main_account');      // cash/bank side (PV: paid from, RV: received in, CV: from)
		$other = (int) $this->input->post('other_account');    // counter side   (PV: paid to, RV: received from, CV: to)
		$amount = round((float) str_replace(',', '', (string) $this->input->post('amount')), 2);
		$ref = trim((string) $this->input->post('reference'));
		$narr = trim((string) $this->input->post('narration'));

		$errors = array();
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) { $errors['voucher_date'] = 'Select a valid date.'; }
		if (!$main) { $errors['main_account'] = 'Select the cash / bank account.'; }
		if (!$other) { $errors['other_account'] = 'Select the account.'; }
		if ($main && $other && $main === $other) { $errors['other_account'] = 'Both accounts cannot be the same.'; }
		if ($amount <= 0) { $errors['amount'] = 'Enter an amount greater than zero.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$ma = $this->Account_model->get($main);
		if (!$ma || $ma->cash_bank === 'none') { $this->fail('Select a cash or bank account.', array('main_account' => 'Not a cash / bank account.')); }
		if ($type === 'CV') {
			$oa = $this->Account_model->get($other);
			if (!$oa || $oa->cash_bank === 'none') { $this->fail('Select a cash or bank account.', array('other_account' => 'Not a cash / bank account.')); }
		}

		$fy = $this->Fy_model->for_date($date);
		if (!$fy) { $this->fail('No fiscal year exists for ' . dmy($date) . '.', array('voucher_date' => 'No fiscal year for this date.')); }
		if ((int) $fy->is_closed) { $this->fail("Fiscal year {$fy->title} is closed.", array('voucher_date' => 'Fiscal year is closed.')); }

		if ($type === 'PV')      { $rows = array(array('account_id' => $other, 'debit' => $amount, 'credit' => 0), array('account_id' => $main, 'debit' => 0, 'credit' => $amount)); }
		elseif ($type === 'RV')  { $rows = array(array('account_id' => $main, 'debit' => $amount, 'credit' => 0), array('account_id' => $other, 'debit' => 0, 'credit' => $amount)); }
		else                     { $rows = array(array('account_id' => $other, 'debit' => $amount, 'credit' => 0), array('account_id' => $main, 'debit' => 0, 'credit' => $amount)); }
		foreach ($rows as &$r) { $r['description'] = $narr; }
		unset($r);

		list($lines, $total, $err) = $this->Voucher_model->clean_lines($rows);
		if ($err) { $this->fail($err); }

		$this->load->model('Project_model');
		$qpid = (int) $this->input->post('project_id');
		if ($qpid && !$this->Project_model->usable($qpid)) { $this->fail('Select an active project.'); }
		$vid = $this->Voucher_model->create(array('type' => $type, 'date' => $date, 'reference' => $ref, 'narration' => $narr, 'user_id' => $this->user->id, 'project_id' => $qpid), $lines, $total, $fy);
		if (!$vid) { $this->fail('Could not save. Please try again.'); }
		$new = $this->Voucher_model->get($vid);
		$this->audit('quick_entries', 'create', $vid, "Quick " . strtolower(voucher_type_name($type)) . " {$new->voucher_no} for " . number_format($total, 2), null, $this->Voucher_model->snapshot($new));
		$this->ok($new->voucher_no . ' posted.');
	}

	public function void($id = 0)
	{
		$this->post_only();
		$this->require_perm('quick_entries', 'void');
		$this->do_void((int) $id, trim((string) $this->input->post('reason')), 'quick_entries');
	}
}
