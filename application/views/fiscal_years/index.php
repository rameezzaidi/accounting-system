<div class="card">
	<div class="card-header">
		<span>Fiscal years</span>
		<div class="ms-auto"><?php if (can('fiscal_years', 'add')): ?><button class="btn btn-primary btn-sm" data-modal="<?= site_url('fiscal-years/form') ?>"><?= icon('plus', 15) ?> Add fiscal year</button><?php endif; ?></div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover">
			<thead><tr><th>Title</th><th>Start</th><th>End</th><th class="num">Vouchers</th><th class="num">Opening balances</th><th>Status</th><th class="text-end" style="width:150px">Actions</th></tr></thead>
			<tbody>
			<?php foreach ($rows as $r): ?>
				<tr>
					<td><b><?= h($r->title) ?></b></td><td><?= dmy($r->start_date) ?></td><td><?= dmy($r->end_date) ?></td>
					<td class="num"><?= (int) $r->vouchers ?></td><td class="num"><?= (int) $r->ob ?></td>
					<td><?= $r->is_active ? '<span class="badge badge-soft-success">Active</span> ' : '' ?><?= $r->is_closed ? '<span class="badge badge-soft-secondary">Closed</span>' : '' ?></td>
					<td class="text-end">
						<?php if (can('fiscal_years', 'edit') && !$r->is_closed): ?>
							<button class="btn btn-icon" title="Edit" data-modal="<?= site_url('fiscal-years/form/' . $r->id) ?>"><?= icon('edit', 15) ?></button>
							<?php if (!$r->is_active): ?>
								<button class="btn btn-icon" title="Make active" data-btn="btn-primary" data-ok="Make active" data-reload="1"
									data-confirm="Make <?= h($r->title) ?> the active year? New entries default to it." data-action="<?= site_url('fiscal-years/activate/' . $r->id) ?>"><?= icon('check', 15) ?></button>
								<button class="btn btn-icon danger" title="Delete" data-reload="1" data-confirm="Delete <?= h($r->title) ?>?" data-action="<?= site_url('fiscal-years/delete/' . $r->id) ?>"><?= icon('trash', 15) ?></button>
							<?php endif; ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty-row">No fiscal years yet.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
<p class="text-muted small mt-2">To close a year and carry its balances forward use <a href="<?= site_url('financial-closing') ?>">Accounts &gt; Financial Closing</a>.</p>
