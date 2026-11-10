<div class="card">
	<div class="card-header">
		<span>Asset register</span>
		<div class="ms-auto">
			<a class="btn btn-light btn-sm" href="<?= site_url('fixed-assets/export') ?>"><?= icon('download', 15) ?> Export CSV</a>
			<?php if (can('fixed_assets', 'add')): ?><button class="btn btn-primary btn-sm" data-size="lg" data-modal="<?= site_url('fixed-assets/form') ?>"><?= icon('plus', 15) ?> Add asset</button><?php endif; ?>
		</div>
	</div>
	<form class="filter-bar" method="get" action="<?= site_url('fixed-assets') ?>">
		<div><label>Search</label><input type="search" name="q" class="form-control" value="<?= h($f['q']) ?>" placeholder="Name, no, serial"></div>
		<div><label>Category</label><select name="category" class="form-select"><option value="">All</option><?php foreach ($cats as $c): ?><option value="<?= $c->id ?>" <?= (int) $f['category'] === (int) $c->id ? 'selected' : '' ?>><?= h($c->name) ?></option><?php endforeach; ?></select></div>
		<div><label>Status</label><select name="status" class="form-select"><option value="">All</option><option value="active" <?= $f['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="disposed" <?= $f['status'] === 'disposed' ? 'selected' : '' ?>>Disposed</option></select></div>
		<div><button class="btn btn-primary">Filter</button> <a class="btn btn-light" href="<?= site_url('fixed-assets') ?>">Reset</a></div>
	</form>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Asset no</th><th>Name</th><th>Category</th><th>Purchased</th><th class="num">Cost</th><th class="num">Accumulated dep.</th><th class="num">Book value</th><th>Status</th><th class="text-end" style="width:90px"></th></tr></thead>
		<tbody>
		<?php $tc = 0; $ta = 0; foreach ($rows as $r): if ($r->status === 'active') { $tc += (float) $r->cost; $ta += (float) $r->accum; } ?>
			<tr class="<?= $r->status === 'disposed' ? 'row-void' : '' ?>">
				<td><a href="<?= site_url('fixed-assets/view/' . $r->id) ?>"><b><?= h($r->asset_no) ?></b></a></td><td><?= h($r->name) ?><?php if ($r->location): ?><div class="small text-muted"><?= h($r->location) ?></div><?php endif; ?></td>
				<td><?= h($r->cat_name) ?></td><td><?= dmy($r->purchase_date) ?></td>
				<td class="num"><?= fmt($r->cost) ?></td><td class="num"><?= fmt($r->accum) ?></td><td class="num"><b><?= fmt((float) $r->cost - (float) $r->accum) ?></b></td>
				<td><?= $r->status === 'active' ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Disposed</span>' ?></td>
				<td class="text-end"><a class="btn btn-icon" title="Open" href="<?= site_url('fixed-assets/view/' . $r->id) ?>"><?= icon('eye', 15) ?></a>
					<?php if ($r->status === 'active' && can('fixed_assets', 'edit')): ?><button class="btn btn-icon" title="Edit" data-size="lg" data-modal="<?= site_url('fixed-assets/form/' . $r->id) ?>"><?= icon('edit', 15) ?></button><?php endif; ?></td>
			</tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row">No assets found.</td></tr><?php endif; ?>
		</tbody>
		<?php if ($rows): ?><tfoot><tr><th colspan="4" class="text-end">Active assets</th><th class="num"><?= fmt($tc) ?></th><th class="num"><?= fmt($ta) ?></th><th class="num"><?= fmt($tc - $ta) ?></th><th colspan="2"></th></tr></tfoot><?php endif; ?>
	</table></div>
</div>
