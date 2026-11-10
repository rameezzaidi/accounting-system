<div class="card">
	<div class="card-header">
		<span>Suppliers</span>
		<div class="ms-auto"><?php if (can('suppliers', 'add')): ?><button class="btn btn-primary btn-sm" data-size="lg" data-modal="<?= site_url('suppliers/form') ?>"><?= icon('plus', 15) ?> Add supplier</button><?php endif; ?></div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover">
			<thead><tr><th>Code</th><th>Name</th><th>Contact</th><th>Payable account</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Balance due</th><th>Status</th><th class="text-end" style="width:150px">Actions</th></tr></thead>
			<tbody>
			<?php $tb = 0; foreach ($rows as $s): $bal = (float) $s->billed - (float) $s->paid; $tb += $bal; ?>
				<tr class="<?= (int) $s->is_active ? '' : 'row-void' ?>">
					<td><b><?= h($s->code) ?></b></td>
					<td><a href="<?= site_url('suppliers/statement/' . $s->id) ?>"><?= h($s->name) ?></a></td>
					<td><?= h($s->contact_person) ?><?php if ($s->phone): ?><div class="small text-muted"><?= h($s->phone) ?></div><?php endif; ?></td>
					<td class="small"><?= h($s->acc_code . ' ' . $s->acc_name) ?></td>
					<td class="num"><?= fmt($s->billed) ?></td><td class="num"><?= fmt($s->paid) ?></td><td class="num"><b><?= fmt($bal) ?></b></td>
					<td><?= (int) $s->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' ?></td>
					<td class="text-end">
						<?php if (can('supplier_txn', 'view')): ?><a class="btn btn-icon" title="Statement" href="<?= site_url('suppliers/statement/' . $s->id) ?>"><?= icon('file', 15) ?></a><?php endif; ?>
						<?php if (can('suppliers', 'edit')): ?>
							<button class="btn btn-icon" title="Edit" data-size="lg" data-modal="<?= site_url('suppliers/form/' . $s->id) ?>"><?= icon('edit', 15) ?></button>
							<button class="btn btn-icon" title="<?= (int) $s->is_active ? 'Deactivate' : 'Activate' ?>" data-btn="btn-primary" data-ok="Yes" data-reload="1"
								data-confirm="<?= (int) $s->is_active ? 'Deactivate' : 'Activate' ?> <?= h($s->name) ?>?" data-action="<?= site_url('suppliers/toggle/' . $s->id) ?>"><?= icon('ban', 15) ?></button>
						<?php endif; ?>
						<?php if (can('suppliers', 'delete') && !(int) $s->txns): ?>
							<button class="btn btn-icon danger" title="Delete" data-reload="1" data-confirm="Delete <?= h($s->name) ?>?" data-action="<?= site_url('suppliers/delete/' . $s->id) ?>"><?= icon('trash', 15) ?></button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row">No suppliers yet.</td></tr><?php endif; ?>
			</tbody>
			<?php if ($rows): ?><tfoot><tr><th colspan="6" class="text-end">Total payable to suppliers</th><th class="num"><?= fmt($tb) ?></th><th colspan="2"></th></tr></tfoot><?php endif; ?>
		</table>
	</div>
</div>
