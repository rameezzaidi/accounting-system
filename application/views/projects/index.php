<div class="card">
	<div class="card-header"><span>Projects</span>
		<div class="ms-auto"><?php if (can('projects', 'add')): ?><button class="btn btn-primary btn-sm" data-size="lg" data-modal="<?= site_url('projects/form') ?>"><?= icon('plus', 15) ?> Add project</button><?php endif; ?></div></div>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Code</th><th>Project</th><th>Duration</th><th class="num">Budget</th><th class="num">Income</th><th class="num">Spent</th><th style="width:150px">Utilisation</th><th>Status</th><th class="text-end" style="width:130px"></th></tr></thead>
		<tbody>
		<?php foreach ($rows as $p): $u = (float) $p->budget > 0 ? round($p->expense / (float) $p->budget * 100, 1) : null; ?>
			<tr class="<?= $p->status === 'closed' ? 'row-void' : '' ?>">
				<td><b><?= h($p->code) ?></b></td><td><?= h($p->name) ?><div class="small text-muted"><?= h($p->funder) ?></div></td>
				<td class="small"><?= dmy($p->start_date) ?><?= $p->end_date ? ' - ' . dmy($p->end_date) : '' ?></td>
				<td class="num"><?= fmt($p->budget) ?></td><td class="num"><?= fmt($p->income) ?></td><td class="num"><?= fmt($p->expense) ?></td>
				<td><?php if ($u === null): ?><span class="small text-muted">no budget</span><?php else: ?><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar <?= $u > 100 ? 'bg-danger' : 'bg-primary' ?>" style="width:<?= min(100, $u) ?>%"></div></div><span class="small"><?= $u ?>%</span></div><?php endif; ?></td>
				<td><?= $p->status === 'active' ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Closed</span>' ?></td>
				<td class="text-end">
					<?php if (can('pmu_report', 'view')): ?><a class="btn btn-icon" title="Statement" href="<?= site_url('project-statement?project=' . $p->id) ?>"><?= icon('file', 15) ?></a><?php endif; ?>
					<?php if (can('projects', 'edit')): ?>
						<button class="btn btn-icon" title="Edit" data-size="lg" data-modal="<?= site_url('projects/form/' . $p->id) ?>"><?= icon('edit', 15) ?></button>
						<button class="btn btn-icon" title="<?= $p->status === 'active' ? 'Close' : 'Reopen' ?>" data-btn="btn-primary" data-ok="Yes" data-reload="1" data-confirm="<?= $p->status === 'active' ? 'Close' : 'Reopen' ?> project <?= h($p->code) ?>?" data-action="<?= site_url('projects/toggle/' . $p->id) ?>"><?= icon('ban', 15) ?></button>
					<?php endif; ?>
					<?php if (can('projects', 'delete') && !(int) $p->vouchers): ?><button class="btn btn-icon danger" title="Delete" data-reload="1" data-confirm="Delete <?= h($p->code) ?>?" data-action="<?= site_url('projects/delete/' . $p->id) ?>"><?= icon('trash', 15) ?></button><?php endif; ?>
				</td>
			</tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row">No projects yet.</td></tr><?php endif; ?>
		</tbody>
	</table></div>
</div>
<div class="card mt-3"><div class="card-body small text-muted">Choose a project on a Journal Voucher, Quick Entry, supplier bill / payment or payroll run to tag it. Income and spending are taken from the income and expense lines of the tagged vouchers.</div></div>
