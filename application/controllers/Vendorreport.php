<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Vendor report: per supplier opening / billed / paid / closing for a period, ageing of unpaid bills, and a check against the ledger */
class Vendorreport extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Supplier_model', 'Report_model'));
	}

	private function compute($p, $supplier_id)
	{
		$live = "(SELECT t.* FROM supplier_txns t JOIN vouchers v ON v.id = t.voucher_id AND v.status = 'posted')";
		$sql = "SELECT s.id, s.code, s.name,
				COALESCE(SUM(CASE WHEN t.txn_date < ? THEN (CASE WHEN t.kind='bill' THEN t.amount ELSE -t.amount END) END),0) opening,
				COALESCE(SUM(CASE WHEN t.txn_date BETWEEN ? AND ? AND t.kind='bill' THEN t.amount END),0) billed,
				COALESCE(SUM(CASE WHEN t.txn_date BETWEEN ? AND ? AND t.kind='payment' THEN t.amount END),0) paid
			FROM suppliers s LEFT JOIN $live t ON t.supplier_id = s.id" . ($supplier_id ? ' WHERE s.id = ' . (int) $supplier_id : '') . ' GROUP BY s.id ORDER BY s.name';
		$rows = $this->db->query($sql, array($p['from'], $p['from'], $p['to'], $p['from'], $p['to']))->result();
		$tot = array('opening' => 0, 'billed' => 0, 'paid' => 0, 'closing' => 0);
		$out = array();
		foreach ($rows as $r) {
			$r->closing = round((float) $r->opening + (float) $r->billed - (float) $r->paid, 2);
			if (abs((float) $r->opening) < 0.005 && (float) $r->billed == 0 && (float) $r->paid == 0 && abs($r->closing) < 0.005) { continue; }
			foreach ($tot as $k => $_) { $tot[$k] += (float) $r->$k; }
			$out[] = $r;
		}
		$age = array('Current / not due' => 0, '1-30 days' => 0, '31-60 days' => 0, '61-90 days' => 0, 'Over 90 days' => 0);
		$bills = $this->Supplier_model->open_bills($supplier_id, $p['to']);
		foreach ($bills as $b) {
			$ref = $b->due_date ? $b->due_date : $b->txn_date;
			$d = (int) floor((strtotime($p['to']) - strtotime($ref)) / 86400);
			$k = $d <= 0 ? 'Current / not due' : ($d <= 30 ? '1-30 days' : ($d <= 60 ? '31-60 days' : ($d <= 90 ? '61-90 days' : 'Over 90 days')));
			$b->days = max(0, $d); $b->bucket = $k;
			$age[$k] += (float) $b->outstanding;
		}
		// ledger control check: balance of the payable accounts used by suppliers
		$ids = array();
		foreach ($this->db->select('DISTINCT payable_account_id', false)->get('suppliers')->result() as $x) { $ids[(int) $x->payable_account_id] = true; }
		$gl = 0.0;
		foreach ($this->Report_model->balances($p['fy']->id, $p['fy']->start_date, $p['to'], true) as $b) {
			if (isset($ids[(int) $b->id])) { $gl += -((float) $b->ob + (float) $b->prior + (float) $b->dr - (float) $b->cr); }
		}
		$sub = 0.0;
		foreach ($this->Supplier_model->listing() as $x) { $sub += $this->Supplier_model->balance($x->id, $p['to']); }
		return array('rows' => $out, 'tot' => $tot, 'age' => $age, 'bills' => $bills, 'gl' => round($gl, 2), 'sub' => round($sub, 2));
	}

	public function index()
	{
		$this->require_perm('vendor_report', 'view');
		$fy = $this->active_fy;
		$this->render('vendor_report/index', array('page_title' => 'Vendor Report', 'fys' => $this->Fy_model->all(), 'fy' => $fy,
			'to' => $fy ? min(date('Y-m-d'), $fy->end_date) : '', 'suppliers' => $this->db->order_by('name')->get('suppliers')->result(), 'can_export' => $this->can('vendor_report', 'export')));
	}

	public function data()
	{
		$this->require_perm('vendor_report', 'view');
		$p = $this->period_params();
		if (!is_array($p)) { $this->partial('ledger/_message', array('message' => $p)); }
		$this->partial('vendor_report/_data', array('p' => $p, 'r' => $this->compute($p, (int) $this->input->get('supplier'))));
	}

	public function export()
	{
		$this->require_perm('vendor_report', 'export');
		$p = $this->period_params();
		if (!is_array($p)) { show_error($p, 400); }
		$r = $this->compute($p, (int) $this->input->get('supplier'));
		$this->audit('vendor_report', 'export', null, "Exported vendor report ({$p['from']} to {$p['to']})");
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$rows = array(array(setting('company_name')), array('Vendor Report - ' . $p['fy']->title), array('Period', $p['from'] . ' to ' . $p['to']), array(),
			array('Code', 'Supplier', 'Opening', 'Billed', 'Paid', 'Closing'));
		foreach ($r['rows'] as $x) { $rows[] = array($x->code, $x->name, $n($x->opening), $n($x->billed), $n($x->paid), $n($x->closing)); }
		$rows[] = array('', 'Total', $n($r['tot']['opening']), $n($r['tot']['billed']), $n($r['tot']['paid']), $n($r['tot']['closing']));
		$rows[] = array(); $rows[] = array('Ageing of unpaid bills as at ' . $p['to']);
		foreach ($r['age'] as $k => $v) { $rows[] = array($k, $n($v)); }
		$rows[] = array(); $rows[] = array('Supplier', 'Voucher', 'Invoice', 'Bill date', 'Due date', 'Bill amount', 'Outstanding', 'Days overdue');
		foreach ($r['bills'] as $b) { $rows[] = array($b->supplier_name, $b->voucher_no, $b->ref_no, $b->txn_date, $b->due_date, $n($b->amount), $n($b->outstanding), $b->days); }
		csv_out('vendor_report_' . $p['from'] . '_' . $p['to'] . '.csv', $rows);
	}
}
