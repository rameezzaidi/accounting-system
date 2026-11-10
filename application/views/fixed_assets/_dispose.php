<form method="post" action="<?= site_url('fixed-assets/dispose') ?>" data-ajax>
	<?= csrf_field() ?>
	<input type="hidden" name="id" value="<?= (int) $a->id ?>">
	<div class="modal-header"><h5 class="modal-title">Dispose <?= h($a->asset_no) ?> - <?= h($a->name) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
	<div class="modal-body"><div class="row g-3">
		<div class="col-12"><div class="info-box">Cost <b><?= fmt($a->cost) ?></b> &nbsp;|&nbsp; Accumulated depreciation <b><?= fmt($accum) ?></b> &nbsp;|&nbsp; Book value <b><?= fmt((float) $a->cost - $accum) ?></b></div></div>
		<div class="col-md-6"><label class="form-label">Disposal date *</label><input type="date" name="disposed_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
		<div class="col-md-6"><label class="form-label">Sale proceeds</label><input type="text" inputmode="decimal" name="amount" class="form-control num" value="0" autocomplete="off"></div>
		<div class="col-12"><label class="form-label">Proceeds received in</label>
			<select name="recv_account" class="form-select select2" data-placeholder="Cash / bank / receivable (needed if proceeds > 0)"><option value=""></option>
			<?php foreach ($recv as $x): ?><option value="<?= $x->id ?>"><?= h($x->code . ' - ' . $x->name) ?></option><?php endforeach; ?></select></div>
		<div class="col-12 form-text">The difference between proceeds and book value is posted to 4003 Gain / (Loss) on Asset Disposal.</div>
	</div></div>
	<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger"><?= icon('check', 16) ?> Dispose asset</button></div>
</form>
