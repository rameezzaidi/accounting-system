<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Bank accounts overview: book balance, last reconciliation, items waiting to be cleared */
class Banking extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Bank_model', 'Report_model'));
	}

	public function index()
	{
		$this->require_perm('banking', 'view');
		$fy = $this->active_fy;
		$rows = array(); $total = 0;
		if ($fy) {
			$to = min(date('Y-m-d'), $fy->end_date);
			if ($to < $fy->start_date) { $to = $fy->start_date; }
			$bal = array();
			foreach ($this->Report_model->balances($fy->id, $fy->start_date, $to, true) as $b) {
				$bal[(int) $b->id] = round((float) $b->ob + (float) $b->prior + (float) $b->dr - (float) $b->cr, 2);
			}
			foreach ($this->Bank_model->cash_bank_accounts() as $a) {
				if (!(int) $a->is_active && empty($bal[(int) $a->id])) { continue; }
				$a->balance = isset($bal[(int) $a->id]) ? $bal[(int) $a->id] : 0;
				$a->last = $a->cash_bank === 'bank' ? $this->Bank_model->last_recon($a->id) : null;
				$a->open = $a->cash_bank === 'bank' ? $this->Bank_model->uncleared_count($a->id, $fy, $to) : 0;
				$total += $a->balance;
				$rows[] = $a;
			}
		}
		$this->render('banking/index', array('page_title' => 'Bank Accounts', 'rows' => $rows, 'total' => round($total, 2), 'fy' => $fy, 'to' => isset($to) ? $to : ''));
	}
}
