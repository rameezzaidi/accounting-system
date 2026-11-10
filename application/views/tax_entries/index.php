<div class="card">
	<div class="card-header">
		<span>Tax entries</span>
		<div class="ms-auto">
			<?php if (can('tax_entries', 'export')): ?><a class="btn btn-light btn-sm" id="teExport" href="#"><?= icon('download', 15) ?> Export CSV</a><?php endif; ?>
			<?php if (can('tax_entries', 'add')): ?><button class="btn btn-primary btn-sm" data-size="lg" data-modal="<?= site_url('tax-entries/form') ?>"><?= icon('plus', 15) ?> New tax entry</button><?php endif; ?>
		</div>
	</div>
	<form class="filter-bar table-filter" id="teFilter" data-target="#teTable">
		<div><label>Search</label><input type="search" name="q" class="form-control live-search" placeholder="Voucher, party, invoice"></div>
		<div><label>Type</label><select name="type" class="form-select auto"><option value="">All</option><?php foreach (tax_types() as $k => $l): ?><option value="<?= $k ?>"><?= h($l) ?></option><?php endforeach; ?></select></div>
		<div><label>From</label><input type="date" name="from" class="form-control"></div>
		<div><label>To</label><input type="date" name="to" class="form-control"></div>
		<div><label>Status</label><select name="status" class="form-select auto"><option value="">All</option><option value="posted">Posted</option><option value="void">Void</option></select></div>
		<div><button type="submit" class="btn btn-primary">Filter</button> <button type="button" class="btn btn-light btn-reset">Reset</button></div>
	</form>
	<div id="teTable" class="ajax-table" data-url="tax-entries/grid" data-filter="#teFilter"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#teExport').on('click', function (e) { e.preventDefault(); window.location = App.url('tax-entries/export?' + $('#teFilter').serialize()); });
})(jQuery); });
</script>
