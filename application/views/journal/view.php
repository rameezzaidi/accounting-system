<?php $is_jv = $v->voucher_type === 'JV'; ?>
<div class="page-head">
	<div>
		<h2><?= h($v->voucher_no) ?> <?= status_badge($v->status) ?></h2>
		<div class="sub"><?= voucher_type_name($v->voucher_type) ?> &middot; <?= h($v->fy_title) ?></div>
	</div>
	<div class="d-flex gap-2 flex-wrap no-print">
		<a class="btn btn-light" href="<?= site_url('journal-vouchers') ?>">Back to list</a>
		<?php if ($v->status === 'posted' && $is_jv && can('journal', 'edit')): ?>
			<a class="btn btn-soft" href="<?= site_url('journal-vouchers/form/' . $v->id) ?>"><?= icon('edit', 16) ?> Edit</a>
		<?php endif; ?>
		<?php if (can('journal', 'print')): ?>
			<a class="btn btn-soft" target="_blank" href="<?= site_url('journal-vouchers/print_voucher/' . $v->id) ?>"><?= icon('print', 16) ?> Print</a>
		<?php endif; ?>
		<?php if ($v->status === 'posted' && can('journal', 'void')): ?>
			<button class="btn btn-danger" data-reason="1" data-ok="Void voucher" data-reload="1" data-confirm="Void voucher <?= h($v->voucher_no) ?>? It will no longer count in the books."
				data-action="<?= site_url('journal-vouchers/void/' . $v->id) ?>"><?= icon('ban', 16) ?> Void</button>
		<?php endif; ?>
	</div>
</div>

<?php if ($v->status === 'void'): ?>
	<div class="alert alert-danger">This voucher was voided on <?= dmy_time($v->voided_at) ?> by <?= h($v->voided_by_name) ?>. Reason: <?= h($v->void_reason) ?></div>
<?php endif; ?>

<div class="card mb-3">
	<div class="card-body">
		<div class="row g-3">
			<div class="col-6 col-md-3"><div class="text-muted small">Date</div><b><?= dmy($v->voucher_date) ?></b></div>
			<div class="col-6 col-md-3"><div class="text-muted small">Reference</div><b><?= h($v->reference) ?: '-' ?></b></div>
			<div class="col-6 col-md-3"><div class="text-muted small">Amount</div><b><?= fmt($v->total_amount) ?></b></div>
			<div class="col-6 col-md-3"><div class="text-muted small">Entered by</div><b><?= h($v->created_by_name) ?></b><br><small class="text-muted"><?= dmy_time($v->created_at) ?></small></div>
			<?php if (!empty($v->project_id)): $pj = $this->db->where('id', $v->project_id)->get('projects')->row(); if ($pj): ?><div class="col-12 col-md-6"><div class="text-muted small">Project</div><b><?= h($pj->code . ' - ' . $pj->name) ?></b></div><?php endif; endif; ?>
			<div class="col-12"><div class="text-muted small">Narration</div><?= h($v->narration) ?: '-' ?></div>
		</div>
	</div>
</div>

<div class="card">
	<div class="table-responsive">
		<table class="table">
			<thead><tr><th>#</th><th>Account</th><th>Description</th><th class="num">Debit</th><th class="num">Credit</th></tr></thead>
			<tbody>
			<?php $td = 0; $tc = 0; foreach ($v->lines as $i => $l): $td += $l->debit; $tc += $l->credit; ?>
				<tr><td><?= $i + 1 ?></td><td><b><?= h($l->account_code) ?></b> <?= h($l->account_name) ?></td><td><?= h($l->description) ?></td>
					<td class="num"><?= fmt_blank($l->debit) ?></td><td class="num"><?= fmt_blank($l->credit) ?></td></tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot><tr class="tfoot-total"><td colspan="3" class="text-end">Total</td><td class="num"><?= fmt($td) ?></td><td class="num"><?= fmt($tc) ?></td></tr></tfoot>
		</table>
	</div>
</div>
