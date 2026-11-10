<div class="p-3">
	<div class="table-responsive"><table class="table table-sm">
		<thead><tr><th>Asset</th><th>Category</th><th>Method</th><th class="num">Months</th><th class="num">Depreciation</th></tr></thead>
		<tbody>
		<?php foreach ($rows as $a): ?><tr><td><?= h($a->asset_no . ' ' . $a->name) ?></td><td><?= h($a->cat_name) ?></td><td><?= $a->dep_method === 'sl' ? 'SL' : 'WDV' ?> <?= rtrim(rtrim(number_format((float) $a->dep_rate, 2), '0'), '.') ?>%</td><td class="num"><?= (int) $a->months ?></td><td class="num"><?= fmt($a->amount) ?></td></tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="empty-row">Nothing to depreciate for <?= h($ym) ?>.</td></tr><?php endif; ?>
		</tbody>
		<?php if ($rows): ?><tfoot><tr><th colspan="4" class="text-end">Total</th><th class="num"><?= fmt($total) ?></th></tr></tfoot><?php endif; ?>
	</table></div>
	<?php if ($rows): ?>
	<form method="post" action="<?= site_url('depreciation/run') ?>" data-ajax class="mt-2">
		<?= csrf_field() ?><input type="hidden" name="period" value="<?= h($ym) ?>">
		<button class="btn btn-primary"><?= icon('check', 16) ?> Post depreciation for <?= h($ym) ?></button>
	</form>
	<?php endif; ?>
</div>
