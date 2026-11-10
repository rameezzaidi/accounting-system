<div class="card mb-3">
	<div class="card-header">
		<span><?= h($s->code) ?> - <?= h($s->name) ?></span>
		<div class="ms-auto no-print">
			<a class="btn btn-light btn-sm" href="<?= site_url('suppliers') ?>">Back</a>
			<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button>
			<?php if ((int) $s->is_active && can('supplier_txn', 'add')): ?>
				<button class="btn btn-primary btn-sm" data-size="lg" data-modal="<?= site_url('suppliers/billform/' . $s->id) ?>"><?= icon('plus', 15) ?> New bill</button>
				<button class="btn btn-success btn-sm" data-size="lg" data-modal="<?= site_url('suppliers/payform/' . $s->id) ?>"><?= icon('check', 15) ?> Make payment</button>
			<?php endif; ?>
		</div>
	</div>
	<div class="card-body">
		<div class="row g-3">
			<div class="col-md-3"><div class="stat-mini"><small>Balance due to supplier</small><b><?= fmt($balance) ?></b></div></div>
			<div class="col-md-3"><div class="stat-mini"><small>Open bills</small><b><?= count($open) ?></b></div></div>
			<div class="col-md-6 small text-muted">
				<?= h($s->contact_person) ?> <?= h($s->phone) ?> <?= h($s->email) ?><br><?= h($s->address) ?><br>Payable account: <?= h($s->acc_code . ' ' . $s->acc_name) ?>
			</div>
		</div>
	</div>
</div>

<?php if ($open): ?>
<div class="card mb-3">
	<div class="card-header"><span>Open bills</span></div>
	<div class="table-responsive"><table class="table">
		<thead><tr><th>Date</th><th>Voucher</th><th>Invoice</th><th>Due</th><th class="num">Bill</th><th class="num">Outstanding</th><th class="no-print"></th></tr></thead>
		<tbody>
		<?php foreach ($open as $b): $od = $b->due_date && $b->due_date < date('Y-m-d'); ?>
			<tr><td><?= dmy($b->txn_date) ?></td><td><a href="<?= site_url('journal-vouchers/view/' . $b->voucher_id) ?>"><b><?= h($b->voucher_no) ?></b></a></td><td><?= h($b->ref_no) ?></td>
				<td><?= dmy($b->due_date) ?> <?= $od ? '<span class="badge badge-soft-danger">Overdue</span>' : '' ?></td>
				<td class="num"><?= fmt($b->amount) ?></td><td class="num"><b><?= fmt($b->outstanding) ?></b></td>
				<td class="text-end no-print"><?php if ((int) $s->is_active && can('supplier_txn', 'add')): ?><button class="btn btn-light btn-sm" data-size="lg" data-modal="<?= site_url('suppliers/payform/' . $s->id . '/' . $b->id) ?>">Pay</button><?php endif; ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
</div>
<?php endif; ?>

<div class="card">
	<div class="card-header"><span>Statement</span></div>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Date</th><th>Voucher</th><th>Type</th><th>Details</th><th class="num">Bill (Cr)</th><th class="num">Paid (Dr)</th><th class="num">Balance</th><th class="text-end no-print" style="width:60px"></th></tr></thead>
		<tbody>
		<?php foreach ($rows as $r): $void = $r->status === 'void'; ?>
			<tr class="<?= $void ? 'row-void' : '' ?>">
				<td><?= dmy($r->txn_date) ?></td>
				<td><a href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><b><?= h($r->voucher_no) ?></b></a></td>
				<td><?= $r->kind === 'bill' ? '<span class="badge badge-soft-warning">Bill</span>' : '<span class="badge badge-soft-success">Payment</span>' ?></td>
				<td><?= h($r->ref_no) ?> <span class="text-muted small"><?= h($r->description) ?></span></td>
				<td class="num"><?= $r->kind === 'bill' ? fmt($r->amount) : '' ?></td><td class="num"><?= $r->kind === 'payment' ? fmt($r->amount) : '' ?></td>
				<td class="num"><?= $void ? '' : fmt($r->running) ?></td>
				<td class="text-end no-print"><?php if (!$void && can('supplier_txn', 'void')): ?>
					<button class="btn btn-icon danger" title="Void" data-reason="1" data-reload="1" data-ok="Void" data-confirm="Void <?= h($r->voucher_no) ?>?" data-action="<?= site_url('suppliers/void/' . $r->id) ?>"><?= icon('ban', 15) ?></button><?php endif; ?></td>
			</tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty-row">No bills or payments yet.</td></tr><?php endif; ?>
		</tbody>
	</table></div>
</div>
