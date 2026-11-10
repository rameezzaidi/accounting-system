<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Monthly depreciation run. One voucher per run (all categories together):
 *   Dr depreciation expense (per category)   Cr accumulated depreciation (per category)
 * Only the latest run of an asset can be voided, so the calculation order is never broken.
 */
class Depreciation extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Voucher_model', 'Asset_model'));
	}

	/** assets to depreciate up to $ym with their amounts; [rows, total] */
	private function build($ym, $fy)
	{
		$fy_ym = substr($fy->start_date, 0, 7);
		$assets = $this->db->select('f.*, c.name cat_name, c.accum_account_id, c.expense_account_id')->from('fixed_assets f')->join('asset_categories c', 'c.id = f.category_id')
			->where('f.status', 'active')->where('f.purchase_date <=', date('Y-m-t', strtotime($ym . '-01')))->order_by('f.asset_no')->get()->result();
		$rows = array(); $tot = 0.0;
		foreach ($assets as $a) {
			list($m, $amt) = $this->Asset_model->plan($a, $ym, $fy_ym);
			if ($m > 0 && $amt > 0) { $a->months = $m; $a->amount = $amt; $rows[] = $a; $tot += $amt; }
		}
		return array($rows, round($tot, 2));
	}

	public function index()
	{
		$this->require_perm('depreciation', 'view');
		$runs = $this->db->query("SELECT d.voucher_id, MAX(d.period) period, COUNT(*) assets, SUM(d.amount) amount, v.voucher_no, v.voucher_date
			FROM asset_depreciation d JOIN vouchers v ON v.id = d.voucher_id GROUP BY d.voucher_id, v.voucher_no, v.voucher_date ORDER BY period DESC, d.voucher_id DESC")->result();
		$latest = $this->db->query('SELECT MAX(period) p FROM asset_depreciation')->row()->p;
		$this->render('depreciation/index', array('page_title' => 'Depreciation', 'runs' => $runs, 'latest' => $latest));
	}

	public function preview()
	{
		$this->require_perm('depreciation', 'run');
		$ym = (string) $this->input->get('period');
		$r = month_range($ym);
		if (!$r) { $this->partial('ledger/_message', array('message' => 'Select a month.')); }
		if ($ym > date('Y-m')) { $this->partial('ledger/_message', array('message' => 'Depreciation cannot be run for a future month.')); }
		$fy = $this->Fy_model->for_date($r[1]);
		if (!$fy) { $this->partial('ledger/_message', array('message' => 'No fiscal year exists for this month.')); }
		if ((int) $fy->is_closed) { $this->partial('ledger/_message', array('message' => "Fiscal year {$fy->title} is closed.")); }
		list($rows, $tot) = $this->build($ym, $fy);
		$this->partial('depreciation/_preview', array('ym' => $ym, 'fy' => $fy, 'rows' => $rows, 'total' => $tot));
	}

	public function run()
	{
		$this->post_only();
		$this->require_perm('depreciation', 'run');
		$ym = (string) $this->input->post('period');
		$r = month_range($ym);
		if (!$r) { $this->fail('Select a month.'); }
		if ($ym > date('Y-m')) { $this->fail('Depreciation cannot be run for a future month.'); }
		$fy = $this->fy_or_fail($r[1], 'period');
		list($rows, $tot) = $this->build($ym, $fy);
		if (!$rows) { $this->fail('Nothing to depreciate for ' . $ym . '.'); }

		$dr = array(); $cr = array();
		foreach ($rows as $a) {
			$dr[(int) $a->expense_account_id] = (isset($dr[(int) $a->expense_account_id]) ? $dr[(int) $a->expense_account_id] : 0) + $a->amount;
			$cr[(int) $a->accum_account_id] = (isset($cr[(int) $a->accum_account_id]) ? $cr[(int) $a->accum_account_id] : 0) + $a->amount;
		}
		$narr = 'Depreciation for ' . date('F Y', strtotime($ym . '-01')) . ' (' . count($rows) . ' assets)';
		$in = array();
		foreach ($dr as $acc => $v) { $in[] = array('account_id' => $acc, 'debit' => round($v, 2), 'credit' => 0, 'description' => $narr); }
		foreach ($cr as $acc => $v) { $in[] = array('account_id' => $acc, 'debit' => 0, 'credit' => round($v, 2), 'description' => $narr); }
		list($lines, $total, $err) = $this->Voucher_model->clean_lines($in);
		if ($err) { $this->fail($err); }

		$date = min($r[1], $fy->end_date);
		$this->db->trans_begin();
		$vid = $this->Voucher_model->create(array('type' => 'JV', 'date' => $date, 'reference' => 'DEP-' . $ym, 'narration' => $narr, 'user_id' => $this->user->id), $lines, $total, $fy);
		if ($vid) {
			foreach ($rows as $a) { $this->db->insert('asset_depreciation', array('asset_id' => $a->id, 'period' => $ym, 'months' => $a->months, 'amount' => $a->amount, 'voucher_id' => $vid)); }
		}
		if (!$vid || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not post the depreciation. Please try again (a run for this month may already exist).'); }
		$this->db->trans_commit();
		$new = $this->Voucher_model->get($vid);
		$this->audit('depreciation', 'run', $vid, "Depreciation run $ym: {$new->voucher_no} for " . number_format($total, 2) . ' on ' . count($rows) . ' assets', null, $this->Voucher_model->snapshot($new));
		$this->ok("{$new->voucher_no} posted: depreciation " . number_format($total, 2) . ' on ' . count($rows) . ' assets.', array('reload' => true));
	}

	public function void($voucher_id = 0)
	{
		$this->post_only();
		$this->require_perm('depreciation', 'void');
		$vid = (int) $voucher_id;
		$items = $this->db->select('d.*, f.asset_no, f.status')->from('asset_depreciation d')->join('fixed_assets f', 'f.id = d.asset_id')->where('d.voucher_id', $vid)->get()->result();
		if (!$items) { $this->fail('Depreciation run not found.'); }
		foreach ($items as $i) {
			if ($i->status !== 'active') { $this->fail("Asset {$i->asset_no} has been disposed. Reverse its disposal first."); }
			if ((string) $this->Asset_model->last_period($i->asset_id) !== (string) $i->period) { $this->fail("Asset {$i->asset_no} has depreciation booked after this run. Void the later run first."); }
		}
		$v = $this->Voucher_model->get($vid);
		$reason = trim((string) $this->input->post('reason'));
		if ($reason === '') { $this->fail('Please enter a reason.'); }
		$fy = $this->Fy_model->get($v->fiscal_year_id);
		if ($fy && (int) $fy->is_closed) { $this->fail('This run belongs to a closed fiscal year.'); }
		if ($this->Voucher_model->has_reconciled($vid)) { $this->fail('The voucher is bank-reconciled.'); }
		$this->db->trans_begin();
		$this->Voucher_model->void($vid, $reason, $this->user->id);
		$this->db->where('voucher_id', $vid)->delete('asset_depreciation');
		if ($this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not void. Please try again.'); }
		$this->db->trans_commit();
		$this->audit('depreciation', 'void', $vid, "Voided depreciation run {$v->voucher_no}. Reason: $reason", $this->Voucher_model->snapshot($v), array('status' => 'void', 'reason' => $reason));
		$this->ok("Depreciation run {$v->voucher_no} voided.", array('reload' => true));
	}
}
