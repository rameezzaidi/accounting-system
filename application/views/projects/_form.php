<form method="post" action="<?= site_url('projects/save') ?>" data-ajax>
	<?= csrf_field() ?><input type="hidden" name="id" value="<?= $p ? (int) $p->id : 0 ?>">
	<div class="modal-header"><h5 class="modal-title"><?= $p ? 'Edit project' : 'Add project' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
	<div class="modal-body"><div class="row g-3">
		<div class="col-md-4"><label class="form-label">Code *</label><input type="text" name="code" class="form-control text-uppercase" maxlength="20" value="<?= $p ? h($p->code) : '' ?>" autofocus></div>
		<div class="col-md-8"><label class="form-label">Project name *</label><input type="text" name="name" class="form-control" maxlength="150" value="<?= $p ? h($p->name) : '' ?>"></div>
		<div class="col-md-6"><label class="form-label">Funder / donor</label><input type="text" name="funder" class="form-control" maxlength="150" value="<?= $p ? h($p->funder) : '' ?>"></div>
		<div class="col-md-6"><label class="form-label">Project budget</label><input type="text" inputmode="decimal" name="budget" class="form-control num" value="<?= $p ? h((float) $p->budget) : '' ?>"></div>
		<div class="col-md-6"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="<?= $p ? h($p->start_date) : '' ?>"></div>
		<div class="col-md-6"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="<?= $p ? h($p->end_date) : '' ?>"></div>
		<div class="col-12"><label class="form-label">Description</label><input type="text" name="description" class="form-control" maxlength="255" value="<?= $p ? h($p->description) : '' ?>"></div>
	</div></div>
	<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save</button></div>
</form>
