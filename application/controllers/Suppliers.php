<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Supplier master + supplier bills and payments.
 *   bill     Dr expense / asset      Cr supplier's payable account      (JV)
 *   payment  Dr supplier's payable   Cr cash / bank                     (PV)
 */
class Suppliers extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model', 'Voucher_model', 'Supplier_model', 'Project_model'));
	}

	/* ---------------- master ---------------- */
	public function index()
	{
		$this->require_perm('suppliers', 'view');
		$this->render('suppliers/index', array('page_title' => 'Suppliers', 'rows' => $this->Supplier_model->listing()));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('suppliers', $id ? 'edit' : 'add');
		$s = $id ? $this->Supplier_model->get($id) : null;
		if ($id && !$s) { show_404(); }
		$accs = array();
		foreach ($this->Account_model->posting(true) as $a) { if ($a->account_type === 'liability') { $accs[] = $a; } }
		$def = $this->default_account('acc_payable', 'G0101');
		$this->partial('suppliers/_form', array('s' => $s, 'accounts' => $accs, 'default_acc' => $def ? (int) $def->id : 0,
			'used' => $s ? $this->db->where('supplier_id', $id)->count_all_results('supplier_txns') : 0));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('suppliers', $id ? 'edit' : 'add');
		$old = $id ? $this->db->where('id', $id)->get('suppliers')->row() : null;
		if ($id && !$old) { $this->fail('Supplier not found.'); }
		$used = $id ? $this->db->where('supplier_id', $id)->count_all_results('supplier_txns') : 0;

		$name = trim((string) $this->input->post('name'));
		$email = trim((string) $this->input->post('email'));
		$acc_id = $used ? (int) $old->payable_account_id : (int) $this->input->post('payable_account_id');
		$errors = array();
		if ($name === '' || mb_strlen($name) > 150) { $errors['name'] = 'Enter the supplier name (up to 150 characters).'; }
		elseif ($this->db->where('name', $name)->where('id !=', $id)->count_all_results('suppliers') > 0) { $errors['name'] = 'A supplier with this name already exists.'; }
		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email address.'; }
		$acc = $this->Account_model->get($acc_id);
		if (!$acc || (int) $acc->is_group || $acc->account_type !== 'liability' || (!(int) $acc->is_active && !$used)) { $errors['payable_account_id'] = 'Select an active liability (payable) account.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$data = array('name' => $name, 'contact_person' => mb_substr(trim((string) $this->input->post('contact_person')), 0, 120) ?: null,
			'phone' => mb_substr(trim((string) $this->input->post('phone')), 0, 40) ?: null, 'email' => $email ?: null,
			'address' => mb_substr(trim((string) $this->input->post('address')), 0, 255) ?: null,
			'tax_no' => mb_substr(trim((string) $this->input->post('tax_no')), 0, 40) ?: null, 'payable_account_id' => $acc_id);
		if ($id) {
			$data['updated_at'] = date('Y-m-d H:i:s');
			$this->db->where('id', $id)->update('suppliers', $data);
			$this->audit('suppliers', 'update', $id, "Updated supplier {$old->code} {$old->name}", (array) $old, $data);
			$this->ok('Supplier updated.', array('reload' => true));
		}
		$data['code'] = $this->Supplier_model->next_code(); $data['is_active'] = 1; $data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('suppliers', $data);
		$this->audit('suppliers', 'create', $this->db->insert_id(), "Added supplier {$data['code']} $name", null, $data);
		$this->ok("Supplier {$data['code']} added.", array('reload' => true));
	}

	public function toggle($id = 0)
	{
		$this->post_only();
		$this->require_perm('suppliers', 'edit');
		$s = $this->db->where('id', (int) $id)->get('suppliers')->row();
		if (!$s) { $this->fail('Supplier not found.'); }
		$new = (int) $s->is_active ? 0 : 1;
		$this->db->where('id', $s->id)->update('suppliers', array('is_active' => $new, 'updated_at' => date('Y-m-d H:i:s')));
		$this->audit('suppliers', $new ? 'activate' : 'deactivate', $s->id, ($new ? 'Activated' : 'Deactivated') . " supplier {$s->code}");
		$this->ok('Supplier ' . ($new ? 'activated.' : 'deactivated.'));
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('suppliers', 'delete');
		$s = $this->db->where('id', (int) $id)->get('suppliers')->row();
		if (!$s) { $this->fail('Supplier not found.'); }
		if ($this->db->where('supplier_id', $s->id)->count_all_results('supplier_txns') > 0) { $this->fail('This supplier has bills / payments and cannot be deleted. Deactivate it instead.'); }
		$this->db->where('id', $s->id)->delete('suppliers');
		$this->audit('suppliers', 'delete', $s->id, "Deleted supplier {$s->code} {$s->name}", (array) $s);
		$this->ok('Supplier deleted.');
	}

	/* ---------------- statement ---------------- */
	public function statement($id = 0)
	{
		$this->require_perm('supplier_txn', 'view');
		$s = $this->Supplier_model->get($id);
		if (!$s) { show_404(); }
		$this->render('suppliers/statement', array('page_title' => 'Supplier statement', 's' => $s, 'rows' => $this->Supplier_model->statement($s->id),
			'open' => $this->Supplier_model->open_bills($s->id), 'balance' => $this->Supplier_model->balance($s->id)));
	}

	/* ---------------- bills ---------------- */
	public function billform($sid = 0)
	{
		$this->require_perm('supplier_txn', 'add');
		$s = $this->Supplier_model->get($sid);
		if (!$s) { show_404(); }
		$accs = array();
		foreach ($this->Account_model->posting(true) as $a) { if (in_array($a->account_type, array('expense', 'asset'), true) && $a->cash_bank === 'none') { $accs[] = $a; } }
		$this->partial('suppliers/_billform', array('s' => $s, 'accounts' => $accs));
	}

	public function billsave()
	{
		$this->post_only();
		$this->require_perm('supplier_txn', 'add');
		$s = $this->Supplier_model->get($this->input->post('supplier_id'));
		if (!$s || !(int) $s->is_active) { $this->fail('Supplier not found or inactive.'); }
		$date = (string) $this->input->post('voucher_date');
		$due = (string) $this->input->post('due_date');
		$amount = $this->money('amount');
		$exp = (int) $this->input->post('expense_account');
		$inv = mb_substr(trim((string) $this->input->post('ref_no')), 0, 60);
		$note = trim((string) $this->input->post('narration'));
		$pid = (int) $this->input->post('project_id');

		$errors = array();
		if ($amount <= 0) { $errors['amount'] = 'Enter an amount greater than zero.'; }
		if (!$exp) { $errors['expense_account'] = 'Select the expense / asset account.'; }
		if ($due !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) || !strtotime($due) || $due < $date)) { $errors['due_date'] = 'Due date cannot be before the bill date.'; }
		if ($pid && !$this->Project_model->usable($pid)) { $errors['project_id'] = 'Select an active project.'; }
		$ea = $this->Account_model->get($exp);
		if ($exp && (!$ea || !in_array($ea->account_type, array('expense', 'asset'), true) || $ea->cash_bank !== 'none')) { $errors['expense_account'] = 'Choose an expense or asset account.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }
		$fy = $this->fy_or_fail($date);

		$narr = "Bill from {$s->name}" . ($inv !== '' ? " (Inv $inv)" : '') . ($note !== '' ? ': ' . $note : '');
		list($lines, $total, $err) = $this->Voucher_model->clean_lines(array(
			array('account_id' => $exp, 'debit' => $amount, 'credit' => 0, 'description' => $narr),
			array('account_id' => $s->payable_account_id, 'debit' => 0, 'credit' => $amount, 'description' => $narr)));
		if ($err) { $this->fail($err); }

		$this->db->trans_begin();
		$vid = $this->Voucher_model->create(array('type' => 'JV', 'date' => $date, 'reference' => $inv, 'narration' => $narr, 'user_id' => $this->user->id, 'project_id' => $pid), $lines, $total, $fy);
		if ($vid) {
			$this->db->insert('supplier_txns', array('supplier_id' => $s->id, 'kind' => 'bill', 'voucher_id' => $vid, 'txn_date' => $date, 'due_date' => $due !== '' ? $due : null,
				'ref_no' => $inv !== '' ? $inv : null, 'amount' => $amount, 'description' => $note !== '' ? mb_substr($note, 0, 255) : null,
				'created_by' => $this->user->id, 'created_at' => date('Y-m-d H:i:s')));
		}
		if (!$vid || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();
		$new = $this->Voucher_model->get($vid);
		$this->audit('supplier_txn', 'create', $vid, "Bill {$new->voucher_no} from {$s->name} for " . number_format($amount, 2), null, $this->Voucher_model->snapshot($new));
		$this->ok("Bill {$new->voucher_no} posted.", array('reload' => true));
	}

	/* ---------------- payments ---------------- */
	public function payform($sid = 0, $bill = 0)
	{
		$this->require_perm('supplier_txn', 'add');
		$s = $this->Supplier_model->get($sid);
		if (!$s) { show_404(); }
		$this->partial('suppliers/_payform', array('s' => $s, 'cash' => $this->Account_model->posting(true, true), 'open' => $this->Supplier_model->open_bills($s->id), 'bill_id' => (int) $bill));
	}

	public function paysave()
	{
		$this->post_only();
		$this->require_perm('supplier_txn', 'add');
		$s = $this->Supplier_model->get($this->input->post('supplier_id'));
		if (!$s || !(int) $s->is_active) { $this->fail('Supplier not found or inactive.'); }
		$date = (string) $this->input->post('voucher_date');
		$amount = $this->money('amount');
		$cash = (int) $this->input->post('cash_account');
		$ref = mb_substr(trim((string) $this->input->post('ref_no')), 0, 60);
		$note = trim((string) $this->input->post('narration'));
		$bill_id = (int) $this->input->post('bill_id');
		$pid = (int) $this->input->post('project_id');

		$errors = array();
		if ($amount <= 0) { $errors['amount'] = 'Enter an amount greater than zero.'; }
		$ca = $this->Account_model->get($cash);
		if (!$ca || $ca->cash_bank === 'none' || !(int) $ca->is_active) { $errors['cash_account'] = 'Select the cash / bank account.'; }
		if ($pid && !$this->Project_model->usable($pid)) { $errors['project_id'] = 'Select an active project.'; }
		if ($bill_id) {
			$b = null;
			foreach ($this->Supplier_model->open_bills($s->id, $date) as $x) { if ((int) $x->id === $bill_id) { $b = $x; } }
			if (!$b) { $errors['bill_id'] = 'This bill is not open on the payment date.'; }
			elseif ($amount > (float) $b->outstanding + 0.004) { $errors['amount'] = 'More than the outstanding ' . number_format((float) $b->outstanding, 2) . ' of this bill.'; }
		}
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }
		$fy = $this->fy_or_fail($date);

		$narr = "Payment to {$s->name}" . ($ref !== '' ? " (Ref $ref)" : '') . ($note !== '' ? ': ' . $note : '');
		list($lines, $total, $err) = $this->Voucher_model->clean_lines(array(
			array('account_id' => $s->payable_account_id, 'debit' => $amount, 'credit' => 0, 'description' => $narr),
			array('account_id' => $cash, 'debit' => 0, 'credit' => $amount, 'description' => $narr)));
		if ($err) { $this->fail($err); }

		$this->db->trans_begin();
		$vid = $this->Voucher_model->create(array('type' => 'PV', 'date' => $date, 'reference' => $ref, 'narration' => $narr, 'user_id' => $this->user->id, 'project_id' => $pid), $lines, $total, $fy);
		if ($vid) {
			$this->db->insert('supplier_txns', array('supplier_id' => $s->id, 'kind' => 'payment', 'voucher_id' => $vid, 'txn_date' => $date, 'ref_no' => $ref !== '' ? $ref : null,
				'amount' => $amount, 'bill_id' => $bill_id ?: null, 'description' => $note !== '' ? mb_substr($note, 0, 255) : null,
				'created_by' => $this->user->id, 'created_at' => date('Y-m-d H:i:s')));
		}
		if (!$vid || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();
		$new = $this->Voucher_model->get($vid);
		$this->audit('supplier_txn', 'create', $vid, "Payment {$new->voucher_no} to {$s->name} for " . number_format($amount, 2), null, $this->Voucher_model->snapshot($new));
		$this->ok("Payment {$new->voucher_no} posted.", array('reload' => true));
	}

	public function void($txn_id = 0)
	{
		$this->post_only();
		$this->require_perm('supplier_txn', 'void');
		$t = $this->db->where('id', (int) $txn_id)->get('supplier_txns')->row();
		if (!$t) { $this->fail('Entry not found.'); }
		if ($t->kind === 'bill' && $this->Supplier_model->payments_against($t->id) > 0) { $this->fail('Payments have been made against this bill. Void those payments first.'); }
		$this->do_void((int) $t->voucher_id, trim((string) $this->input->post('reason')), 'supplier_txn', true);
	}
}
