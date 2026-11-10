<div class="card">
	<div class="card-header">
		<span>Asset categories</span>
		<div class="ms-auto"><?php if (can('asset_categories', 'add')): ?><button class="btn btn-primary btn-sm" data-size="lg" data-modal="<?= site_url('asset-categories/form') ?>"><?= icon('plus', 15) ?> Add category</button><?php endif; ?></div>
	</div>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Name</th><th>Asset account</th><th>Accumulated depreciation</th><th>Depreciation expense</th><th>Method</th><th class="num">Rate %</th><th class="num">Assets</th><th>Status</th><th class="text-end" style="width:120px">Actions</th></tr></thead>
		<tbody>
		<?php foreach ($rows as $c): ?>
			<tr class="<?= (int) $c->is_active ? '' : 'row-void' ?>">
				<td><b><?= h($c->name) ?></b></td><td class="small"><?= h($c->ac . ' ' . $c->an) ?></td><td class="small"><?= h($c->xc . ' ' . $c->xn) ?></td><td class="small"><?= h($c->ec . ' ' . $c->en) ?></td>
				<td><?= $c->dep_method === 'sl' ? 'Straight line' : 'Reducing balance' ?></td><td class="num"><?= rtrim(rtrim(number_format((float) $c->dep_rate, 2), '0'), '.') ?></td><td class="num"><?= (int) $c->used ?></td>
				<td><?= (int) $c->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' ?></td>
				<td class="text-end">
					<?php if (can('asset_categories', 'edit')): ?>
						<button class="btn btn-icon" title="Edit" data-size="lg" data-modal="<?= site_url('asset-categories/form/' . $c->id) ?>"><?= icon('edit', 15) ?></button>
						<button class="btn btn-icon" title="<?= (int) $c->is_active ? 'Deactivate' : 'Activate' ?>" data-btn="btn-primary" data-ok="Yes" data-reload="1" data-confirm="<?= (int) $c->is_active ? 'Deactivate' : 'Activate' ?> <?= h($c->name) ?>?" data-action="<?= site_url('asset-categories/toggle/' . $c->id) ?>"><?= icon('ban', 15) ?></button>
					<?php endif; ?>
					<?php if (can('asset_categories', 'delete') && !(int) $c->used): ?><button class="btn btn-icon danger" title="Delete" data-reload="1" data-confirm="Delete <?= h($c->name) ?>?" data-action="<?= site_url('asset-categories/delete/' . $c->id) ?>"><?= icon('trash', 15) ?></button><?php endif; ?>
				</td>
			</tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row">No categories yet.</td></tr><?php endif; ?>
		</tbody>
	</table></div>
</div>
