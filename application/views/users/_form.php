<form method="post" action="<?= site_url('users/save') ?>" data-ajax autocomplete="off">
	<?= csrf_field() ?>
	<input type="hidden" name="id" value="<?= $u ? (int) $u->id : 0 ?>">
	<div class="modal-header">
		<h5 class="modal-title"><?= $u ? 'Edit user' : 'Add user' ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-12"><label class="form-label">Full name *</label><input type="text" name="full_name" class="form-control" maxlength="120" value="<?= $u ? h($u->full_name) : '' ?>" autofocus></div>
			<div class="col-md-6"><label class="form-label">Username *</label><input type="text" name="username" class="form-control" maxlength="60" value="<?= $u ? h($u->username) : '' ?>" autocomplete="off"></div>
			<div class="col-md-6"><label class="form-label">Email</label><input type="text" name="email" class="form-control" maxlength="150" value="<?= $u ? h($u->email) : '' ?>"></div>
			<div class="col-md-6">
				<label class="form-label">Role *</label>
				<select name="role_id" class="form-select">
					<option value="">Select role</option>
					<?php foreach ($roles as $r): ?><option value="<?= $r->id ?>" <?= $u && (int) $u->role_id === (int) $r->id ? 'selected' : '' ?>><?= h($r->name) ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-6">
				<label class="form-label">Password <?= $u ? '<small class="text-muted fw-normal">(leave blank to keep)</small>' : '*' ?></label>
				<input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="<?= $u ? 'Unchanged' : 'Min 6 characters' ?>">
			</div>
			<div class="col-12">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" name="status" id="usStatus" value="1" <?= !$u || $u->status ? 'checked' : '' ?>>
					<label class="form-check-label" for="usStatus">Active (can sign in)</label>
				</div>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save user</button>
	</div>
</form>
