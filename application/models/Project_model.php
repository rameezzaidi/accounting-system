<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** PMU projects: lookup helpers and the income / expense figures of a project (taken from tagged vouchers) */
class Project_model extends CI_Model
{
	public function get($id)
	{
		return $this->db->where('id', (int) $id)->get('projects')->row();
	}

	public function active()
	{
		return $this->db->where('status', 'active')->order_by('code')->get('projects')->result();
	}

	/** true when the project exists and is still open for new transactions */
	public function usable($id)
	{
		$p = $this->get($id);
		return $p && $p->status === 'active';
	}

	/** income / expense of a project (posted vouchers only), optionally inside a date range */
	public function totals($id, $from = '', $to = '')
	{
		$sql = "SELECT a.account_type t, COALESCE(SUM(vl.debit),0) d, COALESCE(SUM(vl.credit),0) c
			FROM voucher_lines vl JOIN vouchers v ON v.id = vl.voucher_id AND v.status = 'posted'
			JOIN accounts a ON a.id = vl.account_id
			WHERE v.project_id = ? AND a.account_type IN ('income','expense')";
		$par = array((int) $id);
		if ($from !== '') { $sql .= ' AND v.voucher_date >= ?'; $par[] = $from; }
		if ($to !== '')   { $sql .= ' AND v.voucher_date <= ?'; $par[] = $to; }
		$sql .= ' GROUP BY a.account_type';
		$out = array('income' => 0.0, 'expense' => 0.0);
		foreach ($this->db->query($sql, $par)->result() as $r) {
			if ($r->t === 'income')  { $out['income']  = round((float) $r->c - (float) $r->d, 2); }
			if ($r->t === 'expense') { $out['expense'] = round((float) $r->d - (float) $r->c, 2); }
		}
		return $out;
	}
}
