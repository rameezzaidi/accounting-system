<?php if (can('depreciation', 'run')): ?>
<div class="card mb-3">
	<div class="card-header"><span>Run depreciation</span></div>
	<form class="filter-bar" id="depForm" onsubmit="return false">
		<div><label>Month</label><input type="month" id="depMonth" class="form-control" value="<?= date('Y-m') ?>" max="<?= date('Y-m') ?>"></div>
		<div><button type="button" class="btn btn-primary" id="depShow">Calculate</button></div>
		<div class="small text-muted align-self-center">Months that were skipped are caught up automatically inside the same fiscal year.</div>
	</form>
	<div id="depPreview"></div>
</div>
<?php endif; ?>
<div class="card">
	<div class="card-header"><span>Depreciation runs</span></div>
	<div class="table-responsive"><table class="table table-hover">
		<thead><tr><th>Period</th><th>Voucher</th><th>Date</th><th class="num">Assets</th><th class="num">Depreciation</th><th class="text-end" style="width:80px"></th></tr></thead>
		<tbody>
		<?php foreach ($runs as $r): ?>
			<tr><td><b><?= h($r->period) ?></b></td><td><a href="<?= site_url('journal-vouchers/view/' . $r->voucher_id) ?>"><?= h($r->voucher_no) ?></a></td><td><?= dmy($r->voucher_date) ?></td>
				<td class="num"><?= (int) $r->assets ?></td><td class="num"><b><?= fmt($r->amount) ?></b></td>
				<td class="text-end"><?php if (can('depreciation', 'void')): ?><button class="btn btn-icon danger" title="Void run" data-reason="1" data-reload="1" data-ok="Void run" data-confirm="Void depreciation run <?= h($r->voucher_no) ?>? The months can then be run again." data-action="<?= site_url('depreciation/void/' . $r->voucher_id) ?>"><?= icon('ban', 15) ?></button><?php endif; ?></td></tr>
		<?php endforeach; if (!$runs): ?><tr><td colspan="6" class="empty-row">No depreciation has been run yet.</td></tr><?php endif; ?>
		</tbody>
	</table></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	$('#depShow').on('click', function () {
		$('#depPreview').html('<div class="p-3 text-muted">Calculating...</div>');
		$.get(App.url('depreciation/preview'), { period: $('#depMonth').val() }).done(function (h) { $('#depPreview').html(h); })
			.fail(function () { $('#depPreview').html('<div class="p-3 text-danger">Could not calculate.</div>'); });
	});
})(jQuery); });
</script>
