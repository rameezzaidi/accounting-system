<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Chart of Accounts */
class Coa extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Account_model');
	}

	private function filters()
	{
		return array(
			'q'      => trim((string) $this->input->get('q')),
			'type'   => (string) $this->input->get('type'),
			'status' => $this->input->get('status') === null ? '' : (string) $this->input->get('status'),
		);
	}

	public function index()
	{
		$this->require_perm('coa', 'view');
		$this->render('coa/index', array('page_title' => 'Chart of Accounts'));
	}

	public function grid()
	{
		$this->require_perm('coa', 'view');
		$this->partial('coa/_grid', array('rows' => $this->Account_model->tree($this->filters())));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('coa', $id ? 'edit' : 'add');
		$acc = $id ? $this->Account_model->get($id) : null;
		if ($id && !$acc) { show_404(); }
		$parent = (int) $this->input->get('parent');
		$this->partial('coa/_form', array(
			'acc' => $acc,
			'groups' => $this->Account_model->groups(),
			'parent_id' => $acc ? $acc->parent_id : ($parent ?: ''),
			'used' => $acc ? $this->Account_model->is_used($id) : false,
			'has_children' => $acc ? $this->Account_model->has_children($id) : false,
		));
	}

	public function next_code()
	{
		$this->require_perm('coa', 'add');
		$this->json(array('code' => $this->Account_model->next_code((int) $this->input->get('parent'))));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('coa', $id ? 'edit' : 'add');

		$code = trim((string) $this->input->post('code'));
		$name = trim((string) $this->input->post('name'));
		$parent_id = (int) $this->input->post('parent_id') ?: null;
		$type = (string) $this->input->post('account_type');
		$is_group = $this->input->post('is_group') ? 1 : 0;
		$cash_bank = (string) $this->input->post('cash_bank');
		$is_active = $this->input->post('is_active') ? 1 : 0;
		$desc = trim((string) $this->input->post('description'));

		$old = $id ? $this->Account_model->get($id) : null;
		if ($id && !$old) { $this->fail('Account not found.'); }

		$errors = array();
		if ($code === '' || !preg_match('/^[A-Za-z0-9\-\.]{1,20}$/', $code)) { $errors['code'] = 'Code is required (letters, numbers, - and . only, max 20).'; }
		elseif ($this->Account_model->code_exists($code, $id)) { $errors['code'] = 'This code is already used.'; }
		if ($name === '') { $errors['name'] = 'Account name is required.'; }

		$parent = null;
		if ($parent_id) {
			$parent = $this->Account_model->get($parent_id);
			if (!$parent || !(int) $parent->is_group) { $errors['parent_id'] = 'Parent must be a group account.'; }
			elseif ($id && in_array($parent_id, $this->Account_model->descendant_ids($id))) { $errors['parent_id'] = 'An account cannot be placed under itself.'; }
			else { $type = $parent->account_type; }   // child always follows the parent's type
		}
		if (!in_array($type, array('asset', 'liability', 'equity', 'income', 'expense'), true)) { $errors['account_type'] = 'Select the account type.'; }

		if ($old) {
			$used = $this->Account_model->is_used($id);
			if ($used && $old->account_type !== $type) { $errors['account_type'] = 'Type cannot be changed after transactions exist.'; }
			if ($is_group && !(int) $old->is_group && $used) { $errors['is_group'] = 'This account has transactions, so it cannot become a group.'; }
			if (!$is_group && (int) $old->is_group && $this->Account_model->has_children($id)) { $errors['is_group'] = 'This group has child accounts. Move or delete them first.'; }
			if ($old->account_type !== $type && $this->Account_model->has_children($id)) { $errors['account_type'] = 'Type cannot be changed while child accounts exist.'; }
		}
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		if ($is_group || $type !== 'asset' || !in_array($cash_bank, array('cash', 'bank'), true)) { $cash_bank = 'none'; }

		$data = array('parent_id' => $parent_id, 'code' => $code, 'name' => $name, 'account_type' => $type,
			'is_group' => $is_group, 'cash_bank' => $cash_bank, 'is_active' => $is_active, 'description' => $desc !== '' ? $desc : null);

		if ($id) {
			$this->Account_model->update($id, $data);
			$this->audit('coa', 'update', $id, "Updated account $code $name", $this->only($old, $data), $data);
			$this->ok('Account updated.');
		}
		$new_id = $this->Account_model->insert($data);
		$this->audit('coa', 'create', $new_id, "Created account $code $name", null, $data);
		$this->ok('Account added.');
	}

	public function toggle($id = 0)
	{
		$this->post_only();
		$this->require_perm('coa', 'edit');
		$a = $this->Account_model->get((int) $id);
		if (!$a) { $this->fail('Account not found.'); }
		$new = (int) $a->is_active ? 0 : 1;
		$this->Account_model->update($a->id, array('is_active' => $new));
		$this->audit('coa', $new ? 'activate' : 'deactivate', $a->id, ($new ? 'Activated' : 'Deactivated') . " account {$a->code} {$a->name}");
		$this->ok('Account ' . ($new ? 'activated' : 'deactivated') . '.');
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('coa', 'delete');
		$a = $this->Account_model->get((int) $id);
		if (!$a) { $this->fail('Account not found.'); }
		if ($this->Account_model->has_children($a->id)) { $this->fail('This account has child accounts. Delete or move them first.'); }
		if ($this->Account_model->is_used($a->id)) { $this->fail('This account has transactions, an opening balance, a tax rate or a bank reconciliation. Deactivate it instead of deleting.'); }
		$this->Account_model->delete($a->id);
		$this->audit('coa', 'delete', $a->id, "Deleted account {$a->code} {$a->name}", (array) $a);
		$this->ok('Account deleted.');
	}

	public function export()
	{
		$this->require_perm('coa', 'export');
		$rows = $this->Account_model->tree($this->filters());
		$this->audit('coa', 'export', null, 'Exported chart of accounts (' . count($rows) . ' rows)');
		$this->output->set_content_type('text/csv')->set_header('Content-Disposition: attachment; filename="chart_of_accounts.csv"');
		$out = "Code,Name,Type,Level,Group,Cash/Bank,Active\n";
		foreach ($rows as $a) {
			$out .= implode(',', array($this->csv($a->code), $this->csv(str_repeat('  ', $a->level) . $a->name), $a->account_type, $a->level + 1,
				$a->is_group ? 'Yes' : 'No', $a->cash_bank, $a->is_active ? 'Yes' : 'No')) . "\n";
		}
		$this->output->set_output($out);
		$this->output->_display();
		exit;
	}

	private function csv($v) { return '"' . str_replace('"', '""', (string) $v) . '"'; }

	/** only the fields that were editable, for old/new comparison in the audit log */
	private function only($old, $new)
	{
		$o = array();
		foreach ($new as $k => $v) { $o[$k] = $old->$k; }
		return $o;
	}
}
