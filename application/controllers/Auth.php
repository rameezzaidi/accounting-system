<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
	const MAX_FAILS = 5;       // failed logins allowed ...
	const LOCK_MINUTES = 10;   // ... inside this many minutes

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Audit_model'));
	}

	public function login()
	{
		if ($this->session->userdata('user_id')) { redirect('dashboard'); }
		if ($this->input->method() !== 'post') {
			$this->load->view('auth/login');
			return;
		}

		$username = trim((string) $this->input->post('username'));
		$password = (string) $this->input->post('password');
		$ip = $this->input->ip_address();

		if ($username === '' || $password === '') { return $this->reply(false, 'Enter your username and password.'); }

		// simple brute-force protection (counted from the audit log)
		$since = date('Y-m-d H:i:s', strtotime('-' . self::LOCK_MINUTES . ' minutes'));
		$fails = $this->db->where('action', 'login_failed')->where('created_at >=', $since)->group_start()
			->where('ip_address', $ip)->or_where('username', $username)->group_end()->count_all_results('audit_logs');
		if ($fails >= self::MAX_FAILS) {
			return $this->reply(false, 'Too many failed attempts. Please wait ' . self::LOCK_MINUTES . ' minutes and try again.');
		}

		$u = $this->db->where('username', $username)->get('users')->row();
		if (!$u || !password_verify($password, $u->password)) {
			$this->Audit_model->log($u ?: null, 'auth', 'login_failed', $u ? $u->id : null, 'Failed login for username "' . $username . '"');
			usleep(400000);
			return $this->reply(false, 'Wrong username or password.');
		}
		if ((int) $u->status !== 1) {
			$this->Audit_model->log($u, 'auth', 'login_failed', $u->id, 'Login blocked - account inactive');
			return $this->reply(false, 'Your account is disabled. Please contact the administrator.');
		}

		$this->session->sess_regenerate(TRUE);
		$this->session->set_userdata('user_id', (int) $u->id);
		$this->db->where('id', $u->id)->update('users', array('last_login' => date('Y-m-d H:i:s')));
		$this->Audit_model->log($u, 'auth', 'login', $u->id, 'User logged in');
		return $this->reply(true, 'Welcome back!', array('redirect' => site_url('dashboard')));
	}

	public function logout()
	{
		$uid = $this->session->userdata('user_id');
		if ($uid) {
			$u = $this->db->where('id', $uid)->get('users')->row();
			if ($u) { $this->Audit_model->log($u, 'auth', 'logout', $u->id, 'User logged out'); }
		}
		$this->session->sess_destroy();
		redirect('login');
	}

	private function reply($status, $message, $extra = array())
	{
		$this->output->set_content_type('application/json', 'utf-8')
			->set_output(json_encode(array_merge(array('status' => $status, 'message' => $message), $extra)));
	}
}
