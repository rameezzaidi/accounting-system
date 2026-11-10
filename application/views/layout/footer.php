		</main>
		<footer class="app-footer">&copy; <?= date('Y') ?> <?= h(setting('company_name', 'Accounting')) ?> &middot; Accounting System</footer>
	</div>
</div>

<!-- generic modal (forms are loaded into it with ajax) -->
<div class="modal fade" id="appModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered"><div class="modal-content"></div></div>
</div>

<!-- confirm modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
		<div class="modal-body text-center p-4">
			<div class="confirm-icon"><?= icon('info', 28) ?></div>
			<p class="mb-3 fw-semibold" id="confirmText">Are you sure?</p>
			<textarea class="form-control mb-3 d-none" id="confirmReason" rows="2" placeholder="Reason (required)"></textarea>
			<div class="d-flex gap-2 justify-content-center">
				<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-danger" id="confirmOk">Yes, continue</button>
			</div>
		</div>
	</div></div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastBox" style="z-index:2000"></div>

<script src="<?= base_url('assets/vendor/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/select2.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>?v=1"></script>
<?php if (!empty($scripts)): foreach ($scripts as $s): ?>
<script src="<?= base_url($s) ?>"></script>
<?php endforeach; endif; ?>
<?php if (!empty($inline_js)): ?>
<script><?= $inline_js ?></script>
<?php endif; ?>
</body>
</html>
