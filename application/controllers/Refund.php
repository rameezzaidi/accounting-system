<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'core/Reversal_base.php';

/** Refund: money handed back to a payer against an earlier receipt voucher */
class Refund extends Reversal_base
{
	protected $kind = 'refund';
	protected $module = 'refund';
	protected $route = 'refund';
	protected $labels = array(
		'title' => 'Refund', 'verb' => 'Refund', 'orig' => 'Receipt voucher', 'party' => 'Refunded to',
		'intro' => 'Pay back money you received earlier. The refund is linked to the original receipt (RV) and can never be more than what was received.',
		'cash' => 'Paid from (cash / bank) *', 'btn' => 'New refund',
	);
}
