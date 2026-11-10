<div class="card">
	<div class="card-header">
		<span>Account ledger</span>
		<div class="ms-auto no-print">
			<?php if (can('ledger', 'export')): ?><a class="btn btn-light btn-sm" id="lgExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
			<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button>
		</div>
	</div>
	<form class="filter-bar table-filter" id="lgFilter" data-target="#lgTable">
		<div><label>Fiscal year</label>
			<select name="fy" id="lgFy" class="form-select" data-keep="1">
				<?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" data-start="<?= $f->start_date ?>" data-end="<?= $f->end_date ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?>
			</select></div>
		<div><label>Account (groups show all accounts below them)</label>
			<select name="account" class="form-select select2" data-placeholder="Select account" style="min-width:300px" data-keep="1">
				<option value=""></option>
				<?php foreach ($accounts as $a): ?>
					<option value="<?= $a->id ?>" <?= (int) $a->id === $sel ? 'selected' : '' ?>><?= str_repeat('&nbsp;&nbsp;', (int) $a->level) . h($a->code . ' - ' . $a->name) . ($a->is_group ? ' (group)' : '') ?></option>
				<?php endforeach; ?>
			</select></div>
		<div><label>From</label><input type="date" name="from" id="lgFrom" class="form-control" value="<?= h($from) ?>"></div>
		<div><label>To</label><input type="date" name="to" id="lgTo" class="form-control" value="<?= h($to) ?>"></div>
		<div><button type="submit" class="btn btn-primary">Show ledger</button></div>
	</form>
	<div id="lgTable" class="ajax-table" data-url="general-ledger/grid" data-filter="#lgFilter" data-auto="<?= $sel ? 1 : 0 ?>">
		<div class="empty-row">Select an account and press <b>Show ledger</b>.</div>
	</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	// when the fiscal year changes, reset the dates to that year
	$('#lgFy').on('change', function () {
		var o = $(this).find('option:selected'), today = new Date().toISOString().slice(0, 10);
		$('#lgFrom').val(o.data('start'));
		$('#lgTo').val(today < o.data('end') && today > o.data('start') ? today : o.data('end'));
	});
	if ($('#lgFilter [name=account]').val()) { $('#lgFilter').trigger('submit'); }   // opened from another screen with an account chosen
	$('#lgExport').on('click', function (e) {
		e.preventDefault();
		if (!$('#lgFilter [name=account]').val()) { App.toast('Select an account first.', 'warning'); return; }
		window.location = App.url('general-ledger/export?' + $('#lgFilter').serialize());
	});
})(jQuery); });
</script>
