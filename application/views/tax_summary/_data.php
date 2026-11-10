<?php $types = tax_types(); ?>
<div class="report-paper">
	<?php $this->load->view('reports/_head', array('title' => 'Tax Summary', 'p' => $p, 'range' => true)); ?>
	<div class="table-responsive">
	<table class="table table-sm">
		<thead><tr><th style="width:110px">Code</th><th>Name</th><th class="num" style="width:80px">Rate %</th><th class="num" style="width:80px">Entries</th><th class="num" style="width:160px">Base amount</th><th class="num" style="width:160px">Tax amount</th></tr></thead>
		<tbody>
		<?php foreach ($r['by'] as $t => $rows): ?>
			<tr class="row-group"><td colspan="6"><?= h($types[$t]) ?></td></tr>
			<?php foreach ($rows as $x): ?>
				<tr><td><?= h($x->code) ?></td><td><?= h($x->name) ?></td><td class="num"><?= rtrim(rtrim(number_format((float) $x->rate, 3), '0'), '.') ?></td><td class="num"><?= (int) $x->n ?></td><td class="num"><?= fmt($x->base) ?></td><td class="num"><?= fmt($x->tax) ?></td></tr>
			<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="empty-row">No entries.</td></tr><?php endif; ?>
			<tr class="tfoot-total"><td colspan="4" class="text-end">Total</td><td class="num"><?= fmt($r['base'][$t]) ?></td><td class="num"><?= fmt($r['tot'][$t]) ?></td></tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>

	<div class="row g-3 mt-1">
		<div class="col-md-6">
			<div class="stat-mini"><small>Net sales tax <?= $r['net_sales_tax'] >= 0 ? 'payable' : 'refundable' ?> (output - input)</small><b class="<?= $r['net_sales_tax'] >= 0 ? 'balance-bad' : 'balance-ok' ?>"><?= fmt(abs($r['net_sales_tax'])) ?></b></div>
		</div>
		<div class="col-md-3"><div class="stat-mini"><small>Withheld by us (to deposit)</small><b><?= fmt($r['tot']['withholding']) ?></b></div></div>
		<div class="col-md-3"><div class="stat-mini"><small>Advance tax (adjustable)</small><b><?= fmt($r['tot']['advance']) ?></b></div></div>
	</div>

	<?php if ($r['accounts']): ?>
	<h6 class="mt-4">Balance of tax accounts as at <?= dmy($p['to']) ?></h6>
	<div class="table-responsive">
	<table class="table table-sm">
		<thead><tr><th style="width:110px">Code</th><th>Account</th><th class="num" style="width:200px">Balance</th></tr></thead>
		<tbody><?php foreach ($r['accounts'] as $a): ?>
			<tr><td><?= h($a->code) ?></td><td><?= h($a->name) ?></td>
			<td class="num"><?= fmt(abs($a->balance)) ?> <?= $a->balance >= 0 ? 'Dr' : 'Cr' ?></td></tr>
		<?php endforeach; ?></tbody>
	</table>
	</div>
	<p class="small text-muted mb-0">Payments made to the tax authority (posted as a normal payment to the tax account) reduce these balances.</p>
	<?php endif; ?>
</div>
