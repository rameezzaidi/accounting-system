<div class="p-3">
	<div class="text-center mb-3"><h5 class="mb-0"><?= h(setting('company_name')) ?></h5><div class="text-muted small">Budget vs actual &middot; <?= h($p['fy']->title) ?> &middot; <?= h($p['from_m']) ?> to <?= h($p['to_m']) ?></div></div>
	<?php foreach (array('income' => 'Income', 'expense' => 'Expenses') as $t => $label): $rows = $r['sections'][$t]; ?>
	<h6 class="mt-3"><?= $label ?></h6>
	<div class="table-responsive"><table class="table table-sm table-hover">
		<thead><tr><th>Account</th><th class="num">Budget</th><th class="num">Actual</th><th class="num">Variance</th><th style="width:210px">Used</th></tr></thead>
		<tbody>
		<?php foreach ($rows as $x): $over = $x->pct !== null && $x->pct > 100; ?>
			<tr><td><a href="<?= site_url('general-ledger?account=' . $x->id) ?>"><?= h($x->code . ' ' . $x->name) ?></a></td>
				<td class="num"><?= fmt($x->budget) ?></td><td class="num"><?= fmt($x->actual) ?></td>
				<td class="num <?= $x->variance < 0 ? 'text-danger' : 'text-success' ?>"><?= fmt($x->variance) ?></td>
				<td><?php if ($x->pct === null): ?><span class="text-muted small">no budget</span><?php else: ?>
					<div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar <?= ($t === 'expense' && $over) ? 'bg-danger' : ($t === 'income' ? 'bg-success' : 'bg-primary') ?>" style="width:<?= min(100, $x->pct) ?>%"></div></div><span class="small"><?= $x->pct ?>%</span></div><?php endif; ?></td></tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="empty-row">Nothing budgeted or posted.</td></tr><?php endif; ?>
		</tbody>
		<?php $tb = $r['tot'][$t]['b']; $ta = $r['tot'][$t]['a']; $tv = $t === 'income' ? $ta - $tb : $tb - $ta; ?>
		<tfoot><tr><th>Total <?= strtolower($label) ?></th><th class="num"><?= fmt($tb) ?></th><th class="num"><?= fmt($ta) ?></th><th class="num <?= $tv < 0 ? 'text-danger' : 'text-success' ?>"><?= fmt($tv) ?></th><th><?= $tb > 0 ? round($ta / $tb * 100, 1) . '%' : '' ?></th></tr></tfoot>
	</table></div>
	<?php endforeach; ?>
	<?php $nb = $r['tot']['income']['b'] - $r['tot']['expense']['b']; $na = $r['tot']['income']['a'] - $r['tot']['expense']['a']; ?>
	<div class="info-box mt-3">Planned surplus / (deficit): <b><?= fmt($nb) ?></b> &nbsp;|&nbsp; Actual surplus / (deficit): <b><?= fmt($na) ?></b> &nbsp;|&nbsp; Variance: <b><?= fmt($na - $nb) ?></b></div>
	<div class="small text-muted mt-2">Variance is shown as positive when favourable (more income, or less expense than planned). Year-end closing entries are excluded.</div>
</div>
