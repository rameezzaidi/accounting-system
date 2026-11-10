<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Payslip <?= h($r->period) ?></title>
<style>
	body { font-family: "Segoe UI", Arial, sans-serif; font-size: 13px; color: #111; margin: 0; padding: 24px; }
	.slip { max-width: 700px; margin: 0 auto 28px; border: 1px solid #999; padding: 18px; page-break-after: always; }
	.head { text-align: center; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; } .head h1 { margin: 0; font-size: 20px; } .head p { margin: 2px 0; color: #444; }
	.title { text-align: center; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin: 8px 0 12px; }
	.meta { display: flex; justify-content: space-between; line-height: 1.7; margin-bottom: 10px; }
	table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #999; padding: 6px 9px; } th { background: #eee; text-align: left; } .n { text-align: right; }
	.net td { font-weight: 700; background: #f5f5f5; font-size: 14px; } .sign { display: flex; justify-content: space-between; margin-top: 50px; } .sign div { width: 40%; text-align: center; border-top: 1px solid #111; padding-top: 5px; font-size: 12px; }
	.bar { text-align: center; margin-bottom: 14px; } .bar button { padding: 8px 20px; font-size: 14px; cursor: pointer; }
	@media print { body { padding: 0; } .bar { display: none; } .slip { border: none; margin: 0 auto; } }
</style></head><body>
<div class="bar"><button onclick="window.print()">Print</button></div>
<?php foreach ($lines as $l): ?>
<div class="slip">
	<div class="head"><img src="<?= base_url('assets/img/logo.jpg') ?>" alt="" style="height:60px;margin-bottom:4px"><br><h1><?= h(setting('company_name')) ?></h1><p><?= h(setting('company_address')) ?></p></div>
	<div class="title">Salary slip - <?= h(date('F Y', strtotime($r->period . '-01'))) ?></div>
	<div class="meta"><div><b>Employee:</b> <?= h($l->name) ?><br><b>Code:</b> <?= h($l->emp_code) ?></div><div><b>Designation:</b> <?= h($l->designation) ?><br><b>Department:</b> <?= h($l->department) ?></div></div>
	<table>
		<tr><th>Earnings</th><th class="n" style="width:130px">Amount</th><th>Deductions</th><th class="n" style="width:130px">Amount</th></tr>
		<tr><td>Basic salary</td><td class="n"><?= fmt($l->basic) ?></td><td>Deductions</td><td class="n"><?= fmt($l->deductions) ?></td></tr>
		<tr><td>Allowances</td><td class="n"><?= fmt($l->allowances) ?></td><td></td><td></td></tr>
		<tr><td>Bonus / other</td><td class="n"><?= fmt($l->bonus) ?></td><td></td><td></td></tr>
		<tr><th>Gross</th><th class="n"><?= fmt($l->gross) ?></th><th>Total deductions</th><th class="n"><?= fmt($l->deductions) ?></th></tr>
		<tr class="net"><td colspan="3">Net salary (<?= h(setting('currency_code', 'PKR')) ?>)</td><td class="n"><?= fmt($l->net) ?></td></tr>
	</table>
	<?php if ($l->remarks): ?><p>Remarks: <?= h($l->remarks) ?></p><?php endif; ?>
	<div class="sign"><div>Employee signature</div><div>Authorised signatory</div></div>
</div>
<?php endforeach; ?>
</body></html>
