<div class="table-responsive">
<table class="table table-hover">
	<thead><tr><th>Date</th><th>Voucher no</th><th>Type</th><th>Narration</th><th class="num">Amount</th><th>Status</th><th>Entered by</th><th class="text-end" style="width:150px">Actions</th></tr></thead>
	<tbody>
	<?php foreach ($rows as $v): ?>
		<tr class="<?= $v->status === 'void' ? 'row-void' : '' ?>">
			<td><?= dmy($v->voucher_date) ?></td>
			<td><a href="<?= site_url('journal-vouchers/view/' . $v->id) ?>"><b><?= h($v->voucher_no) ?></b></a></td>
			<td><span class="badge badge-soft-secondary"><?= h($v->voucher_type) ?></span></td>
			<td><?= h(mb_strimwidth((string) $v->narration, 0, 70, '...')) ?><?= $v->reference ? ' <small class="text-muted">[' . h($v->reference) . ']</small>' : '' ?></td>
			<td class="num"><?= fmt($v->total_amount) ?></td>
			<td><?= status_badge($v->status) ?></td>
			<td><?= h($v->created_by_name) ?></td>
			<td class="text-end">
				<a class="btn btn-icon" title="View" href="<?= site_url('journal-vouchers/view/' . $v->id) ?>"><?= icon('eye', 15) ?></a>
				<?php if ($v->status === 'posted' && $v->voucher_type === 'JV' && can('journal', 'edit')): ?>
					<a class="btn btn-icon" title="Edit" href="<?= site_url('journal-vouchers/form/' . $v->id) ?>"><?= icon('edit', 15) ?></a>
				<?php endif; ?>
				<?php if (can('journal', 'print')): ?>
					<a class="btn btn-icon" title="Print" target="_blank" href="<?= site_url('journal-vouchers/print_voucher/' . $v->id) ?>"><?= icon('print', 15) ?></a>
				<?php endif; ?>
				<?php if ($v->status === 'posted' && can('journal', 'void')): ?>
					<button class="btn btn-icon danger" title="Void" data-reason="1" data-confirm="Void voucher <?= h($v->voucher_no) ?>? It will no longer count in the books." data-ok="Void voucher"
						data-action="<?= site_url('journal-vouchers/void/' . $v->id) ?>"><?= icon('ban', 15) ?></button>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; if (!$rows): ?>
		<tr><td colspan="8" class="empty-row">No vouchers found.</td></tr>
	<?php endif; ?>
	</tbody>
</table>
</div>
<div class="table-footer"><span>Showing <?= count($rows) ?> of <?= (int) $total ?> voucher(s)</span><?= paginate_html($total, $page, $per) ?></div>
