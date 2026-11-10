<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-name" content="<?= $this->security->get_csrf_token_name() ?>">
<meta name="csrf-hash" content="<?= $this->security->get_csrf_hash() ?>">
<meta name="base-url" content="<?= base_url() ?>">
<title>Login | <?= h(setting('company_name', 'Accounting')) ?></title>
<link rel="icon" href="<?= base_url('assets/img/logo.jpg') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=3">
</head>
<body class="login-body">
	<div class="login-card">
		<div class="login-brand">
			<img class="login-logo" src="<?= base_url('assets/img/logo.jpg') ?>" alt="BBSHRRDB">
			<h2><?= h(setting('company_name', 'Accounting System')) ?></h2>
			<p class="text-muted mb-0">Sign in to your accounting workspace</p>
		</div>
		<form id="loginForm" method="post" action="<?= site_url('login') ?>" autocomplete="off">
			<?= csrf_field() ?>
			<div class="mb-3">
				<label class="form-label">Username</label>
				<input type="text" name="username" class="form-control form-control-lg" autofocus required>
			</div>
			<div class="mb-3">
				<label class="form-label">Password</label>
				<input type="password" name="password" class="form-control form-control-lg" required>
			</div>
			<div class="alert alert-danger py-2 d-none" id="loginError"></div>
			<button type="submit" class="btn btn-primary btn-lg w-100" id="loginBtn">Sign in</button>
		</form>
	</div>
<script src="<?= base_url('assets/vendor/jquery.min.js') ?>"></script>
<script>
$('#loginForm').on('submit', function (e) {
	e.preventDefault();
	var $b = $('#loginBtn').prop('disabled', true).text('Signing in...');
	$('#loginError').addClass('d-none');
	$.ajax({ url: this.action, type: 'POST', data: $(this).serialize(), dataType: 'json' })
	.done(function (r) {
		if (r.status) { window.location = r.redirect; return; }
		$('#loginError').text(r.message).removeClass('d-none');
		$b.prop('disabled', false).text('Sign in');
	})
	.fail(function () {
		$('#loginError').text('Could not sign in. Please refresh the page and try again.').removeClass('d-none');
		$b.prop('disabled', false).text('Sign in');
	});
});
</script>
</body>
</html>
