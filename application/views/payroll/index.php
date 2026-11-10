<div class="card">
	<div class="card-header"><span>Payroll runs</span>
		<div class="ms-auto"><?php if (can('payroll', 'run')): ?><a class="btn btn-primary btn-sm" href="<?= site_url('payroll/newrun') ?>"><?= icon('plus', 15) ?> New payroll run</a><?php endif; ?></div></div>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Month</th><th>Voucher</th><th>Run date</th><th class="num">Employees</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th>Status</th><th class="text-end" style="width:60px"></th></tr></thead>
		<tbody>
		<?php foreach ($rows as $r): $void = $r->vstatus === 'void'; ?>
			<tr class="<?= $void ? 'row-void' : '' ?>">
				<td><a href="<?= site_url('payroll/view/' . $r->id) ?>"><b><?= h(date('M Y', strtotime($r->period . '-01'))) ?></b></a></td>
				<td><a href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><?= h($r->voucher_no) ?></a></td><td><?= dmy($r->run_date) ?></td>
				<td class="num"><?= (int) $r->emps ?></td><td class="num"><?= fmt($r->gross) ?></td><td class="num"><?= fmt($r->deductions) ?></td><td class="num"><b><?= fmt($r->net) ?></b></td>
				<td><?= $void ? '<span class="badge badge-soft-danger">Void</span>' : ($r->pay_voucher_id ? '<span class="badge badge-soft-success">Paid</span>' : '<span class="badge badge-soft-warning">Unpaid</span>') ?></td>
				<td class="text-end"><a class="btn btn-icon" title="Open" href="<?= site_url('payroll/view/' . $r->id) ?>"><?= icon('eye', 15) ?></a></td>
			</tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row">No payroll has been run yet.</td></tr><?php endif; ?>
		</tbody>
	</table></div>
</div>
