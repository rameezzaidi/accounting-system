<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Employee master used by payroll */
class Employees extends MY_Controller
{
	public function index()
	{
		$this->require_perm('employees', 'view');
		$rows = $this->db->query('SELECT e.*, (SELECT COUNT(*) FROM payroll_lines l WHERE l.employee_id = e.id) used FROM employees e ORDER BY e.status, e.name')->result();
		$this->render('employees/index', array('page_title' => 'Employees', 'rows' => $rows));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('employees', $id ? 'edit' : 'add');
		$e = $id ? $this->db->where('id', $id)->get('employees')->row() : null;
		if ($id && !$e) { show_404(); }
		$this->partial('employees/_form', array('e' => $e));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('employees', $id ? 'edit' : 'add');
		$old = $id ? $this->db->where('id', $id)->get('employees')->row() : null;
		if ($id && !$old) { $this->fail('Employee not found.'); }

		$name = trim((string) $this->input->post('name'));
		$basic = $this->money('basic_salary'); $allow = $this->money('allowances'); $ded = $this->money('fixed_deductions');
		$join = (string) $this->input->post('join_date');
		$status = (string) $this->input->post('status') === 'inactive' ? 'inactive' : 'active';
		$errors = array();
		if ($name === '' || mb_strlen($name) > 120) { $errors['name'] = 'Enter the employee name.'; }
		if ($basic < 0) { $errors['basic_salary'] = 'Cannot be negative.'; }
		if ($allow < 0) { $errors['allowances'] = 'Cannot be negative.'; }
		if ($ded < 0 || $ded > $basic + $allow) { $errors['fixed_deductions'] = 'Must be between 0 and the gross salary.'; }
		if ($join !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $join) || !strtotime($join))) { $errors['join_date'] = 'Select a valid date.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$data = array('name' => $name, 'designation' => mb_substr(trim((string) $this->input->post('designation')), 0, 100) ?: null,
			'department' => mb_substr(trim((string) $this->input->post('department')), 0, 100) ?: null, 'phone' => mb_substr(trim((string) $this->input->post('phone')), 0, 40) ?: null,
			'join_date' => $join !== '' ? $join : null, 'basic_salary' => $basic, 'allowances' => $allow, 'fixed_deductions' => $ded, 'status' => $status);
		if ($id) {
			$data['updated_at'] = date('Y-m-d H:i:s');
			$this->db->where('id', $id)->update('employees', $data);
			$this->audit('employees', 'update', $id, "Updated employee {$old->emp_code} {$old->name}", (array) $old, $data);
			$this->ok('Employee updated.', array('reload' => true));
		}
		$r = $this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(emp_code, 5) AS UNSIGNED)),0)+1 n FROM employees WHERE emp_code LIKE 'EMP-%'")->row();
		$data['emp_code'] = 'EMP-' . str_pad((string) $r->n, 4, '0', STR_PAD_LEFT); $data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('employees', $data);
		$this->audit('employees', 'create', $this->db->insert_id(), "Added employee {$data['emp_code']} $name", null, $data);
		$this->ok("Employee {$data['emp_code']} added.", array('reload' => true));
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('employees', 'delete');
		$e = $this->db->where('id', (int) $id)->get('employees')->row();
		if (!$e) { $this->fail('Employee not found.'); }
		if ($this->db->where('employee_id', $e->id)->count_all_results('payroll_lines') > 0) { $this->fail('This employee appears in payroll runs and cannot be deleted. Mark as inactive instead.'); }
		$this->db->where('id', $e->id)->delete('employees');
		$this->audit('employees', 'delete', $e->id, "Deleted employee {$e->emp_code} {$e->name}", (array) $e);
		$this->ok('Employee deleted.');
	}
}
