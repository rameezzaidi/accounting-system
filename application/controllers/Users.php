<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends MY_Controller
{
	const PER_PAGE = 15;

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('User_model', 'Role_model'));
	}

	private function filters()
	{
		return array('q' => trim((string) $this->input->get('q')), 'role_id' => (int) $this->input->get('role_id'),
			'status' => $this->input->get('status') === null ? '' : (string) $this->input->get('status'));
	}

	public function index()
	{
		$this->require_perm('users', 'view');
		$this->render('users/index', array('page_title' => 'Users', 'roles' => $this->Role_model->all()));
	}

	public function grid()
	{
		$this->require_perm('users', 'view');
		$f = $this->filters(); $page = max(1, (int) $this->input->get('page'));
		$this->partial('users/_grid', array('rows' => $this->User_model->search($f, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
			'total' => $this->User_model->count($f), 'page' => $page, 'per' => self::PER_PAGE));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('users', $id ? 'edit' : 'add');
		$u = $id ? $this->User_model->get($id) : null;
		if ($id && !$u) { show_404(); }
		$this->partial('users/_form', array('u' => $u, 'roles' => $this->Role_model->all()));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('users', $id ? 'edit' : 'add');

		$name = trim((string) $this->input->post('full_name'));
		$username = trim((string) $this->input->post('username'));
		$email = trim((string) $this->input->post('email'));
		$role_id = (int) $this->input->post('role_id');
		$password = (string) $this->input->post('password');
		$status = $this->input->post('status') ? 1 : 0;

		$old = $id ? $this->User_model->get($id) : null;
		if ($id && !$old) { $this->fail('User not found.'); }

		$errors = array();
		if ($name === '') { $errors['full_name'] = 'Full name is required.'; }
		if (!preg_match('/^[A-Za-z0-9._\-]{3,60}$/', $username)) { $errors['username'] = 'Username: 3-60 letters, numbers, . _ - only.'; }
		elseif ($this->User_model->username_exists($username, $id)) { $errors['username'] = 'This username is already taken.'; }
		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email address.'; }
		if (!$this->Role_model->get($role_id)) { $errors['role_id'] = 'Select a role.'; }
		if (!$id && strlen($password) < 6) { $errors['password'] = 'Password must be at least 6 characters.'; }
		if ($id && $password !== '' && strlen($password) < 6) { $errors['password'] = 'Password must be at least 6 characters.'; }

		if ($old && !$errors) {
			$was_super = ((int) $old->role_id === 1 && (int) $old->status === 1);
			$stays_super = ($role_id === 1 && $status === 1);
			if ($was_super && !$stays_super && $this->User_model->active_super_admins() <= 1) { $this->fail('This is the last active Super Admin. Create another one first.'); }
			if ((int) $old->id === (int) $this->user->id && $status === 0) { $this->fail('You cannot deactivate your own account.'); }
			if ((int) $old->id === (int) $this->user->id && $this->is_super && $role_id !== 1) { $this->fail('You cannot remove your own Super Admin role.'); }
		}
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$data = array('full_name' => $name, 'username' => $username, 'email' => $email !== '' ? $email : null, 'role_id' => $role_id, 'status' => $status);
		if ($password !== '') { $data['password'] = password_hash($password, PASSWORD_BCRYPT); }
		$data['updated_at'] = date('Y-m-d H:i:s');

		if ($id) {
			$this->db->where('id', $id)->update('users', $data);
			$before = array('full_name' => $old->full_name, 'username' => $old->username, 'email' => $old->email, 'role_id' => (int) $old->role_id, 'status' => (int) $old->status);
			$after = array('full_name' => $name, 'username' => $username, 'email' => $data['email'], 'role_id' => $role_id, 'status' => $status);
			if ($password !== '') { $after['password'] = 'changed'; }
			$this->audit('users', 'update', $id, "Updated user $username" . ($password !== '' ? ' (password reset)' : ''), $before, $after);
			$this->ok('User updated.');
		}
		$data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('users', $data);
		$new_id = $this->db->insert_id();
		unset($data['password']);
		$this->audit('users', 'create', $new_id, "Created user $username", null, $data);
		$this->ok('User created.');
	}

	public function toggle($id = 0)
	{
		$this->post_only();
		$this->require_perm('users', 'edit');
		$u = $this->User_model->get((int) $id);
		if (!$u) { $this->fail('User not found.'); }
		if ((int) $u->id === (int) $this->user->id) { $this->fail('You cannot deactivate your own account.'); }
		$new = (int) $u->status ? 0 : 1;
		if (!$new && (int) $u->role_id === 1 && $this->User_model->active_super_admins() <= 1) { $this->fail('This is the last active Super Admin.'); }
		$this->db->where('id', $u->id)->update('users', array('status' => $new, 'updated_at' => date('Y-m-d H:i:s')));
		$this->audit('users', $new ? 'activate' : 'deactivate', $u->id, ($new ? 'Activated' : 'Deactivated') . " user {$u->username}");
		$this->ok('User ' . ($new ? 'activated' : 'deactivated') . '.');
	}

	public function delete($id = 0)
	{
		$this->post_only();
		$this->require_perm('users', 'delete');
		$u = $this->User_model->get((int) $id);
		if (!$u) { $this->fail('User not found.'); }
		if ((int) $u->id === (int) $this->user->id) { $this->fail('You cannot delete your own account.'); }
		if ((int) $u->role_id === 1 && (int) $u->status === 1 && $this->User_model->active_super_admins() <= 1) { $this->fail('This is the last active Super Admin.'); }
		$used = $this->db->where('created_by', $u->id)->count_all_results('vouchers');
		if ($used) { $this->fail('This user has entered vouchers, so the account cannot be deleted. Deactivate it instead.'); }
		$this->db->where('id', $u->id)->delete('users');
		$this->audit('users', 'delete', $u->id, "Deleted user {$u->username}", array('username' => $u->username, 'full_name' => $u->full_name, 'role_id' => (int) $u->role_id));
		$this->ok('User deleted.');
	}
}
