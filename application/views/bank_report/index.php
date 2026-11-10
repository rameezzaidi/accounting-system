<div class="card">
	<div class="card-header"><span>Banking report</span>
		<div class="ms-auto no-print"><?php if ($can_export): ?><a class="btn btn-light btn-sm" id="bkExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
		<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button></div></div>
	<form class="filter-bar table-filter" id="bkFilter" data-target="#bkTable">
		<div><label>Fiscal year</label><select name="fy" id="bkFy" class="form-select"><?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" data-start="<?= $f->start_date ?>" data-end="<?= $f->end_date ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?></select></div>
		<div><label>From</label><input type="date" name="from" id="bkFrom" class="form-control" value="<?= $fy ? h($fy->start_date) : '' ?>"></div>
		<div><label>To</label><input type="date" name="to" id="bkTo" class="form-control" value="<?= h($to) ?>"></div>
		<div><button type="submit" class="btn btn-primary">Show</button></div>
	</form>
	<div id="bkTable" class="ajax-table" data-url="banking-report/data" data-filter="#bkFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#bkFy').on('change', function () {
		var o = $(this).find('option:selected'), today = new Date().toISOString().slice(0, 10);
		$('#bkFrom').val(o.data('start')); $('#bkTo').val(today < o.data('end') && today > o.data('start') ? today : o.data('end'));
	});
	$('#bkExport').on('click', function (e) { e.preventDefault(); window.location = App.url('banking-report/export?' + $('#bkFilter').serialize()); });
})(jQuery); });
</script>
