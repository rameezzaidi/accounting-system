<div class="p-3">
	<div class="text-center mb-3"><h5 class="mb-0"><?= h(setting('company_name')) ?></h5><div class="text-muted small">Banking report &middot; <?= dmy($p['from']) ?> to <?= dmy($p['to']) ?></div></div>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Account</th><th>Type</th><th class="num">Opening</th><th class="num">Receipts</th><th class="num">Payments</th><th class="num">Closing</th><th>Last reconciled</th><th class="num">Uncleared items</th></tr></thead>
		<tbody>
		<?php foreach ($r['rows'] as $x): ?>
			<tr><td><a href="<?= site_url('general-ledger?account=' . $x->id) ?>"><?= h($x->code . ' ' . $x->name) ?></a></td><td><?= $x->kind === 'bank' ? 'Bank' : 'Cash' ?></td>
				<td class="num"><?= fmt($x->opening) ?></td><td class="num"><?= fmt($x->receipts) ?></td><td class="num"><?= fmt($x->payments) ?></td><td class="num"><b><?= fmt($x->closing) ?></b></td>
				<td><?= $x->kind === 'bank' ? ($x->last ? dmy($x->last->statement_date) : '<span class="text-muted small">never</span>') : '<span class="text-muted small">n/a</span>' ?></td>
				<td class="num"><?= $x->open_items === null ? '' : (int) $x->open_items ?></td></tr>
		<?php endforeach; if (!$r['rows']) { echo '<tr><td colspan="8" class="empty-row">No cash / bank activity in this period.</td></tr>'; } ?>
		</tbody>
		<tfoot><tr><th colspan="2" class="text-end">Total</th><th class="num"><?= fmt($r['tot']['opening']) ?></th><th class="num"><?= fmt($r['tot']['receipts']) ?></th><th class="num"><?= fmt($r['tot']['payments']) ?></th><th class="num"><?= fmt($r['tot']['closing']) ?></th><th colspan="2"></th></tr></tfoot>
	</table></div>
</div>
