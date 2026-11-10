<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Fixed asset helpers: numbering, accumulated depreciation, depreciation calculation */
class Asset_model extends CI_Model
{
	public function next_no()
	{
		$r = $this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(asset_no, 5) AS UNSIGNED)),0)+1 n FROM fixed_assets WHERE asset_no LIKE 'AST-%'")->row();
		return 'AST-' . str_pad((string) $r->n, 4, '0', STR_PAD_LEFT);
	}

	public function accumulated($asset_id)
	{
		return round((float) $this->db->query('SELECT COALESCE(SUM(amount),0) s FROM asset_depreciation WHERE asset_id = ?', array((int) $asset_id))->row()->s, 2);
	}

	public function last_period($asset_id)
	{
		$r = $this->db->query('SELECT MAX(period) p FROM asset_depreciation WHERE asset_id = ?', array((int) $asset_id))->row();
		return $r->p ?: null;
	}

	public static function next_ym($ym)
	{
		return date('Y-m', strtotime($ym . '-01 +1 month'));
	}

	/**
	 * Depreciation still to be booked for an asset up to (and including) month $upto (YYYY-MM).
	 * Months before the fiscal year start are not booked here (run them in their own year).
	 * returns [months, amount]
	 */
	public function plan($a, $upto, $fy_start_ym)
	{
		$last = $this->last_period($a->id);
		$start = $last ? self::next_ym($last) : substr($a->purchase_date, 0, 7);
		if ($start < $fy_start_ym) { $start = $fy_start_ym; }
		if ($start > $upto) { return array(0, 0.0); }
		$accum = $this->accumulated($a->id);
		$depreciable = (float) $a->cost - (float) $a->salvage_value;
		$total = 0.0; $months = 0;
		for ($m = $start; $m <= $upto; $m = self::next_ym($m)) {
			if ($a->dep_method === 'wdv') { $amt = ((float) $a->cost - $accum) * (float) $a->dep_rate / 1200; }
			else { $amt = $depreciable * (float) $a->dep_rate / 1200; }
			$amt = round(min($amt, $depreciable - $accum), 2);
			if ($amt < 0) { $amt = 0.0; }
			$accum += $amt; $total += $amt; $months++;
		}
		return array($months, round($total, 2));
	}
}
