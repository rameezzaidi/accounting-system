<form method="post" action="<?= site_url('employees/save') ?>" data-ajax>
	<?= csrf_field() ?><input type="hidden" name="id" value="<?= $e ? (int) $e->id : 0 ?>">
	<div class="modal-header"><h5 class="modal-title"><?= $e ? 'Edit ' . h($e->emp_code) : 'Add employee' ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
	<div class="modal-body"><div class="row g-3">
		<div class="col-md-8"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" maxlength="120" value="<?= $e ? h($e->name) : '' ?>" autofocus></div>
		<div class="col-md-4"><label class="form-label">Joining date</label><input type="date" name="join_date" class="form-control" value="<?= $e ? h($e->join_date) : '' ?>"></div>
		<div class="col-md-4"><label class="form-label">Designation</label><input type="text" name="designation" class="form-control" maxlength="100" value="<?= $e ? h($e->designation) : '' ?>"></div>
		<div class="col-md-4"><label class="form-label">Department</label><input type="text" name="department" class="form-control" maxlength="100" value="<?= $e ? h($e->department) : '' ?>"></div>
		<div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" maxlength="40" value="<?= $e ? h($e->phone) : '' ?>"></div>
		<div class="col-md-4"><label class="form-label">Basic salary</label><input type="text" inputmode="decimal" name="basic_salary" class="form-control num" value="<?= $e ? h((float) $e->basic_salary) : '' ?>"></div>
		<div class="col-md-4"><label class="form-label">Allowances</label><input type="text" inputmode="decimal" name="allowances" class="form-control num" value="<?= $e ? h((float) $e->allowances) : '' ?>"></div>
		<div class="col-md-4"><label class="form-label">Fixed deductions</label><input type="text" inputmode="decimal" name="fixed_deductions" class="form-control num" value="<?= $e ? h((float) $e->fixed_deductions) : '' ?>"></div>
		<div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive" <?= $e && $e->status === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
	</div></div>
	<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save</button></div>
</form>
