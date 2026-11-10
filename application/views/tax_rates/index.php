<div class="card">
	<div class="card-header">
		<span>Tax rates</span>
		<div class="ms-auto"><?php if (can('tax_rates', 'add')): ?><button class="btn btn-primary btn-sm" data-modal="<?= site_url('tax-rates/form') ?>"><?= icon('plus', 15) ?> Add tax rate</button><?php endif; ?></div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover">
			<thead><tr><th>Code</th><th>Name</th><th>Type</th><th class="num">Rate %</th><th>Posts tax to</th><th class="num">Entries</th><th>Status</th><th class="text-end" style="width:130px">Actions</th></tr></thead>
			<tbody>
			<?php foreach ($rows as $t): ?>
				<tr class="<?= (int) $t->is_active ? '' : 'row-void' ?>">
					<td><b><?= h($t->code) ?></b></td><td><?= h($t->name) ?></td><td><?= tax_type_badge($t->tax_type) ?></td>
					<td class="num"><?= rtrim(rtrim(number_format((float) $t->rate, 3), '0'), '.') ?></td>
					<td><?= h($t->acc_code . ' ' . $t->acc_name) ?></td><td class="num"><?= (int) $t->used ?></td>
					<td><?= (int) $t->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' ?></td>
					<td class="text-end">
						<?php if (can('tax_rates', 'edit')): ?>
							<button class="btn btn-icon" title="Edit" data-modal="<?= site_url('tax-rates/form/' . $t->id) ?>"><?= icon('edit', 15) ?></button>
							<button class="btn btn-icon" title="<?= (int) $t->is_active ? 'Deactivate' : 'Activate' ?>" data-btn="btn-primary" data-ok="Yes" data-reload="1"
								data-confirm="<?= (int) $t->is_active ? 'Deactivate' : 'Activate' ?> <?= h($t->code) ?>?" data-action="<?= site_url('tax-rates/toggle/' . $t->id) ?>"><?= icon('ban', 15) ?></button>
						<?php endif; ?>
						<?php if (can('tax_rates', 'delete') && !(int) $t->used): ?>
							<button class="btn btn-icon danger" title="Delete" data-reload="1" data-confirm="Delete <?= h($t->code) ?>?" data-action="<?= site_url('tax-rates/delete/' . $t->id) ?>"><?= icon('trash', 15) ?></button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty-row">No tax rates yet.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
<div class="card mt-3"><div class="card-body small text-muted">
	<b>How the four tax types post</b><br>
	<b>Sales tax charged (output):</b> Dr received-in / receivable (amount + tax), Cr income (amount), Cr tax payable (tax).<br>
	<b>Sales tax paid (input):</b> Dr expense / asset (amount), Dr input tax receivable (tax), Cr paid-from / payable (amount + tax).<br>
	<b>Tax withheld by us:</b> Dr expense / asset (gross), Cr tax payable (tax), Cr paid-from / payable (gross - tax).<br>
	<b>Tax withheld from us (advance tax):</b> Dr received-in / receivable (gross - tax), Dr advance tax asset (tax), Cr income (gross).
</div></div>
