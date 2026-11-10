<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Refunds and stipend reversals.
 *   refund  : money handed back against a RECEIPT (RV)  -> posts a payment voucher  Dr income/counter, Cr cash-bank
 *   stipend : money returned against a PAYMENT (PV)     -> posts a receipt voucher  Dr cash-bank, Cr expense/counter
 * Each one is linked to the original voucher and can never exceed what is still unreversed on it.
 */
class Reversal_model extends CI_Model
{
	public static $cfg = array(
		'refund'  => array('orig_type' => 'RV', 'post_type' => 'PV'),
		'stipend' => array('orig_type' => 'PV', 'post_type' => 'RV'),
	);

	private function apply($kind, $f)
	{
		$this->db->where('r.kind', $kind);
		if (!empty($f['status'])) { $this->db->where('v.status', $f['status']); }
		if (!empty($f['from']))   { $this->db->where('v.voucher_date >=', $f['from']); }
		if (!empty($f['to']))     { $this->db->where('v.voucher_date <=', $f['to']); }
		if (!empty($f['q'])) {
			$this->db->group_start()->like('v.voucher_no', $f['q'])->or_like('ov.voucher_no', $f['q'])
				->or_like('r.party_name', $f['q'])->or_like('r.reason', $f['q'])->group_end();
		}
	}

	public function search($kind, $f, $limit, $offset)
	{
		$this->apply($kind, $f);
		return $this->db->select('r.*, v.voucher_no, v.voucher_date, v.status, ov.voucher_no AS orig_no, ov.voucher_date AS orig_date, u.full_name AS created_by_name')
			->from('reversals r')->join('vouchers v', 'v.id = r.voucher_id')->join('vouchers ov', 'ov.id = r.original_voucher_id')
			->join('users u', 'u.id = r.created_by', 'left')
			->order_by('v.voucher_date', 'DESC')->order_by('r.id', 'DESC')->limit($limit, $offset)->get()->result();
	}

	public function count($kind, $f)
	{
		$this->apply($kind, $f);
		return $this->db->from('reversals r')->join('vouchers v', 'v.id = r.voucher_id')->join('vouchers ov', 'ov.id = r.original_voucher_id')->count_all_results();
	}

	/** amount already reversed (non-void) against an original voucher */
	public function done($orig_id)
	{
		$r = $this->db->query("SELECT COALESCE(SUM(r.amount),0) s FROM reversals r JOIN vouchers v ON v.id = r.voucher_id WHERE r.original_voucher_id = ? AND v.status = 'posted'", array((int) $orig_id))->row();
		return round((float) $r->s, 2);
	}

	/**
	 * Original voucher info: counter account, cash/bank account, total, remaining.
	 * Returns null when the voucher cannot be reversed (wrong type, void, not a simple 2-line voucher)
	 */
	public function original($kind, $id)
	{
		$v = $this->db->where('id', (int) $id)->get('vouchers')->row();
		if (!$v || $v->status !== 'posted' || $v->voucher_type !== self::$cfg[$kind]['orig_type'] || (int) $v->is_closing) { return null; }
		$lines = $this->db->select('vl.*, a.code, a.name, a.cash_bank')->from('voucher_lines vl')->join('accounts a', 'a.id = vl.account_id')
			->where('vl.voucher_id', $v->id)->get()->result();
		if (count($lines) !== 2) { return null; }
		$cash = null; $counter = null;
		foreach ($lines as $l) {
			// RV: Dr cash / Cr counter     PV: Dr counter / Cr cash
			$is_cash = ($kind === 'refund') ? ((float) $l->debit > 0) : ((float) $l->credit > 0);
			if ($is_cash) { $cash = $l; } else { $counter = $l; }
		}
		if (!$cash || !$counter) { return null; }
		$v->cash = $cash; $v->counter = $counter;
		$v->done = $this->done($v->id);
		$v->remaining = round((float) $v->total_amount - $v->done, 2);
		return $v;
	}

	/** vouchers that still have something left to reverse (for the search box) */
	public function eligible($kind, $q, $limit = 30)
	{
		$type = self::$cfg[$kind]['orig_type'];
		$sql = "SELECT v.id FROM vouchers v WHERE v.voucher_type = ? AND v.status = 'posted' AND v.is_closing = 0";
		$args = array($type);
		if ($q !== '') {
			$sql .= ' AND (v.voucher_no LIKE ? OR v.narration LIKE ? OR v.reference LIKE ?)';
			$like = '%' . $this->db->escape_like_str($q) . '%';
			array_push($args, $like, $like, $like);
		}
		$sql .= ' ORDER BY v.voucher_date DESC, v.id DESC LIMIT 200';
		$out = array();
		foreach ($this->db->query($sql, $args)->result() as $r) {
			$o = $this->original($kind, $r->id);
			if ($o && $o->remaining > 0.004) { $out[] = $o; }
			if (count($out) >= $limit) { break; }
		}
		return $out;
	}
}
