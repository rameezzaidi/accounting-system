<div class="card">
	<div class="card-header">
		<span>Accounts</span>
		<div class="ms-auto">
			<?php if (can('coa', 'export')): ?>
				<a class="btn btn-light btn-sm" id="coaExport" href="<?= site_url('chart-of-accounts/export') ?>"><?= icon('download', 15) ?> Export CSV</a>
			<?php endif; ?>
			<?php if (can('coa', 'add')): ?>
				<button class="btn btn-primary btn-sm" data-modal="<?= site_url('chart-of-accounts/form') ?>"><?= icon('plus', 15) ?> Add account</button>
			<?php endif; ?>
		</div>
	</div>
	<form class="filter-bar table-filter" id="coaFilter" data-target="#coaTable">
		<div><label>Search</label><input type="search" name="q" class="form-control live-search" placeholder="Code or name"></div>
		<div><label>Type</label>
			<select name="type" class="form-select auto">
				<option value="">All types</option>
				<?php foreach (array('asset', 'liability', 'equity', 'income', 'expense') as $t): ?><option value="<?= $t ?>"><?= ucfirst($t) ?></option><?php endforeach; ?>
			</select></div>
		<div><label>Status</label>
			<select name="status" class="form-select auto"><option value="">All</option><option value="1">Active</option><option value="0">Inactive</option></select></div>
		<div><button type="button" class="btn btn-light btn-reset">Reset</button></div>
	</form>
	<div id="coaTable" class="ajax-table" data-url="chart-of-accounts/grid" data-filter="#coaFilter"></div>
</div>
