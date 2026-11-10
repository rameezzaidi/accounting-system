<div class="p-3">
	<div class="text-center mb-3"><h5 class="mb-0"><?= h(setting('company_name')) ?></h5><div class="text-muted small">Payroll report &middot; <?= h($p['from']) ?> to <?= h($p['to']) ?></div></div>
	<div class="row g-2 mb-3">
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Gross</small><b><?= fmt($r['tot']['gross']) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Deductions</small><b><?= fmt($r['tot']['deductions']) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Net salaries</small><b><?= fmt($r['tot']['net']) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Paid / unpaid</small><b><?= fmt($r['tot']['paid']) ?> / <?= fmt($r['tot']['net'] - $r['tot']['paid']) ?></b></div></div>
	</div>
	<h6>Payroll runs</h6>
	<div class="table-responsive"><table class="table table-sm table-hover">
		<thead><tr><th>Month</th><th>Voucher</th><th class="num">Employees</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th>Status</th></tr></thead>
		<tbody><?php foreach ($r['runs'] as $x): ?>
			<tr><td><a href="<?= site_url('payroll/view/' . $x->id) ?>"><?= h($x->period) ?></a></td><td><?= h($x->voucher_no) ?></td><td class="num"><?= (int) $x->emps ?></td><td class="num"><?= fmt($x->gross) ?></td><td class="num"><?= fmt($x->deductions) ?></td><td class="num"><?= fmt($x->net) ?></td>
				<td><?= $x->pay_voucher_id ? '<span class="badge badge-soft-success">Paid ' . dmy($x->paid_date) . '</span>' : '<span class="badge badge-soft-warning">Unpaid</span>' ?></td></tr>
		<?php endforeach; if (!$r['runs']) { echo '<tr><td colspan="7" class="empty-row">No payroll in this period.</td></tr>'; } ?></tbody>
	</table></div>
	<h6 class="mt-4">By employee</h6>
	<div class="table-responsive"><table class="table table-sm table-hover">
		<thead><tr><th>Employee</th><th>Department</th><th class="num">Months</th><th class="num">Basic</th><th class="num">Allowances</th><th class="num">Bonus</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th></tr></thead>
		<tbody><?php foreach ($r['emps'] as $x): ?>
			<tr><td><?= h($x->name) ?> <span class="text-muted small"><?= h($x->emp_code) ?></span></td><td><?= h($x->department) ?></td><td class="num"><?= (int) $x->months ?></td><td class="num"><?= fmt($x->basic) ?></td><td class="num"><?= fmt($x->allowances) ?></td><td class="num"><?= fmt($x->bonus) ?></td><td class="num"><?= fmt($x->gross) ?></td><td class="num"><?= fmt($x->deductions) ?></td><td class="num"><b><?= fmt($x->net) ?></b></td></tr>
		<?php endforeach; if (!$r['emps']) { echo '<tr><td colspan="9" class="empty-row">No data.</td></tr>'; } ?></tbody>
	</table></div>
	<h6 class="mt-4">By department</h6>
	<div class="table-responsive"><table class="table table-sm">
		<thead><tr><th>Department</th><th class="num">Employees</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th></tr></thead>
		<tbody><?php foreach ($r['dept'] as $x): ?><tr><td><?= h($x->department) ?></td><td class="num"><?= (int) $x->emps ?></td><td class="num"><?= fmt($x->gross) ?></td><td class="num"><?= fmt($x->deductions) ?></td><td class="num"><?= fmt($x->net) ?></td></tr><?php endforeach; ?></tbody>
	</table></div>
</div>
