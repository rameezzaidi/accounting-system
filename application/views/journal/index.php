<div class="card">
	<div class="card-header">
		<span>Voucher register</span>
		<div class="ms-auto">
			<?php if (can('journal', 'add')): ?><a class="btn btn-primary btn-sm" href="<?= site_url('journal-vouchers/form') ?>"><?= icon('plus', 15) ?> New journal voucher</a><?php endif; ?>
		</div>
	</div>
	<form class="filter-bar table-filter" id="jvFilter" data-target="#jvTable">
		<div><label>Search</label><input type="search" name="q" class="form-control live-search" placeholder="Voucher no, narration, reference"></div>
		<div><label>Type</label>
			<select name="type" class="form-select auto">
				<option value="">All types</option>
				<?php foreach (array('JV', 'PV', 'RV', 'CV') as $t): ?><option value="<?= $t ?>"><?= voucher_type_name($t) ?></option><?php endforeach; ?>
			</select></div>
		<div><label>Account</label>
			<select name="account_id" class="form-select select2 auto" data-placeholder="All accounts" style="min-width:230px">
				<option value=""></option>
				<?php foreach ($accounts as $a): ?><option value="<?= $a->id ?>"><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?>
			</select></div>
		<div><label>From</label><input type="date" name="from" class="form-control"></div>
		<div><label>To</label><input type="date" name="to" class="form-control"></div>
		<div><label>Status</label>
			<select name="status" class="form-select auto"><option value="">All</option><option value="posted">Posted</option><option value="void">Void</option></select></div>
		<div><button type="submit" class="btn btn-primary">Filter</button> <button type="button" class="btn btn-light btn-reset">Reset</button></div>
	</form>
	<div id="jvTable" class="ajax-table" data-url="journal-vouchers/grid" data-filter="#jvFilter"></div>
</div>
