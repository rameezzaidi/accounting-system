<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'core/Reversal_base.php';

/** Stipend Reverse Payment: a stipend / payment that is returned or cancelled, linked to the original payment voucher */
class Stipend extends Reversal_base
{
	protected $kind = 'stipend';
	protected $module = 'stipend_reversal';
	protected $route = 'stipend-reverse-payment';
	protected $labels = array(
		'title' => 'Stipend Reverse Payment', 'verb' => 'Stipend reversal', 'orig' => 'Payment voucher', 'party' => 'Beneficiary',
		'intro' => 'Record a stipend (or any payment) that came back or was cancelled. The amount returns to cash / bank and the expense is reduced. It is linked to the original payment (PV) and can never be more than what was paid.',
		'cash' => 'Received back in (cash / bank) *', 'btn' => 'New reverse payment',
	);
}
