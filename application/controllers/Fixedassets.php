<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Asset register: purchase, details, disposal (with gain / loss). Depreciation is run from the Depreciation screen. */
class Fixedassets extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model', 'Voucher_model', 'Asset_model'));
	}

	public function index()
	{
		$this->require_perm('fixed_assets', 'view');
		$status = (string) $this->input->get('status');
		$cat = (int) $this->input->get('category');
		$q = trim((string) $this->input->get('q'));
		$sql = "SELECT f.*, c.name cat_name, COALESCE((SELECT SUM(d.amount) FROM asset_depreciation d WHERE d.asset_id = f.id),0) accum
			FROM fixed_assets f JOIN asset_categories c ON c.id = f.category_id WHERE 1=1";
		$par = array();
		if ($status === 'active' || $status === 'disposed') { $sql .= ' AND f.status = ?'; $par[] = $status; }
		if ($cat) { $sql .= ' AND f.category_id = ?'; $par[] = $cat; }
		if ($q !== '') { $sql .= ' AND (f.name LIKE ? OR f.asset_no LIKE ? OR f.serial_no LIKE ?)'; $l = '%' . $this->db->escape_like_str($q) . '%'; array_push($par, $l, $l, $l); }
		$rows = $this->db->query($sql . ' ORDER BY f.asset_no', $par)->result();
		$this->render('fixed_assets/index', array('page_title' => 'Asset Register', 'rows' => $rows, 'cats' => $this->db->order_by('name')->get('asset_categories')->result(),
			'f' => array('status' => $status, 'category' => $cat, 'q' => $q)));
	}

	public function form($id = 0)
	{
		$id = (int) $id;
		$this->require_perm('fixed_assets', $id ? 'edit' : 'add');
		$a = $id ? $this->db->where('id', $id)->get('fixed_assets')->row() : null;
		if ($id && !$a) { show_404(); }
		$pay = array();
		foreach ($this->Account_model->posting(true) as $x) { if (in_array($x->account_type, array('asset', 'liability'), true)) { $pay[] = $x; } }
		$locked = $a && ($a->purchase_voucher_id || $this->Asset_model->last_period($a->id) || $a->status === 'disposed');
		$this->partial('fixed_assets/_form', array('a' => $a, 'cats' => $this->db->where('is_active', 1)->order_by('name')->get('asset_categories')->result(), 'pay' => $pay, 'locked' => $locked));
	}

	public function save()
	{
		$this->post_only();
		$id = (int) $this->input->post('id');
		$this->require_perm('fixed_assets', $id ? 'edit' : 'add');
		$old = $id ? $this->db->where('id', $id)->get('fixed_assets')->row() : null;
		if ($id && !$old) { $this->fail('Asset not found.'); }
		$locked = $old && ($old->purchase_voucher_id || $this->Asset_model->last_period($old->id) || $old->status === 'disposed');

		$name = trim((string) $this->input->post('name'));
		$common = array('name' => $name, 'serial_no' => mb_substr(trim((string) $this->input->post('serial_no')), 0, 80) ?: null,
			'location' => mb_substr(trim((string) $this->input->post('location')), 0, 100) ?: null, 'notes' => mb_substr(trim((string) $this->input->post('notes')), 0, 255) ?: null);
		if ($name === '' || mb_strlen($name) > 150) { $this->fail('Please fix the highlighted fields.', array('name' => 'Enter the asset name.')); }

		if ($locked) {
			$this->db->where('id', $id)->update('fixed_assets', $common);
			$this->audit('fixed_assets', 'update', $id, "Updated asset {$old->asset_no} details", array('name' => $old->name, 'serial_no' => $old->serial_no, 'location' => $old->location, 'notes' => $old->notes), $common);
			$this->ok('Asset updated.', array('reload' => true));
		}

		$cat = $this->db->where('id', (int) $this->input->post('category_id'))->get('asset_categories')->row();
		$date = (string) $this->input->post('purchase_date');
		$cost = $this->money('cost'); $salv = $this->money('salvage_value');
		$method = (string) $this->input->post('dep_method'); $rate = (string) $this->input->post('dep_rate');
		$errors = array();
		if (!$cat || (!(int) $cat->is_active && !($old && (int) $old->category_id === (int) $cat->id))) { $errors['category_id'] = 'Select a category.'; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) { $errors['purchase_date'] = 'Select a valid date.'; }
		if ($cost <= 0) { $errors['cost'] = 'Enter the cost.'; }
		if ($salv < 0 || $salv >= $cost) { $errors['salvage_value'] = 'Salvage value must be less than the cost.'; }
		if (!in_array($method, array('sl', 'wdv'), true)) { $errors['dep_method'] = 'Select the method.'; }
		if (!is_numeric($rate) || (float) $rate < 0 || (float) $rate > 100) { $errors['dep_rate'] = 'Enter a rate between 0 and 100.'; }
		$payacc = (int) $this->input->post('pay_account');
		if (!$id && $payacc) {
			$pa = $this->Account_model->get($payacc);
			if (!$pa || (int) $pa->is_group || !(int) $pa->is_active || !in_array($pa->account_type, array('asset', 'liability'), true) || ($cat && (int) $pa->id === (int) $cat->asset_account_id)) { $errors['pay_account'] = 'Select a valid cash / bank / payable account.'; }
		}
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$data = array_merge($common, array('category_id' => (int) $cat->id, 'purchase_date' => $date, 'cost' => $cost, 'salvage_value' => $salv, 'dep_method' => $method, 'dep_rate' => round((float) $rate, 2)));
		if ($id) {
			$this->db->where('id', $id)->update('fixed_assets', $data);
			$this->audit('fixed_assets', 'update', $id, "Updated asset {$old->asset_no}", (array) $old, $data);
			$this->ok('Asset updated.', array('reload' => true));
		}

		$data['asset_no'] = $this->Asset_model->next_no(); $data['status'] = 'active'; $data['created_by'] = $this->user->id; $data['created_at'] = date('Y-m-d H:i:s');
		$vid = null; $fy = null;
		if ($payacc) {
			$fy = $this->fy_or_fail($date, 'purchase_date');
			$narr = "Purchase of {$data['asset_no']} $name";
			list($lines, $total, $err) = $this->Voucher_model->clean_lines(array(
				array('account_id' => $cat->asset_account_id, 'debit' => $cost, 'credit' => 0, 'description' => $narr),
				array('account_id' => $payacc, 'debit' => 0, 'credit' => $cost, 'description' => $narr)));
			if ($err) { $this->fail($err); }
			$pa = $this->Account_model->get($payacc);
		}
		$this->db->trans_begin();
		if ($payacc) {
			$vid = $this->Voucher_model->create(array('type' => $pa->cash_bank !== 'none' ? 'PV' : 'JV', 'date' => $date, 'reference' => $data['asset_no'], 'narration' => $narr, 'user_id' => $this->user->id), $lines, $total, $fy);
			$data['purchase_voucher_id'] = $vid;
		}
		if (!$payacc || $vid) { $this->db->insert('fixed_assets', $data); }
		if (($payacc && !$vid) || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();
		$aid = $this->db->insert_id();
		$this->audit('fixed_assets', 'create', $aid, "Added asset {$data['asset_no']} $name, cost " . number_format($cost, 2) . ($vid ? ' (purchase voucher posted)' : ' (no voucher)'), null, $data);
		$this->ok("Asset {$data['asset_no']} added.", array('reload' => true));
	}

	public function view($id = 0)
	{
		$this->require_perm('fixed_assets', 'view');
		$a = $this->db->select('f.*, c.name cat_name, c.asset_account_id, c.accum_account_id, pv.voucher_no pv_no, dv.voucher_no dv_no')->from('fixed_assets f')
			->join('asset_categories c', 'c.id = f.category_id')->join('vouchers pv', 'pv.id = f.purchase_voucher_id', 'left')->join('vouchers dv', 'dv.id = f.disposal_voucher_id', 'left')
			->where('f.id', (int) $id)->get()->row();
		if (!$a) { show_404(); }
		$deps = $this->db->select('d.*, v.voucher_no')->from('asset_depreciation d')->join('vouchers v', 'v.id = d.voucher_id')->where('d.asset_id', $a->id)->order_by('d.period')->get()->result();
		$this->render('fixed_assets/view', array('page_title' => 'Asset ' . $a->asset_no, 'a' => $a, 'deps' => $deps, 'accum' => $this->Asset_model->accumulated($a->id)));
	}

	/* ---------------- disposal ---------------- */
	public function disposeform($id = 0)
	{
		$this->require_perm('fixed_assets', 'dispose');
		$a = $this->db->where('id', (int) $id)->get('fixed_assets')->row();
		if (!$a || $a->status !== 'active') { show_404(); }
		$recv = array();
		$cat = $this->db->where('id', $a->category_id)->get('asset_categories')->row();
		foreach ($this->Account_model->posting(true) as $x) { if ($x->account_type === 'asset' && (int) $x->id !== (int) $cat->asset_account_id && (int) $x->id !== (int) $cat->accum_account_id) { $recv[] = $x; } }
		$accum = $this->Asset_model->accumulated($a->id);
		$this->partial('fixed_assets/_dispose', array('a' => $a, 'accum' => $accum, 'recv' => $recv));
	}

	public function dispose()
	{
		$this->post_only();
		$this->require_perm('fixed_assets', 'dispose');
		$a = $this->db->where('id', (int) $this->input->post('id'))->get('fixed_assets')->row();
		if (!$a || $a->status !== 'active') { $this->fail('Asset not found or already disposed.'); }
		$cat = $this->db->where('id', $a->category_id)->get('asset_categories')->row();
		$date = (string) $this->input->post('disposed_date');
		$proceeds = $this->money('amount');
		$recv = (int) $this->input->post('recv_account');
		$errors = array();
		if ($proceeds < 0) { $errors['amount'] = 'Proceeds cannot be negative.'; }
		if ($proceeds > 0) {
			$ra = $this->Account_model->get($recv);
			if (!$ra || $ra->account_type !== 'asset' || (int) $ra->is_group || !(int) $ra->is_active) { $errors['recv_account'] = 'Select where the sale proceeds were received.'; }
		}
		if ($date !== '' && $date < $a->purchase_date) { $errors['disposed_date'] = 'Cannot be before the purchase date.'; }
		$last = $this->Asset_model->last_period($a->id);
		if ($last && $date !== '' && substr($date, 0, 7) < $last) { $errors['disposed_date'] = 'Depreciation is booked up to ' . $last . '. Dispose on or after that month.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }
		$fy = $this->fy_or_fail($date, 'disposed_date');
		$gain_acc = $this->default_account('acc_disposal_gain', 'C03904');
		if (!$gain_acc || !(int) $gain_acc->is_active) { $this->fail('The disposal gain/loss account is missing or inactive (Settings > acc_disposal_gain).'); }

		$accum = $this->Asset_model->accumulated($a->id);
		$gain = round($proceeds - ((float) $a->cost - $accum), 2);
		$narr = "Disposal of {$a->asset_no} {$a->name}";
		$rows = array();
		if ($proceeds > 0) { $rows[] = array('account_id' => $recv, 'debit' => $proceeds, 'credit' => 0, 'description' => $narr); }
		if ($accum > 0)    { $rows[] = array('account_id' => $cat->accum_account_id, 'debit' => $accum, 'credit' => 0, 'description' => $narr); }
		if ($gain < 0)     { $rows[] = array('account_id' => $gain_acc->id, 'debit' => -$gain, 'credit' => 0, 'description' => $narr . ' - loss'); }
		$rows[] = array('account_id' => $cat->asset_account_id, 'debit' => 0, 'credit' => (float) $a->cost, 'description' => $narr);
		if ($gain > 0)     { $rows[] = array('account_id' => $gain_acc->id, 'debit' => 0, 'credit' => $gain, 'description' => $narr . ' - gain'); }
		list($lines, $total, $err) = $this->Voucher_model->clean_lines($rows);
		if ($err) { $this->fail($err); }

		$this->db->trans_begin();
		$vid = $this->Voucher_model->create(array('type' => 'JV', 'date' => $date, 'reference' => $a->asset_no, 'narration' => $narr, 'user_id' => $this->user->id), $lines, $total, $fy);
		if ($vid) {
			$this->db->where('id', $a->id)->update('fixed_assets', array('status' => 'disposed', 'disposed_date' => $date, 'disposal_amount' => $proceeds, 'disposal_voucher_id' => $vid));
		}
		if (!$vid || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();
		$new = $this->Voucher_model->get($vid);
		$this->audit('fixed_assets', 'dispose', $a->id, "Disposed {$a->asset_no} for " . number_format($proceeds, 2) . ' (' . ($gain >= 0 ? 'gain ' : 'loss ') . number_format(abs($gain), 2) . ') - ' . $new->voucher_no, null, $this->Voucher_model->snapshot($new));
		$this->ok("Asset disposed. {$new->voucher_no} posted (" . ($gain >= 0 ? 'gain ' : 'loss ') . number_format(abs($gain), 2) . ').', array('reload' => true));
	}

	public function undispose($id = 0)
	{
		$this->post_only();
		$this->require_perm('fixed_assets', 'dispose');
		$a = $this->db->where('id', (int) $id)->get('fixed_assets')->row();
		if (!$a || $a->status !== 'disposed') { $this->fail('Asset is not disposed.'); }
		$reason = trim((string) $this->input->post('reason'));
		if ($reason === '') { $this->fail('Please enter a reason.'); }
		$v = $this->Voucher_model->get($a->disposal_voucher_id);
		$fy = $v ? $this->Fy_model->get($v->fiscal_year_id) : null;
		if ($fy && (int) $fy->is_closed) { $this->fail('The disposal belongs to a closed fiscal year.'); }
		$this->db->trans_begin();
		if ($v && $v->status === 'posted') { $this->Voucher_model->void($v->id, $reason, $this->user->id); }
		$this->db->where('id', $a->id)->update('fixed_assets', array('status' => 'active', 'disposed_date' => null, 'disposal_amount' => null, 'disposal_voucher_id' => null));
		if ($this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();
		$this->audit('fixed_assets', 'undispose', $a->id, "Reversed disposal of {$a->asset_no}. Reason: $reason", $v ? $this->Voucher_model->snapshot($v) : null);
		$this->ok('Disposal reversed. The asset is active again.', array('reload' => true));
	}

	/* ---------------- remove (wrongly entered asset) ---------------- */
	public function remove($id = 0)
	{
		$this->post_only();
		$this->require_perm('fixed_assets', 'edit');
		$a = $this->db->where('id', (int) $id)->get('fixed_assets')->row();
		if (!$a) { $this->fail('Asset not found.'); }
		if ($a->status !== 'active') { $this->fail('A disposed asset cannot be removed. Reverse the disposal first.'); }
		if ($this->Asset_model->last_period($a->id)) { $this->fail('Depreciation has been booked on this asset. Void the depreciation runs first.'); }
		$reason = trim((string) $this->input->post('reason'));
		if ($reason === '') { $this->fail('Please enter a reason.'); }
		$v = $a->purchase_voucher_id ? $this->Voucher_model->get($a->purchase_voucher_id) : null;
		if ($v) {
			$fy = $this->Fy_model->get($v->fiscal_year_id);
			if ($fy && (int) $fy->is_closed) { $this->fail('The purchase voucher belongs to a closed fiscal year.'); }
			if ($this->Voucher_model->has_reconciled($v->id)) { $this->fail('The purchase voucher is bank-reconciled. Undo the reconciliation first.'); }
		}
		$this->db->trans_begin();
		$this->db->where('id', $a->id)->delete('fixed_assets');
		if ($v && $v->status === 'posted') { $this->Voucher_model->void($v->id, 'Asset removed: ' . $reason, $this->user->id); }
		if ($this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not remove. Please try again.'); }
		$this->db->trans_commit();
		$this->audit('fixed_assets', 'remove', $a->id, "Removed asset {$a->asset_no} {$a->name}. Reason: $reason", (array) $a);
		$this->ok('Asset removed' . ($v ? ' and its purchase voucher voided.' : '.'), array('redirect' => site_url('fixed-assets')));
	}

	public function export()
	{
		$this->require_perm('fixed_assets', 'view');
		$rows = $this->db->query("SELECT f.*, c.name cat_name, COALESCE((SELECT SUM(d.amount) FROM asset_depreciation d WHERE d.asset_id = f.id),0) accum FROM fixed_assets f JOIN asset_categories c ON c.id = f.category_id ORDER BY f.asset_no")->result();
		$this->audit('fixed_assets', 'export', null, 'Exported asset register');
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$out = array(array('Asset no', 'Name', 'Category', 'Serial', 'Location', 'Purchase date', 'Cost', 'Accumulated depreciation', 'Book value', 'Status'));
		foreach ($rows as $r) { $out[] = array($r->asset_no, $r->name, $r->cat_name, $r->serial_no, $r->location, $r->purchase_date, $n($r->cost), $n($r->accum), $n($r->cost - $r->accum), $r->status); }
		csv_out('asset_register.csv', $out);
	}
}
