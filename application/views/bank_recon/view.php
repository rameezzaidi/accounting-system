<?php $td = 0; $tc = 0; foreach ($items as $i) { $td += (float) $i->debit; $tc += (float) $i->credit; } ?>
<div class="card report-paper">
	<div class="card-header no-print">
		<span>Reconciliation #<?= (int) $r->id ?> <?= $r->status === 'active' ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-danger">Undone</span>' ?></span>
		<div class="ms-auto">
			<a class="btn btn-light btn-sm" href="<?= site_url('bank-reconciliation') ?>">Back</a>
			<button class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button>
			<?php if ($can_undo && can('bank_recon', 'void')): ?>
				<button class="btn btn-danger btn-sm" data-reason="1" data-ok="Undo" data-confirm="Undo this reconciliation? Its items become open again."
					data-action="<?= site_url('bank-reconciliation/undo/' . $r->id) ?>" data-reload="1"><?= icon('undo', 15) ?> Undo</button>
			<?php endif; ?>
		</div>
	</div>
	<div class="p-3">
		<div class="report-head">
			<h3><?= h(setting('company_name', 'Company')) ?></h3>
			<div class="t">Bank Reconciliation - <?= h($r->code . ' ' . $r->name) ?></div>
			<div class="d">Statement date <?= dmy($r->statement_date) ?> &middot; <?= h(setting('currency_code', 'PKR')) ?></div>
		</div>
		<div class="row g-3 mb-3">
			<div class="col-6 col-md-3"><div class="stat-mini"><small>Statement balance</small><b><?= fmt($r->statement_balance) ?></b></div></div>
			<div class="col-6 col-md-3"><div class="stat-mini"><small>Book balance</small><b><?= fmt($r->book_balance) ?></b></div></div>
			<div class="col-6 col-md-3"><div class="stat-mini"><small>Outstanding (book - statement)</small><b><?= fmt($r->book_balance - $r->statement_balance) ?></b></div></div>
			<div class="col-6 col-md-3"><div class="stat-mini"><small>Cleared items</small><b><?= (int) $r->item_count ?></b></div></div>
		</div>
		<p class="small text-muted">Prepared by <?= h($r->by_name) ?> on <?= dmy_time($r->created_at) ?>.<?= $r->notes ? ' Notes: ' . h($r->notes) : '' ?>
			<?php if ($r->status === 'undone'): ?><br>Undone by <?= h($r->undone_name) ?> on <?= dmy_time($r->undone_at) ?>. Reason: <?= h($r->undo_reason) ?><?php endif; ?></p>
		<?php if ($r->status === 'active'): ?>
		<div class="table-responsive">
		<table class="table table-sm">
			<thead><tr><th>Date</th><th>Voucher</th><th>Reference</th><th>Description</th><th class="num">Deposits</th><th class="num">Withdrawals</th></tr></thead>
			<tbody>
			<?php foreach ($items as $i): ?>
				<tr><td><?= dmy($i->voucher_date) ?></td><td><a href="<?= site_url('journal-vouchers/view/' . $i->voucher_id) ?>"><?= h($i->voucher_no) ?></a></td><td><?= h($i->reference) ?></td><td><?= h($i->description) ?></td>
					<td class="num"><?= fmt_blank($i->debit) ?></td><td class="num"><?= fmt_blank($i->credit) ?></td></tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot><tr class="tfoot-total"><td colspan="4" class="text-end">Total</td><td class="num"><?= fmt($td) ?></td><td class="num"><?= fmt($tc) ?></td></tr></tfoot>
		</table>
		</div>
		<?php endif; ?>
	</div>
</div>
