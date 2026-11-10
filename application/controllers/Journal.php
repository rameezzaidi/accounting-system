<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Journal vouchers (and the voucher register for every voucher type) */
class Journal extends MY_Controller
{
	const PER_PAGE = 15;

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model', 'Voucher_model'));
	}

	private function filters()
	{
		return array(
			'q'          => trim((string) $this->input->get('q')),
			'type'       => (string) $this->input->get('type'),
			'status'     => (string) $this->input->get('status'),
			'from'       => (string) $this->input->get('from'),
			'to'         => (string) $this->input->get('to'),
			'account_id' => (int) $this->input->get('account_id'),
		);
	}

	public function index()
	{
		$this->require_perm('journal', 'view');
		$this->render('journal/index', array('page_title' => 'Journal Vouchers', 'accounts' => $this->Account_model->posting(false)));
	}

	public function grid()
	{
		$this->require_perm('journal', 'view');
		$f = $this->filters();
		$page = max(1, (int) $this->input->get('page'));
		$this->partial('journal/_grid', array(
			'rows' => $this->Voucher_model->search($f, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
			'total' => $this->Voucher_model->count($f), 'page' => $page, 'per' => self::PER_PAGE,
		));
	}

	/* ---------------- create / edit ---------------- */
	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('journal', $id ? 'edit' : 'add');
		$v = null;
		if ($id) {
			$v = $this->Voucher_model->get($id);
			if (!$v) { show_404(); }
			if ($v->voucher_type !== 'JV') { $this->session->set_flashdata('err', 'Only journal vouchers can be edited. Void this voucher and enter it again.'); redirect('journal-vouchers/view/' . $id); }
			if ($v->status === 'void') { redirect('journal-vouchers/view/' . $id); }
		}
		$accounts = array();
		foreach ($this->Account_model->posting(true) as $a) { $accounts[] = array('id' => (int) $a->id, 'text' => $a->code . ' - ' . $a->name); }
		// an inactive account used by an old voucher must still be selectable while editing
		if ($v) {
			$have = array_column($accounts, 'id');
			foreach ($v->lines as $l) { if (!in_array((int) $l->account_id, $have)) { $accounts[] = array('id' => (int) $l->account_id, 'text' => $l->account_code . ' - ' . $l->account_name); $have[] = (int) $l->account_id; } }
		}
		$this->render('journal/form', array(
			'page_title' => $v ? 'Edit ' . $v->voucher_no : 'New Journal Voucher', 'v' => $v, 'accounts' => $accounts,
			'scripts' => array('assets/js/voucher_form.js'),
			'inline_js' => 'VoucherForm.init(' . json_encode(array('accounts' => $accounts, 'lines' => $v ? $this->lines_for_js($v) : array())) . ');',
		));
	}

	private function lines_for_js($v)
	{
		$out = array();
		foreach ($v->lines as $l) { $out[] = array('account_id' => (int) $l->account_id, 'description' => $l->description, 'debit' => (float) $l->debit, 'credit' => (float) $l->credit); }
		return $out;
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('journal', $id ? 'edit' : 'add');

		$date = (string) $this->input->post('voucher_date');
		$ref = trim((string) $this->input->post('reference'));
		$narr = trim((string) $this->input->post('narration'));

		$errors = array();
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) { $errors['voucher_date'] = 'Select a valid date.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$fy = $this->Fy_model->for_date($date);
		if (!$fy) { $this->fail('No fiscal year exists for ' . dmy($date) . '. Add one in Settings > Fiscal Years.', array('voucher_date' => 'No fiscal year for this date.')); }
		if ((int) $fy->is_closed) { $this->fail("Fiscal year {$fy->title} is closed. Entries cannot be posted.", array('voucher_date' => 'Fiscal year is closed.')); }

		$rows = array();
		$aid = (array) $this->input->post('account_id'); $desc = (array) $this->input->post('description');
		$dr = (array) $this->input->post('debit'); $cr = (array) $this->input->post('credit');
		foreach ($aid as $i => $a) { $rows[] = array('account_id' => $a, 'description' => isset($desc[$i]) ? $desc[$i] : '', 'debit' => isset($dr[$i]) ? $dr[$i] : 0, 'credit' => isset($cr[$i]) ? $cr[$i] : 0); }
		list($lines, $total, $err) = $this->Voucher_model->clean_lines($rows);
		if ($err) { $this->fail($err); }

		$this->load->model('Project_model');
		$pid = (int) $this->input->post('project_id');
		if ($pid && !$this->Project_model->usable($pid)) { $this->fail('Select an active project.', array('project_id' => 'Select an active project.')); }
		$head = array('type' => 'JV', 'date' => $date, 'reference' => $ref, 'narration' => $narr, 'user_id' => $this->user->id, 'project_id' => $pid);

		if ($id) {
			$old = $this->Voucher_model->get($id);
			if (!$old || $old->voucher_type !== 'JV') { $this->fail('Voucher not found.'); }
			if ($old->status === 'void') { $this->fail('A void voucher cannot be edited.'); }
			$old_fy = $this->Fy_model->get($old->fiscal_year_id);
			if ($old_fy && (int) $old_fy->is_closed) { $this->fail('This voucher belongs to a closed fiscal year.'); }
			$block = $this->Voucher_model->edit_block($id);
			if ($block) { $this->fail($block); }
			if (!$this->Voucher_model->update($id, $head, $lines, $total, $fy)) { $this->fail('Could not save the voucher. Please try again.'); }
			$new = $this->Voucher_model->get($id);
			$this->audit('journal', 'update', $id, "Edited voucher {$new->voucher_no}", $this->Voucher_model->snapshot($old), $this->Voucher_model->snapshot($new));
			$this->ok('Voucher updated.', array('redirect' => site_url('journal-vouchers/view/' . $id)));
		}

		$vid = $this->Voucher_model->create($head, $lines, $total, $fy);
		if (!$vid) { $this->fail('Could not save the voucher. Please try again.'); }
		$new = $this->Voucher_model->get($vid);
		$this->audit('journal', 'create', $vid, "Created voucher {$new->voucher_no} for " . number_format($total, 2), null, $this->Voucher_model->snapshot($new));
		$this->ok('Voucher ' . $new->voucher_no . ' posted.', array('redirect' => site_url('journal-vouchers/view/' . $vid)));
	}

	/* ---------------- view / print / void ---------------- */
	public function view($id = 0)
	{
		$this->require_perm('journal', 'view');
		$v = $this->Voucher_model->get((int) $id);
		if (!$v) { show_404(); }
		$this->render('journal/view', array('page_title' => $v->voucher_no, 'v' => $v));
	}

	public function print_voucher($id = 0)
	{
		$this->require_perm('journal', 'print');
		$v = $this->Voucher_model->get((int) $id);
		if (!$v) { show_404(); }
		$this->audit('journal', 'print', $v->id, "Printed voucher {$v->voucher_no}");
		$this->load->view('journal/print', array('v' => $v));
	}

	public function void($id = 0)
	{
		$this->post_only();
		$this->require_perm('journal', 'void');
		$this->do_void((int) $id, trim((string) $this->input->post('reason')), 'journal');
	}
}
