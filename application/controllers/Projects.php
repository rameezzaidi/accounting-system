<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** PMU: projects. Vouchers are tagged with a project (journal, quick entries, supplier bills, payroll); the project statement is built from them. */
class Projects extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Project_model');
	}

	public function index()
	{
		$this->require_perm('projects', 'view');
		$rows = $this->db->query("SELECT p.*, (SELECT COUNT(*) FROM vouchers v WHERE v.project_id = p.id) vouchers FROM projects p ORDER BY p.status, p.code")->result();
		foreach ($rows as $p) { $t = $this->Project_model->totals($p->id); $p->income = $t['income']; $p->expense = $t['expense']; }
		$this->render('projects/index', array('page_title' => 'PMU Projects', 'rows' => $rows));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('projects', $id ? 'edit' : 'add');
		$p = $id ? $this->Project_model->get($id) : null;
		if ($id && !$p) { show_404(); }
		$this->partial('projects/_form', array('p' => $p));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('projects', $id ? 'edit' : 'add');
		$old = $id ? $this->Project_model->get($id) : null;
		if ($id && !$old) { $this->fail('Project not found.'); }
		$code = strtoupper(trim((string) $this->input->post('code')));
		$name = trim((string) $this->input->post('name'));
		$budget = $this->money('budget');
		$s = (string) $this->input->post('start_date'); $e = (string) $this->input->post('end_date');
		$errors = array();
		if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) { $errors['code'] = 'Use 2-20 letters, numbers, - or _.'; }
		elseif ($this->db->where('code', $code)->where('id !=', $id)->count_all_results('projects') > 0) { $errors['code'] = 'This code already exists.'; }
		if ($name === '' || mb_strlen($name) > 150) { $errors['name'] = 'Enter the project name.'; }
		if ($budget < 0) { $errors['budget'] = 'Cannot be negative.'; }
		foreach (array('start_date' => $s, 'end_date' => $e) as $k => $v) { if ($v !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) || !strtotime($v))) { $errors[$k] = 'Select a valid date.'; } }
		if (!$errors && $s !== '' && $e !== '' && $e < $s) { $errors['end_date'] = 'End date is before the start date.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }
		$data = array('code' => $code, 'name' => $name, 'funder' => mb_substr(trim((string) $this->input->post('funder')), 0, 150) ?: null, 'start_date' => $s ?: null, 'end_date' => $e ?: null,
			'budget' => $budget, 'description' => mb_substr(trim((string) $this->input->post('description')), 0, 255) ?: null);
		if ($id) {
			$data['updated_at'] = date('Y-m-d H:i:s');
			$this->db->where('id', $id)->update('projects', $data);
			$this->audit('projects', 'update', $id, "Updated project $code", (array) $old, $data);
			$this->ok('Project updated.', array('reload' => true));
		}
		$data['status'] = 'active'; $data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('projects', $data);
		$this->audit('projects', 'create', $this->db->insert_id(), "Added project $code $name", null, $data);
		$this->ok('Project added.', array('reload' => true));
	}

	public function toggle($id = 0)
	{
		$this->post_only();
		$this->require_perm('projects', 'edit');
		$p = $this->Project_model->get($id);
		if (!$p) { $this->fail('Project not found.'); }
		$new = $p->status === 'active' ? 'closed' : 'active';
		$this->db->where('id', $p->id)->update('projects', array('status' => $new, 'updated_at' => date('Y-m-d H:i:s')));
		$this->audit('projects', $new === 'closed' ? 'close' : 'reopen', $p->id, ($new === 'closed' ? 'Closed' : 'Reopened') . " project {$p->code}");
		$this->ok('Project ' . ($new === 'closed' ? 'closed.' : 'reopened.'));
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('projects', 'delete');
		$p = $this->Project_model->get($id);
		if (!$p) { $this->fail('Project not found.'); }
		if ($this->db->where('project_id', $p->id)->count_all_results('vouchers') > 0 || $this->db->where('project_id', $p->id)->count_all_results('payroll_runs') > 0) {
			$this->fail('Transactions are tagged to this project. Close it instead.');
		}
		$this->db->where('id', $p->id)->delete('projects');
		$this->audit('projects', 'delete', $p->id, "Deleted project {$p->code}", (array) $p);
		$this->ok('Project deleted.');
	}
}
