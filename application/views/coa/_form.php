<form method="post" action="<?= site_url('chart-of-accounts/save') ?>" data-ajax id="coaForm">
	<?= csrf_field() ?>
	<input type="hidden" name="id" value="<?= $acc ? (int) $acc->id : 0 ?>">
	<div class="modal-header">
		<h5 class="modal-title"><?= $acc ? 'Edit account' : 'Add account' ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label">Parent account <small class="text-muted fw-normal">(leave empty for a top level account)</small></label>
				<select name="parent_id" class="form-select select2" id="coaParent" data-placeholder="No parent (top level)">
					<option value=""></option>
					<?php foreach ($groups as $g): if ($acc && (int) $g->id === (int) $acc->id) { continue; } ?>
						<option value="<?= $g->id ?>" data-type="<?= $g->account_type ?>" <?= (string) $parent_id === (string) $g->id ? 'selected' : '' ?>><?= h($g->code . ' - ' . $g->name) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-4">
				<label class="form-label">Code *</label>
				<input type="text" name="code" id="coaCode" class="form-control" maxlength="20" value="<?= $acc ? h($acc->code) : '' ?>" autofocus>
			</div>
			<div class="col-md-8">
				<label class="form-label">Account name *</label>
				<input type="text" name="name" class="form-control" maxlength="150" value="<?= $acc ? h($acc->name) : '' ?>">
			</div>
			<div class="col-md-6">
				<label class="form-label">Account type *</label>
				<select name="account_type" id="coaType" class="form-select" <?= $used ? 'data-used="1"' : '' ?>>
					<option value="">Select type</option>
					<?php foreach (array('asset', 'liability', 'equity', 'income', 'expense') as $t): ?>
						<option value="<?= $t ?>" <?= $acc && $acc->account_type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
					<?php endforeach; ?>
				</select>
				<div class="form-text" id="typeNote"></div>
			</div>
			<div class="col-md-6" id="cashBankBox">
				<label class="form-label">Cash / Bank account?</label>
				<select name="cash_bank" class="form-select">
					<option value="none">No (normal account)</option>
					<option value="cash" <?= $acc && $acc->cash_bank === 'cash' ? 'selected' : '' ?>>Cash</option>
					<option value="bank" <?= $acc && $acc->cash_bank === 'bank' ? 'selected' : '' ?>>Bank</option>
				</select>
				<div class="form-text">Used by Quick Entries and the dashboard.</div>
			</div>
			<div class="col-12">
				<label class="form-label">Description</label>
				<input type="text" name="description" class="form-control" maxlength="255" value="<?= $acc ? h($acc->description) : '' ?>">
			</div>
			<div class="col-md-6">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" name="is_group" id="coaIsGroup" value="1" <?= $acc && $acc->is_group ? 'checked' : '' ?>>
					<label class="form-check-label" for="coaIsGroup">Group account (header, no entries)</label>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" name="is_active" id="coaActive" value="1" <?= !$acc || $acc->is_active ? 'checked' : '' ?>>
					<label class="form-check-label" for="coaActive">Active</label>
				</div>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save account</button>
	</div>
</form>
<script>
(function () {
	var $f = $('#coaForm'), isNew = <?= $acc ? 'false' : 'true' ?>;
	function sync() {
		var $p = $('#coaParent option:selected');
		var t = $p.data('type');
		if ($p.val() && t) { $('#coaType').val(t).prop('disabled', true); $('#typeNote').text('Type follows the parent account.'); }
		else { $('#coaType').prop('disabled', false); $('#typeNote').text(''); }
		var showCb = !$('#coaIsGroup').is(':checked') && $('#coaType').val() === 'asset';
		$('#cashBankBox').toggle(showCb);
	}
	// a disabled select is not posted, so keep the value in a hidden field
	$f.on('submit', function () {
		$f.find('input.type-hidden').remove();
		if ($('#coaType').prop('disabled')) { $('<input type="hidden" class="type-hidden" name="account_type">').val($('#coaType').val()).appendTo($f); }
	});
	$('#coaParent').on('change', function () {
		sync();
		if (isNew && $(this).val() !== undefined) {
			$.get(App.url('chart-of-accounts/next_code'), { parent: $(this).val() || 0 }, function (r) { if (r.code && !$('#coaCode').data('touched')) { $('#coaCode').val(r.code); } });
		}
	});
	$('#coaCode').on('input', function () { $(this).data('touched', true); });
	$('#coaType, #coaIsGroup').on('change', sync);
	sync();
	if (isNew && $('#coaParent').val() && !$('#coaCode').val()) { $('#coaParent').trigger('change'); }
})();
</script>
