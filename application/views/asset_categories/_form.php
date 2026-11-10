<?php $sel = function ($k) use ($c) { return $c ? (int) $c->$k : 0; };
$opts = function ($list, $v) { $o = '<option value=""></option>'; foreach ($list as $a) { $o .= '<option value="' . $a->id . '"' . ((int) $v === (int) $a->id ? ' selected' : '') . '>' . h($a->code . ' - ' . $a->name) . '</option>'; } return $o; }; ?>
<form method="post" action="<?= site_url('asset-categories/save') ?>" data-ajax>
	<?= csrf_field() ?>
	<input type="hidden" name="id" value="<?= $c ? (int) $c->id : 0 ?>">
	<div class="modal-header"><h5 class="modal-title"><?= $c ? 'Edit category' : 'Add category' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
	<div class="modal-body"><div class="row g-3">
		<div class="col-12"><label class="form-label">Category name *</label><input type="text" name="name" class="form-control" maxlength="100" value="<?= $c ? h($c->name) : '' ?>" autofocus></div>
		<div class="col-md-6"><label class="form-label">Default method *</label>
			<select name="dep_method" class="form-select"><option value="sl" <?= $c && $c->dep_method === 'sl' ? 'selected' : '' ?>>Straight line</option><option value="wdv" <?= $c && $c->dep_method === 'wdv' ? 'selected' : '' ?>>Reducing balance</option></select></div>
		<div class="col-md-6"><label class="form-label">Default rate % per year *</label><input type="text" inputmode="decimal" name="dep_rate" class="form-control num" value="<?= $c ? h((float) $c->dep_rate) : '' ?>" placeholder="10"></div>
		<div class="col-12"><label class="form-label">Asset (cost) account *</label><select name="asset_account_id" class="form-select select2" data-placeholder="Select" <?= $used ? 'disabled' : '' ?>><?= $opts($assets, $sel('asset_account_id')) ?></select></div>
		<div class="col-12"><label class="form-label">Accumulated depreciation account *</label><select name="accum_account_id" class="form-select select2" data-placeholder="Select" <?= $used ? 'disabled' : '' ?>><?= $opts($assets, $sel('accum_account_id')) ?></select></div>
		<div class="col-12"><label class="form-label">Depreciation expense account *</label><select name="expense_account_id" class="form-select select2" data-placeholder="Select" <?= $used ? 'disabled' : '' ?>><?= $opts($expenses, $sel('expense_account_id')) ?></select></div>
		<?php if ($used): ?><div class="col-12 form-text">The three accounts are locked because assets already use this category.</div><?php endif; ?>
	</div></div>
	<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save</button></div>
</form>
