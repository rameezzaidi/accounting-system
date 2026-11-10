<?php $inc = $r['tot']['income']; $exp = $r['tot']['expense']; $bud = (float) $p->budget; ?>
<div class="p-3">
	<div class="text-center mb-3"><h5 class="mb-0"><?= h($p->code . ' - ' . $p->name) ?></h5><div class="text-muted small"><?= h($p->funder) ?> &middot; <?= $from || $to ? h(($from ? dmy($from) : 'start') . ' to ' . ($to ? dmy($to) : 'date')) : 'all dates' ?> &middot; <?= $p->status === 'active' ? 'Active' : 'Closed' ?></div></div>
	<div class="row g-2 mb-3">
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Project budget</small><b><?= fmt($bud) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Income received</small><b><?= fmt($inc) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small>Spent</small><b><?= fmt($exp) ?></b></div></div>
		<div class="col-6 col-md-3"><div class="stat-mini"><small><?= $bud > 0 ? 'Budget remaining' : 'Income - spent' ?></small><b><?= fmt($bud > 0 ? $bud - $exp : $inc - $exp) ?></b></div></div>
	</div>
	<?php if ($bud > 0): $u = round($exp / $bud * 100, 1); ?>
	<div class="mb-3"><div class="d-flex justify-content-between small"><span>Budget utilisation</span><span><?= $u ?>%</span></div><div class="progress" style="height:10px"><div class="progress-bar <?= $u > 100 ? 'bg-danger' : 'bg-primary' ?>" style="width:<?= min(100, $u) ?>%"></div></div></div>
	<?php endif; ?>
	<div class="row">
		<?php foreach (array('income' => 'Income by account', 'expense' => 'Spending by account') as $t => $label): ?>
		<div class="col-md-6"><h6><?= $label ?></h6>
			<table class="table table-sm"><tbody>
				<?php foreach ($r['by'][$t] as $x): ?><tr><td><?= h($x->code . ' ' . $x->name) ?></td><td class="num"><?= fmt($x->amount) ?></td></tr><?php endforeach; if (!$r['by'][$t]) { echo '<tr><td class="empty-row">None</td></tr>'; } ?>
			</tbody><tfoot><tr><th>Total</th><th class="num"><?= fmt($r['tot'][$t]) ?></th></tr></tfoot></table></div>
		<?php endforeach; ?>
	</div>
	<h6 class="mt-3">Transactions</h6>
	<div class="table-responsive"><table class="table table-sm table-hover">
		<thead><tr><th>Date</th><th>Voucher</th><th>Account</th><th>Narration</th><th class="num">Income</th><th class="num">Spent</th></tr></thead>
		<tbody>
		<?php foreach ($r['rows'] as $x): ?><tr><td><?= dmy($x->voucher_date) ?></td><td><a href="<?= site_url('journal-vouchers/view/' . $x->vid) ?>"><?= h($x->voucher_no) ?></a></td><td><?= h($x->code . ' ' . $x->name) ?></td><td class="small"><?= h($x->narration) ?></td>
			<td class="num"><?= $x->account_type === 'income' ? fmt($x->amount) : '' ?></td><td class="num"><?= $x->account_type === 'expense' ? fmt($x->amount) : '' ?></td></tr>
		<?php endforeach; if (!$r['rows']) { echo '<tr><td colspan="6" class="empty-row">No transactions are tagged to this project yet.</td></tr>'; } ?>
		</tbody>
	</table></div>
</div>
