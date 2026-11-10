<?php if (!$fy): ?>
	<div class="card empty-state"><h4>No fiscal year</h4><p class="text-muted">Create a fiscal year first.</p></div>
<?php return; endif; ?>
<form method="post" action="<?= site_url('opening-balance/save') ?>" data-ajax id="obForm">
<?= csrf_field() ?>
<input type="hidden" name="fy_id" value="<?= (int) $fy->id ?>">
<div class="card">
	<div class="card-header">
		<span>Opening balances</span>
		<div class="ms-auto">
			<select class="form-select form-select-sm" id="obFy" style="width:auto">
				<?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" <?= (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?><?= $f->is_closed ? ' (closed)' : '' ?></option><?php endforeach; ?>
			</select>
			<?php if ($can_save): ?><button type="submit" class="btn btn-primary btn-sm"><?= icon('check', 15) ?> Save opening balances</button><?php endif; ?>
		</div>
	</div>
	<div class="card-body py-2">
		<small class="text-muted">Enter the balance of each account on <b><?= dmy($fy->start_date) ?></b> in the Debit or Credit column. Total debit must equal total credit.
		<?php if (!$can_save): ?><b class="text-danger">View only<?= (int) $fy->is_closed ? ' (fiscal year closed)' : '' ?>.</b><?php endif; ?></small>
	</div>
	<div class="table-responsive">
		<table class="table table-hover" id="obTable">
			<thead><tr><th style="width:110px">Code</th><th>Account</th><th>Type</th><th class="num" style="width:180px">Debit</th><th class="num" style="width:180px">Credit</th></tr></thead>
			<tbody>
			<?php foreach ($rows as $a): ?>
				<?php if ((int) $a->is_group): ?>
					<tr class="row-group"><td><?= h($a->code) ?></td><td colspan="4"><span class="tree-indent" style="width:<?= (int) $a->level * 22 ?>px"></span><?= h($a->name) ?></td></tr>
				<?php else: ?>
					<tr>
						<td><?= h($a->code) ?></td>
						<td><span class="tree-indent" style="width:<?= (int) $a->level * 22 ?>px"></span><?= h($a->name) ?>
							<input type="hidden" name="account_id[]" value="<?= (int) $a->id ?>"></td>
						<td><?= type_badge($a->account_type) ?></td>
						<td><input type="text" inputmode="decimal" name="debit[]" class="form-control form-control-sm num ob-dr" value="<?= $a->debit > 0 ? number_format($a->debit, 2, '.', '') : '' ?>" <?= $can_save ? '' : 'readonly' ?>></td>
						<td><input type="text" inputmode="decimal" name="credit[]" class="form-control form-control-sm num ob-cr" value="<?= $a->credit > 0 ? number_format($a->credit, 2, '.', '') : '' ?>" <?= $can_save ? '' : 'readonly' ?>></td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<div class="balance-bar">
		<span>Total debit: <span id="obTd">0.00</span></span>
		<span>Total credit: <span id="obTc">0.00</span></span>
		<span id="obDiff">Difference: 0.00</span>
	</div>
</div>
</form>
