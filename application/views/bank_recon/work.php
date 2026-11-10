<form method="post" action="<?= site_url('bank-reconciliation/save') ?>" data-ajax id="rcForm">
<?= csrf_field() ?>
<div class="card">
	<div class="card-header"><span>New bank reconciliation</span><div class="ms-auto"><a class="btn btn-light btn-sm" href="<?= site_url('bank-reconciliation') ?>">Back</a></div></div>
	<div class="filter-bar">
		<div><label>Bank account *</label>
			<select name="account_id" id="rcAcc" class="form-select select2" style="min-width:280px" data-placeholder="Select bank account">
				<option value=""></option>
				<?php foreach ($accounts as $a): ?><option value="<?= $a->id ?>" <?= (int) $a->id === (int) $sel ? 'selected' : '' ?>><?= h($a->code . ' ' . $a->name) ?></option><?php endforeach; ?>
			</select></div>
		<div><label>Statement date *</label><input type="date" name="statement_date" id="rcDate" class="form-control" value="<?= date('Y-m-d') ?>"></div>
		<div><label>Statement closing balance *</label><input type="text" inputmode="decimal" name="statement_balance" id="rcStmt" class="form-control num" placeholder="0.00" autocomplete="off"></div>
		<div><label>Notes</label><input type="text" name="notes" class="form-control" maxlength="255" style="min-width:200px"></div>
		<div><button type="button" class="btn btn-primary" id="rcLoad">Load items</button></div>
	</div>
	<div id="rcLines"><div class="empty-row">Choose the bank account and statement date, enter the closing balance from your bank statement and press <b>Load items</b>.</div></div>
</div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	function r2(x) { return Math.round(x * 100) / 100; }
	function calc() {
		var $b = $('#rcBox'); if (!$b.length) { return; }
		var book = parseFloat($b.data('book')) || 0, stmt = App.num($('#rcStmt').val());
		var out = 0, n = 0;
		$b.find('.rc-line').each(function () {
			var on = $(this).find('input:checkbox').prop('checked');
			$(this).toggleClass('row-cleared', on);
			if (on) { n++; } else { out += parseFloat($(this).data('net')) || 0; }
		});
		var cleared = r2(book - out), diff = r2(stmt - cleared);
		$('#rcOut').text(App.money(out)); $('#rcCleared').text(App.money(cleared)); $('#rcStmtShow').text(App.money(stmt));
		$('#rcDiff').text(App.money(diff)); $('#rcCount').text(n);
		$('#rcDiffBox').toggleClass('diff-ok', Math.abs(diff) < 0.005 && n > 0).toggleClass('diff-bad', !(Math.abs(diff) < 0.005 && n > 0));
		$('#rcSave').prop('disabled', !(n > 0 && Math.abs(diff) < 0.005));
	}
	function load() {
		if (!$('#rcAcc').val()) { App.toast('Select a bank account first.', 'warning'); return; }
		$('#rcLines').addClass('loading');
		$.get(App.url('bank-reconciliation/lines'), { account: $('#rcAcc').val(), date: $('#rcDate').val() }).done(function (h) {
			$('#rcLines').removeClass('loading').html(h); calc();
		}).fail(function (x) { $('#rcLines').removeClass('loading').html('<div class="p-4 text-center text-danger">' + (x.status === 403 ? 'You do not have permission.' : 'Could not load items.') + '</div>'); });
	}
	$('#rcLoad').on('click', load);
	$('#rcAcc, #rcDate').on('change', function () { $('#rcLines').html('<div class="empty-row">Press <b>Load items</b>.</div>'); });
	$(document).on('change input', '#rcBox input:checkbox, #rcStmt', calc);
	$(document).on('change', '#rcAll', function () { $('#rcBox .rc-line input:checkbox').prop('checked', this.checked); calc(); });
	if ($('#rcAcc').val()) { load(); }
})(jQuery); });
</script>
