<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Year-end closing: closing journal, carry-forward of balances, close / reopen a fiscal year */
class Financialclosing extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Closing_model', 'Report_model', 'Voucher_model'));
	}

	public function index()
	{
		$this->require_perm('financial_closing', 'view');
		$rows = $this->Fy_model->all();
		foreach ($rows as $r) {
			$is = $this->Report_model->income_statement($r->id, $r->start_date, $r->end_date);
			$r->income = $is['total_income']; $r->expense = $is['total_expense']; $r->net = $is['net'];
			$r->closing_no = $r->closing_voucher_id ? $this->db->select('voucher_no')->where('id', $r->closing_voucher_id)->get('vouchers')->row() : null;
			$r->closed_by_name = $r->closed_by ? $this->db->select('full_name')->where('id', $r->closed_by)->get('users')->row() : null;
			$r->next = $r->carried_to_id ? $this->Fy_model->get($r->carried_to_id) : null;
		}
		$this->render('financial_closing/index', array('page_title' => 'Financial Closing', 'rows' => $rows));
	}

	public function form($id = 0)
	{
		$this->require_perm('financial_closing', 'close');
		$fy = $this->Fy_model->get((int) $id);
		if (!$fy) { show_404(); }
		$re = $this->Closing_model->re_accounts();
		$default = 0;
		foreach ($re as $a) { if ($a->code === 'H02' || $a->code === '3002' || stripos($a->name, 'retained') !== false) { $default = (int) $a->id; break; } }
		$this->partial('financial_closing/_form', array('fy' => $fy, 'plan' => $this->Closing_model->plan($fy), 're' => $re, 'default_re' => $default));
	}

	public function close()
	{
		$this->post_only();
		$this->require_perm('financial_closing', 'close');
		$fy = $this->Fy_model->get((int) $this->input->post('fy_id'));
		if (!$fy) { $this->fail('Fiscal year not found.'); }
		if (!$this->input->post('confirm')) { $this->fail('Please tick the confirmation box.', array('confirm' => 'Please confirm.')); }
		$err = '';
		$res = $this->Closing_model->close($fy, (int) $this->input->post('re_account'), (bool) $this->input->post('replace_ob'), $this->user->id, $err);
		if (!$res) { $this->fail($err); }
		$this->audit('financial_closing', 'close', $fy->id,
			"Closed {$fy->title}. Net " . ($res['profit'] >= 0 ? 'profit ' : 'loss ') . number_format(abs($res['profit']), 2) . "; {$res['accounts_carried']} balances carried to {$res['next']->title}",
			array('fiscal_year' => $fy->title, 'is_closed' => 0),
			array('is_closed' => 1, 'closing_voucher_id' => $res['voucher_id'], 'next_year' => $res['next']->title, 'accounts_carried' => $res['accounts_carried'], 'net_profit' => $res['profit']));
		$this->ok("{$fy->title} is closed. Balances carried forward to {$res['next']->title}.", array('reload' => true));
	}

	public function reopen($id = 0)
	{
		$this->post_only();
		$this->require_perm('financial_closing', 'reopen');
		$fy = $this->Fy_model->get((int) $id);
		if (!$fy) { $this->fail('Fiscal year not found.'); }
		$reason = trim((string) $this->input->post('reason'));
		if ($reason === '') { $this->fail('Please enter a reason.'); }
		$err = '';
		if (!$this->Closing_model->reopen($fy, $reason, $this->user->id, $err)) { $this->fail($err); }
		$this->audit('financial_closing', 'reopen', $fy->id, "Reopened {$fy->title}. Reason: $reason",
			array('is_closed' => 1, 'closing_voucher_id' => $fy->closing_voucher_id), array('is_closed' => 0, 'reason' => $reason));
		$this->ok("{$fy->title} is open again. The closing journal was voided.", array('reload' => true));
	}
}
