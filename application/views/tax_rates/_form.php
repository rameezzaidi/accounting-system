<form method="post" action="<?= site_url('tax-rates/save') ?>" data-ajax>
	<?= csrf_field() ?>
	<input type="hidden" name="id" value="<?= $t ? (int) $t->id : 0 ?>">
	<div class="modal-header">
		<h5 class="modal-title"><?= $t ? 'Edit tax rate' : 'Add tax rate' ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-md-5"><label class="form-label">Code *</label><input type="text" name="code" class="form-control text-uppercase" maxlength="20" value="<?= $t ? h($t->code) : '' ?>" placeholder="GST-OUT" autofocus></div>
			<div class="col-md-7"><label class="form-label">Rate % *</label><input type="text" inputmode="decimal" name="rate" class="form-control num" value="<?= $t ? h(rtrim(rtrim(number_format((float) $t->rate, 3, '.', ''), '0'), '.')) : '' ?>" placeholder="18"></div>
			<div class="col-12"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" maxlength="120" value="<?= $t ? h($t->name) : '' ?>"></div>
			<div class="col-12">
				<label class="form-label">Type *</label>
				<select name="tax_type" class="form-select" <?= $used ? 'disabled' : '' ?>>
					<option value="">Select</option>
					<?php foreach (tax_types() as $k => $lab): ?><option value="<?= $k ?>" <?= $t && $t->tax_type === $k ? 'selected' : '' ?>><?= h($lab) ?></option><?php endforeach; ?>
				</select>
				<?php if ($used): ?><input type="hidden" name="tax_type" value="<?= h($t->tax_type) ?>"><div class="form-text">Type and account are locked because this rate has <?= (int) $used ?> entries.</div><?php endif; ?>
			</div>
			<div class="col-12">
				<label class="form-label">Tax amount is posted to *</label>
				<select name="account_id" class="form-select select2" data-placeholder="Select account" <?= $used ? 'disabled' : '' ?>>
					<option value=""></option>
					<?php foreach ($accounts as $a): ?><option value="<?= $a->id ?>" <?= $t && (int) $t->account_id === (int) $a->id ? 'selected' : '' ?>><?= h($a->code . ' - ' . $a->name) ?> (<?= $a->account_type ?>)</option><?php endforeach; ?>
				</select>
				<?php if ($used): ?><input type="hidden" name="account_id" value="<?= (int) $t->account_id ?>"><?php endif; ?>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save</button>
	</div>
</form>
