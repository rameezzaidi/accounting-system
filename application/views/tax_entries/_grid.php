<div class="table-responsive">
<table class="table table-hover">
	<thead><tr><th>Date</th><th>Voucher</th><th>Type</th><th>Rate</th><th>Party / invoice</th><th class="num">Base amount</th><th class="num">Tax</th><th>Status</th><th class="text-end" style="width:100px">Actions</th></tr></thead>
	<tbody>
	<?php foreach ($rows as $r): ?>
		<tr class="<?= $r->status === 'void' ? 'row-void' : '' ?>">
			<td><?= dmy($r->entry_date) ?></td>
			<td><a href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><b><?= h($r->voucher_no) ?></b></a></td>
			<td><?= tax_type_badge($r->tax_type) ?></td>
			<td><?= h($r->rate_code) ?> <span class="text-muted">(<?= rtrim(rtrim(number_format((float) $r->rate, 3), '0'), '.') ?>%)</span></td>
			<td><?= h($r->party_name) ?><?php if ($r->invoice_no): ?><div class="small text-muted">Inv <?= h($r->invoice_no) ?></div><?php endif; ?></td>
			<td class="num"><?= fmt($r->base_amount) ?></td><td class="num"><b><?= fmt($r->tax_amount) ?></b></td>
			<td><?= status_badge($r->status) ?></td>
			<td class="text-end">
				<a class="btn btn-icon" title="View voucher" href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><?= icon('eye', 15) ?></a>
				<?php if ($r->status === 'posted' && can('tax_entries', 'void')): ?>
					<button class="btn btn-icon danger" title="Void" data-reason="1" data-ok="Void entry" data-confirm="Void <?= h($r->voucher_no) ?>? It will no longer count in the books or the tax summary."
						data-action="<?= site_url('tax-entries/void/' . $r->id) ?>"><?= icon('ban', 15) ?></button>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row">No tax entries found.</td></tr><?php endif; ?>
	</tbody>
</table>
</div>
<div class="table-footer"><span>Showing <?= count($rows) ?> of <?= (int) $total ?> entr<?= $total == 1 ? 'y' : 'ies' ?></span><?= paginate_html($total, $page, $per) ?></div>
