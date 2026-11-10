/* Journal voucher entry grid */
var VoucherForm = (function ($) {
	var accounts = [];
	var $body;

	function accOptions(selected) {
		var h = '<option value=""></option>';
		$.each(accounts, function (i, a) { h += '<option value="' + a.id + '"' + (String(a.id) === String(selected) ? ' selected' : '') + '>' + $('<div>').text(a.text).html() + '</option>'; });
		return h;
	}

	function renumber() { $body.find('tr').each(function (i) { $(this).find('.ln').text(i + 1); }); }

	function totals() {
		var td = 0, tc = 0;
		$body.find('.v-dr').each(function () { td += App.num($(this).val()); });
		$body.find('.v-cr').each(function () { tc += App.num($(this).val()); });
		td = Math.round(td * 100) / 100; tc = Math.round(tc * 100) / 100;
		var diff = Math.round((td - tc) * 100) / 100;
		$('#vTd').text(App.money(td)); $('#vTc').text(App.money(tc));
		$('#vDiff').text(diff === 0 && td > 0 ? 'Balanced' : (td === 0 && tc === 0 ? 'Enter amounts' : 'Difference: ' + App.money(Math.abs(diff)) + (diff > 0 ? ' (credit needed)' : ' (debit needed)')))
			.toggleClass('balance-ok', diff === 0 && td > 0).toggleClass('balance-bad', diff !== 0);
		return { td: td, tc: tc, diff: diff };
	}

	function addLine(data) {
		data = data || {};
		var $tr = $('<tr>' +
			'<td class="ln text-muted"></td>' +
			'<td class="col-acc"><select name="account_id[]" class="form-select v-acc" data-placeholder="Select account">' + accOptions(data.account_id) + '</select></td>' +
			'<td><input type="text" name="description[]" class="form-control" maxlength="255" value=""></td>' +
			'<td><input type="text" inputmode="decimal" name="debit[]" class="form-control num v-dr" autocomplete="off"></td>' +
			'<td><input type="text" inputmode="decimal" name="credit[]" class="form-control num v-cr" autocomplete="off"></td>' +
			'<td><button type="button" class="btn btn-icon danger v-del" title="Remove line"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button></td>' +
			'</tr>');
		$tr.find('input[name="description[]"]').val(data.description || '');
		if (data.debit > 0) { $tr.find('.v-dr').val(parseFloat(data.debit).toFixed(2)); }
		if (data.credit > 0) { $tr.find('.v-cr').val(parseFloat(data.credit).toFixed(2)); }
		$body.append($tr);
		$tr.find('.v-acc').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Select account', allowClear: false });
		renumber();
		return $tr;
	}

	function init(opts) {
		accounts = opts.accounts || [];
		$body = $('#vGrid tbody');
		var lines = opts.lines && opts.lines.length ? opts.lines : [{}, {}];
		$.each(lines, function (i, l) { addLine(l); });
		totals();

		$('#vAddLine').on('click', function () {
			var t = totals();
			var $tr = addLine();
			// pre-fill the amount needed to balance
			if (t.diff > 0) { $tr.find('.v-cr').val(t.diff.toFixed(2)); } else if (t.diff < 0) { $tr.find('.v-dr').val(Math.abs(t.diff).toFixed(2)); }
			totals();
			$tr.find('.v-acc').select2('open');
		});

		$body.on('click', '.v-del', function () {
			if ($body.find('tr').length <= 2) { App.toast('A voucher needs at least two lines.', 'warning'); return; }
			$(this).closest('tr').remove(); renumber(); totals();
		});

		// debit / credit are exclusive on a line
		$body.on('input', '.v-dr, .v-cr', function () {
			var $tr = $(this).closest('tr');
			if (App.num($(this).val()) > 0) { $tr.find($(this).hasClass('v-dr') ? '.v-cr' : '.v-dr').val(''); }
			totals();
		});
		$body.on('blur', '.v-dr, .v-cr', function () { var v = App.num($(this).val()); $(this).val(v > 0 ? v.toFixed(2) : ''); totals(); });
		$body.on('keydown', '.v-cr', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); if ($(this).closest('tr').is(':last-child')) { $('#vAddLine').trigger('click'); } }
		});

		// stop an unbalanced voucher before it is sent
		$('#jvForm').on('submit', function (e) {
			var t = totals();
			if (t.td <= 0 || t.diff !== 0) {
				e.preventDefault(); e.stopImmediatePropagation();
				App.toast(t.td <= 0 ? 'Enter the voucher amounts first.' : 'Voucher is not balanced. Debit and credit must be equal.', 'error');
			}
		});
	}
	return { init: init };
})(jQuery);
