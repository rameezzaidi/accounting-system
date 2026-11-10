<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** SOE - Statement of Expenditure: where the money was spent, by expense account */
class Soe extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Report_model'));
	}

	private function params()
	{
		$fy = $this->Fy_model->get((int) $this->input->get('fy')) ?: $this->active_fy;
		if (!$fy) { return 'No fiscal year found.'; }
		$from = (string) $this->input->get('from'); $to = (string) $this->input->get('to');
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = $fy->start_date; }
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to = min(date('Y-m-d'), $fy->end_date); if ($to < $fy->start_date) { $to = $fy->end_date; } }
		if ($from < $fy->start_date) { $from = $fy->start_date; }
		if ($to > $fy->end_date) { $to = $fy->end_date; }
		if ($from > $to) { return 'The "from" date cannot be after the "to" date.'; }
		$view = (string) $this->input->get('view') === 'monthly' ? 'monthly' : 'summary';
		return array('fy' => $fy, 'from' => $from, 'to' => $to, 'view' => $view);
	}

	public function index()
	{
		$this->require_perm('soe', 'view');
		$fy = $this->active_fy;
		$this->render('soe/index', array('page_title' => 'SOE - Statement of Expenditure', 'fys' => $this->Fy_model->all(), 'fy' => $fy,
			'to' => $fy ? min(date('Y-m-d'), $fy->end_date) : '', 'can_export' => $this->can('soe', 'export')));
	}

	public function data()
	{
		$this->require_perm('soe', 'view');
		$p = $this->params();
		if (!is_array($p)) { $this->partial('ledger/_message', array('message' => $p)); }
		$this->partial('soe/_data', array('p' => $p, 'r' => $this->Report_model->soe($p['fy'], $p['from'], $p['to'])));
	}

	public function export()
	{
		$this->require_perm('soe', 'export');
		$p = $this->params();
		if (!is_array($p)) { show_error($p, 400); }
		$r = $this->Report_model->soe($p['fy'], $p['from'], $p['to']);
		$this->audit('soe', 'export', null, "Exported SOE ({$p['from']} to {$p['to']}, {$p['view']})");
		$q = function ($v) { return '"' . str_replace('"', '""', (string) $v) . '"'; };
		$n = function ($v) { return number_format((float) $v, 2, '.', ''); };
		$out = $q(setting('company_name')) . "\n" . $q('Statement of Expenditure - ' . $p['fy']->title) . "\nPeriod,{$p['from']} to {$p['to']}\n\n";
		if ($p['view'] === 'monthly') {
			$out .= 'Code,Account,' . implode(',', array_map($q, $r['months'])) . ",Total\n";
			foreach ($r['groups'] as $g) {
				foreach ($g->rows as $x) {
					$cells = array(); foreach ($r['months'] as $ym => $lab) { $cells[] = $n(isset($x->months[$ym]) ? $x->months[$ym] : 0); }
					$out .= $x->code . ',' . $q($x->name) . ',' . implode(',', $cells) . ',' . $n($x->period) . "\n";
				}
			}
			$cells = array(); foreach ($r['months'] as $ym => $lab) { $cells[] = $n(isset($r['totals']['months'][$ym]) ? $r['totals']['months'][$ym] : 0); }
			$out .= ',Total,' . implode(',', $cells) . ',' . $n($r['totals']['period']) . "\n";
		} else {
			$out .= "Code,Account,This period,Cumulative (year to date),% of period\n";
			foreach ($r['groups'] as $g) {
				$out .= ',' . $q($g->code . ' ' . $g->name) . ",,,\n";
				foreach ($g->rows as $x) {
					$pc = $r['totals']['period'] != 0 ? $x->period / $r['totals']['period'] * 100 : 0;
					$out .= $x->code . ',' . $q($x->name) . ',' . $n($x->period) . ',' . $n($x->cum) . ',' . number_format($pc, 1) . "\n";
				}
				$out .= ',' . $q('Subtotal ' . $g->name) . ',' . $n($g->period) . ',' . $n($g->cum) . ",\n";
			}
			$out .= ',Total expenditure,' . $n($r['totals']['period']) . ',' . $n($r['totals']['cum']) . ",100.0\n";
		}
		$this->output->set_content_type('text/csv', 'utf-8')->set_header('Content-Disposition: attachment; filename="soe_' . $p['from'] . '_' . $p['to'] . '.csv"')->set_output("\xEF\xBB\xBF" . $out);
	}
}
