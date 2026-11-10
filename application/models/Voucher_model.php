<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Voucher_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Fy_model');
	}

	/* ------------------------------------------------------------
	 | reading
	 * ------------------------------------------------------------ */
	public function get($id)
	{
		$v = $this->db->select('v.*, fy.title AS fy_title, cu.full_name AS created_by_name, vu.full_name AS voided_by_name')
			->from('vouchers v')->join('fiscal_years fy', 'fy.id = v.fiscal_year_id')
			->join('users cu', 'cu.id = v.created_by', 'left')->join('users vu', 'vu.id = v.voided_by', 'left')
			->where('v.id', (int) $id)->get()->row();
		if (!$v) { return null; }
		$v->lines = $this->db->select('vl.*, a.code AS account_code, a.name AS account_name')
			->from('voucher_lines vl')->join('accounts a', 'a.id = vl.account_id')
			->where('vl.voucher_id', (int) $id)->order_by('vl.id')->get()->result();
		return $v;
	}

	private function apply($f)
	{
		if (!empty($f['types']))   { $this->db->where_in('v.voucher_type', $f['types']); }
		if (!empty($f['type']))    { $this->db->where('v.voucher_type', $f['type']); }
		if (!empty($f['status']))  { $this->db->where('v.status', $f['status']); }
		if (!empty($f['from']))    { $this->db->where('v.voucher_date >=', $f['from']); }
		if (!empty($f['to']))      { $this->db->where('v.voucher_date <=', $f['to']); }
		if (!empty($f['fy_id']))   { $this->db->where('v.fiscal_year_id', (int) $f['fy_id']); }
		if (!empty($f['q'])) {
			$this->db->group_start()->like('v.voucher_no', $f['q'])->or_like('v.narration', $f['q'])->or_like('v.reference', $f['q'])->group_end();
		}
		if (!empty($f['account_id'])) {
			$this->db->where('EXISTS (SELECT 1 FROM voucher_lines x WHERE x.voucher_id = v.id AND x.account_id = ' . (int) $f['account_id'] . ')', null, false);
		}
	}

	public function search($f, $limit, $offset)
	{
		$this->apply($f);
		return $this->db->select('v.*, u.full_name AS created_by_name')->from('vouchers v')
			->join('users u', 'u.id = v.created_by', 'left')
			->order_by('v.voucher_date', 'DESC')->order_by('v.id', 'DESC')->limit($limit, $offset)->get()->result();
	}

	public function count($f)
	{
		$this->apply($f);
		return $this->db->from('vouchers v')->count_all_results();
	}

	/* ------------------------------------------------------------
	 | validation of the voucher grid
	 |   $rows = [ ['account_id'=>, 'description'=>, 'debit'=>, 'credit'=>], ... ]
	 |   returns [clean_lines, total_debit, error_message|null]
	 * ------------------------------------------------------------ */
	public function clean_lines($rows)
	{
		$clean = array(); $td = 0; $tc = 0;
		$acc_cache = array();
		foreach ($rows as $i => $r) {
			$aid = isset($r['account_id']) ? (int) $r['account_id'] : 0;
			$dr  = round((float) str_replace(',', '', isset($r['debit']) ? $r['debit'] : 0), 2);
			$cr  = round((float) str_replace(',', '', isset($r['credit']) ? $r['credit'] : 0), 2);
			if (!$aid && $dr == 0 && $cr == 0) { continue; }           // blank row
			$n = count($clean) + 1;
			if (!$aid)                  { return array(null, 0, "Line $n: please select an account."); }
			if ($dr < 0 || $cr < 0)     { return array(null, 0, "Line $n: amounts cannot be negative."); }
			if ($dr > 0 && $cr > 0)     { return array(null, 0, "Line $n: enter either debit or credit, not both."); }
			if ($dr == 0 && $cr == 0)   { return array(null, 0, "Line $n: enter a debit or credit amount."); }
			if (!isset($acc_cache[$aid])) { $acc_cache[$aid] = $this->db->where('id', $aid)->get('accounts')->row(); }
			$a = $acc_cache[$aid];
			if (!$a)                    { return array(null, 0, "Line $n: account not found."); }
			if ((int) $a->is_group === 1) { return array(null, 0, "Line $n: {$a->code} {$a->name} is a group account. Choose a posting account."); }
			if ((int) $a->is_active !== 1) { return array(null, 0, "Line $n: {$a->code} {$a->name} is inactive."); }
			$clean[] = array(
				'account_id'  => $aid,
				'description' => isset($r['description']) ? mb_substr(trim($r['description']), 0, 255) : null,
				'debit'       => $dr,
				'credit'      => $cr,
			);
			$td += $dr; $tc += $cr;
		}
		if (count($clean) < 2) { return array(null, 0, 'A voucher needs at least two lines.'); }
		$td = round($td, 2); $tc = round($tc, 2);
		if ($td <= 0)           { return array(null, 0, 'Voucher total must be greater than zero.'); }
		if (abs($td - $tc) > 0.004) { return array(null, 0, 'Voucher is not balanced. Debit ' . number_format($td, 2) . ' / Credit ' . number_format($tc, 2) . '.'); }
		return array($clean, $td, null);
	}

	/* ------------------------------------------------------------
	 | writing
	 * ------------------------------------------------------------ */
	private function make_no($type, $fy, $seq)
	{
		$prefix = $this->db->where('setting_key', 'prefix_' . $type)->get('settings')->row();
		$prefix = ($prefix && $prefix->setting_value !== '') ? $prefix->setting_value : $type;
		return $prefix . '-' . $this->Fy_model->short_label($fy) . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Create a voucher with lines. Returns new voucher id, or false.
	 * $head = [type, date, reference, narration, user_id]
	 */
	public function create($head, $lines, $total, $fy)
	{
		for ($try = 0; $try < 5; $try++) {
			$this->db->trans_start();
			$row = $this->db->query('SELECT COALESCE(MAX(seq),0)+1 AS n FROM vouchers WHERE voucher_type = ? AND fiscal_year_id = ? FOR UPDATE',
				array($head['type'], (int) $fy->id))->row();
			$seq = (int) $row->n;
			$ok = $this->db->insert('vouchers', array(
				'voucher_type'   => $head['type'],
				'fiscal_year_id' => (int) $fy->id,
				'seq'            => $seq,
				'voucher_no'     => $this->make_no($head['type'], $fy, $seq),
				'voucher_date'   => $head['date'],
				'reference'      => $head['reference'] !== '' ? $head['reference'] : null,
				'narration'      => $head['narration'],
				'project_id'     => !empty($head['project_id']) ? (int) $head['project_id'] : null,
				'total_amount'   => $total,
				'status'         => 'posted',
				'created_by'     => $head['user_id'],
				'created_at'     => date('Y-m-d H:i:s'),
			));
			if ($ok) {
				$vid = $this->db->insert_id();
				foreach ($lines as &$l) { $l['voucher_id'] = $vid; }
				unset($l);
				$this->db->insert_batch('voucher_lines', $lines);
			}
			$this->db->trans_complete();
			if ($this->db->trans_status() !== false && $ok) { return $vid; }
		}
		return false;
	}

	public function update($id, $head, $lines, $total, $fy)
	{
		$old = $this->get($id);
		$this->db->trans_start();
		$upd = array(
			'voucher_date' => $head['date'],
			'reference'    => $head['reference'] !== '' ? $head['reference'] : null,
			'narration'    => $head['narration'],
			'total_amount' => $total,
			'updated_by'   => $head['user_id'],
			'updated_at'   => date('Y-m-d H:i:s'),
		);
		if (array_key_exists('project_id', $head)) { $upd['project_id'] = !empty($head['project_id']) ? (int) $head['project_id'] : null; }
		// if the date moved into another fiscal year the number is re-issued
		if ((int) $old->fiscal_year_id !== (int) $fy->id) {
			$row = $this->db->query('SELECT COALESCE(MAX(seq),0)+1 AS n FROM vouchers WHERE voucher_type = ? AND fiscal_year_id = ? FOR UPDATE',
				array($old->voucher_type, (int) $fy->id))->row();
			$upd['fiscal_year_id'] = (int) $fy->id;
			$upd['seq'] = (int) $row->n;
			$upd['voucher_no'] = $this->make_no($old->voucher_type, $fy, (int) $row->n);
		}
		$this->db->where('id', (int) $id)->update('vouchers', $upd);
		$this->db->where('voucher_id', (int) $id)->delete('voucher_lines');
		foreach ($lines as &$l) { $l['voucher_id'] = (int) $id; }
		unset($l);
		$this->db->insert_batch('voucher_lines', $lines);
		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	public function void($id, $reason, $user_id)
	{
		$this->db->where('id', (int) $id)->update('vouchers', array(
			'status' => 'void', 'void_reason' => mb_substr($reason, 0, 255),
			'voided_by' => $user_id, 'voided_at' => date('Y-m-d H:i:s'),
		));
		return $this->db->affected_rows() > 0;
	}

	/** true when any line of the voucher was cleared in a bank reconciliation */
	public function has_reconciled($id)
	{
		$r = $this->db->query('SELECT COUNT(*) n FROM bank_recon_items i JOIN voucher_lines vl ON vl.id = i.line_id WHERE vl.voucher_id = ?', array((int) $id))->row();
		return (int) $r->n > 0;
	}

	/** name of the module that owns a voucher posted from a sub-ledger screen, or null for a plain voucher */
	public function owner($id)
	{
		$id = (int) $id;
		$chk = array(
			'Supplier bills & payments' => 'SELECT COUNT(*) n FROM supplier_txns WHERE voucher_id = ?',
			'Depreciation'              => 'SELECT COUNT(*) n FROM asset_depreciation WHERE voucher_id = ?',
			'Asset register'            => 'SELECT COUNT(*) n FROM fixed_assets WHERE purchase_voucher_id = ? OR disposal_voucher_id = ?',
			'Payroll'                   => 'SELECT COUNT(*) n FROM payroll_runs WHERE voucher_id = ? OR pay_voucher_id = ?',
		);
		foreach ($chk as $name => $sql) {
			$par = substr_count($sql, '?') === 2 ? array($id, $id) : array($id);
			if ((int) $this->db->query($sql, $par)->row()->n > 0) { return $name; }
		}
		return null;
	}

	/** message when a voucher must not be edited, otherwise null */
	public function edit_block($id)
	{
		$v = $this->db->where('id', (int) $id)->get('vouchers')->row();
		if (!$v) { return 'Voucher not found.'; }
		if ((int) $v->is_closing) { return 'This is the year-end closing voucher. Reopen the fiscal year from Financial Closing instead.'; }
		if ($this->db->where('voucher_id', (int) $id)->count_all_results('tax_entries') > 0) { return 'This voucher was posted from Tax Entries. Void it there and enter it again.'; }
		if ($this->has_reconciled($id)) { return 'This voucher has bank-reconciled lines. Undo the bank reconciliation first.'; }
		if ($o = $this->owner($id)) { return "This voucher was posted from $o. Change it from that screen."; }
		return null;
	}

	/** message when a voucher must not be voided, otherwise null */
	public function void_block($id)
	{
		$v = $this->db->where('id', (int) $id)->get('vouchers')->row();
		if (!$v) { return 'Voucher not found.'; }
		if ((int) $v->is_closing) { return 'This is the year-end closing voucher. Reopen the fiscal year from Financial Closing instead.'; }
		if ($this->has_reconciled($id)) { return 'This voucher has bank-reconciled lines. Undo the bank reconciliation first.'; }
		$r = $this->db->query("SELECT COUNT(*) n FROM reversals r JOIN vouchers rv ON rv.id = r.voucher_id WHERE r.original_voucher_id = ? AND rv.status = 'posted'", array((int) $id))->row();
		if ((int) $r->n > 0) { return 'A refund / stipend reversal has been posted against this voucher. Void that first.'; }
		if ($o = $this->owner($id)) { return "This voucher was posted from $o. Void it from that screen."; }
		return null;
	}

	/** compact copy of a voucher for the audit trail */
	public function snapshot($v)
	{
		if (!$v) { return null; }
		$lines = array();
		foreach ($v->lines as $l) {
			$lines[] = array('account' => $l->account_code . ' ' . $l->account_name, 'debit' => (float) $l->debit, 'credit' => (float) $l->credit, 'description' => $l->description);
		}
		return array('voucher_no' => $v->voucher_no, 'date' => $v->voucher_date, 'reference' => $v->reference,
			'narration' => $v->narration, 'total' => (float) $v->total_amount, 'status' => $v->status, 'lines' => $lines);
	}
}
