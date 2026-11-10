(function ($) {
	function recalc() {
		var td = 0, tc = 0;
		$('.ob-dr').each(function () { td += App.num($(this).val()); });
		$('.ob-cr').each(function () { tc += App.num($(this).val()); });
		var diff = Math.round((td - tc) * 100) / 100;
		$('#obTd').text(App.money(td));
		$('#obTc').text(App.money(tc));
		$('#obDiff').text(diff === 0 ? 'Balanced' : 'Difference: ' + App.money(Math.abs(diff)) + (diff > 0 ? ' (more debit)' : ' (more credit)'))
			.toggleClass('balance-ok', diff === 0).toggleClass('balance-bad', diff !== 0);
	}
	// debit and credit of one row are exclusive
	$('#obTable').on('input', '.ob-dr, .ob-cr', function () {
		var $tr = $(this).closest('tr');
		if (App.num($(this).val()) > 0) { $tr.find($(this).hasClass('ob-dr') ? '.ob-cr' : '.ob-dr').val(''); }
		recalc();
	});
	$('#obTable').on('blur', '.ob-dr, .ob-cr', function () {
		var v = App.num($(this).val());
		$(this).val(v > 0 ? v.toFixed(2) : '');
	});
	$('#obFy').on('change', function () { window.location = App.url('opening-balance?fy=' + $(this).val()); });
	recalc();
})(jQuery);
