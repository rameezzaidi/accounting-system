<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared screen logic for Refund and Stipend Reverse Payment.
 * Child classes only set $kind / $module / $route / $labels.
 */
abstract class Reversal_base extends MY_Controller
{
	const PER_PAGE = 15;
	protected $kind = 'refund';
	protected $module = 'refund';
	protected $route = 'refund';
	protected $labels = array();

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Account_model', 'Voucher_model', 'Reversal_model'));
	}

	private function filters()
	{
		return array('q' => trim((string) $this->input->get('q')), 'status' => (string) $this->input->get('status'),
			'from' => (string) $this->input->get('from'), 'to' => (string) $this->input->get('to'));
	}

	public function index()
	{
		$this->require_perm($this->module, 'view');
		$this->render('reversals/index', array('page_title' => $this->labels['title'], 'L' => $this->labels, 'route' => $this->route, 'module' => $this->module));
	}

	public function grid()
	{
		$this->require_perm($this->module, 'view');
		$f = $this->filters();
		$page = max(1, (int) $this->input->get('page'));
		$this->partial('reversals/_grid', array(
			'rows' => $this->Reversal_model->search($this->kind, $f, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
			'total' => $this->Reversal_model->count($this->kind, $f), 'page' => $page, 'per' => self::PER_PAGE,
			'L' => $this->labels, 'route' => $this->route, 'module' => $this->module,
		));
	}

	public function form()
	{
		$this->require_perm($this->module, 'add');
		$this->partial('reversals/_form', array('L' => $this->labels, 'route' => $this->route, 'cash_accounts' => $this->Account_model->posting(true, true)));
	}

	/** select2 search: original vouchers that can still be reversed */
	public function lookup()
	{
		$this->require_perm($this->module, 'add');
		$res = array();
		foreach ($this->Reversal_model->eligible($this->kind, trim((string) $this->input->get('q'))) as $o) {
			$res[] = array(
				'id' => (int) $o->id,
				'text' => $o->voucher_no . ' | ' . dmy($o->voucher_date) . ' | ' . $o->counter->name . ' | ' . number_format($o->total_amount, 2),
				'voucher_no' => $o->voucher_no, 'date' => dmy($o->voucher_date), 'raw_date' => $o->voucher_date,
				'counter' => $o->counter->code . ' ' . $o->counter->name, 'total' => number_format($o->total_amount, 2),
				'done' => number_format($o->done, 2), 'remaining' => $o->remaining, 'remaining_fmt' => number_format($o->remaining, 2),
				'cash_id' => (int) $o->cash->account_id, 'narration' => (string) $o->narration,
			);
		}
		$this->json(array('results' => $res));
	}

	public function save()
	{
		$this->post_only();
		$this->require_perm($this->module, 'add');
		$date = (string) $this->input->post('voucher_date');
		$cash_id = (int) $this->input->post('cash_account');
		$amount = round((float) str_replace(',', '', (string) $this->input->post('amount')), 2);
		$party = mb_substr(trim((string) $this->input->post('party_name')), 0, 150);
		$reason = mb_substr(trim((string) $this->input->post('reason')), 0, 255);
		$orig_id = (int) $this->input->post('original_voucher');

		$errors = array();
		if (!$orig_id) { $errors['original_voucher'] = 'Select the original voucher.'; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) { $errors['voucher_date'] = 'Select a valid date.'; }
		if (!$cash_id) { $errors['cash_account'] = 'Select the cash / bank account.'; }
		if ($amount <= 0) { $errors['amount'] = 'Enter an amount greater than zero.'; }
		if ($reason === '') { $errors['reason'] = 'Please give a reason.'; }
		if ($errors) { $this->fail('Please fix the highlighted fields.', $errors); }

		$ca = $this->Account_model->get($cash_id);
		if (!$ca || $ca->cash_bank === 'none' || !(int) $ca->is_active || (int) $ca->is_group) { $this->fail('Select an active cash or bank account.', array('cash_account' => 'Not a cash / bank account.')); }
		$fy = $this->Fy_model->for_date($date);
		if (!$fy) { $this->fail('No fiscal year exists for ' . dmy($date) . '.', array('voucher_date' => 'No fiscal year for this date.')); }
		if ((int) $fy->is_closed) { $this->fail("Fiscal year {$fy->title} is closed.", array('voucher_date' => 'Fiscal year is closed.')); }

		$this->db->trans_begin();
		$this->db->query('SELECT id FROM vouchers WHERE id = ? FOR UPDATE', array($orig_id));      // serialise concurrent reversals of the same voucher
		$o = $this->Reversal_model->original($this->kind, $orig_id);
		if (!$o) { $this->db->trans_rollback(); $this->fail('This voucher cannot be used here (it must be a posted ' . ($this->kind === 'refund' ? 'receipt' : 'payment') . ' with a single account on each side).', array('original_voucher' => 'Not allowed.')); }
		if ($date < $o->voucher_date) { $this->db->trans_rollback(); $this->fail('The date cannot be before the original voucher (' . dmy($o->voucher_date) . ').', array('voucher_date' => 'Before the original voucher.')); }
		if ($amount > $o->remaining + 0.004) {
			$this->db->trans_rollback();
			$this->fail('Only ' . number_format($o->remaining, 2) . ' is left to ' . ($this->kind === 'refund' ? 'refund' : 'reverse') . ' on ' . $o->voucher_no . '.', array('amount' => 'Maximum ' . number_format($o->remaining, 2)));
		}

		$counter = (int) $o->counter->account_id;
		$narr = $this->labels['verb'] . ' of ' . $o->voucher_no . ($party !== '' ? " ($party)" : '') . ': ' . $reason;
		if ($this->kind === 'refund') {   // PV: Dr counter / Cr cash-bank
			$rows = array(array('account_id' => $counter, 'debit' => $amount, 'credit' => 0), array('account_id' => $cash_id, 'debit' => 0, 'credit' => $amount));
		} else {                            // RV: Dr cash-bank / Cr counter
			$rows = array(array('account_id' => $cash_id, 'debit' => $amount, 'credit' => 0), array('account_id' => $counter, 'debit' => 0, 'credit' => $amount));
		}
		foreach ($rows as &$r) { $r['description'] = $narr; }
		unset($r);
		list($lines, $total, $err) = $this->Voucher_model->clean_lines($rows);
		if ($err) { $this->db->trans_rollback(); $this->fail($err); }

		$vid = $this->Voucher_model->create(array('type' => Reversal_model::$cfg[$this->kind]['post_type'], 'date' => $date, 'reference' => $o->voucher_no, 'narration' => $narr, 'user_id' => $this->user->id), $lines, $total, $fy);
		if ($vid) {
			$this->db->insert('reversals', array('kind' => $this->kind, 'original_voucher_id' => $o->id, 'voucher_id' => $vid, 'party_name' => $party !== '' ? $party : null,
				'amount' => $amount, 'reason' => $reason, 'created_by' => $this->user->id, 'created_at' => date('Y-m-d H:i:s')));
		}
		if (!$vid || $this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();

		$new = $this->Voucher_model->get($vid);
		$this->audit($this->module, 'create', $vid, "{$this->labels['title']} {$new->voucher_no} of " . number_format($amount, 2) . " against {$o->voucher_no}. Reason: $reason", null, $this->Voucher_model->snapshot($new));
		$this->ok($new->voucher_no . ' posted.');
	}

	public function void($id = 0)
	{
		$this->post_only();
		$this->require_perm($this->module, 'void');
		$r = $this->db->where(array('id' => (int) $id, 'kind' => $this->kind))->get('reversals')->row();
		if (!$r) { $this->fail('Record not found.'); }
		$this->do_void((int) $r->voucher_id, trim((string) $this->input->post('reason')), $this->module);
	}
}
