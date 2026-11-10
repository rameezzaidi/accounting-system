<div class="report-paper">
	<?php $this->load->view('reports/_head', array('title' => $title, 'p' => $p, 'range' => true)); ?>
	<?php $t = $r['totals']; $ok = abs($t['c_dr'] - $t['c_cr']) < 0.005 && abs($t['m_dr'] - $t['m_cr']) < 0.005; ?>
	<div class="table-responsive">
	<table class="table table-sm">
		<thead>
			<tr><th rowspan="2">Code</th><th rowspan="2">Account</th><th colspan="2" class="num">Opening</th><th colspan="2" class="num">Movement</th><th colspan="2" class="num">Closing</th></tr>
			<tr><th class="num">Debit</th><th class="num">Credit</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Debit</th><th class="num">Credit</th></tr>
		</thead>
		<tbody>
		<?php foreach ($r['rows'] as $x): ?>
			<tr>
				<td><?= h($x->code) ?></td><td><?= h($x->name) ?></td>
				<td class="num"><?= fmt_blank($x->o_dr) ?></td><td class="num"><?= fmt_blank($x->o_cr) ?></td>
				<td class="num"><?= fmt_blank($x->dr) ?></td><td class="num"><?= fmt_blank($x->cr) ?></td>
				<td class="num"><?= fmt_blank($x->c_dr) ?></td><td class="num"><?= fmt_blank($x->c_cr) ?></td>
			</tr>
		<?php endforeach; if (!$r['rows']): ?>
			<tr><td colspan="8" class="empty-row">No balances or transactions in this period.</td></tr>
		<?php endif; ?>
		</tbody>
		<tfoot><tr class="tfoot-total"><td colspan="2" class="text-end">Total</td>
			<td class="num"><?= fmt($t['o_dr']) ?></td><td class="num"><?= fmt($t['o_cr']) ?></td>
			<td class="num"><?= fmt($t['m_dr']) ?></td><td class="num"><?= fmt($t['m_cr']) ?></td>
			<td class="num"><?= fmt($t['c_dr']) ?></td><td class="num"><?= fmt($t['c_cr']) ?></td></tr></tfoot>
	</table>
	</div>
	<div class="mt-2 <?= $ok ? 'balance-ok' : 'balance-bad' ?> fw-semibold"><?= $ok ? 'Trial balance is balanced.' : 'Trial balance is NOT balanced. Check the opening balances.' ?></div>
</div>
