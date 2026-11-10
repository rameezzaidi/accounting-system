<div class="table-responsive">
<table class="table table-hover">
	<thead><tr><th>Date</th><th>Voucher no</th><th>Type</th><th>Cash / bank</th><th>Other account</th><th>Narration</th><th class="num">Amount</th><th>Status</th><th class="text-end" style="width:120px">Actions</th></tr></thead>
	<tbody>
	<?php foreach ($rows as $v):
		// cash/bank side = the first line that sits on a cash/bank account
		$main = null; $other = null;
		foreach ($v->lines as $l) {
			$is_main = ($v->voucher_type === 'PV') ? ((float) $l->credit > 0) : (($v->voucher_type === 'RV') ? ((float) $l->debit > 0) : ((float) $l->credit > 0));
			if ($is_main) { $main = $l; } else { $other = $l; }
		}
		$colors = array('PV' => 'danger', 'RV' => 'success', 'CV' => 'primary');
		$names = array('PV' => 'Payment', 'RV' => 'Receipt', 'CV' => 'Transfer'); ?>
		<tr class="<?= $v->status === 'void' ? 'row-void' : '' ?>">
			<td><?= dmy($v->voucher_date) ?></td>
			<td><a href="<?= site_url('journal-vouchers/view/' . $v->id) ?>"><b><?= h($v->voucher_no) ?></b></a></td>
			<td><span class="badge badge-soft-<?= $colors[$v->voucher_type] ?>"><?= $names[$v->voucher_type] ?></span></td>
			<td><?= $main ? h($main->account_code . ' ' . $main->account_name) : '' ?></td>
			<td><?= $other ? h($other->account_code . ' ' . $other->account_name) : '' ?></td>
			<td><?= h(mb_strimwidth((string) $v->narration, 0, 45, '...')) ?></td>
			<td class="num"><?= fmt($v->total_amount) ?></td>
			<td><?= status_badge($v->status) ?></td>
			<td class="text-end">
				<a class="btn btn-icon" title="View" href="<?= site_url('journal-vouchers/view/' . $v->id) ?>"><?= icon('eye', 15) ?></a>
				<?php if (can('journal', 'print')): ?><a class="btn btn-icon" title="Print" target="_blank" href="<?= site_url('journal-vouchers/print_voucher/' . $v->id) ?>"><?= icon('print', 15) ?></a><?php endif; ?>
				<?php if ($v->status === 'posted' && can('quick_entries', 'void')): ?>
					<button class="btn btn-icon danger" title="Void" data-reason="1" data-ok="Void entry" data-confirm="Void <?= h($v->voucher_no) ?>? It will no longer count in the books."
						data-action="<?= site_url('quick-entries/void/' . $v->id) ?>"><?= icon('ban', 15) ?></button>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; if (!$rows): ?>
		<tr><td colspan="9" class="empty-row">No quick entries found.</td></tr>
	<?php endif; ?>
	</tbody>
</table>
</div>
<div class="table-footer"><span>Showing <?= count($rows) ?> of <?= (int) $total ?> entr<?= $total == 1 ? 'y' : 'ies' ?></span><?= paginate_html($total, $page, $per) ?></div>
