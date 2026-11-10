<?php $monthly = $p['view'] === 'monthly'; $T = $r['totals']; $ncols = $monthly ? 3 + count($r['months']) : 5; ?>
<div class="report-paper">
	<?php $this->load->view('reports/_head', array('title' => 'Statement of Expenditure', 'p' => $p, 'range' => true)); ?>
	<div class="table-responsive">
	<table class="table table-sm">
		<?php if ($monthly): ?>
		<thead><tr><th style="width:90px">Code</th><th>Account</th><?php foreach ($r['months'] as $lab): ?><th class="num"><?= h($lab) ?></th><?php endforeach; ?><th class="num">Total</th></tr></thead>
		<tbody>
		<?php foreach ($r['groups'] as $g): ?>
			<tr class="row-group"><td colspan="<?= $ncols ?>"><?= h(trim($g->code . ' ' . $g->name)) ?></td></tr>
			<?php foreach ($g->rows as $x): ?>
				<tr><td><?= h($x->code) ?></td><td><?= h($x->name) ?></td>
					<?php foreach ($r['months'] as $ym => $lab): ?><td class="num"><?= fmt_blank(isset($x->months[$ym]) ? $x->months[$ym] : 0) ?></td><?php endforeach; ?>
					<td class="num"><b><?= fmt($x->period) ?></b></td></tr>
			<?php endforeach; ?>
			<tr class="tfoot-total"><td colspan="2" class="text-end">Subtotal</td>
				<?php foreach ($r['months'] as $ym => $lab): ?><td class="num"><?= fmt_blank(isset($g->months[$ym]) ? $g->months[$ym] : 0) ?></td><?php endforeach; ?>
				<td class="num"><?= fmt($g->period) ?></td></tr>
		<?php endforeach; if (!$r['groups']): ?><tr><td colspan="<?= $ncols ?>" class="empty-row">No expenditure in this period.</td></tr><?php endif; ?>
		</tbody>
		<tfoot><tr class="tfoot-total"><td colspan="2" class="text-end">Total expenditure</td>
			<?php foreach ($r['months'] as $ym => $lab): ?><td class="num"><?= fmt_blank(isset($T['months'][$ym]) ? $T['months'][$ym] : 0) ?></td><?php endforeach; ?>
			<td class="num"><?= fmt($T['period']) ?></td></tr></tfoot>
		<?php else: ?>
		<thead><tr><th style="width:90px">Code</th><th>Account</th><th class="num" style="width:160px">This period</th><th class="num" style="width:180px">Cumulative (year to date)</th><th class="num" style="width:90px">% of period</th></tr></thead>
		<tbody>
		<?php foreach ($r['groups'] as $g): ?>
			<tr class="row-group"><td colspan="5"><?= h(trim($g->code . ' ' . $g->name)) ?></td></tr>
			<?php foreach ($g->rows as $x): ?>
				<tr><td><?= h($x->code) ?></td>
					<td><?php if (can('ledger', 'view')): ?><a href="<?= site_url('general-ledger?account=' . $x->id . '&fy=' . $p['fy']->id) ?>"><?= h($x->name) ?></a><?php else: ?><?= h($x->name) ?><?php endif; ?></td>
					<td class="num"><?= fmt($x->period) ?></td><td class="num"><?= fmt($x->cum) ?></td>
					<td class="num"><?= $T['period'] != 0 ? number_format($x->period / $T['period'] * 100, 1) : '0.0' ?></td></tr>
			<?php endforeach; ?>
			<tr class="tfoot-total"><td colspan="2" class="text-end">Subtotal <?= h($g->name) ?></td><td class="num"><?= fmt($g->period) ?></td><td class="num"><?= fmt($g->cum) ?></td>
				<td class="num"><?= $T['period'] != 0 ? number_format($g->period / $T['period'] * 100, 1) : '0.0' ?></td></tr>
		<?php endforeach; if (!$r['groups']): ?><tr><td colspan="5" class="empty-row">No expenditure in this period.</td></tr><?php endif; ?>
		</tbody>
		<tfoot><tr class="tfoot-total"><td colspan="2" class="text-end">Total expenditure</td><td class="num"><?= fmt($T['period']) ?></td><td class="num"><?= fmt($T['cum']) ?></td><td class="num">100.0</td></tr></tfoot>
		<?php endif; ?>
	</table>
	</div>
	<p class="small text-muted mt-2 mb-0">Refunds and stipend reversals reduce the expense they were charged to. Year-end closing entries are not counted.</p>
</div>
