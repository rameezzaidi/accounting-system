<div class="card">
	<div class="card-header"><span>Project statement</span>
		<div class="ms-auto no-print"><?php if ($can_export): ?><a class="btn btn-light btn-sm" id="pmExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
		<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button></div></div>
	<form class="filter-bar table-filter" id="pmFilter" data-target="#pmTable">
		<div><label>Project</label><select name="project" class="form-select auto"><option value="">Select project</option><?php foreach ($projects as $p): ?><option value="<?= $p->id ?>" <?= $sel === (int) $p->id ? 'selected' : '' ?>><?= h($p->code . ' - ' . $p->name) ?></option><?php endforeach; ?></select></div>
		<div><label>From</label><input type="date" name="from" class="form-control"></div>
		<div><label>To</label><input type="date" name="to" class="form-control"></div>
		<div><button type="submit" class="btn btn-primary">Show</button></div>
	</form>
	<div id="pmTable" class="ajax-table" data-url="project-statement/data" data-filter="#pmFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#pmExport').on('click', function (e) { e.preventDefault(); window.location = App.url('project-statement/export?' + $('#pmFilter').serialize()); });
})(jQuery); });
</script>
