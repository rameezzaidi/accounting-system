<div class="card">
	<div class="card-header">
		<span>Budget report</span>
		<div class="ms-auto no-print">
			<?php if ($can_export): ?><a class="btn btn-light btn-sm" id="brExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
			<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button>
		</div>
	</div>
	<form class="filter-bar table-filter" id="brFilter" data-target="#brTable">
		<div><label>Fiscal year</label>
			<select name="fy" id="brFy" class="form-select"><?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" data-start="<?= substr($f->start_date, 0, 7) ?>" data-end="<?= substr($f->end_date, 0, 7) ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?></select></div>
		<div><label>From month</label><input type="month" name="from" id="brFrom" class="form-control" value="<?= $fy ? h(substr($fy->start_date, 0, 7)) : '' ?>"></div>
		<div><label>To month</label><input type="month" name="to" id="brTo" class="form-control" value="<?= h($to) ?>"></div>
		<div><button type="submit" class="btn btn-primary">Show</button></div>
	</form>
	<div id="brTable" class="ajax-table" data-url="budget-report/data" data-filter="#brFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#brFy').on('change', function () {
		var o = $(this).find('option:selected'), now = new Date().toISOString().slice(0, 7);
		$('#brFrom').val(o.data('start')); $('#brTo').val(now < o.data('end') && now > o.data('start') ? now : o.data('end'));
	});
	$('#brExport').on('click', function (e) { e.preventDefault(); window.location = App.url('budget-report/export?' + $('#brFilter').serialize()); });
})(jQuery); });
</script>
