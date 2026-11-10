<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fy_model extends CI_Model
{
	public function all()
	{
		return $this->db->order_by('start_date', 'DESC')->get('fiscal_years')->result();
	}

	public function get($id)
	{
		return $this->db->where('id', (int) $id)->get('fiscal_years')->row();
	}

	public function active()
	{
		$r = $this->db->where('is_active', 1)->get('fiscal_years')->row();
		if (!$r) { $r = $this->db->order_by('start_date', 'DESC')->limit(1)->get('fiscal_years')->row(); }
		return $r;
	}

	/** Fiscal year that contains a given date */
	public function for_date($date)
	{
		return $this->db->where('start_date <=', $date)->where('end_date >=', $date)->get('fiscal_years')->row();
	}

	/** Dates overlapping another fiscal year? */
	public function overlaps($start, $end, $except_id = 0)
	{
		$this->db->where('start_date <=', $end)->where('end_date >=', $start);
		if ($except_id) { $this->db->where('id !=', (int) $except_id); }
		return $this->db->count_all_results('fiscal_years') > 0;
	}

	public function title_exists($title, $except_id = 0)
	{
		$this->db->where('title', $title);
		if ($except_id) { $this->db->where('id !=', (int) $except_id); }
		return $this->db->count_all_results('fiscal_years') > 0;
	}

	public function has_data($id)
	{
		$v = $this->db->where('fiscal_year_id', (int) $id)->count_all_results('vouchers');
		$o = $this->db->where('fiscal_year_id', (int) $id)->count_all_results('opening_balances');
		return ($v + $o) > 0;
	}

	public function set_active($id)
	{
		$this->db->trans_start();
		$this->db->update('fiscal_years', array('is_active' => 0));
		$this->db->where('id', (int) $id)->update('fiscal_years', array('is_active' => 1));
		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	/** "2026-27" style short label used in voucher numbers */
	public function short_label($fy)
	{
		$s = (int) date('Y', strtotime($fy->start_date));
		$e = (int) date('Y', strtotime($fy->end_date));
		return $s === $e ? (string) $s : $s . '-' . substr((string) $e, -2);
	}
}
