<div class="alert alert-info d-flex gap-2 align-items-start">
	<span><?= icon('info', 18) ?></span>
	<div>
		<b>What closing a year does:</b> it posts one closing journal that moves the year's income and expenses into the profit / loss (retained earnings) account,
		carries every asset, liability and equity balance into the next year as opening balances, and locks the year (no new entries, edits or voids).
		Income statements of a closed year still show the full-year profit. A year can be reopened by an authorised user; the closing journal is then voided.
	</div>
</div>
<div class="card">
	<div class="card-header"><span>Fiscal years</span></div>
	<div class="table-responsive">
		<table class="table table-hover">
			<thead><tr><th>Fiscal year</th><th>Period</th><th class="num">Income</th><th class="num">Expenses</th><th class="num">Net profit / (loss)</th><th>Status</th><th>Closing journal</th><th class="text-end" style="width:170px">Actions</th></tr></thead>
			<tbody>
			<?php foreach ($rows as $r): ?>
				<tr>
					<td><b><?= h($r->title) ?></b><?= $r->is_active ? ' <span class="badge badge-soft-success">Active</span>' : '' ?></td>
					<td><?= dmy($r->start_date) ?> to <?= dmy($r->end_date) ?></td>
					<td class="num"><?= fmt($r->income) ?></td>
					<td class="num"><?= fmt($r->expense) ?></td>
					<td class="num <?= $r->net >= 0 ? 'balance-ok' : 'balance-bad' ?>"><?= $r->net >= 0 ? fmt($r->net) : '(' . fmt(-$r->net) . ')' ?></td>
					<td>
						<?php if ($r->is_closed): ?>
							<span class="badge badge-soft-secondary">Closed</span>
							<div class="small text-muted"><?= dmy_time($r->closed_at) ?><?= $r->closed_by_name ? ' by ' . h($r->closed_by_name->full_name) : '' ?></div>
						<?php else: ?><span class="badge badge-soft-primary">Open</span><?php endif; ?>
					</td>
					<td>
						<?php if ($r->closing_voucher_id && $r->closing_no): ?>
							<a href="<?= site_url('journal-vouchers/view/' . $r->closing_voucher_id) ?>"><?= h($r->closing_no->voucher_no) ?></a>
						<?php elseif ($r->is_closed): ?><span class="text-muted">None needed</span><?php else: ?><span class="text-muted">-</span><?php endif; ?>
						<?php if ($r->is_closed && $r->next): ?><div class="small text-muted">Carried to <?= h($r->next->title) ?></div><?php endif; ?>
					</td>
					<td class="text-end">
						<?php if (!$r->is_closed && can('financial_closing', 'close')): ?>
							<button class="btn btn-primary btn-sm" data-size="lg" data-modal="<?= site_url('financial-closing/form/' . $r->id) ?>"><?= icon('lock', 14) ?> Close year</button>
						<?php endif; ?>
						<?php if ($r->is_closed && can('financial_closing', 'reopen')): ?>
							<button class="btn btn-light btn-sm" data-reason="1" data-reload="1" data-ok="Reopen year" data-btn="btn-primary"
								data-confirm="Reopen <?= h($r->title) ?>? The closing journal will be voided and the carried-forward opening balances of the next year removed."
								data-action="<?= site_url('financial-closing/reopen/' . $r->id) ?>"><?= icon('undo', 14) ?> Reopen</button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty-row">No fiscal years yet.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
