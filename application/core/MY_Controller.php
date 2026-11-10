<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for every screen that needs a logged-in user.
 *  - checks login (AJAX requests get a JSON 401)
 *  - loads permissions of the user's role (Super Admin = everything)
 *  - helpers: render(), json(), partial(), audit(), require_perm()
 */
#[AllowDynamicProperties]
class MY_Controller extends CI_Controller
{
	public $user = null;               // current user row (object)
	protected $perms = array();      // ['coa.view' => true, ...]
	protected $is_super = false;
	public $active_fy = null;      // active fiscal year row

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Setting_model', 'Audit_model', 'Fy_model'));

		$uid = $this->session->userdata('user_id');
		if (!$uid) {
			if ($this->input->is_ajax_request()) {
				$this->json(array('status' => false, 'message' => 'Session expired. Please login again.', 'login' => site_url('login')), 401);
			}
			redirect('login');
		}

		$this->user = $this->db->select('u.*, r.name AS role_name')
			->from('users u')->join('roles r', 'r.id = u.role_id')
			->where('u.id', $uid)->get()->row();
		if (!$this->user || (int) $this->user->status !== 1) {
			$this->session->sess_destroy();
			if ($this->input->is_ajax_request()) {
				$this->json(array('status' => false, 'message' => 'Your account is not active.', 'login' => site_url('login')), 401);
			}
			redirect('login');
		}

		$this->is_super = ((int) $this->user->role_id === 1);
		if (!$this->is_super) {
			$rows = $this->db->select('p.module, p.action')->from('role_permissions rp')
				->join('permissions p', 'p.id = rp.permission_id')
				->where('rp.role_id', $this->user->role_id)->get()->result();
			foreach ($rows as $r) { $this->perms[$r->module . '.' . $r->action] = true; }
		}
		$this->active_fy = $this->Fy_model->active();
	}

	/* ---------------- permissions ---------------- */
	public function can($module, $action = 'view')
	{
		return $this->is_super || isset($this->perms[$module . '.' . $action]);
	}

	protected function require_perm($module, $action = 'view')
	{
		if ($this->can($module, $action)) { return; }
		if ($this->input->is_ajax_request()) {
			$this->json(array('status' => false, 'message' => 'You do not have permission to do this.'), 403);
		}
		$this->output->set_status_header(403);
		$this->render('errors/forbidden', array('page_title' => 'Access denied'));
		$this->output->_display();
		exit;
	}

	/* ---------------- output helpers ---------------- */
	protected function render($view, $data = array())
	{
		if (!isset($data['page_title'])) { $data['page_title'] = ''; }
		$this->load->view('layout/header', $data);
		$this->load->view($view, $data);
		$this->load->view('layout/footer', $data);
	}

	protected function json($data, $code = 200)
	{
		$this->output->set_status_header($code)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($data));
		$this->output->_display();
		exit;
	}

	protected function ok($message = 'Saved successfully.', $extra = array())
	{
		$this->json(array_merge(array('status' => true, 'message' => $message), $extra));
	}

	protected function fail($message, $errors = array(), $code = 200)
	{
		$this->json(array('status' => false, 'message' => $message, 'errors' => $errors), $code);
	}

	/** Render a view and send it as plain HTML (used by ajax tables / modal forms) */
	protected function partial($view, $data = array())
	{
		$this->output->set_content_type('text/html', 'utf-8');
		$this->load->view($view, $data);
		$this->output->_display();
		exit;
	}

	/** Only accept POST for state-changing actions */
	protected function post_only()
	{
		if ($this->input->method() !== 'post') {
			$this->fail('Invalid request.', array(), 405);
		}
	}

	/* ---------------- audit trail ---------------- */
	protected function audit($module, $action, $record_id = null, $description = '', $old = null, $new = null)
	{
		$this->Audit_model->log($this->user, $module, $action, $record_id, $description, $old, $new);
	}

	/** Make sure a date falls in an open fiscal year; returns the FY row or false */
	protected function open_fy_for($date)
	{
		$fy = $this->Fy_model->for_date($date);
		if (!$fy) { return false; }
		return $fy;
	}

	/** Validate a posting date and return its open fiscal year (stops with a JSON error otherwise) */
	protected function fy_or_fail($date, $field = 'voucher_date')
	{
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) || !strtotime($date)) { $this->fail('Please fix the highlighted fields.', array($field => 'Select a valid date.')); }
		$fy = $this->Fy_model->for_date($date);
		if (!$fy) { $this->fail('No fiscal year exists for ' . dmy($date) . '.', array($field => 'No fiscal year for this date.')); }
		if ((int) $fy->is_closed) { $this->fail("Fiscal year {$fy->title} is closed.", array($field => 'Fiscal year is closed.')); }
		return $fy;
	}

	/** fiscal year + from/to from the query string for report screens (array, or an error message string) */
	protected function period_params()
	{
		$fy = $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
		if (!$fy) { return 'No fiscal year found.'; }
		$from = (string) $this->input->get('from'); $to = (string) $this->input->get('to');
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = $fy->start_date; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to = min(date('Y-m-d'), $fy->end_date); if ($to < $fy->start_date) { $to = $fy->end_date; } }
		if ($from < $fy->start_date) { $from = $fy->start_date; }
		if ($to > $fy->end_date) { $to = $fy->end_date; }
		if ($from > $to) { return 'The "from" date cannot be after the "to" date.'; }
		return array('fy' => $fy, 'from' => $from, 'to' => $to);
	}

	/** money value from POST as a rounded float */
	protected function money($key)
	{
		return round((float) str_replace(',', '', (string) $this->input->post($key)), 2);
	}

	/** Void a posted voucher (shared by Journal and Quick Entries) */
	protected function do_void($id, $reason, $module, $checked = false)
	{
		$this->load->model('Voucher_model');
		$v = $this->Voucher_model->get($id);
		if (!$v) { $this->fail('Voucher not found.'); }
		if ($v->status === 'void') { $this->fail('This voucher is already void.'); }
		if ($reason === '') { $this->fail('Please enter a reason.'); }
		$fy = $this->Fy_model->get($v->fiscal_year_id);
		if ($fy && (int) $fy->is_closed) { $this->fail('This voucher belongs to a closed fiscal year and cannot be voided.'); }
		if (!$checked) {
			$block = $this->Voucher_model->void_block($id);
			if ($block) { $this->fail($block); }
		}
		$this->Voucher_model->void($id, $reason, $this->user->id);
		$this->audit($module, 'void', $id, "Voided voucher {$v->voucher_no}. Reason: $reason", $this->Voucher_model->snapshot($v), array('status' => 'void', 'reason' => $reason));
		$this->ok("Voucher {$v->voucher_no} voided.");
	}

	/** Default account row for a module, taken from Settings (acc_* keys = account codes) */
	protected function default_account($key, $fallback_code = '')
	{
		$code = '';
		$r = $this->db->where('setting_key', $key)->get('settings')->row();
		if ($r && trim($r->setting_value) !== '') { $code = trim($r->setting_value); } else { $code = $fallback_code; }
		// databases created before the government chart still use the old numeric codes
		$legacy = array('acc_payable' => '2101', 'acc_salary_payable' => '2106', 'acc_payroll_ded' => '2107', 'acc_disposal_gain' => '4003', 'acc_salary_exp' => '5001');
		$row = $code === '' ? null : $this->db->where('code', $code)->get('accounts')->row();
		if (!$row && isset($legacy[$key])) { $row = $this->db->where('code', $legacy[$key])->get('accounts')->row(); }
		return $row;
	}
}
