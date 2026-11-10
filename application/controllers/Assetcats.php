<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Assets master: asset categories with their ledger accounts and default depreciation */
class Assetcats extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Account_model');
	}

	public function index()
	{
		$this->require_perm('asset_categories', 'view');
		$rows = $this->db->query("SELECT c.*, a.code ac, a.name an, x.code xc, x.name xn, e.code ec, e.name en,
				(SELECT COUNT(*) FROM fixed_assets f WHERE f.category_id = c.id) used
			FROM asset_categories c JOIN accounts a ON a.id = c.asset_account_id JOIN accounts x ON x.id = c.accum_account_id JOIN accounts e ON e.id = c.expense_account_id ORDER BY c.name")->result();
		$this->render('asset_categories/index', array('page_title' => 'Assets Master', 'rows' => $rows));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('asset_categories', $id ? 'edit' : 'add');
		$c = $id ? $this->db->where('id', $id)->get('asset_categories')->row() : null;
		if ($id && !$c) { show_404(); }
		$assets = array(); $exp = array();
		foreach ($this->Account_model->posting(true) as $a) {
			if ($a->account_type === 'asset' && $a->cash_bank === 'none') { $assets[] = $a; }
			if ($a->account_type === 'expense') { $exp[] = $a; }
		}
		$this->partial('asset_categories/_form', array('c' => $c, 'assets' => $assets, 'expenses' => $exp,
			'used' => $c ? $this->db->where('category_id', $id)->count_all_results('fixed_assets') : 0));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('asset_categories', $id ? 'edit' : 'add');
		$old = $id ? $this->db->where('id', $id)->get('asset_categories')->row() : null;
		if ($id && !$old) { $this->fail('Category not found.'); }
		$used = $id ? $this->db->where('category_id', $id)->count_all_results('fixed_assets') : 0;

		$name = trim((string) $this->input->post('name'));
		$ids = array('asset_account_id' => (int) $this->input->post('asset_account_id'), 'accum_account_id' => (int) $this->input->post('accum_account_id'), 'expense_account_id' => (int) $this->input->post('expense_account_id'));
		if ($used) { foreach ($ids as $k => $_) { $ids[$k] = (int) $old->$k; } }
		$method = (string) $this->input->post('dep_method');
		$rate = (string) $this->input->post('dep_rate');

		$errors = array();
		if ($name === '' || mb_strlen($name) > 100) { $errors['name'] = 'Enter a name (up to 100 characters).'; }
		elseif ($this->db->where('name', $name)->where('id !=', $id)->count_all_results('asset_categories') > 0) { $errors['name'] = 'This category already exists.'; }
		if (!in_array($method, array('sl', 'wdv'), true)) { $errors['dep_method'] = 'Select the method.'; }
		if (!is_numeric($rate) || (float) $rate < 0 || (float) $rate > 100) { $errors['dep_rate'] = 'Enter a rate between 0 and 100.'; }
		$want = array('asset_account_id' => 'asset', 'accum_account_id' => 'asset', 'expense_account_id' => 'expense');
		foreach ($want as $k => $type) {
			$a = $this->Account_model->get($ids[$k]);
			if (!$a || (int) $a->is_group || $a->account_type !== $type || (!(int) $a->is_active && !$used)) { $errors[$k] = 'Select an active ' . $type . ' account.'; }
		}
		if (!$errors && $ids['asset_account_id'] === $ids['accum_account_id']) { $errors['accum_account_id'] = 'Must be different from the asset account.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$data = array_merge(array('name' => $name, 'dep_method' => $method, 'dep_rate' => round((float) $rate, 2)), $ids);
		if ($id) {
			$this->db->where('id', $id)->update('asset_categories', $data);
			$this->audit('asset_categories', 'update', $id, "Updated asset category $name", (array) $old, $data);
			$this->ok('Category updated.', array('reload' => true));
		}
		$data['is_active'] = 1; $data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('asset_categories', $data);
		$this->audit('asset_categories', 'create', $this->db->insert_id(), "Added asset category $name", null, $data);
		$this->ok('Category added.', array('reload' => true));
	}

	public function toggle($id = 0)
	{
		$this->post_only();
		$this->require_perm('asset_categories', 'edit');
		$c = $this->db->where('id', (int) $id)->get('asset_categories')->row();
		if (!$c) { $this->fail('Category not found.'); }
		$new = (int) $c->is_active ? 0 : 1;
		$this->db->where('id', $c->id)->update('asset_categories', array('is_active' => $new));
		$this->audit('asset_categories', $new ? 'activate' : 'deactivate', $c->id, ($new ? 'Activated' : 'Deactivated') . " asset category {$c->name}");
		$this->ok('Category ' . ($new ? 'activated.' : 'deactivated.'));
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('asset_categories', 'delete');
		$c = $this->db->where('id', (int) $id)->get('asset_categories')->row();
		if (!$c) { $this->fail('Category not found.'); }
		if ($this->db->where('category_id', $c->id)->count_all_results('fixed_assets') > 0) { $this->fail('Assets exist in this category. Deactivate it instead.'); }
		$this->db->where('id', $c->id)->delete('asset_categories');
		$this->audit('asset_categories', 'delete', $c->id, "Deleted asset category {$c->name}", (array) $c);
		$this->ok('Category deleted.');
	}
}
