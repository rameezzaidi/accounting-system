<form method="post" action="<?= site_url('payroll/save') ?>" data-ajax id="prForm">
	<?= csrf_field() ?><input type="hidden" name="payload" id="prPayload">
	<div class="card mb-3">
		<div class="card-header"><span>New payroll run</span><div class="ms-auto"><a class="btn btn-light btn-sm" href="<?= site_url('payroll') ?>">Cancel</a> <button class="btn btn-primary btn-sm"><?= icon('check', 15) ?> Post payroll</button></div></div>
		<div class="card-body"><div class="row g-3">
			<div class="col-md-3"><label class="form-label">Salary month *</label><input type="month" name="period" class="form-control" value="<?= date('Y-m') ?>"></div>
			<div class="col-md-3"><label class="form-label">Posting date *</label><input type="date" name="run_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
			<?= project_select(0, 'col-md-6') ?>
			<div class="col-md-4"><label class="form-label">Salary expense account *</label><select name="expense_account_id" class="form-select select2"><option value=""></option><?php foreach ($expenses as $a): ?><option value="<?= $a->id ?>" <?= (int) $def['expense'] === (int) $a->id ? 'selected' : '' ?>><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?></select></div>
			<div class="col-md-4"><label class="form-label">Salaries payable account *</label><select name="payable_account_id" class="form-select select2"><option value=""></option><?php foreach ($liabilities as $a): ?><option value="<?= $a->id ?>" <?= (int) $def['payable'] === (int) $a->id ? 'selected' : '' ?>><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?></select></div>
			<div class="col-md-4"><label class="form-label">Deductions payable account *</label><select name="deduction_account_id" class="form-select select2"><option value=""></option><?php foreach ($liabilities as $a): ?><option value="<?= $a->id ?>" <?= (int) $def['deduction'] === (int) $a->id ? 'selected' : '' ?>><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?></select></div>
			<div class="col-12"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" maxlength="255"></div>
		</div></div>
	</div>
	<div class="card">
		<div class="table-responsive"><table class="table table-sm align-middle" id="prTable">
			<thead><tr><th style="width:36px"><input type="checkbox" id="prAll" checked></th><th>Employee</th><th class="num">Basic</th><th class="num">Allowances</th><th class="num" style="width:120px">Bonus</th><th class="num">Gross</th><th class="num" style="width:120px">Deductions</th><th class="num">Net</th></tr></thead>
			<tbody>
			<?php foreach ($emps as $e): ?>
				<tr data-id="<?= $e->id ?>" data-fixed="<?= h((float) $e->basic_salary + (float) $e->allowances) ?>">
					<td><input type="checkbox" class="pr-on" checked></td><td><?= h($e->name) ?><div class="small text-muted"><?= h($e->emp_code) ?> <?= h($e->designation) ?></div></td>
					<td class="num"><?= fmt($e->basic_salary) ?></td><td class="num"><?= fmt($e->allowances) ?></td>
					<td><input type="text" inputmode="decimal" class="form-control form-control-sm num pr-bonus" value="0"></td>
					<td class="num pr-gross"></td>
					<td><input type="text" inputmode="decimal" class="form-control form-control-sm num pr-ded" value="<?= h((float) $e->fixed_deductions) ?>"></td>
					<td class="num pr-net"></td>
				</tr>
			<?php endforeach; if (!$emps): ?><tr><td colspan="8" class="empty-row">No active employees. Add employees first.</td></tr><?php endif; ?>
			</tbody>
			<tfoot><tr><th colspan="5" class="text-end">Total</th><th class="num" id="prG">0.00</th><th class="num" id="prD">0.00</th><th class="num" id="prN">0.00</th></tr></tfoot>
		</table></div>
	</div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	function calc() {
		var G = 0, D = 0, N = 0;
		$('#prTable tbody tr[data-id]').each(function () {
			var $r = $(this), g = parseFloat($r.data('fixed')) + App.num($r.find('.pr-bonus').val()), d = App.num($r.find('.pr-ded').val());
			$r.find('.pr-gross').text(App.money(g)); $r.find('.pr-net').text(App.money(g - d));
			if ($r.find('.pr-on').is(':checked')) { G += g; D += d; N += g - d; }
			$r.toggleClass('text-muted', !$r.find('.pr-on').is(':checked'));
		});
		$('#prG').text(App.money(G)); $('#prD').text(App.money(D)); $('#prN').text(App.money(N));
	}
	$('#prTable').on('input change', 'input', calc);
	$('#prAll').on('change', function () { $('.pr-on').prop('checked', this.checked); calc(); });
	$('#prForm').on('submit', function () {
		var d = {};
		$('#prTable tbody tr[data-id]').each(function () { var $r = $(this); if ($r.find('.pr-on').is(':checked')) { d[$r.data('id')] = { bonus: $r.find('.pr-bonus').val(), deductions: $r.find('.pr-ded').val() }; } });
		$('#prPayload').val(JSON.stringify(d));
	});
	calc();
})(jQuery); });
</script>
