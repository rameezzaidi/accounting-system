<div class="card">
	<div class="card-header">
		<span>Reconciliations</span>
		<div class="ms-auto"><?php if (can('bank_recon', 'add')): ?><a class="btn btn-primary btn-sm" href="<?= site_url('bank-reconciliation/create') ?>"><?= icon('plus', 15) ?> New reconciliation</a><?php endif; ?></div>
	</div>
	<form class="filter-bar table-filter" id="brFilter" data-target="#brTable">
		<div><label>Bank account</label>
			<select name="account" class="form-select auto"><option value="">All</option><?php foreach ($accounts as $a): ?><option value="<?= $a->id ?>"><?= h($a->code . ' ' . $a->name) ?></option><?php endforeach; ?></select></div>
		<div><label>Status</label><select name="status" class="form-select auto"><option value="">All</option><option value="active">Active</option><option value="undone">Undone</option></select></div>
		<div><button type="submit" class="btn btn-primary">Filter</button> <button type="button" class="btn btn-light btn-reset">Reset</button></div>
	</form>
	<div id="brTable" class="ajax-table" data-url="bank-reconciliation/grid" data-filter="#brFilter"></div>
</div>
