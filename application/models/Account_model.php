<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account_model extends CI_Model
{
	public function get($id)
	{
		return $this->db->where('id', (int) $id)->get('accounts')->row();
	}

	/** Every account in tree order with ->level and ->has_children */
	public function tree($f = array())
	{
		$all = $this->db->order_by('code')->get('accounts')->result();
		$by_parent = array();
		foreach ($all as $a) {
			$key = $a->parent_id === null ? 0 : (int) $a->parent_id;
			$by_parent[$key][] = $a;
		}
		$out = array();
		$walk = function ($pid, $level) use (&$walk, &$out, $by_parent) {
			if (empty($by_parent[$pid])) { return; }
			foreach ($by_parent[$pid] as $a) {
				$a->level = $level;
				$a->has_children = !empty($by_parent[(int) $a->id]);
				$out[] = $a;
				$walk((int) $a->id, $level + 1);
			}
		};
		$walk(0, 0);

		// filters are applied after the tree is built so parents keep their position
		if (!empty($f['type']) || !empty($f['q']) || (isset($f['status']) && $f['status'] !== '')) {
			$q = !empty($f['q']) ? mb_strtolower($f['q']) : '';
			$out = array_values(array_filter($out, function ($a) use ($f, $q) {
				if (!empty($f['type']) && $a->account_type !== $f['type']) { return false; }
				if (isset($f['status']) && $f['status'] !== '' && (int) $a->is_active !== (int) $f['status']) { return false; }
				if ($q !== '' && mb_strpos(mb_strtolower($a->code . ' ' . $a->name), $q) === false) { return false; }
				return true;
			}));
		}
		return $out;
	}

	/** Accounts that can take transactions (non group) */
	public function posting($only_active = true, $cash_bank = false)
	{
		$this->db->where('is_group', 0);
		if ($only_active) { $this->db->where('is_active', 1); }
		if ($cash_bank) { $this->db->where('cash_bank !=', 'none'); }
		return $this->db->order_by('code')->get('accounts')->result();
	}

	/** Group (header) accounts that may be chosen as parent */
	public function groups()
	{
		return $this->db->where('is_group', 1)->order_by('code')->get('accounts')->result();
	}

	/** id of an account + all accounts below it */
	public function descendant_ids($id)
	{
		$ids = array((int) $id);
		$frontier = array((int) $id);
		while ($frontier) {
			$rows = $this->db->select('id')->where_in('parent_id', $frontier)->get('accounts')->result();
			$frontier = array();
			foreach ($rows as $r) { $ids[] = (int) $r->id; $frontier[] = (int) $r->id; }
		}
		return $ids;
	}

	public function code_exists($code, $except_id = 0)
	{
		$this->db->where('code', $code);
		if ($except_id) { $this->db->where('id !=', (int) $except_id); }
		return $this->db->count_all_results('accounts') > 0;
	}

	public function has_children($id)
	{
		return $this->db->where('parent_id', (int) $id)->count_all_results('accounts') > 0;
	}

	public function is_used($id)
	{
		$v = $this->db->where('account_id', (int) $id)->count_all_results('voucher_lines');
		$o = $this->db->where('account_id', (int) $id)->count_all_results('opening_balances');
		$t = $this->db->where('account_id', (int) $id)->count_all_results('tax_rates');            // posts tax to this account
		$b = $this->db->where('account_id', (int) $id)->count_all_results('bank_reconciliations');
		return ($v + $o + $t + $b) > 0;
	}

	public function insert($data)
	{
		$data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('accounts', $data);
		return $this->db->insert_id();
	}

	public function update($id, $data)
	{
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where('id', (int) $id)->update('accounts', $data);
	}

	public function delete($id)
	{
		$this->db->where('id', (int) $id)->delete('accounts');
	}

	/** Suggest the next free code under a parent, e.g. parent 1100 => 1105 */
	public function next_code($parent_id)
	{
		if (!$parent_id) {
			$max = $this->db->select_max('code')->where('parent_id IS NULL', null, false)->get('accounts')->row();
			return $max && ctype_digit((string) $max->code) ? (string) ((int) $max->code + 1000) : '';
		}
		$max = $this->db->select_max('code')->where('parent_id', (int) $parent_id)->get('accounts')->row();
		if ($max && $max->code !== null && ctype_digit((string) $max->code)) { return (string) ((int) $max->code + 1); }
		$p = $this->get($parent_id);
		return ($p && ctype_digit((string) $p->code)) ? (string) ((int) $p->code + 1) : '';
	}
}
