<form method="post" action="<?= site_url('suppliers/save') ?>" data-ajax>
	<?= csrf_field() ?>
	<input type="hidden" name="id" value="<?= $s ? (int) $s->id : 0 ?>">
	<div class="modal-header">
		<h5 class="modal-title"><?= $s ? 'Edit supplier ' . h($s->code) : 'Add supplier' ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-md-8"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" maxlength="150" value="<?= $s ? h($s->name) : '' ?>" autofocus></div>
			<div class="col-md-4"><label class="form-label">Tax no (NTN)</label><input type="text" name="tax_no" class="form-control" maxlength="40" value="<?= $s ? h($s->tax_no) : '' ?>"></div>
			<div class="col-md-4"><label class="form-label">Contact person</label><input type="text" name="contact_person" class="form-control" maxlength="120" value="<?= $s ? h($s->contact_person) : '' ?>"></div>
			<div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" maxlength="40" value="<?= $s ? h($s->phone) : '' ?>"></div>
			<div class="col-md-4"><label class="form-label">Email</label><input type="text" name="email" class="form-control" maxlength="120" value="<?= $s ? h($s->email) : '' ?>"></div>
			<div class="col-12"><label class="form-label">Address</label><input type="text" name="address" class="form-control" maxlength="255" value="<?= $s ? h($s->address) : '' ?>"></div>
			<div class="col-12">
				<label class="form-label">Payable account *</label>
				<select name="payable_account_id" class="form-select select2" data-placeholder="Select payable account" <?= $used ? 'disabled' : '' ?>>
					<option value=""></option>
					<?php $sel = $s ? (int) $s->payable_account_id : $default_acc; foreach ($accounts as $a): ?><option value="<?= $a->id ?>" <?= $sel === (int) $a->id ? 'selected' : '' ?>><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?>
				</select>
				<?php if ($used): ?><div class="form-text">Locked because this supplier already has bills / payments.</div><?php endif; ?>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save</button>
	</div>
</form>
