<div class="report-head">
	<h3><?= h(setting('company_name', 'Company')) ?></h3>
	<div class="t"><?= h($title) ?></div>
	<div class="d"><?= $range ? 'Period: ' . dmy($p['from']) . ' to ' . dmy($p['to']) : 'As at ' . dmy($p['to']) ?> &middot; <?= h($p['fy']->title) ?> &middot; <?= h(setting('currency_code', 'PKR')) ?></div>
</div>
