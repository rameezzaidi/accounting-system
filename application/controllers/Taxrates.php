<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tax rates master: each rate has a type, a percentage and the ledger account its tax amount is posted to */
class Taxrates extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model'));
	}

	public function index()
	{
		$this->require_perm('tax_rates', 'view');
		$rows = $this->db->select('t.*, a.code AS acc_code, a.name AS acc_name, (SELECT COUNT(*) FROM tax_entries e WHERE e.tax_rate_id = t.id) AS used')
			->from('tax_rates t')->join('accounts a', 'a.id = t.account_id')->order_by('t.tax_type')->order_by('t.code')->get()->result();
		$this->render('tax_rates/index', array('page_title' => 'Tax Rates', 'rows' => $rows));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('tax_rates', $id ? 'edit' : 'add');
		$t = $id ? $this->db->where('id', $id)->get('tax_rates')->row() : null;
		if ($id && !$t) { show_404(); }
		$accounts = array();
		foreach ($this->Account_model->posting(true) as $a) { if (in_array($a->account_type, array('asset', 'liability'), true)) { $accounts[] = $a; } }
		$used = $t ? $this->db->where('tax_rate_id', $id)->count_all_results('tax_entries') : 0;
		$this->partial('tax_rates/_form', array('t' => $t, 'accounts' => $accounts, 'used' => $used));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('tax_rates', $id ? 'edit' : 'add');
		$old = $id ? $this->db->where('id', $id)->get('tax_rates')->row() : null;
		if ($id && !$old) { $this->fail('Tax rate not found.'); }
		$used = $id ? $this->db->where('tax_rate_id', $id)->count_all_results('tax_entries') : 0;

		$code = strtoupper(trim((string) $this->input->post('code')));
		$name = trim((string) $this->input->post('name'));
		$type = (string) $this->input->post('tax_type');
		$rate = (string) $this->input->post('rate');
		$acc_id = (int) $this->input->post('account_id');
		if ($used) { $type = $old->tax_type; $acc_id = (int) $old->account_id; }    // locked once entries exist

		$errors = array();
		if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) { $errors['code'] = 'Use 2-20 letters, numbers, - or _.'; }
		elseif ($this->db->where('code', $code)->where('id !=', $id)->count_all_results('tax_rates') > 0) { $errors['code'] = 'This code already exists.'; }
		if ($name === '' || mb_strlen($name) > 120) { $errors['name'] = 'Enter a name (up to 120 characters).'; }
		if (!array_key_exists($type, tax_types())) { $errors['tax_type'] = 'Select the tax type.'; }
		if (!is_numeric($rate) || (float) $rate < 0 || (float) $rate > 100) { $errors['rate'] = 'Enter a rate between 0 and 100.'; }
		$acc = $this->Account_model->get($acc_id);
		if (!$acc || (int) $acc->is_group || !(int) $acc->is_active) { $errors['account_id'] = 'Select an active posting account.'; }
		elseif (isset($errors['tax_type']) === false) {
			$ok = ($type === 'output' || $type === 'withholding') ? ($acc->account_type === 'liability') : in_array($acc->account_type, array('asset', 'liability'), true);
			if (!$ok) { $errors['account_id'] = ($type === 'output' || $type === 'withholding') ? 'Choose a liability account (tax payable).' : 'Choose an asset or liability account.'; }
		}
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$data = array('code' => $code, 'name' => $name, 'tax_type' => $type, 'rate' => round((float) $rate, 3), 'account_id' => $acc_id);
		if ($id) {
			$data['updated_at'] = date('Y-m-d H:i:s');
			$this->db->where('id', $id)->update('tax_rates', $data);
			$this->audit('tax_rates', 'update', $id, "Updated tax rate $code", array('code' => $old->code, 'name' => $old->name, 'type' => $old->tax_type, 'rate' => (float) $old->rate, 'account_id' => (int) $old->account_id), $data);
			$this->ok('Tax rate updated.');
		}
		$data['is_active'] = 1; $data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('tax_rates', $data);
		$this->audit('tax_rates', 'create', $this->db->insert_id(), "Added tax rate $code ({$data['rate']}%)", null, $data);
		$this->ok('Tax rate added.');
	}

	public function toggle($id = 0)
	{
		$this->post_only();
		$this->require_perm('tax_rates', 'edit');
		$t = $this->db->where('id', (int) $id)->get('tax_rates')->row();
		if (!$t) { $this->fail('Tax rate not found.'); }
		$new = (int) $t->is_active ? 0 : 1;
		$this->db->where('id', $t->id)->update('tax_rates', array('is_active' => $new, 'updated_at' => date('Y-m-d H:i:s')));
		$this->audit('tax_rates', $new ? 'activate' : 'deactivate', $t->id, ($new ? 'Activated' : 'Deactivated') . " tax rate {$t->code}");
		$this->ok('Tax rate ' . ($new ? 'activated.' : 'deactivated.'));
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('tax_rates', 'delete');
		$t = $this->db->where('id', (int) $id)->get('tax_rates')->row();
		if (!$t) { $this->fail('Tax rate not found.'); }
		if ($this->db->where('tax_rate_id', $t->id)->count_all_results('tax_entries') > 0) { $this->fail('This rate has tax entries and cannot be deleted. Deactivate it instead.'); }
		$this->db->where('id', $t->id)->delete('tax_rates');
		$this->audit('tax_rates', 'delete', $t->id, "Deleted tax rate {$t->code}", (array) $t);
		$this->ok('Tax rate deleted.');
	}
}
