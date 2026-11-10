<form method="post" action="<?= site_url('suppliers/billsave') ?>" data-ajax>
	<?= csrf_field() ?>
	<input type="hidden" name="supplier_id" value="<?= (int) $s->id ?>">
	<div class="modal-header">
		<h5 class="modal-title">New bill - <?= h($s->name) ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-md-4"><label class="form-label">Bill date *</label><input type="date" name="voucher_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
			<div class="col-md-4"><label class="form-label">Due date</label><input type="date" name="due_date" class="form-control"></div>
			<div class="col-md-4"><label class="form-label">Supplier invoice no</label><input type="text" name="ref_no" class="form-control" maxlength="60"></div>
			<div class="col-md-4"><label class="form-label">Amount *</label><input type="text" inputmode="decimal" name="amount" class="form-control num" placeholder="0.00" autocomplete="off"></div>
			<div class="col-md-8"><label class="form-label">Expense / asset account *</label>
				<select name="expense_account" class="form-select select2" data-placeholder="What was purchased?"><option value=""></option>
				<?php foreach ($accounts as $a): ?><option value="<?= $a->id ?>"><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?></select></div>
			<div class="col-12"><label class="form-label">Narration</label><input type="text" name="narration" class="form-control" placeholder="Optional details"></div>
			<?= project_select(0, 'col-12') ?>
			<div class="col-12"><div class="info-box">Posts: Dr expense / asset, Cr <?= h($s->acc_code . ' ' . $s->acc_name) ?>.</div></div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Post bill</button>
	</div>
</form>
