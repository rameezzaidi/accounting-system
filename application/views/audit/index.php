<div class="card">
	<div class="card-header"><span>Activity log</span><small class="text-muted fw-normal">Every login, change, void and export is recorded here and cannot be edited.</small></div>
	<form class="filter-bar table-filter" id="auFilter" data-target="#auTable">
		<div><label>Search</label><input type="search" name="q" class="form-control live-search" placeholder="Description, record or user"></div>
		<div><label>User</label>
			<select name="user_id" class="form-select auto"><option value="">All users</option>
				<?php foreach ($users as $u): ?><option value="<?= $u->id ?>"><?= h($u->full_name) ?> (<?= h($u->username) ?>)</option><?php endforeach; ?></select></div>
		<div><label>Module</label>
			<select name="module" class="form-select auto"><option value="">All</option><?php foreach ($modules as $m): ?><option value="<?= h($m) ?>"><?= h($m) ?></option><?php endforeach; ?></select></div>
		<div><label>Action</label>
			<select name="action" class="form-select auto"><option value="">All</option><?php foreach ($actions as $a): ?><option value="<?= h($a) ?>"><?= h($a) ?></option><?php endforeach; ?></select></div>
		<div><label>From</label><input type="date" name="from" class="form-control"></div>
		<div><label>To</label><input type="date" name="to" class="form-control"></div>
		<div><button type="submit" class="btn btn-primary">Filter</button> <button type="button" class="btn btn-light btn-reset">Reset</button></div>
	</form>
	<div id="auTable" class="ajax-table" data-url="audit-log/grid" data-filter="#auFilter"></div>
</div>
