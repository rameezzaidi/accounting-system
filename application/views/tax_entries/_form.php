<form method="post" action="<?= site_url('tax-entries/save') ?>" data-ajax id="teForm">
	<?= csrf_field() ?>
	<div class="modal-header">
		<h5 class="modal-title">New tax entry</h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-md-8">
				<label class="form-label">Tax rate *</label>
				<select name="tax_rate" id="teRate" class="form-select select2" data-placeholder="Select tax rate">
					<option value=""></option>
					<?php foreach ($rates as $t): ?><option value="<?= $t->id ?>" data-type="<?= h($t->tax_type) ?>" data-rate="<?= h((float) $t->rate) ?>"><?= h($t->code . ' - ' . $t->name) ?> (<?= rtrim(rtrim(number_format((float) $t->rate, 3), '0'), '.') ?>%)</option><?php endforeach; ?>
				</select>
				<div class="form-text" id="teTypeHint">Choose a rate to see how this entry will post.</div>
			</div>
			<div class="col-md-4"><label class="form-label">Date *</label><input type="date" name="voucher_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
			<div class="col-md-6"><label class="form-label">Party (customer / supplier)</label><input type="text" name="party_name" class="form-control" maxlength="150"></div>
			<div class="col-md-6"><label class="form-label">Invoice / reference no</label><input type="text" name="invoice_no" class="form-control" maxlength="60"></div>
			<div class="col-md-4"><label class="form-label" id="teBaseLbl">Amount *</label><input type="text" inputmode="decimal" name="base_amount" id="teBase" class="form-control num" placeholder="0.00" autocomplete="off"></div>
			<div class="col-md-8"><label class="form-label" id="teMainLbl">Account *</label>
				<select name="main_account" id="teMain" class="form-select select2" data-placeholder="Select account"><option value=""></option></select></div>
			<div class="col-12"><label class="form-label" id="teSetLbl">Settlement account *</label>
				<select name="settle_account" id="teSettle" class="form-select select2" data-placeholder="Select cash / bank, receivable or payable account"><option value=""></option></select></div>
			<div class="col-12"><label class="form-label">Narration</label><input type="text" name="narration" class="form-control" placeholder="Optional details"></div>
			<div class="col-12"><div class="info-box" id="tePreview">Tax: <b id="teTax">0.00</b> &nbsp;|&nbsp; <span id="teSetText">Settlement</span>: <b id="teSet">0.00</b></div></div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Post entry</button>
	</div>
</form>
<script>
(function ($) {
	var ACC = <?= json_encode($accounts) ?>;
	var CFG = {
		output:      { base: 'Amount before tax *',  main: 'Income account *',               set: 'Received in / receivable from (amount + tax) *', types: ['income'],          hint: 'Sales tax is added on top. Dr settlement, Cr income, Cr tax payable.' },
		input:       { base: 'Amount before tax *',  main: 'Expense / asset account *',      set: 'Paid from / payable to (amount + tax) *',        types: ['expense','asset'], hint: 'Sales tax is added on top. Dr expense, Dr input tax, Cr settlement.' },
		withholding: { base: 'Gross amount *',       main: 'Expense / asset account *',      set: 'Paid from / payable to (gross - tax) *',         types: ['expense','asset'], hint: 'Tax is deducted from the gross amount. Dr expense, Cr tax payable, Cr settlement.' },
		advance:     { base: 'Gross amount *',       main: 'Income account *',               set: 'Received in / receivable from (gross - tax) *',  types: ['income'],          hint: 'Tax is deducted from what you receive. Dr settlement, Dr advance tax, Cr income.' }
	};
	var $rate = $('#teRate'), $main = $('#teMain'), $set = $('#teSettle');
	function fill($s, list) {
		$s.empty().append('<option value=""></option>');
		$.each(list, function (i, a) { $s.append($('<option>').val(a.id).text(a.text)); });
	}
	function cur() { var o = $rate.find('option:selected'); return o.val() ? { type: o.data('type'), rate: parseFloat(o.data('rate')) || 0 } : null; }
	function calc() {
		var c = cur(), base = App.num($('#teBase').val());
		if (!c) { $('#teTax').text('0.00'); $('#teSet').text('0.00'); return; }
		var tax = Math.round(base * c.rate / 100 * 100) / 100;
		var set = (c.type === 'output' || c.type === 'input') ? base + tax : base - tax;
		$('#teTax').text(App.money(tax)); $('#teSet').text(App.money(set));
	}
	function onRate() {
		var c = cur();
		if (!c) { return; }
		var k = CFG[c.type];
		$('#teBaseLbl').text(k.base); $('#teMainLbl').text(k.main); $('#teSetLbl').text(k.set); $('#teTypeHint').text(k.hint);
		$('#teSetText').text(c.type === 'output' || c.type === 'input' ? 'Settlement (amount + tax)' : 'Settlement (gross - tax)');
		fill($main, ACC.filter(function (a) { return k.types.indexOf(a.type) !== -1; }));
		$main.val('').trigger('change');
		calc();
	}
	fill($set, ACC);
	$rate.on('change', onRate);
	$('#teBase').on('input', calc);
})(jQuery);
</script>
