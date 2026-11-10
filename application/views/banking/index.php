<div class="row g-3 mb-3">
	<div class="col-md-4"><div class="stat-mini"><small>Total cash &amp; bank<?= $to ? ' as at ' . dmy($to) : '' ?></small><b><?= fmt($total) ?></b></div></div>
	<div class="col-md-4"><div class="stat-mini"><small>Fiscal year</small><b><?= $fy ? h($fy->title) : '-' ?></b></div></div>
</div>
<div class="card">
	<div class="card-header">
		<span>Cash &amp; bank accounts</span>
		<div class="ms-auto"><?php if (can('bank_recon', 'add')): ?><a class="btn btn-primary btn-sm" href="<?= site_url('bank-reconciliation/create') ?>"><?= icon('check', 15) ?> Reconcile</a><?php endif; ?></div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover">
			<thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="num">Book balance</th><th>Last reconciled</th><th class="num">Items to clear</th><th class="text-end" style="width:200px">Actions</th></tr></thead>
			<tbody>
			<?php foreach ($rows as $a): ?>
				<tr class="<?= (int) $a->is_active ? '' : 'row-void' ?>">
					<td><?= h($a->code) ?></td><td><b><?= h($a->name) ?></b></td><td><?= type_badge($a->cash_bank) ?></td>
					<td class="num"><?= fmt($a->balance) ?></td>
					<td><?php if ($a->cash_bank !== 'bank'): ?><span class="text-muted">-</span>
						<?php elseif ($a->last): ?><?= dmy($a->last->statement_date) ?><div class="small text-muted">Statement <?= fmt($a->last->statement_balance) ?></div>
						<?php else: ?><span class="text-muted">Never</span><?php endif; ?></td>
					<td class="num"><?= $a->cash_bank === 'bank' ? (int) $a->open : '-' ?></td>
					<td class="text-end">
						<?php if (can('ledger', 'view')): ?><a class="btn btn-light btn-sm" href="<?= site_url('general-ledger?account=' . $a->id) ?>">Ledger</a><?php endif; ?>
						<?php if ($a->cash_bank === 'bank' && can('bank_recon', 'add')): ?><a class="btn btn-light btn-sm" href="<?= site_url('bank-reconciliation/create?account=' . $a->id) ?>">Reconcile</a><?php endif; ?>
					</td>
				</tr>
			<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty-row">No cash or bank accounts. Mark an account as Cash or Bank in the Chart of Accounts.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
