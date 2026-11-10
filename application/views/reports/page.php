<ul class="nav nav-pills mb-3 no-print">
	<?php foreach ($tabs as $k => $t): ?>
		<li class="nav-item"><a class="nav-link <?= $k === $which ? 'active' : '' ?>" href="<?= site_url('reports/' . $group . '/' . $k) ?>"><?= h($t) ?></a></li>
	<?php endforeach; ?>
</ul>
<div class="card">
	<div class="card-header">
		<span><?= h($title) ?></span>
		<div class="ms-auto no-print">
			<?php if ($can_export): ?><a class="btn btn-light btn-sm" id="rpExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
			<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button>
		</div>
	</div>
	<form class="filter-bar table-filter" id="rpFilter" data-target="#rpTable">
		<div><label>Fiscal year</label>
			<select name="fy" id="rpFy" class="form-select">
				<?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" data-start="<?= $f->start_date ?>" data-end="<?= $f->end_date ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?>
			</select></div>
		<?php if ($mode === 'range'): ?>
			<div><label>From</label><input type="date" name="from" id="rpFrom" class="form-control" value="<?= $fy ? h($fy->start_date) : '' ?>"></div>
			<div><label>To</label><input type="date" name="to" id="rpTo" class="form-control" value="<?= h($to) ?>"></div>
		<?php else: ?>
			<div><label>As at date</label><input type="date" name="to" id="rpTo" class="form-control" value="<?= h($to) ?>"></div>
		<?php endif; ?>
		<?php if ($which === 'day_book'): ?>
			<div><label>Voucher type</label>
				<select name="type" class="form-select"><option value="">All</option><?php foreach (array('JV', 'PV', 'RV', 'CV') as $t): ?><option value="<?= $t ?>"><?= voucher_type_name($t) ?></option><?php endforeach; ?></select></div>
		<?php endif; ?>
		<div><button type="submit" class="btn btn-primary">Show report</button></div>
	</form>
	<div id="rpTable" class="ajax-table" data-url="reports/data/<?= $which ?>" data-filter="#rpFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#rpFy').on('change', function () {
		var o = $(this).find('option:selected'), today = new Date().toISOString().slice(0, 10);
		$('#rpFrom').val(o.data('start'));
		$('#rpTo').val(today < o.data('end') && today > o.data('start') ? today : o.data('end'));
	});
	$('#rpExport').on('click', function (e) {
		e.preventDefault();
		window.location = App.url('reports/export/<?= $which ?>?' + $('#rpFilter').serialize());
	});
})(jQuery); });
</script>
