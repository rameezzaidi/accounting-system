<?php
$bal = function ($n) { return fmt(abs($n)) . ' ' . ($n >= 0 ? 'Dr' : 'Cr'); };
$cols = $is_group ? 7 : 6;
?>
<div class="report-paper">
	<div class="report-head">
		<h3><?= h(setting('company_name', 'Company')) ?></h3>
		<div class="t">General Ledger &mdash; <?= h($p['acc']->code . ' ' . $p['acc']->name) ?><?= $is_group ? ' (group)' : '' ?></div>
		<div class="d">Period: <?= dmy($p['from']) ?> to <?= dmy($p['to']) ?> &middot; <?= h($p['fy']->title) ?></div>
	</div>
	<div class="table-responsive">
	<table class="table table-sm">
		<thead><tr>
			<th>Date</th><th>Voucher</th><?php if ($is_group): ?><th>Account</th><?php endif; ?><th>Narration</th>
			<th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th></tr></thead>
		<tbody>
			<tr class="row-group"><td colspan="<?= $cols - 1 ?>" class="text-end">Opening balance</td><td class="num"><?= $bal($l['opening']) ?></td></tr>
			<?php foreach ($l['rows'] as $r): ?>
				<tr>
					<td><?= dmy($r->voucher_date) ?></td>
					<td><a href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><?= h($r->voucher_no) ?></a></td>
					<?php if ($is_group): ?><td><?= h($r->account_code . ' ' . $r->account_name) ?></td><?php endif; ?>
					<td><?= h($r->description !== null && $r->description !== '' ? $r->description : $r->narration) ?></td>
					<td class="num"><?= fmt_blank($r->debit) ?></td>
					<td class="num"><?= fmt_blank($r->credit) ?></td>
					<td class="num"><?= $bal($r->balance) ?></td>
				</tr>
			<?php endforeach; if (!$l['rows']): ?>
				<tr><td colspan="<?= $cols ?>" class="empty-row">No transactions in this period.</td></tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr class="tfoot-total"><td colspan="<?= $cols - 3 ?>" class="text-end">Period total</td><td class="num"><?= fmt($l['total_debit']) ?></td><td class="num"><?= fmt($l['total_credit']) ?></td><td></td></tr>
			<tr class="tfoot-total"><td colspan="<?= $cols - 1 ?>" class="text-end">Closing balance</td><td class="num"><?= $bal($l['closing']) ?></td></tr>
		</tfoot>
	</table>
	</div>
</div>
