<div class="card">
	<div class="card-header">
		<span>Tax summary</span>
		<div class="ms-auto no-print">
			<?php if ($can_export): ?><a class="btn btn-light btn-sm" id="tsExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
			<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button>
		</div>
	</div>
	<form class="filter-bar table-filter" id="tsFilter" data-target="#tsTable">
		<div><label>Fiscal year</label>
			<select name="fy" id="tsFy" class="form-select">
				<?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" data-start="<?= $f->start_date ?>" data-end="<?= $f->end_date ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?>
			</select></div>
		<div><label>From</label><input type="date" name="from" id="tsFrom" class="form-control" value="<?= $fy ? h($fy->start_date) : '' ?>"></div>
		<div><label>To</label><input type="date" name="to" id="tsTo" class="form-control" value="<?= h($to) ?>"></div>
		<div><button type="submit" class="btn btn-primary">Show</button></div>
	</form>
	<div id="tsTable" class="ajax-table" data-url="tax-summary/data" data-filter="#tsFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#tsFy').on('change', function () {
		var o = $(this).find('option:selected'), today = new Date().toISOString().slice(0, 10);
		$('#tsFrom').val(o.data('start'));
		$('#tsTo').val(today < o.data('end') && today > o.data('start') ? today : o.data('end'));
	});
	$('#tsExport').on('click', function (e) { e.preventDefault(); window.location = App.url('tax-summary/export?' + $('#tsFilter').serialize()); });
})(jQuery); });
</script>
