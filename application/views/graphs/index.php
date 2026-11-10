<div class="card mb-3">
	<div class="card-header"><span>Graphs</span>
		<div class="ms-auto d-flex gap-2 align-items-center no-print"><label class="small text-muted mb-0">Fiscal year</label>
			<select id="gFy" class="form-select form-select-sm" style="width:auto"><?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?></select>
			<button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('print', 15) ?> Print</button></div></div>
</div>
<div class="row g-3" id="gGrid">
	<div class="col-lg-8"><div class="card h-100"><div class="card-header"><span>Income vs expense by month</span></div><div class="card-body"><div class="chart-box"><canvas id="gMonths"></canvas></div></div></div></div>
	<div class="col-lg-4"><div class="card h-100"><div class="card-header"><span>Cash &amp; bank balances</span></div><div class="card-body"><div class="chart-box"><canvas id="gCash"></canvas></div></div></div></div>
	<div class="col-lg-6"><div class="card h-100"><div class="card-header"><span>Where the money came from</span></div><div class="card-body"><div class="chart-box"><canvas id="gInc"></canvas></div></div></div></div>
	<div class="col-lg-6"><div class="card h-100"><div class="card-header"><span>Where the money went</span></div><div class="card-body"><div class="chart-box"><canvas id="gExp"></canvas></div></div></div></div>
	<div class="col-lg-6"><div class="card h-100"><div class="card-header"><span>Budget vs actual expense (monthly)</span></div><div class="card-body"><div class="chart-box"><canvas id="gBud"></canvas></div><div class="small text-muted mt-2" id="gBudNote"></div></div></div></div>
	<div class="col-lg-6"><div class="card h-100"><div class="card-header"><span>Payable to suppliers</span></div><div class="card-body"><div class="chart-box"><canvas id="gPay"></canvas></div><div class="small text-muted mt-2" id="gPayNote"></div></div></div></div>
	<div class="col-lg-6"><div class="card h-100"><div class="card-header"><span>Projects: budget vs spent</span></div><div class="card-body"><div class="chart-box"><canvas id="gProj"></canvas></div><div class="small text-muted mt-2" id="gProjNote"></div></div></div></div>
	<div class="col-lg-6"><div class="card h-100"><div class="card-header"><span>Payroll by month</span></div><div class="card-body"><div class="chart-box"><canvas id="gPr"></canvas></div><div class="small text-muted mt-2" id="gPrNote"></div></div></div></div>
</div>
