<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tax entries: record a sale / purchase / withholding with its tax. Each entry posts one balanced voucher.
 *   output      Dr settle (base+tax)  Cr income (base)   Cr tax payable (tax)
 *   input       Dr expense/asset (base) Dr input tax (tax)  Cr settle (base+tax)
 *   withholding Dr expense/asset (gross) Cr tax payable (tax) Cr settle (gross-tax)
 *   advance     Dr settle (gross-tax)  Dr advance tax (tax)  Cr income (gross)
 */
class Taxentries extends MY_Controller
{
	const PER_PAGE = 15;

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model', 'Voucher_model'));
	}

	private function filters()
	{
		return array('q' => trim((string) $this->input->get('q')), 'type' => (string) $this->input->get('type'), 'status' => (string) $this->input->get('status'),
			'from' => (string) $this->input->get('from'), 'to' => (string) $this->input->get('to'));
	}

	private function apply($f)
	{
		if ($f['type'] !== '' && array_key_exists($f['type'], tax_types())) { $this->db->where('e.tax_type', $f['type']); }
		if ($f['status'] === 'posted' || $f['status'] === 'void') { $this->db->where('v.status', $f['status']); }
		if ($f['from'] !== '') { $this->db->where('e.entry_date >=', $f['from']); }
		if ($f['to'] !== '')   { $this->db->where('e.entry_date <=', $f['to']); }
		if ($f['q'] !== '') { $this->db->group_start()->like('v.voucher_no', $f['q'])->or_like('e.party_name', $f['q'])->or_like('e.invoice_no', $f['q'])->group_end(); }
	}

	private function query($f, $limit = 0, $offset = 0)
	{
		$this->apply($f);
		$this->db->select('e.*, v.voucher_no, v.status, t.code AS rate_code, t.name AS rate_name')->from('tax_entries e')
			->join('vouchers v', 'v.id = e.voucher_id')->join('tax_rates t', 't.id = e.tax_rate_id')
			->order_by('e.entry_date', 'DESC')->order_by('e.id', 'DESC');
		if ($limit) { $this->db->limit($limit, $offset); }
		return $this->db->get()->result();
	}

	public function index()
	{
		$this->require_perm('tax_entries', 'view');
		$this->render('tax_entries/index', array('page_title' => 'Tax Entries'));
	}

	public function grid()
	{
		$this->require_perm('tax_entries', 'view');
		$f = $this->filters();
		$page = max(1, (int) $this->input->get('page'));
		$rows = $this->query($f, self::PER_PAGE, ($page - 1) * self::PER_PAGE);
		$this->apply($f);
		$total = $this->db->from('tax_entries e')->join('vouchers v', 'v.id = e.voucher_id')->count_all_results();
		$this->partial('tax_entries/_grid', array('rows' => $rows, 'total' => $total, 'page' => $page, 'per' => self::PER_PAGE));
	}

	public function form()
	{
		$this->require_perm('tax_entries', 'add');
		$accounts = array();
		foreach ($this->Account_model->posting(true) as $a) { $accounts[] = array('id' => (int) $a->id, 'text' => $a->code . ' - ' . $a->name, 'type' => $a->account_type, 'cb' => $a->cash_bank); }
		$rates = $this->db->select('t.*, a.name AS acc_name')->from('tax_rates t')->join('accounts a', 'a.id = t.account_id')->where('t.is_active', 1)->where('a.is_active', 1)->order_by('t.code')->get()->result();
		$this->partial('tax_entries/_form', array('accounts' => $accounts, 'rates' => $rates));
	}

	public function save()
	{
		$this->post_only();
		$this->require_perm('tax_entries', 'add');
		$date = (string) $this->input->post('voucher_date');
		$base = round((float) str_replace(',', '', (string) $this->input->post('base_amount')), 2);
		$main = (int) $this->input->post('main_account');
		$settle = (int) $this->input->post('settle_account');
		$party = mb_substr(trim((string) $this->input->post('party_name')), 0, 150);
		$inv = mb_substr(trim((string) $this->input->post('invoice_no')), 0, 60);
		$note = trim((string) $this->input->post('narration'));
		$rate = $this->db->where('id', (int) $this->input->post('tax_rate'))->get('tax_rates')->row();

		$errors = array();
		if (!$rate || !(int) $rate->is_active) { $errors['tax_rate'] = 'Select an active tax rate.'; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) { $errors['voucher_date'] = 'Select a valid date.'; }
		if ($base <= 0) { $errors['base_amount'] = 'Enter an amount greater than zero.'; }
		if (!$main) { $errors['main_account'] = 'Select the account.'; }
		if (!$settle) { $errors['settle_account'] = 'Select the account.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$type = $rate->tax_type;
		$tax_acc = $this->Account_model->get($rate->account_id);
		if (!$tax_acc || !(int) $tax_acc->is_active) { $this->fail('The tax account of this rate is inactive. Edit the tax rate first.', array('tax_rate' => 'Tax account inactive.')); }
		$ma = $this->Account_model->get($main); $sa = $this->Account_model->get($settle);
		if (!$ma || !$sa) { $this->fail('Account not found.'); }
		$allowed = ($type === 'output' || $type === 'advance') ? array('income') : array('expense', 'asset');
		if (!in_array($ma->account_type, $allowed, true)) { $this->fail('Choose a ' . implode(' or ', $allowed) . ' account.', array('main_account' => 'Choose a ' . implode(' or ', $allowed) . ' account.')); }
		if ($main === $settle || $main === (int) $tax_acc->id || $settle === (int) $tax_acc->id) { $this->fail('The accounts must be different from each other and from the tax account.', array('settle_account' => 'Must be a different account.')); }

		$fy = $this->Fy_model->for_date($date);
		if (!$fy) { $this->fail('No fiscal year exists for ' . dmy($date) . '.', array('voucher_date' => 'No fiscal year for this date.')); }
		if ((int) $fy->is_closed) { $this->fail("Fiscal year {$fy->title} is closed.", array('voucher_date' => 'Fiscal year is closed.')); }

		$tax = round($base * (float) $rate->rate / 100, 2);
		$rows = array();
		if ($type === 'output')           { $rows[] = array($settle, $base + $tax, 0); $rows[] = array($main, 0, $base); if ($tax > 0) { $rows[] = array((int) $tax_acc->id, 0, $tax); } }
		elseif ($type === 'input')        { $rows[] = array($main, $base, 0); if ($tax > 0) { $rows[] = array((int) $tax_acc->id, $tax, 0); } $rows[] = array($settle, 0, $base + $tax); }
		elseif ($type === 'withholding')  { $rows[] = array($main, $base, 0); if ($tax > 0) { $rows[] = array((int) $tax_acc->id, 0, $tax); } $rows[] = array($settle, 0, round($base - $tax, 2)); }
		else                              { $rows[] = array($settle, round($base - $tax, 2), 0); if ($tax > 0) { $rows[] = array((int) $tax_acc->id, $tax, 0); } $rows[] = array($main, 0, $base); }

		$labels = tax_types();
		$narr = $labels[$type] . " {$rate->code} @ " . rtrim(rtrim(number_format((float) $rate->rate, 3, '.', ''), '0'), '.') . '% on ' . number_format($base, 2)
			. ($party !== '' ? " - $party" : '') . ($inv !== '' ? " (Inv $inv)" : '') . ($note !== '' ? ': ' . $note : '');
		$clean_in = array();
		foreach ($rows as $r) { $clean_in[] = array('account_id' => $r[0], 'debit' => $r[1], 'credit' => $r[2], 'description' => $narr); }
		list($lines, $total, $err) = $this->Voucher_model->clean_lines($clean_in);
		if ($err) { $this->fail($err); }

		$cash = $sa->cash_bank !== 'none';
		$vtype = $cash ? (($type === 'output' || $type === 'advance') ? 'RV' : 'PV') : 'JV';

		$this->db->trans_begin();
		$vid = $this->Voucher_model->create(array('type' => $vtype, 'date' => $date, 'reference' => $inv, 'narration' => $narr, 'user_id' => $this->user->id), $lines, $total, $fy);
		if ($vid) {
			$this->db->insert('tax_entries', array('tax_rate_id' => (int) $rate->id, 'tax_type' => $type, 'rate' => $rate->rate, 'voucher_id' => $vid, 'entry_date' => $date,
				'party_name' => $party !== '' ? $party : null, 'invoice_no' => $inv !== '' ? $inv : null, 'base_amount' => $base, 'tax_amount' => $tax,
				'created_by' => $this->user->id, 'created_at' => date('Y-m-d H:i:s')));
		}
		if (!$vid || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();

		$new = $this->Voucher_model->get($vid);
		$this->audit('tax_entries', 'create', $vid, "Tax entry {$new->voucher_no}: {$rate->code} tax " . number_format($tax, 2) . ' on ' . number_format($base, 2), null, $this->Voucher_model->snapshot($new));
		$this->ok($new->voucher_no . ' posted (tax ' . number_format($tax, 2) . ').');
	}

	public function void($id = 0)
	{
		$this->post_only();
		$this->require_perm('tax_entries', 'void');
		$e = $this->db->where('id', (int) $id)->get('tax_entries')->row();
		if (!$e) { $this->fail('Tax entry not found.'); }
		$this->do_void((int) $e->voucher_id, trim((string) $this->input->post('reason')), 'tax_entries');
	}

	public function export()
	{
		$this->require_perm('tax_entries', 'export');
		$rows = $this->query($this->filters());
		$this->audit('tax_entries', 'export', null, 'Exported ' . count($rows) . ' tax entries');
		$q = function ($v) { return '"' . str_replace('"', '""', (string) $v) . '"'; };
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$types = tax_types();
		$out = "Date,Voucher,Type,Tax code,Rate %,Party,Invoice,Base amount,Tax amount,Status\n";
		foreach ($rows as $r) {
			$out .= implode(',', array($r->entry_date, $r->voucher_no, $q($types[$r->tax_type]), $r->rate_code, (float) $r->rate, $q($r->party_name), $q($r->invoice_no), $n($r->base_amount), $n($r->tax_amount), $r->status)) . "\n";
		}
		$this->output->set_content_type('text/csv', 'utf-8')->set_header('Content-Disposition: attachment; filename="tax_entries.csv"')->set_output("\xEF\xBB\xBF" . $out);
	}
}
