<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Placeholder screen for modules planned in later phases */
class Coming extends MY_Controller
{
	private $titles = array(
		'financial-closing' => array('Financial Closing', 'Year-end closing entries, profit transfer to retained earnings and carry-forward of opening balances.', 2),
		'refund'            => array('Refund', 'Refund entries linked to original vouchers with approval and audit trail.', 2),
		'soe'               => array('SOE', 'Statement of Expenditure.', 2),
		'stipend-reverse-payment' => array('Stipend Reverse Payment', 'Reverse / re-issue stipend payments with proper accounting entries.', 2),
		'banking'           => array('Banking', 'Bank accounts, cheques, deposits and bank reconciliation.', 2),
		'tax'               => array('Tax', 'Tax rates, withholding / sales tax postings and tax returns.', 2),
		'budget'            => array('Budget', 'Annual budgets per account / department with budget vs actual.', 3),
		'asset'             => array('Asset Management', 'Fixed asset register, depreciation and disposal.', 3),
		'payroll'           => array('Payroll', 'Salary sheets, deductions and automatic payroll vouchers.', 3),
		'pmu'               => array('PMU', 'Project Management Unit accounting.', 3),
		'budget-report'     => array('Budget Report', 'Budget vs actual and variance reports.', 3),
		'vendor-report'     => array('Vendor Report', 'Supplier ledgers, aging and payables.', 3),
		'banking-report'    => array('Banking Report', 'Bank book and reconciliation reports.', 3),
		'tax-report'        => array('Tax Report', 'Tax deducted / collected summaries.', 3),
		'payroll-report'    => array('Payroll Report', 'Salary, deduction and payslip reports.', 3),
		'graphs'            => array('Graphs', 'Visual dashboards for income, expense, cash flow and budgets.', 3),
		'assets-master'     => array('Assets', 'Asset master data.', 3),
		'suppliers'         => array('Suppliers', 'Supplier master data linked with vendor reports.', 3),
	);

	public function index($slug = '')
	{
		$t = isset($this->titles[$slug]) ? $this->titles[$slug] : array(ucwords(str_replace('-', ' ', $slug)), 'This module is planned for a later phase.', 2);
		$this->render('coming/index', array('page_title' => $t[0], 'info' => $t));
	}
}
