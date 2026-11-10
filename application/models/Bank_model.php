<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bank reconciliation support.
 * A bank account's lines are "cleared" when they are ticked in a saved reconciliation (bank_recon_items).
 * Book balance at a date = ledger closing balance. Cleared balance = book balance - uncleared lines.
 * Lines of earlier fiscal years that were carried forward (closing) stay reconcilable in the new year.
 */
class Bank_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Fy_model', 'Report_model'));
	}

	/** this fiscal year + every earlier year that was carried into it */
	public function chain($fy)
	{
		$ids = array((int) $fy->id); $cur = $fy; $n = 0;
		while ($cur && $cur->carried_from_id && $n++ < 50) {
			$cur = $this->Fy_model->get($cur->carried_from_id);
			if ($cur) { $ids[] = (int) $cur->id; }
		}
		return $ids;
	}

	public function cash_bank_accounts($only_bank = false)
	{
		$this->db->where('is_group', 0)->where('cash_bank !=', 'none');
		if ($only_bank) { $this->db->where('cash_bank', 'bank'); }
		return $this->db->order_by('code')->get('accounts')->result();
	}

	public function last_recon($account_id)
	{
		return $this->db->where(array('account_id' => (int) $account_id, 'status' => 'active'))
			->order_by('statement_date', 'DESC')->order_by('id', 'DESC')->limit(1)->get('bank_reconciliations')->row();
	}

	/** posted lines of the account up to $date that are not cleared yet */
	public function open_lines($account_id, $fy, $date)
	{
		$ids = implode(',', $this->chain($fy));
		return $this->db->query(
			"SELECT vl.id, vl.debit, vl.credit, vl.description, v.id AS voucher_id, v.voucher_no, v.voucher_type, v.voucher_date, v.reference, v.narration
			 FROM voucher_lines vl JOIN vouchers v ON v.id = vl.voucher_id
			 WHERE vl.account_id = ? AND v.status = 'posted' AND v.fiscal_year_id IN ($ids) AND v.voucher_date <= ?
			   AND NOT EXISTS (SELECT 1 FROM bank_recon_items i WHERE i.line_id = vl.id)
			 ORDER BY v.voucher_date, v.id, vl.id", array((int) $account_id, $date))->result();
	}

	public function book_balance($account_id, $fy, $date)
	{
		$l = $this->Report_model->ledger(array((int) $account_id), $fy->id, $fy->start_date, $date);
		return round((float) $l['closing'], 2);
	}

	public function uncleared_count($account_id, $fy, $date)
	{
		return count($this->open_lines($account_id, $fy, $date));
	}
}
