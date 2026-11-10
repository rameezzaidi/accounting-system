<div class="p-3">
	<div class="text-center mb-3"><h5 class="mb-0"><?= h(setting('company_name')) ?></h5><div class="text-muted small">Vendor report &middot; <?= dmy($p['from']) ?> to <?= dmy($p['to']) ?></div></div>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Code</th><th>Supplier</th><th class="num">Opening</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Closing balance</th></tr></thead>
		<tbody>
		<?php foreach ($r['rows'] as $x): ?>
			<tr><td><?= h($x->code) ?></td><td><a href="<?= site_url('suppliers/statement/' . $x->id) ?>"><?= h($x->name) ?></a></td>
				<td class="num"><?= fmt($x->opening) ?></td><td class="num"><?= fmt($x->billed) ?></td><td class="num"><?= fmt($x->paid) ?></td><td class="num"><b><?= fmt($x->closing) ?></b></td></tr>
		<?php endforeach; if (!$r['rows']) { echo '<tr><td colspan="6" class="empty-row">No supplier activity in this period.</td></tr>'; } ?>
		</tbody>
		<tfoot><tr><th colspan="2" class="text-end">Total</th><th class="num"><?= fmt($r['tot']['opening']) ?></th><th class="num"><?= fmt($r['tot']['billed']) ?></th><th class="num"><?= fmt($r['tot']['paid']) ?></th><th class="num"><?= fmt($r['tot']['closing']) ?></th></tr></tfoot>
	</table></div>

	<h6 class="mt-4">Ageing of unpaid bills as at <?= dmy($p['to']) ?></h6>
	<div class="row g-2 mb-3">
		<?php foreach ($r['age'] as $k => $v): ?><div class="col-6 col-md"><div class="stat-mini"><small><?= h($k) ?></small><b><?= fmt($v) ?></b></div></div><?php endforeach; ?>
	</div>
	<?php if ($r['bills']): ?>
	<div class="table-responsive"><table class="table table-sm">
		<thead><tr><th>Supplier</th><th>Voucher</th><th>Invoice</th><th>Bill date</th><th>Due</th><th class="num">Bill</th><th class="num">Outstanding</th><th>Ageing</th></tr></thead>
		<tbody><?php foreach ($r['bills'] as $b): ?>
			<tr><td><?= h($b->supplier_name) ?></td><td><a href="<?= site_url('journal-vouchers/view/' . $b->voucher_id) ?>"><?= h($b->voucher_no) ?></a></td><td><?= h($b->ref_no) ?></td>
				<td><?= dmy($b->txn_date) ?></td><td><?= dmy($b->due_date) ?></td><td class="num"><?= fmt($b->amount) ?></td><td class="num"><b><?= fmt($b->outstanding) ?></b></td><td><?= h($b->bucket) ?></td></tr>
		<?php endforeach; ?></tbody>
	</table></div>
	<?php endif; ?>

	<div class="info-box mt-3">Supplier sub-ledger total: <b><?= fmt($r['sub']) ?></b> &nbsp;|&nbsp; Ledger balance of payable accounts used by suppliers: <b><?= fmt($r['gl']) ?></b>
		<?php if (abs($r['sub'] - $r['gl']) > 0.004): ?> &nbsp;<span class="badge badge-soft-warning">Difference <?= fmt($r['sub'] - $r['gl']) ?></span> <span class="small text-muted">(manual journal entries on the payable account, or opening balances)</span>
		<?php else: ?> &nbsp;<span class="badge badge-soft-success">Agrees</span><?php endif; ?></div>
</div>
