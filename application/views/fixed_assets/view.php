<div class="card mb-3">
	<div class="card-header">
		<span><?= h($a->asset_no) ?> - <?= h($a->name) ?> <?= $a->status === 'active' ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Disposed</span>' ?></span>
		<div class="ms-auto no-print">
			<a class="btn btn-light btn-sm" href="<?= site_url('fixed-assets') ?>">Back</a>
			<?php if ($a->status === 'active' && can('fixed_assets', 'edit')): ?>
				<button class="btn btn-light btn-sm" data-size="lg" data-modal="<?= site_url('fixed-assets/form/' . $a->id) ?>"><?= icon('edit', 15) ?> Edit</button>
				<?php if (!count($deps)): ?><button class="btn btn-light btn-sm text-danger" data-reason="1" data-ok="Remove asset" data-confirm="Remove <?= h($a->asset_no) ?>?<?= $a->purchase_voucher_id ? ' Its purchase voucher will be voided.' : '' ?>" data-action="<?= site_url('fixed-assets/remove/' . $a->id) ?>"><?= icon('trash', 15) ?> Remove</button><?php endif; ?>
			<?php endif; ?>
			<?php if ($a->status === 'active' && can('fixed_assets', 'dispose')): ?><button class="btn btn-danger btn-sm" data-size="lg" data-modal="<?= site_url('fixed-assets/disposeform/' . $a->id) ?>">Dispose</button><?php endif; ?>
			<?php if ($a->status === 'disposed' && can('fixed_assets', 'dispose')): ?><button class="btn btn-light btn-sm" data-reason="1" data-reload="1" data-ok="Reverse" data-confirm="Reverse the disposal of <?= h($a->asset_no) ?>? The disposal voucher will be voided." data-action="<?= site_url('fixed-assets/undispose/' . $a->id) ?>">Reverse disposal</button><?php endif; ?>
		</div>
	</div>
	<div class="card-body"><div class="row g-3">
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Cost</small><b><?= fmt($a->cost) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Accumulated depreciation</small><b><?= fmt($accum) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Book value</small><b><?= fmt((float) $a->cost - $accum) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Salvage value</small><b><?= fmt($a->salvage_value) ?></b></div></div>
		<div class="col-md-3"><div class="text-muted small">Category</div><b><?= h($a->cat_name) ?></b></div>
		<div class="col-md-3"><div class="text-muted small">Purchased</div><b><?= dmy($a->purchase_date) ?></b><?php if ($a->pv_no): ?> <a class="small" href="<?= site_url('journal-vouchers/view/' . $a->purchase_voucher_id) ?>"><?= h($a->pv_no) ?></a><?php endif; ?></div>
		<div class="col-md-3"><div class="text-muted small">Depreciation</div><b><?= $a->dep_method === 'sl' ? 'Straight line' : 'Reducing balance' ?>, <?= rtrim(rtrim(number_format((float) $a->dep_rate, 2), '0'), '.') ?>%</b></div>
		<div class="col-md-3"><div class="text-muted small">Serial / location</div><b><?= h($a->serial_no) ?: '-' ?> / <?= h($a->location) ?: '-' ?></b></div>
		<?php if ($a->status === 'disposed'): ?><div class="col-12"><div class="info-box">Disposed on <b><?= dmy($a->disposed_date) ?></b> for <b><?= fmt($a->disposal_amount) ?></b> &middot; voucher <a href="<?= site_url('journal-vouchers/view/' . $a->disposal_voucher_id) ?>"><?= h($a->dv_no) ?></a></div></div><?php endif; ?>
		<?php if ($a->notes): ?><div class="col-12 small text-muted"><?= h($a->notes) ?></div><?php endif; ?>
	</div></div>
</div>
<div class="card">
	<div class="card-header"><span>Depreciation history</span></div>
	<div class="table-responsive"><table class="table">
		<thead><tr><th>Period</th><th>Months</th><th>Voucher</th><th class="num">Depreciation</th></tr></thead>
		<tbody>
		<?php foreach ($deps as $d): ?><tr><td><?= h($d->period) ?></td><td><?= (int) $d->months ?></td><td><a href="<?= site_url('journal-vouchers/view/' . $d->voucher_id) ?>"><?= h($d->voucher_no) ?></a></td><td class="num"><?= fmt($d->amount) ?></td></tr>
		<?php endforeach; if (!$deps): ?><tr><td colspan="4" class="empty-row">No depreciation booked yet.</td></tr><?php endif; ?>
		</tbody>
	</table></div>
</div>
