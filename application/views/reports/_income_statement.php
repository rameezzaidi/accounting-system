<div class="report-paper">
	<?php $this->load->view('reports/_head', array('title' => $title, 'p' => $p, 'range' => true)); ?>
	<div class="table-responsive">
	<table class="table table-sm">
		<thead><tr><th style="width:110px">Code</th><th>Account</th><th class="num" style="width:180px">Amount</th></tr></thead>
		<tbody>
			<tr class="row-group"><td colspan="3">Income</td></tr>
			<?php foreach ($r['income'] as $x): ?><tr><td><?= h($x->code) ?></td><td><?= h($x->name) ?></td><td class="num"><?= fmt($x->amount) ?></td></tr><?php endforeach; ?>
			<?php if (!$r['income']): ?><tr><td colspan="3" class="empty-row">No income in this period.</td></tr><?php endif; ?>
			<tr class="tfoot-total"><td colspan="2" class="text-end">Total income</td><td class="num"><?= fmt($r['total_income']) ?></td></tr>

			<tr class="row-group"><td colspan="3">Expenses</td></tr>
			<?php foreach ($r['expense'] as $x): ?><tr><td><?= h($x->code) ?></td><td><?= h($x->name) ?></td><td class="num"><?= fmt($x->amount) ?></td></tr><?php endforeach; ?>
			<?php if (!$r['expense']): ?><tr><td colspan="3" class="empty-row">No expenses in this period.</td></tr><?php endif; ?>
			<tr class="tfoot-total"><td colspan="2" class="text-end">Total expenses</td><td class="num"><?= fmt($r['total_expense']) ?></td></tr>
		</tbody>
		<tfoot><tr class="tfoot-total"><td colspan="2" class="text-end"><?= $r['net'] >= 0 ? 'Net profit' : 'Net loss' ?></td>
			<td class="num <?= $r['net'] >= 0 ? 'balance-ok' : 'balance-bad' ?>"><?= fmt(abs($r['net'])) ?></td></tr></tfoot>
	</table>
	</div>
</div>
