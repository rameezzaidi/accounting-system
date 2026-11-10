<div class="table-responsive">
<table class="table table-hover">
	<thead><tr><th>When</th><th>User</th><th>Module</th><th>Action</th><th>Description</th><th>IP</th><th class="text-end" style="width:70px"></th></tr></thead>
	<tbody>
	<?php
	$tone = array('create' => 'success', 'login' => 'info', 'update' => 'primary', 'delete' => 'danger', 'void' => 'danger', 'login_failed' => 'danger', 'export' => 'secondary', 'print' => 'secondary');
	foreach ($rows as $a): $t = isset($tone[$a->action]) ? $tone[$a->action] : 'secondary'; ?>
		<tr>
			<td class="text-nowrap"><?= dmy_time($a->created_at) ?></td>
			<td><?= h($a->username) ?: '<span class="text-muted">-</span>' ?></td>
			<td><span class="badge badge-soft-secondary"><?= h($a->module) ?></span></td>
			<td><span class="badge badge-soft-<?= $t ?>"><?= h($a->action) ?></span></td>
			<td><?= h($a->description) ?></td>
			<td class="text-muted small"><?= h($a->ip_address) ?></td>
			<td class="text-end"><?php if ($a->old_data !== null || $a->new_data !== null): ?>
				<button class="btn btn-icon" title="Details" data-modal="<?= site_url('audit-log/detail/' . $a->id) ?>" data-size="lg"><?= icon('eye', 15) ?></button><?php endif; ?></td>
		</tr>
	<?php endforeach; if (!$rows): ?>
		<tr><td colspan="7" class="empty-row">No activity found.</td></tr>
	<?php endif; ?>
	</tbody>
</table>
</div>
<div class="table-footer"><span>Showing <?= count($rows) ?> of <?= (int) $total ?> record(s)</span><?= paginate_html($total, $page, $per) ?></div>
