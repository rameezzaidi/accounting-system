<div class="card">
	<div class="card-header">
		<span>Vendor report</span>
		<div class="ms-auto no-print">
			<?php if ($can_export): ?><a class="btn btn-light btn-sm" id="vrExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
			<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button>
		</div>
	</div>
	<form class="filter-bar table-filter" id="vrFilter" data-target="#vrTable">
		<div><label>Fiscal year</label>
			<select name="fy" id="vrFy" class="form-select"><?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" data-start="<?= $f->start_date ?>" data-end="<?= $f->end_date ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?></select></div>
		<div><label>From</label><input type="date" name="from" id="vrFrom" class="form-control" value="<?= $fy ? h($fy->start_date) : '' ?>"></div>
		<div><label>To</label><input type="date" name="to" id="vrTo" class="form-control" value="<?= h($to) ?>"></div>
		<div><label>Supplier</label><select name="supplier" class="form-select auto"><option value="">All suppliers</option><?php foreach ($suppliers as $s): ?><option value="<?= $s->id ?>"><?= h($s->name) ?></option><?php endforeach; ?></select></div>
		<div><button type="submit" class="btn btn-primary">Show</button></div>
	</form>
	<div id="vrTable" class="ajax-table" data-url="vendor-report/data" data-filter="#vrFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#vrFy').on('change', function () {
		var o = $(this).find('option:selected'), today = new Date().toISOString().slice(0, 10);
		$('#vrFrom').val(o.data('start')); $('#vrTo').val(today < o.data('end') && today > o.data('start') ? today : o.data('end'));
	});
	$('#vrExport').on('click', function (e) { e.preventDefault(); window.location = App.url('vendor-report/export?' + $('#vrFilter').serialize()); });
})(jQuery); });
</script>
