<div class="table-responsive">
<table class="table table-hover">
	<thead><tr><th>Date</th><th>Voucher no</th><th>Original voucher</th><th><?= h($L['party']) ?></th><th>Reason</th><th class="num">Amount</th><th>Status</th><th class="text-end" style="width:110px">Actions</th></tr></thead>
	<tbody>
	<?php foreach ($rows as $r): ?>
		<tr class="<?= $r->status === 'void' ? 'row-void' : '' ?>">
			<td><?= dmy($r->voucher_date) ?></td>
			<td><a href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><b><?= h($r->voucher_no) ?></b></a></td>
			<td><a href="<?= site_url('journal-vouchers/view/' . $r->original_voucher_id) ?>"><?= h($r->orig_no) ?></a><div class="small text-muted"><?= dmy($r->orig_date) ?></div></td>
			<td><?= h($r->party_name) ?></td>
			<td><?= h(mb_strimwidth((string) $r->reason, 0, 50, '...')) ?></td>
			<td class="num"><?= fmt($r->amount) ?></td>
			<td><?= status_badge($r->status) ?></td>
			<td class="text-end">
				<a class="btn btn-icon" title="View voucher" href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><?= icon('eye', 15) ?></a>
				<?php if ($r->status === 'posted' && can($module, 'void')): ?>
					<button class="btn btn-icon danger" title="Void" data-reason="1" data-ok="Void" data-confirm="Void <?= h($r->voucher_no) ?>? The amount becomes available again on <?= h($r->orig_no) ?>."
						data-action="<?= site_url($route . '/void/' . $r->id) ?>"><?= icon('ban', 15) ?></button>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty-row">Nothing recorded yet.</td></tr><?php endif; ?>
	</tbody>
</table>
</div>
<div class="table-footer"><span>Showing <?= count($rows) ?> of <?= (int) $total ?> record<?= $total == 1 ? '' : 's' ?></span><?= paginate_html($total, $page, $per) ?></div>
