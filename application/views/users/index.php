<div class="card">
	<div class="card-header">
		<span>System users</span>
		<div class="ms-auto">
			<?php if (can('users', 'add')): ?><button class="btn btn-primary btn-sm" data-modal="<?= site_url('users/form') ?>"><?= icon('plus', 15) ?> Add user</button><?php endif; ?>
		</div>
	</div>
	<form class="filter-bar table-filter" id="usFilter" data-target="#usTable">
		<div><label>Search</label><input type="search" name="q" class="form-control live-search" placeholder="Name, username or email"></div>
		<div><label>Role</label>
			<select name="role_id" class="form-select auto"><option value="">All roles</option>
				<?php foreach ($roles as $r): ?><option value="<?= $r->id ?>"><?= h($r->name) ?></option><?php endforeach; ?></select></div>
		<div><label>Status</label>
			<select name="status" class="form-select auto"><option value="">All</option><option value="1">Active</option><option value="0">Inactive</option></select></div>
		<div><button type="button" class="btn btn-light btn-reset">Reset</button></div>
	</form>
	<div id="usTable" class="ajax-table" data-url="users/grid" data-filter="#usFilter"></div>
</div>
