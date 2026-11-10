<?php $fy = $this->active_fy; ?>
<form method="post" action="<?= site_url('journal-vouchers/save') ?>" data-ajax id="jvForm">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= $v ? (int) $v->id : 0 ?>">
<div class="card mb-3">
	<div class="card-header">
		<span><?= $v ? 'Edit voucher ' . h($v->voucher_no) : 'New journal voucher' ?></span>
		<div class="ms-auto"><a class="btn btn-light btn-sm" href="<?= site_url('journal-vouchers') ?>">Back to list</a></div>
	</div>
	<div class="card-body">
		<div class="row g-3">
			<div class="col-md-3">
				<label class="form-label">Voucher date *</label>
				<input type="date" name="voucher_date" class="form-control" value="<?= $v ? h($v->voucher_date) : date('Y-m-d') ?>" required>
				<?php if ($fy): ?><div class="form-text">Active year: <?= dmy($fy->start_date) ?> to <?= dmy($fy->end_date) ?></div><?php endif; ?>
			</div>
			<div class="col-md-3">
				<label class="form-label">Reference / cheque no</label>
				<input type="text" name="reference" class="form-control" maxlength="100" value="<?= $v ? h($v->reference) : '' ?>">
			</div>
			<div class="col-md-6">
				<label class="form-label">Narration</label>
				<input type="text" name="narration" class="form-control" value="<?= $v ? h($v->narration) : '' ?>" placeholder="What is this entry for?">
			</div>
			<?= project_select($v ? $v->project_id : 0) ?>
		</div>
	</div>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table voucher-grid" id="vGrid">
			<thead><tr><th style="width:42px">#</th><th class="col-acc">Account</th><th>Line description</th><th class="col-amt num">Debit</th><th class="col-amt num">Credit</th><th class="col-act"></th></tr></thead>
			<tbody></tbody>
		</table>
	</div>
	<div class="p-3 border-top"><button type="button" class="btn btn-soft btn-sm" id="vAddLine"><?= icon('plus', 15) ?> Add line</button></div>
	<div class="balance-bar">
		<span>Total debit: <span id="vTd">0.00</span></span>
		<span>Total credit: <span id="vTc">0.00</span></span>
		<span id="vDiff">Balanced</span>
		<span class="ms-auto"><button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> <?= $v ? 'Update voucher' : 'Post voucher' ?></button></span>
	</div>
</div>
</form>
