<?php
$cfg = array(
	'PV' => array('Quick payment', 'Paid from (cash / bank) *', 'Paid to (account) *', 'Payment'),
	'RV' => array('Quick receipt', 'Received in (cash / bank) *', 'Received from (account) *', 'Receipt'),
	'CV' => array('Cash / bank transfer', 'Transfer from *', 'Transfer to *', 'Transfer'),
);
$c = $cfg[$type];
?>
<form method="post" action="<?= site_url('quick-entries/save') ?>" data-ajax>
	<?= csrf_field() ?>
	<input type="hidden" name="voucher_type" value="<?= h($type) ?>">
	<div class="modal-header">
		<h5 class="modal-title"><?= $c[0] ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-md-6">
				<label class="form-label">Date *</label>
				<input type="date" name="voucher_date" class="form-control" value="<?= date('Y-m-d') ?>">
			</div>
			<div class="col-md-6">
				<label class="form-label">Amount *</label>
				<input type="text" inputmode="decimal" name="amount" class="form-control num" placeholder="0.00" autocomplete="off" autofocus>
			</div>
			<div class="col-12">
				<label class="form-label"><?= $c[1] ?></label>
				<select name="main_account" class="form-select select2" data-placeholder="Select cash / bank account">
					<option value=""></option>
					<?php foreach ($cash_accounts as $a): ?><option value="<?= $a->id ?>"><?= h($a->code . ' - ' . $a->name) ?> (<?= $a->cash_bank ?>)</option><?php endforeach; ?>
				</select>
			</div>
			<div class="col-12">
				<label class="form-label"><?= $c[2] ?></label>
				<select name="other_account" class="form-select select2" data-placeholder="Select account">
					<option value=""></option>
					<?php foreach (($type === 'CV' ? $cash_accounts : $all_accounts) as $a): ?><option value="<?= $a->id ?>"><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-5">
				<label class="form-label">Reference / cheque no</label>
				<input type="text" name="reference" class="form-control" maxlength="100">
			</div>
			<div class="col-md-7">
				<label class="form-label">Narration</label>
				<input type="text" name="narration" class="form-control" placeholder="Details of this <?= strtolower($c[3]) ?>">
			</div>
			<?= project_select(0, 'col-12') ?>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Post <?= strtolower($c[3]) ?></button>
	</div>
</form>
