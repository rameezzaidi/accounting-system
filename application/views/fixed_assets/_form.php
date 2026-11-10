<form method="post" action="<?= site_url('fixed-assets/save') ?>" data-ajax id="faForm">
	<?= csrf_field() ?>
	<input type="hidden" name="id" value="<?= $a ? (int) $a->id : 0 ?>">
	<div class="modal-header"><h5 class="modal-title"><?= $a ? 'Edit asset ' . h($a->asset_no) : 'Add fixed asset' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
	<div class="modal-body"><div class="row g-3">
		<div class="col-md-8"><label class="form-label">Asset name *</label><input type="text" name="name" class="form-control" maxlength="150" value="<?= $a ? h($a->name) : '' ?>" autofocus></div>
		<div class="col-md-4"><label class="form-label">Serial no</label><input type="text" name="serial_no" class="form-control" maxlength="80" value="<?= $a ? h($a->serial_no) : '' ?>"></div>
		<div class="col-md-6"><label class="form-label">Location</label><input type="text" name="location" class="form-control" maxlength="100" value="<?= $a ? h($a->location) : '' ?>"></div>
		<div class="col-md-6"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" maxlength="255" value="<?= $a ? h($a->notes) : '' ?>"></div>
		<?php if ($locked): ?>
			<div class="col-12"><div class="info-box">Cost, date and depreciation settings are locked because this asset has a purchase voucher or depreciation. Only the details above can be changed.</div></div>
		<?php else: ?>
		<div class="col-md-6"><label class="form-label">Category *</label>
			<select name="category_id" id="faCat" class="form-select select2" data-placeholder="Select category"><option value=""></option>
			<?php foreach ($cats as $c): ?><option value="<?= $c->id ?>" data-method="<?= $c->dep_method ?>" data-rate="<?= h((float) $c->dep_rate) ?>" <?= $a && (int) $a->category_id === (int) $c->id ? 'selected' : '' ?>><?= h($c->name) ?></option><?php endforeach; ?></select></div>
		<div class="col-md-6"><label class="form-label">Purchase date *</label><input type="date" name="purchase_date" class="form-control" value="<?= $a ? h($a->purchase_date) : date('Y-m-d') ?>"></div>
		<div class="col-md-6"><label class="form-label">Cost *</label><input type="text" inputmode="decimal" name="cost" class="form-control num" value="<?= $a ? h((float) $a->cost) : '' ?>" placeholder="0.00"></div>
		<div class="col-md-6"><label class="form-label">Salvage (residual) value</label><input type="text" inputmode="decimal" name="salvage_value" class="form-control num" value="<?= $a ? h((float) $a->salvage_value) : '0' ?>"></div>
		<div class="col-md-6"><label class="form-label">Method *</label><select name="dep_method" id="faMethod" class="form-select"><option value="sl" <?= $a && $a->dep_method === 'sl' ? 'selected' : '' ?>>Straight line</option><option value="wdv" <?= $a && $a->dep_method === 'wdv' ? 'selected' : '' ?>>Reducing balance</option></select></div>
		<div class="col-md-6"><label class="form-label">Rate % per year *</label><input type="text" inputmode="decimal" name="dep_rate" id="faRate" class="form-control num" value="<?= $a ? h((float) $a->dep_rate) : '' ?>"></div>
		<?php if (!$a): ?>
		<div class="col-12"><label class="form-label">Paid from / payable to (posts the purchase voucher)</label>
			<select name="pay_account" class="form-select select2" data-placeholder="Leave empty if the purchase is already in the books"><option value=""></option>
			<?php foreach ($pay as $x): ?><option value="<?= $x->id ?>"><?= h($x->code . ' - ' . $x->name) ?></option><?php endforeach; ?></select>
			<div class="form-text">If chosen: Dr asset account, Cr this account. If empty, no voucher is posted (use for assets already recorded, e.g. in opening balances).</div></div>
		<?php endif; ?>
		<?php endif; ?>
	</div></div>
	<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save</button></div>
</form>
<script>
(function ($) {
	$('#faCat').on('change', function () {
		var o = $(this).find('option:selected');
		if (o.val() && !$('#faRate').val()) { $('#faMethod').val(o.data('method')); $('#faRate').val(o.data('rate')); }
	});
})(jQuery);
</script>
