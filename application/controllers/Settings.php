<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller
{
	public function index()
	{
		$this->require_perm('settings', 'view');
		$this->render('settings/index', array('page_title' => 'General Settings', 's' => $this->Setting_model->all(), 'can_edit' => $this->can('settings', 'edit')));
	}

	public function save()
	{
		$this->post_only();
		$this->require_perm('settings', 'edit');

		$in = array(
			'company_name'    => trim((string) $this->input->post('company_name')),
			'company_address' => trim((string) $this->input->post('company_address')),
			'company_phone'   => trim((string) $this->input->post('company_phone')),
			'company_email'   => trim((string) $this->input->post('company_email')),
			'company_ntn'     => trim((string) $this->input->post('company_ntn')),
			'currency_code'   => strtoupper(trim((string) $this->input->post('currency_code'))),
		);
		foreach (array('JV', 'PV', 'RV', 'CV') as $t) { $in['prefix_' . $t] = strtoupper(trim((string) $this->input->post('prefix_' . $t))); }

		$errors = array();
		if ($in['company_name'] === '') { $errors['company_name'] = 'Company name is required.'; }
		if ($in['company_email'] !== '' && !filter_var($in['company_email'], FILTER_VALIDATE_EMAIL)) { $errors['company_email'] = 'Enter a valid email.'; }
		if (!preg_match('/^[A-Z]{3}$/', $in['currency_code'])) { $errors['currency_code'] = 'Use a currency code like PKR or USD.'; }
		foreach (array('JV', 'PV', 'RV', 'CV') as $t) {
			if (!preg_match('/^[A-Z0-9]{1,6}$/', $in['prefix_' . $t])) { $errors['prefix_' . $t] = 'Letters/numbers only, max 6.'; }
		}
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$old = $this->Setting_model->all();
		$before = array(); $after = array();
		foreach ($in as $k => $v) { if (!isset($old[$k]) || $old[$k] !== $v) { $before[$k] = isset($old[$k]) ? $old[$k] : null; $after[$k] = $v; } }
		$this->Setting_model->save_many($in);
		if ($after) { $this->audit('settings', 'update', null, 'Updated settings: ' . implode(', ', array_keys($after)), $before, $after); }
		$this->ok('Settings saved.', array('reload' => true));
	}
}
