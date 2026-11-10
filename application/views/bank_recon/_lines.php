<div id="rcBox" data-book="<?= h($book) ?>">
	<div class="px-3 pt-3 small text-muted">
		<?= h($c['acc']->code . ' ' . $c['acc']->name) ?> &middot; <?= h($c['fy']->title) ?> &middot; items up to <?= dmy($c['date']) ?>.
		<?php if ($c['last']): ?>Last reconciled: <?= dmy($c['last']->statement_date) ?>.<?php endif; ?>
		Tick the items that appear on your bank statement. Items you leave unticked stay open for the next reconciliation.
	</div>
	<div class="table-responsive">
	<table class="table table-hover table-sm">
		<thead><tr><th style="width:42px"><input type="checkbox" id="rcAll" class="form-check-input" title="Select all"></th><th>Date</th><th>Voucher</th><th>Reference</th><th>Description</th><th class="num">Deposits (Dr)</th><th class="num">Withdrawals (Cr)</th></tr></thead>
		<tbody>
		<?php foreach ($rows as $r): ?>
			<tr class="rc-line" data-net="<?= h(round((float) $r->debit - (float) $r->credit, 2)) ?>">
				<td><input type="checkbox" class="form-check-input" name="line[]" value="<?= (int) $r->id ?>"></td>
				<td><?= dmy($r->voucher_date) ?></td>
				<td><a href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>" target="_blank"><?= h($r->voucher_no) ?></a></td>
				<td><?= h($r->reference) ?></td>
				<td><?= h(mb_strimwidth((string) ($r->description !== null && $r->description !== '' ? $r->description : $r->narration), 0, 60, '...')) ?></td>
				<td class="num"><?= fmt_blank($r->debit) ?></td><td class="num"><?= fmt_blank($r->credit) ?></td>
			</tr>
		<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty-row">No open items up to this date.</td></tr><?php endif; ?>
		</tbody>
	</table>
	</div>
	<div class="recon-bar">
		<div class="stat-mini"><small>Book balance at <?= dmy($c['date']) ?></small><b><?= fmt($book) ?></b></div>
		<div class="stat-mini"><small>Less: unticked items</small><b id="rcOut">0.00</b></div>
		<div class="stat-mini"><small>Cleared balance</small><b id="rcCleared">0.00</b></div>
		<div class="stat-mini"><small>Statement balance</small><b id="rcStmtShow">0.00</b></div>
		<div class="stat-mini diff-bad" id="rcDiffBox"><small>Difference (<span id="rcCount">0</span> ticked)</small><b id="rcDiff">0.00</b></div>
	</div>
	<div class="p-3 text-end">
		<button type="submit" class="btn btn-primary" id="rcSave" disabled><?= icon('check', 16) ?> Save reconciliation</button>
	</div>
</div>
