<div class="card">
	<div class="card-header"><span>Employees</span>
		<div class="ms-auto"><?php if (can('employees', 'add')): ?><button class="btn btn-primary btn-sm" data-size="lg" data-modal="<?= site_url('employees/form') ?>"><?= icon('plus', 15) ?> Add employee</button><?php endif; ?></div></div>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Code</th><th>Name</th><th>Designation / department</th><th class="num">Basic</th><th class="num">Allowances</th><th class="num">Deductions</th><th class="num">Net</th><th>Status</th><th class="text-end" style="width:90px"></th></tr></thead>
		<tbody>
		<?php foreach ($rows as $e): ?>
			<tr class="<?= $e->status === 'inactive' ? 'row-void' : '' ?>">
				<td><b><?= h($e->emp_code) ?></b></td><td><?= h($e->name) ?></td><td><?= h($e->designation) ?><div class="small text-muted"><?= h($e->department) ?></div></td>
				<td class="num"><?= fmt($e->basic_salary) ?></td><td class="num"><?= fmt($e->allowances) ?></td><td class="num"><?= fmt($e->fixed_deductions) ?></td>
				<td class="num"><b><?= fmt((float) $e->basic_salary + (float) $e->allowances - (float) $e->fixed_deductions) ?></b></td>
				<td><?= $e->status === 'active' ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' ?></td>
				<td class="text-end">
					<?php if (can('employees', 'edit')): ?><button class="btn btn-icon" title="Edit" data-size="lg" data-modal="<?= site_url('employees/form/' . $e->id) ?>"><?= icon('edit', 15) ?></button><?php endif; ?>
					<?php if (can('employees', 'delete') && !(int) $e->used): ?><button class="btn btn-icon danger" title="Delete" data-reload="1" data-confirm="Delete <?= h($e->name) ?>?" data-action="<?= site_url('employees/delete/' . $e->id) ?>"><?= icon('trash', 15) ?></button><?php endif; ?>
				</td>
			</tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row">No employees yet.</td></tr><?php endif; ?>
		</tbody>
	</table></div>
</div>
