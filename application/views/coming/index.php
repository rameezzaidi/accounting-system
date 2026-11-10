<div class="card">
	<div class="empty-state">
		<div class="empty-icon"><?= icon('clock', 34) ?></div>
		<h4><?= h($info[0]) ?></h4>
		<p class="text-muted mb-1"><?= h($info[1]) ?></p>
		<p class="mb-3"><span class="badge badge-soft-info">Planned for Phase <?= (int) $info[2] ?></span></p>
		<a class="btn btn-primary" href="<?= site_url('dashboard') ?>">Back to dashboard</a>
	</div>
</div>
