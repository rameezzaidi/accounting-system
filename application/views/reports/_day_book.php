<div class="report-paper">
	<?php $this->load->view('reports/_head', array('title' => $title, 'p' => $p, 'range' => true)); ?>
	<div class="table-responsive">
	<table class="table table-sm">
		<thead><tr><th>Date</th><th>Voucher</th><th>Account</th><th>Narration</th><th class="num">Debit</th><th class="num">Credit</th></tr></thead>
		<tbody>
		<?php $td = 0; $tc = 0; foreach ($r as $v): ?>
			<?php foreach ($v->lines as $i => $l): $td += $l->debit; $tc += $l->credit; ?>
				<tr>
					<td><?= $i === 0 ? dmy($v->voucher_date) : '' ?></td>
					<td><?php if ($i === 0): ?><a href="<?= site_url('journal-vouchers/view/' . $v->id) ?>"><?= h($v->voucher_no) ?></a><?php endif; ?></td>
					<td><?= h($l->account_code . ' ' . $l->account_name) ?></td>
					<td><?= h($l->description ?: ($i === 0 ? $v->narration : '')) ?></td>
					<td class="num"><?= fmt_blank($l->debit) ?></td><td class="num"><?= fmt_blank($l->credit) ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endforeach; if (!$r): ?>
			<tr><td colspan="6" class="empty-row">No vouchers in this period.</td></tr>
		<?php endif; ?>
		</tbody>
		<tfoot><tr class="tfoot-total"><td colspan="4" class="text-end">Total</td><td class="num"><?= fmt($td) ?></td><td class="num"><?= fmt($tc) ?></td></tr></tfoot>
	</table>
	</div>
</div>
