<?php $ro = $can_edit ? '' : 'readonly'; ?>
<form method="post" action="<?= site_url('settings/save') ?>" data-ajax>
<?= csrf_field() ?>
<div class="row g-3">
	<div class="col-lg-7">
		<div class="card">
			<div class="card-header">Company details <small class="text-muted fw-normal">(shown on vouchers and reports)</small></div>
			<div class="card-body">
				<div class="row g-3">
					<div class="col-12"><label class="form-label">Company name *</label><input type="text" name="company_name" class="form-control" value="<?= h($s['company_name']) ?>" <?= $ro ?>></div>
					<div class="col-12"><label class="form-label">Address</label><input type="text" name="company_address" class="form-control" value="<?= h($s['company_address']) ?>" <?= $ro ?>></div>
					<div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="company_phone" class="form-control" value="<?= h($s['company_phone']) ?>" <?= $ro ?>></div>
					<div class="col-md-6"><label class="form-label">Email</label><input type="text" name="company_email" class="form-control" value="<?= h($s['company_email']) ?>" <?= $ro ?>></div>
					<div class="col-md-6"><label class="form-label">NTN / Tax number</label><input type="text" name="company_ntn" class="form-control" value="<?= h($s['company_ntn']) ?>" <?= $ro ?>></div>
					<div class="col-md-6"><label class="form-label">Currency code</label><input type="text" name="currency_code" class="form-control" maxlength="3" value="<?= h($s['currency_code']) ?>" <?= $ro ?>></div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-5">
		<div class="card">
			<div class="card-header">Voucher number prefixes</div>
			<div class="card-body">
				<div class="row g-3">
					<?php foreach (array('JV' => 'Journal voucher', 'PV' => 'Payment voucher', 'RV' => 'Receipt voucher', 'CV' => 'Contra voucher') as $k => $lbl): ?>
						<div class="col-6"><label class="form-label"><?= $lbl ?></label><input type="text" name="prefix_<?= $k ?>" class="form-control" maxlength="6" value="<?= h(isset($s['prefix_' . $k]) ? $s['prefix_' . $k] : $k) ?>" <?= $ro ?>></div>
					<?php endforeach; ?>
				</div>
				<p class="text-muted small mt-3 mb-0">Example: <b><?= h(isset($s['prefix_JV']) ? $s['prefix_JV'] : 'JV') ?>-2026-27-0001</b>. New prefixes apply to new vouchers only.</p>
			</div>
		</div>
		<?php if ($can_edit): ?><div class="mt-3"><button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save settings</button></div><?php endif; ?>
	</div>
</div>
</form>
