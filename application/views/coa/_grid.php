<div class="table-responsive">
<table class="table table-hover">
	<thead><tr><th style="width:120px">Code</th><th>Account name</th><th>Type</th><th>Kind</th><th>Status</th><th class="text-end" style="width:170px">Actions</th></tr></thead>
	<tbody>
	<?php foreach ($rows as $a): ?>
		<tr class="<?= $a->is_group ? 'row-group' : '' ?>">
			<td><?= h($a->code) ?></td>
			<td><span class="tree-indent" style="width:<?= (int) $a->level * 22 ?>px"></span><?= h($a->name) ?></td>
			<td><?= type_badge($a->account_type) ?></td>
			<td><?php if ($a->is_group): ?><span class="badge badge-soft-secondary">Group</span>
				<?php elseif ($a->cash_bank !== 'none'): ?><span class="badge badge-soft-info text-capitalize"><?= h($a->cash_bank) ?></span>
				<?php else: ?><span class="text-muted">Posting</span><?php endif; ?></td>
			<td><?= $a->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' ?></td>
			<td class="text-end">
				<?php if ($a->is_group && can('coa', 'add')): ?>
					<button class="btn btn-icon" title="Add child account" data-modal="<?= site_url('chart-of-accounts/form/0?parent=' . $a->id) ?>"><?= icon('plus', 15) ?></button>
				<?php endif; ?>
				<?php if (!$a->is_group && can('ledger', 'view')): ?>
					<a class="btn btn-icon" title="View ledger" href="<?= site_url('general-ledger?account=' . $a->id) ?>"><?= icon('book', 15) ?></a>
				<?php endif; ?>
				<?php if (can('coa', 'edit')): ?>
					<button class="btn btn-icon" title="Edit" data-modal="<?= site_url('chart-of-accounts/form/' . $a->id) ?>"><?= icon('edit', 15) ?></button>
					<button class="btn btn-icon" title="<?= $a->is_active ? 'Deactivate' : 'Activate' ?>" data-btn="btn-primary" data-ok="Yes"
						data-confirm="<?= $a->is_active ? 'Deactivate' : 'Activate' ?> account <?= h($a->code) ?>?" data-action="<?= site_url('chart-of-accounts/toggle/' . $a->id) ?>"><?= icon($a->is_active ? 'ban' : 'check', 15) ?></button>
				<?php endif; ?>
				<?php if (can('coa', 'delete')): ?>
					<button class="btn btn-icon danger" title="Delete" data-confirm="Delete account <?= h($a->code . ' ' . $a->name) ?>? This cannot be undone." data-action="<?= site_url('chart-of-accounts/delete/' . $a->id) ?>"><?= icon('trash', 15) ?></button>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; if (!$rows): ?>
		<tr><td colspan="6" class="empty-row">No accounts match your filters.</td></tr>
	<?php endif; ?>
	</tbody>
</table>
</div>
<div class="table-footer"><span><?= count($rows) ?> account(s)</span></div>
