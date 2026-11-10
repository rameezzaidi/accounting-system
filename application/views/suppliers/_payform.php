<form method="post" action="<?= site_url('suppliers/paysave') ?>" data-ajax id="spForm">
	<?= csrf_field() ?>
	<input type="hidden" name="supplier_id" value="<?= (int) $s->id ?>">
	<div class="modal-header">
		<h5 class="modal-title">Payment - <?= h($s->name) ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-md-4"><label class="form-label">Payment date *</label><input type="date" name="voucher_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
			<div class="col-md-4"><label class="form-label">Amount *</label><input type="text" inputmode="decimal" name="amount" id="spAmt" class="form-control num" placeholder="0.00" autocomplete="off"></div>
			<div class="col-md-4"><label class="form-label">Cheque / ref no</label><input type="text" name="ref_no" class="form-control" maxlength="60"></div>
			<div class="col-12"><label class="form-label">Paid from *</label>
				<select name="cash_account" class="form-select select2" data-placeholder="Cash / bank account"><option value=""></option>
				<?php foreach ($cash as $a): ?><option value="<?= $a->id ?>"><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?></select></div>
			<div class="col-12"><label class="form-label">Against bill</label>
				<select name="bill_id" id="spBill" class="form-select"><option value="">On account (not against a specific bill)</option>
				<?php foreach ($open as $b): ?><option value="<?= $b->id ?>" data-out="<?= h((float) $b->outstanding) ?>" <?= (int) $bill_id === (int) $b->id ? 'selected' : '' ?>><?= h($b->voucher_no . ($b->ref_no ? ' / ' . $b->ref_no : '') . ' - due ' . fmt($b->outstanding)) ?></option><?php endforeach; ?></select></div>
			<div class="col-12"><label class="form-label">Narration</label><input type="text" name="narration" class="form-control"></div>
			<?= project_select(0, 'col-12') ?>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-success"><?= icon('check', 16) ?> Post payment</button>
	</div>
</form>
<script>
(function ($) {
	function fillAmt() { var o = $('#spBill option:selected'); if (o.val() && !$('#spAmt').val()) { $('#spAmt').val(App.money(o.data('out')).replace(/,/g, '')); } }
	$('#spBill').on('change', function () { $('#spAmt').val(''); fillAmt(); });
	fillAmt();
})(jQuery);
</script>
