<form method="post" action="<?= site_url('payroll/pay') ?>" data-ajax>
	<?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r->id ?>">
	<div class="modal-header"><h5 class="modal-title">Pay salaries - <?= h(date('F Y', strtotime($r->period . '-01'))) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
	<div class="modal-body"><div class="row g-3">
		<div class="col-12"><div class="info-box">Net salaries to pay: <b><?= fmt($r->net) ?></b></div></div>
		<div class="col-md-5"><label class="form-label">Payment date *</label><input type="date" name="paid_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
		<div class="col-md-7"><label class="form-label">Paid from *</label><select name="cash_account" class="form-select select2" data-placeholder="Cash / bank account"><option value=""></option><?php foreach ($cash as $a): ?><option value="<?= $a->id ?>"><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?></select></div>
	</div></div>
	<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><?= icon('check', 16) ?> Post payment</button></div>
</form>
