<div class="card">
	<div class="card-header">
		<span>Budget <?= $fy ? '- ' . h($fy->title) : '' ?></span>
		<div class="ms-auto no-print d-flex gap-2 align-items-center">
			<form method="get" action="<?= site_url('budget') ?>"><select name="fy" class="form-select form-select-sm" onchange="this.form.submit()"><?php foreach ($fys as $f): ?><option value="<?= $f->id ?>" <?= $fy && (int) $f->id === (int) $fy->id ? 'selected' : '' ?>><?= h($f->title) ?></option><?php endforeach; ?></select></form>
			<?php if ($can_edit): ?><button type="submit" form="budForm" class="btn btn-primary btn-sm"><?= icon('check', 15) ?> Save budget</button><?php endif; ?>
		</div>
	</div>
	<?php if (!$fy): ?><div class="card-body text-muted">No fiscal year found.</div><?php else: ?>
	<div class="card-body small text-muted pb-0">Enter the amount planned for each month. Type a figure in <b>Annual</b> to spread it equally over 12 months.<?= $can_edit ? '' : ' (read only)' ?></div>
	<form method="post" action="<?= site_url('budget/save') ?>" data-ajax id="budForm">
		<?= csrf_field() ?><input type="hidden" name="fy_id" value="<?= (int) $fy->id ?>"><input type="hidden" name="payload" id="budPayload">
		<div class="table-responsive"><table class="table table-sm table-hover bud-table">
			<thead><tr><th style="min-width:200px">Account</th><th class="num" style="min-width:100px">Annual</th><?php foreach ($labels as $l): ?><th class="num" style="min-width:84px"><?= h($l) ?></th><?php endforeach; ?></tr></thead>
			<tbody>
			<?php $last = ''; foreach ($accounts as $a): $it = isset($items[(int) $a->id]) ? $items[(int) $a->id] : null;
				if ($a->account_type !== $last) { $last = $a->account_type; echo '<tr class="table-light"><td colspan="14"><b>' . ($last === 'income' ? 'Income' : 'Expenses') . '</b></td></tr>'; } ?>
				<tr class="<?= (int) $a->is_active ? '' : 'row-void' ?>" data-row>
					<td><?= h($a->code . ' ' . $a->name) ?></td>
					<td><input type="text" inputmode="decimal" class="form-control form-control-sm num bud-annual" value="<?= $it ? h(rtrim(rtrim(number_format(array_sum(array_map(function ($i) use ($it) { return (float) $it->{'m' . $i}; }, range(1, 12))), 2, '.', ''), '0'), '.')) : '' ?>" <?= $can_edit ? '' : 'disabled' ?>></td>
					<?php for ($i = 1; $i <= 12; $i++): ?><td><input type="text" inputmode="decimal" data-acc="<?= $a->id ?>" data-m="<?= $i ?>" class="form-control form-control-sm num bud-m" value="<?= $it && (float) $it->{'m' . $i} != 0 ? h(rtrim(rtrim(number_format((float) $it->{'m' . $i}, 2, '.', ''), '0'), '.')) : '' ?>" <?= $can_edit ? '' : 'disabled' ?>></td><?php endfor; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
	</form>
	<?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () { (function ($) {
	function sum($row) { var t = 0; $row.find('.bud-m').each(function () { t += App.num($(this).val()); }); return t; }
	$('.bud-annual').on('change', function () {
		var $r = $(this).closest('tr'), v = App.num($(this).val()), per = Math.floor(v / 12 * 100) / 100, rest = Math.round((v - per * 12) * 100) / 100;
		$r.find('.bud-m').each(function (i) { $(this).val(v ? (i === 11 ? String(Math.round((per + rest) * 100) / 100) : String(per)) : ''); });
	});
	// all 12 x N amounts travel as one JSON field (avoids PHP max_input_vars)
	$('#budForm').on('submit', function () {
		var d = {};
		$('.bud-m').each(function () { var a = $(this).data('acc'); (d[a] = d[a] || {})[$(this).data('m')] = $(this).val(); });
		$('#budPayload').val(JSON.stringify(d));
	});
	$('.bud-m').on('input', function () { var $r = $(this).closest('tr'), t = sum($r); $r.find('.bud-annual').val(t ? String(Math.round(t * 100) / 100) : ''); });
})(jQuery); });
</script>
