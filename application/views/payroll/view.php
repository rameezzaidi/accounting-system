<?php $void = $r->vstatus === 'void'; ?>
<div class="card mb-3">
	<div class="card-header">
		<span>Payroll <?= h(date('F Y', strtotime($r->period . '-01'))) ?> <?= $void ? '<span class="badge badge-soft-danger">Void</span>' : ($r->pay_voucher_id ? '<span class="badge badge-soft-success">Paid</span>' : '<span class="badge badge-soft-warning">Unpaid</span>') ?></span>
		<div class="ms-auto no-print">
			<a class="btn btn-light btn-sm" href="<?= site_url('payroll') ?>">Back</a>
			<?php if (can('payroll', 'print')): ?><a class="btn btn-light btn-sm" target="_blank" href="<?= site_url('payroll/payslip/' . $r->id) ?>"><?= icon('print', 15) ?> All payslips</a><?php endif; ?>
			<?php if (!$void && !$r->pay_voucher_id && can('payroll', 'pay') && (float) $r->net > 0): ?><button class="btn btn-success btn-sm" data-modal="<?= site_url('payroll/payform/' . $r->id) ?>"><?= icon('check', 15) ?> Pay salaries</button><?php endif; ?>
			<?php if (!$void && $r->pay_voucher_id && can('payroll', 'void')): ?><button class="btn btn-light btn-sm text-danger" data-reason="1" data-reload="1" data-ok="Void payment" data-confirm="Void the salary payment <?= h($r->pay_no) ?>?" data-action="<?= site_url('payroll/voidpay/' . $r->id) ?>">Void payment</button><?php endif; ?>
			<?php if (!$void && !$r->pay_voucher_id && can('payroll', 'void')): ?><button class="btn btn-light btn-sm text-danger" data-reason="1" data-reload="1" data-ok="Void payroll" data-confirm="Void payroll <?= h($r->voucher_no) ?>?" data-action="<?= site_url('payroll/voidrun/' . $r->id) ?>">Void payroll</button><?php endif; ?>
		</div>
	</div>
	<div class="card-body"><div class="row g-3">
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Gross</small><b><?= fmt($r->gross) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Deductions</small><b><?= fmt($r->deductions) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Net payable</small><b><?= fmt($r->net) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Employees</small><b><?= count($lines) ?></b></div></div>
		<div class="col-md-4 small"><span class="text-muted">Voucher</span> <a href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><b><?= h($r->voucher_no) ?></b></a> on <?= dmy($r->run_date) ?></div>
		<div class="col-md-4 small"><?php if ($r->pay_voucher_id): ?><span class="text-muted">Paid</span> <a href="<?= site_url('journal-vouchers/view/' . $r->pay_voucher_id) ?>"><b><?= h($r->pay_no) ?></b></a> on <?= dmy($r->paid_date) ?><?php endif; ?></div>
		<div class="col-md-4 small"><?php if ($r->project_code): ?><span class="text-muted">Project</span> <b><?= h($r->project_code . ' ' . $r->project_name) ?></b><?php endif; ?></div>
		<?php if ($r->notes): ?><div class="col-12 small text-muted"><?= h($r->notes) ?></div><?php endif; ?>
	</div></div>
</div>
<div class="card">
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Employee</th><th class="num">Basic</th><th class="num">Allowances</th><th class="num">Bonus</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th class="no-print" style="width:60px"></th></tr></thead>
		<tbody>
		<?php foreach ($lines as $l): ?>
			<tr><td><?= h($l->name) ?><div class="small text-muted"><?= h($l->emp_code . ' ' . $l->designation) ?></div></td>
				<td class="num"><?= fmt($l->basic) ?></td><td class="num"><?= fmt($l->allowances) ?></td><td class="num"><?= fmt($l->bonus) ?></td><td class="num"><?= fmt($l->gross) ?></td><td class="num"><?= fmt($l->deductions) ?></td><td class="num"><b><?= fmt($l->net) ?></b></td>
				<td class="text-end no-print"><?php if (can('payroll', 'print')): ?><a class="btn btn-icon" title="Payslip" target="_blank" href="<?= site_url('payroll/payslip/' . $r->id . '/' . $l->id) ?>"><?= icon('print', 15) ?></a><?php endif; ?></td></tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot><tr><th>Total</th><th colspan="3"></th><th class="num"><?= fmt($r->gross) ?></th><th class="num"><?= fmt($r->deductions) ?></th><th class="num"><?= fmt($r->net) ?></th><th class="no-print"></th></tr></tfoot>
	</table></div>
</div>
