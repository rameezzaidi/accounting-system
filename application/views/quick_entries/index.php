<div class="card">
	<div class="card-header">
		<span>Payments, receipts &amp; transfers</span>
		<div class="ms-auto">
			<?php if (can('quick_entries', 'add')): ?>
				<button class="btn btn-danger btn-sm" data-modal="<?= site_url('quick-entries/form/PV') ?>"><?= icon('plus', 15) ?> Payment</button>
				<button class="btn btn-success btn-sm" data-modal="<?= site_url('quick-entries/form/RV') ?>"><?= icon('plus', 15) ?> Receipt</button>
				<button class="btn btn-primary btn-sm" data-modal="<?= site_url('quick-entries/form/CV') ?>"><?= icon('swap', 15) ?> Cash / bank transfer</button>
			<?php endif; ?>
		</div>
	</div>
	<form class="filter-bar table-filter" id="qeFilter" data-target="#qeTable">
		<div><label>Search</label><input type="search" name="q" class="form-control live-search" placeholder="Voucher no, narration"></div>
		<div><label>Type</label>
			<select name="type" class="form-select auto"><option value="">All</option><option value="PV">Payments</option><option value="RV">Receipts</option><option value="CV">Transfers</option></select></div>
		<div><label>From</label><input type="date" name="from" class="form-control"></div>
		<div><label>To</label><input type="date" name="to" class="form-control"></div>
		<div><label>Status</label>
			<select name="status" class="form-select auto"><option value="">All</option><option value="posted">Posted</option><option value="void">Void</option></select></div>
		<div><button type="submit" class="btn btn-primary">Filter</button> <button type="button" class="btn btn-light btn-reset">Reset</button></div>
	</form>
	<div id="qeTable" class="ajax-table" data-url="quick-entries/grid" data-filter="#qeFilter"></div>
</div>
