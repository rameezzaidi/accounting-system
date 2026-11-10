<?php $can_edit = !$locked && can('roles', $role ? 'edit' : 'add'); ?>
<form method="post" action="<?= site_url('roles-permissions/save') ?>" data-ajax id="roleForm">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= $role ? (int) $role->id : 0 ?>">
<div class="card mb-3">
	<div class="card-header"><span><?= $role ? 'Role details' : 'New role' ?></span>
		<div class="ms-auto"><a class="btn btn-light btn-sm" href="<?= site_url('roles-permissions') ?>">Back to roles</a></div></div>
	<div class="card-body">
		<?php if ($locked): ?><div class="alert alert-info">Super Admin always has full access and cannot be changed.</div><?php endif; ?>
		<div class="row g-3">
			<div class="col-md-4"><label class="form-label">Role name *</label>
				<input type="text" name="name" class="form-control" maxlength="60" value="<?= $role ? h($role->name) : '' ?>" <?= $can_edit ? '' : 'readonly' ?> autofocus></div>
			<div class="col-md-8"><label class="form-label">Description</label>
				<input type="text" name="description" class="form-control" maxlength="255" value="<?= $role ? h($role->description) : '' ?>" <?= $can_edit ? '' : 'readonly' ?>></div>
		</div>
	</div>
</div>

<div class="card">
	<div class="card-header"><span>Permissions</span>
		<?php if ($can_edit): ?><div class="ms-auto"><button type="button" class="btn btn-light btn-sm" id="permAll">Select all</button> <button type="button" class="btn btn-light btn-sm" id="permNone">Clear all</button></div><?php endif; ?>
	</div>
	<div class="table-responsive">
		<table class="table perm-table">
			<thead><tr><th>Module</th>
				<?php foreach ($cols as $c): ?><th class="text-center text-capitalize"><?= h($c) ?> <?php if ($can_edit): ?><input type="checkbox" class="form-check-input col-toggle" data-col="<?= h($c) ?>" title="Toggle column"><?php endif; ?></th><?php endforeach; ?>
				<?php if ($can_edit): ?><th class="text-center">All</th><?php endif; ?></tr></thead>
			<tbody>
			<?php foreach ($groups as $mod => $g):
				$byAction = array(); foreach ($g['items'] as $p) { $byAction[$p->action] = $p; } ?>
				<tr data-mod="<?= h($mod) ?>">
					<td class="mod"><?= h($g['label']) ?></td>
					<?php foreach ($cols as $c): ?>
						<td class="text-center">
						<?php if (isset($byAction[$c])): $p = $byAction[$c]; ?>
							<input type="checkbox" class="form-check-input perm-box" data-col="<?= h($c) ?>" name="perm[]" value="<?= (int) $p->id ?>" title="<?= h($p->label) ?>"
								<?= ($locked || in_array((int) $p->id, $granted)) ? 'checked' : '' ?> <?= $can_edit ? '' : 'disabled' ?>>
						<?php else: ?><span class="text-muted">&ndash;</span><?php endif; ?>
						</td>
					<?php endforeach; ?>
					<?php if ($can_edit): ?><td class="text-center"><input type="checkbox" class="form-check-input row-toggle" title="Toggle row"></td><?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php if ($can_edit): ?>
	<div class="balance-bar"><span class="ms-auto"><button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save role</button></span></div>
	<?php endif; ?>
</div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	var $f = $('#roleForm');
	function syncRows() { $f.find('tbody tr').each(function () { var $b = $(this).find('.perm-box'); $(this).find('.row-toggle').prop('checked', $b.length && $b.filter(':checked').length === $b.length); }); }
	function syncCols() { $f.find('.col-toggle').each(function () { var $b = $f.find('.perm-box[data-col="' + $(this).data('col') + '"]'); $(this).prop('checked', $b.length && $b.filter(':checked').length === $b.length); }); }
	$f.on('change', '.row-toggle', function () { $(this).closest('tr').find('.perm-box').prop('checked', this.checked); syncCols(); });
	$f.on('change', '.col-toggle', function () { $f.find('.perm-box[data-col="' + $(this).data('col') + '"]').prop('checked', this.checked); syncRows(); });
	$f.on('change', '.perm-box', function () {
		// anything ticked in a row needs "view" in that row
		var $tr = $(this).closest('tr');
		if (this.checked && $(this).data('col') !== 'view') { $tr.find('.perm-box[data-col="view"]').prop('checked', true); }
		if (!this.checked && $(this).data('col') === 'view') { $tr.find('.perm-box').prop('checked', false); }
		syncRows(); syncCols();
	});
	$('#permAll').on('click', function () { $f.find('.perm-box').prop('checked', true); syncRows(); syncCols(); });
	$('#permNone').on('click', function () { $f.find('.perm-box').prop('checked', false); syncRows(); syncCols(); });
	syncRows(); syncCols();
})(jQuery); });
</script>
