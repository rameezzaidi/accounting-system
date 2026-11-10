<div class="card">
	<div class="card-header"><span>Payroll report</span>
		<div class="ms-auto no-print"><?php if ($can_export): ?><a class="btn btn-light btn-sm" id="prExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
		<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button></div></div>
	<form class="filter-bar table-filter" id="prFilter" data-target="#prTable">
		<div><label>From month</label><input type="month" name="from" class="form-control" value="<?= h($from) ?>"></div>
		<div><label>To month</label><input type="month" name="to" class="form-control" value="<?= h($to) ?>"></div>
		<div><button type="submit" class="btn btn-primary">Show</button></div>
	</form>
	<div id="prTable" class="ajax-table" data-url="payroll-report/data" data-filter="#prFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#prExport').on('click', function (e) { e.preventDefault(); window.location = App.url('payroll-report/export?' + $('#prFilter').serialize()); });
})(jQuery); });
</script>
