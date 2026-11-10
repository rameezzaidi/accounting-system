<div class="table-responsive">
<table class="table table-hover">
	<thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Last login</th><th>Status</th><th class="text-end" style="width:140px">Actions</th></tr></thead>
	<tbody>
	<?php foreach ($rows as $u): $me = (int) $u->id === (int) $this->user->id; ?>
		<tr>
			<td><b><?= h($u->full_name) ?></b><?= $me ? ' <span class="badge badge-soft-info">You</span>' : '' ?></td>
			<td><?= h($u->username) ?></td>
			<td><?= h($u->email) ?></td>
			<td><span class="badge badge-soft-primary"><?= h($u->role_name) ?></span></td>
			<td><?= $u->last_login ? dmy_time($u->last_login) : '<span class="text-muted">Never</span>' ?></td>
			<td><?= $u->status ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' ?></td>
			<td class="text-end">
				<?php if (can('users', 'edit')): ?>
					<button class="btn btn-icon" title="Edit" data-modal="<?= site_url('users/form/' . $u->id) ?>"><?= icon('edit', 15) ?></button>
					<?php if (!$me): ?>
					<button class="btn btn-icon" title="<?= $u->status ? 'Deactivate' : 'Activate' ?>" data-btn="btn-primary" data-ok="Yes"
						data-confirm="<?= $u->status ? 'Deactivate' : 'Activate' ?> user <?= h($u->username) ?>?" data-action="<?= site_url('users/toggle/' . $u->id) ?>"><?= icon($u->status ? 'ban' : 'check', 15) ?></button>
					<?php endif; ?>
				<?php endif; ?>
				<?php if (can('users', 'delete') && !$me): ?>
					<button class="btn btn-icon danger" title="Delete" data-confirm="Delete user <?= h($u->username) ?>? This cannot be undone." data-action="<?= site_url('users/delete/' . $u->id) ?>"><?= icon('trash', 15) ?></button>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; if (!$rows): ?>
		<tr><td colspan="7" class="empty-row">No users found.</td></tr>
	<?php endif; ?>
	</tbody>
</table>
</div>
<div class="table-footer"><span>Showing <?= count($rows) ?> of <?= (int) $total ?> user(s)</span><?= paginate_html($total, $page, $per) ?></div>
