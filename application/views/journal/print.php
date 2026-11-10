<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= h($v->voucher_no) ?></title>
<style>
	body { font-family: "Segoe UI", Arial, sans-serif; font-size: 13px; color: #111; margin: 0; padding: 24px; }
	.sheet { max-width: 800px; margin: 0 auto; }
	.head { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
	.head h1 { margin: 0; font-size: 21px; } .head p { margin: 2px 0; color: #444; }
	.title { text-align: center; font-size: 16px; font-weight: 700; margin: 10px 0 14px; text-transform: uppercase; letter-spacing: .05em; }
	.meta { display: flex; justify-content: space-between; margin-bottom: 12px; } .meta div { line-height: 1.7; }
	table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #999; padding: 7px 9px; } th { background: #eee; text-align: left; }
	.n { text-align: right; } tfoot td { font-weight: 700; background: #f5f5f5; }
	.words { margin: 12px 0; } .sign { display: flex; justify-content: space-between; margin-top: 70px; }
	.sign div { width: 22%; text-align: center; border-top: 1px solid #111; padding-top: 6px; font-size: 12px; }
	.void { color: #b00; font-size: 26px; font-weight: 700; text-align: center; border: 3px solid #b00; padding: 4px; margin-bottom: 12px; }
	@media print { body { padding: 0; } .bar { display: none; } }
	.bar { text-align: center; margin-bottom: 14px; } .bar button { padding: 8px 20px; font-size: 14px; cursor: pointer; }
</style>
</head>
<body>
<div class="sheet">
	<div class="bar"><button onclick="window.print()">Print</button></div>
	<div class="head"><img src="<?= base_url('assets/img/logo.jpg') ?>" alt="" style="height:60px;margin-bottom:4px"><br>
		<h1><?= h(setting('company_name', 'Company')) ?></h1>
		<p><?= h(setting('company_address')) ?></p>
		<p><?= h(trim(setting('company_phone') . ' ' . setting('company_email'))) ?><?= setting('company_ntn') ? ' &nbsp;|&nbsp; NTN: ' . h(setting('company_ntn')) : '' ?></p>
	</div>
	<?php if ($v->status === 'void'): ?><div class="void">VOID</div><?php endif; ?>
	<div class="title"><?= voucher_type_name($v->voucher_type) ?></div>
	<div class="meta">
		<div><b>Voucher no:</b> <?= h($v->voucher_no) ?><br><b>Reference:</b> <?= h($v->reference) ?: '-' ?></div>
		<div style="text-align:right"><b>Date:</b> <?= dmy($v->voucher_date) ?><br><b>Fiscal year:</b> <?= h($v->fy_title) ?></div>
	</div>
	<table>
		<thead><tr><th style="width:30px">#</th><th>Account</th><th>Description</th><th class="n" style="width:110px">Debit</th><th class="n" style="width:110px">Credit</th></tr></thead>
		<tbody>
		<?php $td = 0; $tc = 0; foreach ($v->lines as $i => $l): $td += $l->debit; $tc += $l->credit; ?>
			<tr><td><?= $i + 1 ?></td><td><?= h($l->account_code . ' - ' . $l->account_name) ?></td><td><?= h($l->description) ?></td>
				<td class="n"><?= fmt_blank($l->debit) ?></td><td class="n"><?= fmt_blank($l->credit) ?></td></tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot><tr><td colspan="3" class="n">Total (<?= h(setting('currency_code', 'PKR')) ?>)</td><td class="n"><?= fmt($td) ?></td><td class="n"><?= fmt($tc) ?></td></tr></tfoot>
	</table>
	<div class="words"><b>Narration:</b> <?= h($v->narration) ?: '-' ?></div>
	<div class="sign"><div>Prepared by<br><small><?= h($v->created_by_name) ?></small></div><div>Checked by</div><div>Approved by</div><div>Received by</div></div>
</div>
</body>
</html>
