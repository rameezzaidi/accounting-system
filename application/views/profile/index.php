<div class="row"><div class="col-md-6 col-xl-4">
	<div class="card">
		<div class="card-header">Change your password</div>
		<div class="card-body">
			<p class="text-muted">Signed in as <b><?= h($this->user->username) ?></b> (<?= h($this->user->role_name) ?>)</p>
			<form method="post" action="<?= site_url('profile/save') ?>" data-ajax>
				<?= csrf_field() ?>
				<div class="mb-3"><label class="form-label">Current password</label><input type="password" name="current_password" class="form-control" required></div>
				<div class="mb-3"><label class="form-label">New password</label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
				<div class="mb-3"><label class="form-label">Confirm new password</label><input type="password" name="confirm_password" class="form-control" required></div>
				<button type="submit" class="btn btn-primary">Update password</button>
			</form>
		</div>
	</div>
</div></div>
