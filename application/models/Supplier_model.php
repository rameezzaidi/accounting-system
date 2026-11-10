<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Supplier sub-ledger: bills and payments are real vouchers; only posted vouchers count */
class Supplier_model extends CI_Model
{
	public function get($id)
	{
		return $this->db->select('s.*, a.code AS acc_code, a.name AS acc_name')->from('suppliers s')->join('accounts a', 'a.id = s.payable_account_id')
			->where('s.id', (int) $id)->get()->row();
	}

	public function next_code()
	{
		$r = $this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)),0)+1 n FROM suppliers WHERE code LIKE 'SUP-%'")->row();
		return 'SUP-' . str_pad((string) $r->n, 4, '0', STR_PAD_LEFT);
	}

	/** posted transactions only */
	private function live()
	{
		return "(SELECT t.* FROM supplier_txns t JOIN vouchers v ON v.id = t.voucher_id AND v.status = 'posted')";
	}

	public function balance($id, $asat = '')
	{
		$sql = "SELECT COALESCE(SUM(CASE WHEN kind='bill' THEN amount ELSE -amount END),0) b FROM " . $this->live() . ' t WHERE supplier_id = ?';
		$par = array((int) $id);
		if ($asat !== '') { $sql .= ' AND txn_date <= ?'; $par[] = $asat; }
		return round((float) $this->db->query($sql, $par)->row()->b, 2);
	}

	/** list with billed / paid / balance for each supplier */
	public function listing()
	{
		return $this->db->query("SELECT s.*, a.code acc_code, a.name acc_name,
				COALESCE(SUM(CASE WHEN t.kind='bill' THEN t.amount END),0) billed,
				COALESCE(SUM(CASE WHEN t.kind='payment' THEN t.amount END),0) paid,
				(SELECT COUNT(*) FROM supplier_txns x WHERE x.supplier_id = s.id) txns
			FROM suppliers s JOIN accounts a ON a.id = s.payable_account_id
			LEFT JOIN " . $this->live() . " t ON t.supplier_id = s.id
			GROUP BY s.id ORDER BY s.name")->result();
	}

	/** open bills (outstanding > 0) as at a date; $supplier_id 0 = all suppliers */
	public function open_bills($supplier_id = 0, $asat = '')
	{
		$asat = $asat !== '' ? $asat : '9999-12-31';
		$sql = "SELECT b.*, s.name supplier_name, s.code supplier_code, v.voucher_no,
				b.amount - COALESCE((SELECT SUM(p.amount) FROM " . $this->live() . " p WHERE p.bill_id = b.id AND p.txn_date <= ?),0) outstanding
			FROM " . $this->live() . " b JOIN suppliers s ON s.id = b.supplier_id JOIN vouchers v ON v.id = b.voucher_id
			WHERE b.kind = 'bill' AND b.txn_date <= ?" . ($supplier_id ? ' AND b.supplier_id = ' . (int) $supplier_id : '') . '
			HAVING outstanding > 0.004 ORDER BY b.txn_date, b.id';
		return $this->db->query($sql, array($asat, $asat))->result();
	}

	/** statement lines in date order with running balance (voided ones are flagged, not counted) */
	public function statement($id)
	{
		$rows = $this->db->select('t.*, v.voucher_no, v.status')->from('supplier_txns t')->join('vouchers v', 'v.id = t.voucher_id')
			->where('t.supplier_id', (int) $id)->order_by('t.txn_date')->order_by('t.id')->get()->result();
		$bal = 0.0;
		foreach ($rows as $r) {
			if ($r->status === 'posted') { $bal += $r->kind === 'bill' ? (float) $r->amount : -(float) $r->amount; }
			$r->running = round($bal, 2);
		}
		return $rows;
	}

	/** number of posted payments linked to a bill */
	public function payments_against($bill_id)
	{
		return (int) $this->db->query("SELECT COUNT(*) n FROM " . $this->live() . " p WHERE p.bill_id = ?", array((int) $bill_id))->row()->n;
	}
}
