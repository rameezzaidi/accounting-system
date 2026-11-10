<div class="report-paper">
	<?php $this->load->view('reports/_head', array('title' => $title, 'p' => $p, 'range' => false)); ?>
	<div class="row g-4">
		<div class="col-lg-6">
			<table class="table table-sm">
				<thead><tr><th colspan="3">Assets</th></tr></thead>
				<tbody>
				<?php foreach ($r['assets'] as $x): ?><tr><td style="width:90px"><?= h($x->code) ?></td><td><?= h($x->name) ?></td><td class="num"><?= fmt($x->amount) ?></td></tr><?php endforeach; ?>
				<?php if (!$r['assets']): ?><tr><td colspan="3" class="empty-row">No asset balances.</td></tr><?php endif; ?>
				</tbody>
				<tfoot><tr class="tfoot-total"><td colspan="2" class="text-end">Total assets</td><td class="num"><?= fmt($r['total_assets']) ?></td></tr></tfoot>
			</table>
		</div>
		<div class="col-lg-6">
			<table class="table table-sm">
				<thead><tr><th colspan="3">Liabilities</th></tr></thead>
				<tbody>
				<?php foreach ($r['liabilities'] as $x): ?><tr><td style="width:90px"><?= h($x->code) ?></td><td><?= h($x->name) ?></td><td class="num"><?= fmt($x->amount) ?></td></tr><?php endforeach; ?>
				<?php if (!$r['liabilities']): ?><tr><td colspan="3" class="empty-row">No liability balances.</td></tr><?php endif; ?>
				<tr class="tfoot-total"><td colspan="2" class="text-end">Total liabilities</td><td class="num"><?= fmt($r['total_liabilities']) ?></td></tr>
				</tbody>
				<thead><tr><th colspan="3">Equity</th></tr></thead>
				<tbody>
				<?php foreach ($r['equity'] as $x): ?><tr><td><?= h($x->code) ?></td><td><?= h($x->name) ?></td><td class="num"><?= fmt($x->amount) ?></td></tr><?php endforeach; ?>
				<tr><td></td><td>Current period profit / (loss)</td><td class="num"><?= fmt($r['profit']) ?></td></tr>
				<tr class="tfoot-total"><td colspan="2" class="text-end">Total equity</td><td class="num"><?= fmt($r['total_equity'] + $r['profit']) ?></td></tr>
				</tbody>
				<tfoot><tr class="tfoot-total"><td colspan="2" class="text-end">Total liabilities &amp; equity</td><td class="num"><?= fmt($r['total_le']) ?></td></tr></tfoot>
			</table>
		</div>
	</div>
	<?php if (abs($r['difference']) >= 0.005): ?>
		<div class="alert alert-warning mt-3 mb-0">Assets and liabilities + equity differ by <b><?= fmt(abs($r['difference'])) ?></b>. This normally means the opening balances are not balanced.</div>
	<?php endif; ?>
</div>
