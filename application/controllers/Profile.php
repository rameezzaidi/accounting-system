<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Every logged-in user can change their own password */
class Profile extends MY_Controller
{
	public function index()
	{
		$this->render('profile/index', array('page_title' => 'Change password'));
	}

	public function save()
	{
		$this->post_only();
		$cur = (string) $this->input->post('current_password');
		$new = (string) $this->input->post('new_password');
		$con = (string) $this->input->post('confirm_password');

		$errors = array();
		if (!password_verify($cur, $this->user->password)) { $errors['current_password'] = 'Current password is wrong.'; }
		if (strlen($new) < 6) { $errors['new_password'] = 'Use at least 6 characters.'; }
		if ($new !== $con) { $errors['confirm_password'] = 'Passwords do not match.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$this->db->where('id', $this->user->id)->update('users', array('password' => password_hash($new, PASSWORD_BCRYPT), 'updated_at' => date('Y-m-d H:i:s')));
		$this->audit('auth', 'password_change', $this->user->id, 'User changed own password');
		$this->ok('Password changed.', array('reload' => true));
	}
}
