<div class="alert alert-info d-flex gap-2 align-items-start py-2"><span><?= icon('info', 18) ?></span><div><?= h($L['intro']) ?></div></div>
<div class="card">
	<div class="card-header">
		<span><?= h($L['title']) ?></span>
		<div class="ms-auto"><?php if (can($module, 'add')): ?><button class="btn btn-primary btn-sm" data-modal="<?= site_url($route . '/form') ?>"><?= icon('plus', 15) ?> <?= h($L['btn']) ?></button><?php endif; ?></div>
	</div>
	<form class="filter-bar table-filter" id="rvFilter" data-target="#rvTable">
		<div><label>Search</label><input type="search" name="q" class="form-control live-search" placeholder="Voucher no, name, reason"></div>
		<div><label>From</label><input type="date" name="from" class="form-control"></div>
		<div><label>To</label><input type="date" name="to" class="form-control"></div>
		<div><label>Status</label><select name="status" class="form-select auto"><option value="">All</option><option value="posted">Posted</option><option value="void">Void</option></select></div>
		<div><button type="submit" class="btn btn-primary">Filter</button> <button type="button" class="btn btn-light btn-reset">Reset</button></div>
	</form>
	<div id="rvTable" class="ajax-table" data-url="<?= $route ?>/grid" data-filter="#rvFilter"></div>
</div>
