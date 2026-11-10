<div class="card">
	<div class="card-header">
		<span>Roles</span>
		<div class="ms-auto">
			<?php if (can('roles', 'add')): ?><a class="btn btn-primary btn-sm" href="<?= site_url('roles-permissions/form') ?>"><?= icon('plus', 15) ?> Add role</a><?php endif; ?>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover">
			<thead><tr><th>Role</th><th>Description</th><th class="num">Users</th><th class="num">Permissions</th><th class="text-end" style="width:130px">Actions</th></tr></thead>
			<tbody>
			<?php foreach ($roles as $r): ?>
				<tr>
					<td><b><?= h($r->name) ?></b> <?= $r->is_system ? '<span class="badge badge-soft-info">System</span>' : '' ?></td>
					<td><?= h($r->description) ?></td>
					<td class="num"><?= (int) $r->user_count ?></td>
					<td class="num"><?= (int) $r->perm_count ?> / <?= (int) $total_perms ?></td>
					<td class="text-end">
						<?php if (can('roles', 'edit') || can('roles', 'view')): ?>
							<a class="btn btn-icon" title="<?= (int) $r->id === 1 ? 'View permissions' : 'Edit permissions' ?>" href="<?= site_url('roles-permissions/form/' . $r->id) ?>"><?= icon((int) $r->id === 1 ? 'eye' : 'shield', 15) ?></a>
						<?php endif; ?>
						<?php if (can('roles', 'delete') && !$r->is_system): ?>
							<button class="btn btn-icon danger" title="Delete" data-confirm="Delete role <?= h($r->name) ?>?" data-action="<?= site_url('roles-permissions/delete/' . $r->id) ?>"><?= icon('trash', 15) ?></button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
