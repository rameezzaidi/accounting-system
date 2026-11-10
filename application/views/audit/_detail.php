<div class="modal-header">
	<h5 class="modal-title">Activity details</h5>
	<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
	<div class="row g-2 mb-3">
		<div class="col-md-4"><div class="text-muted small">When</div><b><?= dmy_time($a->created_at) ?></b></div>
		<div class="col-md-4"><div class="text-muted small">User</div><b><?= h($a->username) ?: '-' ?></b> <small class="text-muted"><?= h($a->ip_address) ?></small></div>
		<div class="col-md-4"><div class="text-muted small">Module / action</div><b><?= h($a->module) ?> / <?= h($a->action) ?></b><?= $a->record_id !== null ? ' <small class="text-muted">#' . h($a->record_id) . '</small>' : '' ?></div>
		<div class="col-12"><div class="text-muted small">Description</div><?= h($a->description) ?></div>
	</div>
	<div class="row g-3">
		<?php if ($old !== ''): ?><div class="<?= $new !== '' ? 'col-md-6' : 'col-12' ?>"><div class="fw-semibold mb-1">Before</div><pre class="json"><?= h($old) ?></pre></div><?php endif; ?>
		<?php if ($new !== ''): ?><div class="<?= $old !== '' ? 'col-md-6' : 'col-12' ?>"><div class="fw-semibold mb-1">After</div><pre class="json"><?= h($new) ?></pre></div><?php endif; ?>
	</div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
