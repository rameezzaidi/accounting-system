<form method="post" action="<?= site_url('financial-closing/close') ?>" data-ajax>
	<?= csrf_field() ?>
	<input type="hidden" name="fy_id" value="<?= (int) $fy->id ?>">
	<div class="modal-header">
		<h5 class="modal-title">Close <?= h($fy->title) ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	</div>
	<div class="modal-body">
		<?php foreach ($plan['blockers'] as $b): ?><div class="alert alert-danger py-2"><?= h($b) ?></div><?php endforeach; ?>

		<div class="row g-3 mb-3">
			<div class="col-md-4"><div class="stat-mini"><small>Total income</small><b><?= fmt($plan['income']) ?></b></div></div>
			<div class="col-md-4"><div class="stat-mini"><small>Total expenses</small><b><?= fmt($plan['expense']) ?></b></div></div>
			<div class="col-md-4"><div class="stat-mini"><small>Net <?= $plan['profit'] >= 0 ? 'profit' : 'loss' ?></small><b class="<?= $plan['profit'] >= 0 ? 'balance-ok' : 'balance-bad' ?>"><?= fmt(abs($plan['profit'])) ?></b></div></div>
		</div>

		<?php if ($plan['lines']): ?>
		<div class="mb-3">
			<label class="form-label">Post the <?= $plan['profit'] >= 0 ? 'profit' : 'loss' ?> to (equity account) *</label>
			<select name="re_account" class="form-select select2" data-placeholder="Select account">
				<option value=""></option>
				<?php foreach ($re as $a): ?><option value="<?= $a->id ?>" <?= (int) $a->id === (int) $default_re ? 'selected' : '' ?>><?= h($a->code . ' - ' . $a->name) ?></option><?php endforeach; ?>
			</select>
		</div>
		<details class="mb-3">
			<summary class="small text-muted">Closing journal preview (<?= count($plan['lines']) ?> accounts, dated <?= dmy($fy->end_date) ?>)</summary>
			<div class="table-responsive mt-2" style="max-height:220px;overflow:auto">
				<table class="table table-sm mb-0">
					<thead><tr><th>Account</th><th class="num">Debit</th><th class="num">Credit</th></tr></thead>
					<tbody><?php foreach ($plan['lines'] as $l): ?>
						<tr><td><?= h($l['code'] . ' ' . $l['name']) ?></td><td class="num"><?= fmt_blank($l['debit']) ?></td><td class="num"><?= fmt_blank($l['credit']) ?></td></tr>
					<?php endforeach; ?></tbody>
				</table>
			</div>
		</details>
		<?php else: ?>
			<input type="hidden" name="re_account" value="<?= (int) $default_re ?>">
			<div class="alert alert-secondary py-2">There are no income or expense balances, so no closing journal is needed. Balances will still be carried forward.</div>
		<?php endif; ?>

		<div class="alert alert-light border py-2">
			Balances will be carried to <b><?= h($plan['next_title']) ?></b> (<?= dmy($plan['next_start']) ?> to <?= dmy($plan['next_end']) ?>)<?= $plan['next'] ? '' : ' - this year will be created automatically' ?>.
		</div>
		<?php if ($plan['next'] && $plan['next_ob'] > 0): ?>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="replace_ob" value="1" id="clRepl">
				<label class="form-check-label" for="clRepl"><?= h($plan['next']->title) ?> already has <?= (int) $plan['next_ob'] ?> opening balance rows. Replace them with the carried-forward balances.</label>
			</div>
		<?php endif; ?>
		<div class="form-check">
			<input class="form-check-input" type="checkbox" name="confirm" value="1" id="clConf">
			<label class="form-check-label" for="clConf">I understand that <?= h($fy->title) ?> will be locked after closing.</label>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
		<button type="submit" class="btn btn-primary" <?= $plan['blockers'] ? 'disabled' : '' ?>><?= icon('lock', 16) ?> Close fiscal year</button>
	</div>
</form>
