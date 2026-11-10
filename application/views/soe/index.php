<div class="card">
	<div class="card-header">
		<span>Statement of Expenditure</span>
		<div class="ms-auto no-print">
			<?php if ($can_export): ?><a class="btn btn-light btn-sm" id="soeExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
			<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button>
		</div>
	</div>
	<form class="filter-bar table-filter" id="soeFilter" data-target="#soeTable">
		<div><label>Fiscal year</label>
			<select name="fy" id="soeFy" class="form-select">
				<?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" data-start="<?= $f->start_date ?>" data-end="<?= $f->end_date ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?>
			</select></div>
		<div><label>From</label><input type="date" name="from" id="soeFrom" class="form-control" value="<?= $fy ? h($fy->start_date) : '' ?>"></div>
		<div><label>To</label><input type="date" name="to" id="soeTo" class="form-control" value="<?= h($to) ?>"></div>
		<div><label>View</label>
			<select name="view" class="form-select auto"><option value="summary">Period &amp; cumulative</option><option value="monthly">Month by month</option></select></div>
		<div><button type="submit" class="btn btn-primary">Show</button></div>
	</form>
	<div id="soeTable" class="ajax-table" data-url="soe/data" data-filter="#soeFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#soeFy').on('change', function () {
		var o = $(this).find('option:selected'), today = new Date().toISOString().slice(0, 10);
		$('#soeFrom').val(o.data('start'));
		$('#soeTo').val(today < o.data('end') && today > o.data('start') ? today : o.data('end'));
	});
	$('#soeExport').on('click', function (e) { e.preventDefault(); window.location = App.url('soe/export?' + $('#soeFilter').serialize()); });
})(jQuery); });
</script>
