<form method="post" action="<?= site_url('fiscal-years/save') ?>" data-ajax data-reload-page="1">
	<?= csrf_field() ?>
	<input type="hidden" name="id" value="<?= $fy ? (int) $fy->id : 0 ?>">
	<div class="modal-header">
		<h5 class="modal-title"><?= $fy ? 'Edit fiscal year' : 'Add fiscal year' ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<?php if ($locked): ?><div class="alert alert-info py-2">This year already has data, so only the title can be changed.</div><?php endif; ?>
		<div class="row g-3">
			<div class="col-12"><label class="form-label">Title *</label>
				<input type="text" name="title" class="form-control" maxlength="60" value="<?= $fy ? h($fy->title) : ($suggest ? h($suggest->title) : '') ?>" placeholder="e.g. FY 2027-28" autofocus></div>
			<div class="col-md-6"><label class="form-label">Start date *</label>
				<input type="date" name="start_date" class="form-control" value="<?= $fy ? h($fy->start_date) : ($suggest ? h($suggest->start_date) : '') ?>" <?= $locked ? 'readonly' : '' ?>></div>
			<div class="col-md-6"><label class="form-label">End date *</label>
				<input type="date" name="end_date" class="form-control" value="<?= $fy ? h($fy->end_date) : ($suggest ? h($suggest->end_date) : '') ?>" <?= $locked ? 'readonly' : '' ?>></div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save</button>
	</div>
</form>
