<?php if (empty($d)): ?>
	<div class="empty-state card">
		<div class="empty-icon"><?= icon('calendar', 34) ?></div>
		<h4>No fiscal year found</h4>
		<p class="text-muted">Create a fiscal year first (Settings &rarr; Fiscal Years).</p>
	</div>
<?php return; endif; ?>

<div class="row g-3 mb-3">
	<div class="col-sm-6 col-xl-3">
		<div class="card stat-card"><span class="stat-icon tone-blue"><?= icon('wallet', 24) ?></span>
			<div><div class="stat-label">Cash &amp; Bank</div><div class="stat-value"><?= fmt($d['cash_total']) ?></div></div></div>
	</div>
	<div class="col-sm-6 col-xl-3">
		<div class="card stat-card"><span class="stat-icon tone-green"><?= icon('trend', 24) ?></span>
			<div><div class="stat-label">Income (this year)</div><div class="stat-value"><?= fmt($d['income']) ?></div></div></div>
	</div>
	<div class="col-sm-6 col-xl-3">
		<div class="card stat-card"><span class="stat-icon tone-red"><?= icon('swap', 24) ?></span>
			<div><div class="stat-label">Expenses (this year)</div><div class="stat-value"><?= fmt($d['expense']) ?></div></div></div>
	</div>
	<div class="col-sm-6 col-xl-3">
		<div class="card stat-card"><span class="stat-icon <?= $d['profit'] >= 0 ? 'tone-violet' : 'tone-amber' ?>"><?= icon('chart', 24) ?></span>
			<div><div class="stat-label"><?= $d['profit'] >= 0 ? 'Net profit' : 'Net loss' ?></div><div class="stat-value"><?= fmt(abs($d['profit'])) ?></div></div></div>
	</div>
</div>

<div class="row g-3 mb-3">
	<div class="col-lg-8">
		<div class="card h-100">
			<div class="card-header">Income vs Expense <span class="text-muted fw-normal ms-1">(<?= h($fy->title) ?>)</span></div>
			<div class="card-body"><canvas id="monthChart" height="110" data-months='<?= h(json_encode($d['months'])) ?>'></canvas></div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="card h-100">
			<div class="card-header">Cash &amp; Bank balances</div>
			<div class="table-responsive">
				<table class="table">
					<tbody>
					<?php foreach ($d['cash'] as $c): ?>
						<tr><td><b><?= h($c->code) ?></b> <?= h($c->name) ?><br><small class="text-muted text-capitalize"><?= h($c->type) ?></small></td>
							<td class="num"><?= fmt($c->balance) ?></td></tr>
					<?php endforeach; if (!$d['cash']): ?>
						<tr><td class="empty-row">No cash / bank accounts marked yet.</td></tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="table-footer"><span>As at <?= dmy($d['as_at']) ?></span><span>Assets <?= fmt($d['assets']) ?> &middot; Liabilities <?= fmt($d['liabilities']) ?></span></div>
		</div>
	</div>
</div>

<div class="row g-3">
	<div class="col-lg-8">
		<div class="card">
			<div class="card-header">Recent vouchers
				<?php if (can('journal', 'add')): ?><div class="ms-auto"><a class="btn btn-primary btn-sm" href="<?= site_url('journal-vouchers/form') ?>"><?= icon('plus', 15) ?> New journal voucher</a></div><?php endif; ?>
			</div>
			<div class="table-responsive">
				<table class="table table-hover">
					<thead><tr><th>Date</th><th>Voucher</th><th>Narration</th><th class="num">Amount</th><th>Status</th></tr></thead>
					<tbody>
					<?php foreach ($recent as $v): ?>
						<tr class="<?= $v->status === 'void' ? 'row-void' : '' ?>">
							<td><?= dmy($v->voucher_date) ?></td>
							<td><a href="<?= site_url('journal-vouchers/view/' . $v->id) ?>"><?= h($v->voucher_no) ?></a></td>
							<td><?= h(mb_strimwidth((string) $v->narration, 0, 60, '...')) ?></td>
							<td class="num"><?= fmt($v->total_amount) ?></td>
							<td><?= status_badge($v->status) ?></td>
						</tr>
					<?php endforeach; if (!$recent): ?>
						<tr><td colspan="5" class="empty-row">No vouchers yet. Create your first entry to see it here.</td></tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="col-lg-4">
		<div class="card h-100">
			<div class="card-header">Vouchers this year</div>
			<div class="card-body">
				<?php $names = array('JV' => 'Journal', 'PV' => 'Payment', 'RV' => 'Receipt', 'CV' => 'Contra'); $tot = 0;
				foreach ($names as $k => $n): $c = isset($d['counts'][$k]) ? $d['counts'][$k] : 0; $tot += $c; ?>
					<div class="d-flex justify-content-between py-2 border-bottom"><span><?= $n ?> vouchers</span><b><?= $c ?></b></div>
				<?php endforeach; ?>
				<div class="d-flex justify-content-between pt-2"><span class="text-muted">Total posted</span><b><?= $tot ?></b></div>
				<?php if (can('quick_entries', 'add')): ?>
				<hr>
				<div class="d-grid gap-2">
					<button class="btn btn-soft" data-modal="<?= site_url('quick-entries/form/PV') ?>"><?= icon('zap', 16) ?> Quick payment</button>
					<button class="btn btn-soft" data-modal="<?= site_url('quick-entries/form/RV') ?>"><?= icon('zap', 16) ?> Quick receipt</button>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
