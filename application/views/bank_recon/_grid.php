<div class="table-responsive">
<table class="table table-hover">
	<thead><tr><th>#</th><th>Bank account</th><th>Statement date</th><th class="num">Statement balance</th><th class="num">Items</th><th>By</th><th>Status</th><th class="text-end" style="width:110px">Actions</th></tr></thead>
	<tbody>
	<?php foreach ($rows as $r): ?>
		<tr class="<?= $r->status === 'undone' ? 'row-void' : '' ?>">
			<td><a href="<?= site_url('bank-reconciliation/view/' . $r->id) ?>"><b>#<?= (int) $r->id ?></b></a></td>
			<td><?= h($r->code . ' ' . $r->name) ?></td><td><?= dmy($r->statement_date) ?></td>
			<td class="num"><?= fmt($r->statement_balance) ?></td><td class="num"><?= (int) $r->item_count ?></td>
			<td><?= h($r->by_name) ?><div class="small text-muted"><?= dmy_time($r->created_at) ?></div></td>
			<td><?= $r->status === 'active' ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-danger">Undone</span>' ?></td>
			<td class="text-end">
				<a class="btn btn-icon" title="View" href="<?= site_url('bank-reconciliation/view/' . $r->id) ?>"><?= icon('eye', 15) ?></a>
				<?php if ($r->can_undo && can('bank_recon', 'void')): ?>
					<button class="btn btn-icon danger" title="Undo" data-reason="1" data-ok="Undo" data-confirm="Undo reconciliation #<?= (int) $r->id ?>? Its items become open again."
						data-action="<?= site_url('bank-reconciliation/undo/' . $r->id) ?>"><?= icon('undo', 15) ?></button>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty-row">No reconciliations yet.</td></tr><?php endif; ?>
	</tbody>
</table>
</div>
<div class="table-footer"><span>Showing <?= count($rows) ?> of <?= (int) $total ?></span><?= paginate_html($total, $page, $per) ?></div>
