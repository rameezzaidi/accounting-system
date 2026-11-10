<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Bank reconciliation: tick the bank statement items against the books until the difference is zero */
class Bankrecon extends MY_Controller
{
	const PER_PAGE = 15;

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Bank_model', 'Report_model', 'Account_model'));
	}

	public function index()
	{
		$this->require_perm('bank_recon', 'view');
		$this->render('bank_recon/index', array('page_title' => 'Bank Reconciliation', 'accounts' => $this->Bank_model->cash_bank_accounts(true)));
	}

	public function grid()
	{
		$this->require_perm('bank_recon', 'view');
		$page = max(1, (int) $this->input->get('page'));
		$acc = (int) $this->input->get('account'); $st = (string) $this->input->get('status');
		$apply = function ($db) use ($acc, $st) {
			if ($acc) { $db->where('r.account_id', $acc); }
			if ($st === 'active' || $st === 'undone') { $db->where('r.status', $st); }
		};
		$apply($this->db);
		$total = $this->db->from('bank_reconciliations r')->count_all_results();
		$apply($this->db);
		$rows = $this->db->select('r.*, a.code, a.name, u.full_name AS by_name')->from('bank_reconciliations r')->join('accounts a', 'a.id = r.account_id')
			->join('users u', 'u.id = r.created_by', 'left')->order_by('r.statement_date', 'DESC')->order_by('r.id', 'DESC')
			->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->get()->result();
		foreach ($rows as $r) {
			$last = $r->status === 'active' ? $this->Bank_model->last_recon($r->account_id) : null;
			$r->can_undo = $last && (int) $last->id === (int) $r->id;
		}
		$this->partial('bank_recon/_grid', array('rows' => $rows, 'total' => $total, 'page' => $page, 'per' => self::PER_PAGE));
	}

	public function create()
	{
		$this->require_perm('bank_recon', 'add');
		$this->render('bank_recon/work', array('page_title' => 'New bank reconciliation', 'accounts' => $this->Bank_model->cash_bank_accounts(true),
			'sel' => (int) $this->input->get('account')));
	}

	/** validate account + date; returns array(acc, fy, date, last) or an error string */
	private function context($account_id, $date)
	{
		$acc = $this->Account_model->get((int) $account_id);
		if (!$acc || $acc->cash_bank !== 'bank') { return 'Select a bank account.'; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) { return 'Select the statement date.'; }
		$fy = $this->Fy_model->for_date($date);
		if (!$fy) { return 'No fiscal year exists for ' . dmy($date) . '.'; }
		$last = $this->Bank_model->last_recon($acc->id);
		if ($last && $date <= $last->statement_date) { return 'The statement date must be after the last reconciliation (' . dmy($last->statement_date) . ').'; }
		return array('acc' => $acc, 'fy' => $fy, 'date' => $date, 'last' => $last);
	}

	public function lines()
	{
		$this->require_perm('bank_recon', 'add');
		$c = $this->context((int) $this->input->get('account'), (string) $this->input->get('date'));
		if (!is_array($c)) { $this->partial('ledger/_message', array('message' => $c)); }
		$this->partial('bank_recon/_lines', array(
			'rows' => $this->Bank_model->open_lines($c['acc']->id, $c['fy'], $c['date']),
			'book' => $this->Bank_model->book_balance($c['acc']->id, $c['fy'], $c['date']), 'c' => $c,
		));
	}

	public function save()
	{
		$this->post_only();
		$this->require_perm('bank_recon', 'add');
		$stmt = round((float) str_replace(',', '', (string) $this->input->post('statement_balance')), 2);
		if (trim((string) $this->input->post('statement_balance')) === '') { $this->fail('Enter the closing balance shown on the bank statement.', array('statement_balance' => 'Required.')); }
		$c = $this->context((int) $this->input->post('account_id'), (string) $this->input->post('statement_date'));
		if (!is_array($c)) { $this->fail($c); }

		$ticked = array_map('intval', (array) $this->input->post('line'));
		if (!$ticked) { $this->fail('Tick the items that appear on the bank statement.'); }

		$this->db->trans_begin();
		$this->db->query('SELECT id FROM accounts WHERE id = ? FOR UPDATE', array((int) $c['acc']->id));    // one reconciliation at a time per account
		$rows = $this->Bank_model->open_lines($c['acc']->id, $c['fy'], $c['date']);
		$map = array(); foreach ($rows as $r) { $map[(int) $r->id] = $r; }
		foreach ($ticked as $id) { if (!isset($map[$id])) { $this->db->trans_rollback(); $this->fail('Some ticked items are no longer available. Please load the list again.'); } }
		$book = $this->Bank_model->book_balance($c['acc']->id, $c['fy'], $c['date']);
		$out = 0;
		foreach ($map as $id => $r) { if (!in_array($id, $ticked, true)) { $out += (float) $r->debit - (float) $r->credit; } }
		$cleared = round($book - $out, 2);
		$diff = round($stmt - $cleared, 2);
		if (abs($diff) > 0.004) { $this->db->trans_rollback(); $this->fail('The difference is ' . number_format($diff, 2) . '. Reconcile until it is zero.'); }

		$this->db->insert('bank_reconciliations', array('account_id' => (int) $c['acc']->id, 'statement_date' => $c['date'], 'statement_balance' => $stmt,
			'book_balance' => $book, 'item_count' => count($ticked), 'notes' => mb_substr(trim((string) $this->input->post('notes')), 0, 255) ?: null,
			'status' => 'active', 'created_by' => $this->user->id, 'created_at' => date('Y-m-d H:i:s')));
		$rid = $this->db->insert_id();
		$batch = array(); foreach (array_unique($ticked) as $id) { $batch[] = array('recon_id' => $rid, 'line_id' => $id); }
		$this->db->insert_batch('bank_recon_items', $batch);
		if ($this->db->trans_status() === false) { $this->db->trans_rollback(); $this->fail('Could not save. Please try again.'); }
		$this->db->trans_commit();

		$this->audit('bank_recon', 'create', $rid, "Reconciled {$c['acc']->code} {$c['acc']->name} up to " . dmy($c['date']) . ': ' . count($batch) . ' items, statement balance ' . number_format($stmt, 2),
			null, array('account' => $c['acc']->code, 'statement_date' => $c['date'], 'statement_balance' => $stmt, 'book_balance' => $book, 'items' => count($batch)));
		$this->ok('Reconciliation saved.', array('redirect' => site_url('bank-reconciliation/view/' . $rid)));
	}

	public function view($id = 0)
	{
		$this->require_perm('bank_recon', 'view');
		$r = $this->db->select('r.*, a.code, a.name, u.full_name AS by_name, uu.full_name AS undone_name')->from('bank_reconciliations r')->join('accounts a', 'a.id = r.account_id')
			->join('users u', 'u.id = r.created_by', 'left')->join('users uu', 'uu.id = r.undone_by', 'left')->where('r.id', (int) $id)->get()->row();
		if (!$r) { show_404(); }
		$items = $this->db->query("SELECT vl.debit, vl.credit, vl.description, v.id AS voucher_id, v.voucher_no, v.voucher_date, v.reference
			FROM bank_recon_items i JOIN voucher_lines vl ON vl.id = i.line_id JOIN vouchers v ON v.id = vl.voucher_id
			WHERE i.recon_id = ? ORDER BY v.voucher_date, v.id", array($r->id))->result();
		$last = $r->status === 'active' ? $this->Bank_model->last_recon($r->account_id) : null;
		$this->render('bank_recon/view', array('page_title' => 'Reconciliation #' . $r->id, 'r' => $r, 'items' => $items, 'can_undo' => $last && (int) $last->id === (int) $r->id));
	}

	public function undo($id = 0)
	{
		$this->post_only();
		$this->require_perm('bank_recon', 'void');
		$reason = trim((string) $this->input->post('reason'));
		if ($reason === '') { $this->fail('Please enter a reason.'); }
		$r = $this->db->where('id', (int) $id)->get('bank_reconciliations')->row();
		if (!$r || $r->status !== 'active') { $this->fail('Reconciliation not found or already undone.'); }
		$last = $this->Bank_model->last_recon($r->account_id);
		if (!$last || (int) $last->id !== (int) $r->id) { $this->fail('Only the latest reconciliation of an account can be undone. Undo the later one first.'); }
		$this->db->trans_start();
		$this->db->where('recon_id', $r->id)->delete('bank_recon_items');
		$this->db->where('id', $r->id)->update('bank_reconciliations', array('status' => 'undone', 'undone_by' => $this->user->id, 'undone_at' => date('Y-m-d H:i:s'), 'undo_reason' => mb_substr($reason, 0, 255)));
		$this->db->trans_complete();
		if (!$this->db->trans_status()) { $this->fail('Could not undo. Please try again.'); }
		$this->audit('bank_recon', 'void', $r->id, "Undid reconciliation #{$r->id} (statement " . dmy($r->statement_date) . "). Reason: $reason",
			array('status' => 'active', 'items' => (int) $r->item_count), array('status' => 'undone', 'reason' => $reason));
		$this->ok('Reconciliation undone. Its items are open again.');
	}
}
