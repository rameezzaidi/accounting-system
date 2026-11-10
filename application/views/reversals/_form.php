<form method="post" action="<?= site_url($route . '/save') ?>" data-ajax id="rvForm">
	<?= csrf_field() ?>
	<div class="modal-header">
		<h5 class="modal-title"><?= h($L['btn']) ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<div class="row g-3">
			<div class="col-12">
				<label class="form-label"><?= h($L['orig']) ?> *</label>
				<select name="original_voucher" id="rvOrig" class="form-select" data-placeholder="Search voucher no or narration"><option value=""></option></select>
			</div>
			<div class="col-12 d-none" id="rvInfo"><div class="info-box">
				<div class="row g-2">
					<div class="col-6"><small class="text-muted d-block">Account</small><span id="rvInfoAcc"></span></div>
					<div class="col-6"><small class="text-muted d-block">Original date / amount</small><span id="rvInfoAmt"></span></div>
					<div class="col-6"><small class="text-muted d-block">Already reversed</small><span id="rvInfoDone"></span></div>
					<div class="col-6"><small class="text-muted d-block">Available</small><b id="rvInfoRem"></b></div>
				</div>
			</div></div>
			<div class="col-md-6">
				<label class="form-label">Date *</label>
				<input type="date" name="voucher_date" class="form-control" value="<?= date('Y-m-d') ?>">
			</div>
			<div class="col-md-6">
				<label class="form-label">Amount *</label>
				<input type="text" inputmode="decimal" name="amount" id="rvAmount" class="form-control num" placeholder="0.00" autocomplete="off">
			</div>
			<div class="col-12">
				<label class="form-label"><?= h($L['cash']) ?></label>
				<select name="cash_account" id="rvCash" class="form-select select2" data-placeholder="Select cash / bank account">
					<option value=""></option>
					<?php foreach ($cash_accounts as $a): ?><option value="<?= $a->id ?>"><?= h($a->code . ' - ' . $a->name) ?> (<?= $a->cash_bank ?>)</option><?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-5">
				<label class="form-label"><?= h($L['party']) ?></label>
				<input type="text" name="party_name" class="form-control" maxlength="150">
			</div>
			<div class="col-md-7">
				<label class="form-label">Reason *</label>
				<input type="text" name="reason" class="form-control" maxlength="255" placeholder="Why is this <?= strtolower($L['verb']) ?> being made?">
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Post <?= strtolower($L['verb']) ?></button>
	</div>
</form>
<script>
(function ($) {
	var $o = $('#rvOrig');
	$o.select2({
		theme: 'bootstrap-5', width: '100%', dropdownParent: $('#appModal'), placeholder: $o.data('placeholder'),
		ajax: { url: App.url('<?= $route ?>/lookup'), dataType: 'json', delay: 250, data: function (p) { return { q: p.term || '' }; },
			processResults: function (r) { return { results: r.results }; } }
	});
	$o.on('select2:select', function (e) {
		var d = e.params.data;
		$('#rvInfo').removeClass('d-none');
		$('#rvInfoAcc').text(d.counter);
		$('#rvInfoAmt').text(d.date + '  /  ' + d.total);
		$('#rvInfoDone').text(d.done);
		$('#rvInfoRem').text(d.remaining_fmt);
		$('#rvAmount').val(App.money(d.remaining));
		$('#rvCash').val(d.cash_id).trigger('change');
		var $dt = $('#rvForm [name=voucher_date]');
		if ($dt.val() < d.raw_date) { $dt.val(d.raw_date); }
	});
	App.initSelect2($('#rvForm'));
})(jQuery);
</script>
