/* ===== Accounting System - shared front-end helpers (jQuery + Bootstrap 5) ===== */
(function ($) {
	'use strict';

	var BASE = $('meta[name=base-url]').attr('content') || '/';
	var csrfName = $('meta[name=csrf-name]').attr('content');
	var csrfHash = $('meta[name=csrf-hash]').attr('content');

	var App = window.App = {};
	App.base = BASE;
	App.url = function (p) { return BASE + (p || ''); };

	/* ---------- toast ---------- */
	App.toast = function (msg, type) {
		type = type || 'success';
		var cls = { success: 'text-bg-success', error: 'text-bg-danger', warning: 'text-bg-warning', info: 'text-bg-primary' }[type] || 'text-bg-success';
		var $t = $('<div class="toast align-items-center border-0 ' + cls + '" role="alert"><div class="d-flex"><div class="toast-body"></div>' +
			'<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>');
		$t.find('.toast-body').text(msg);
		$('#toastBox').append($t);
		var t = new bootstrap.Toast($t[0], { delay: type === 'error' ? 6000 : 3500 });
		t.show();
		$t.on('hidden.bs.toast', function () { $t.remove(); });
	};

	/* ---------- ajax ---------- */
	function withToken(data) {
		if (typeof data === 'string') {
			if (data.indexOf(csrfName + '=') === -1) { data += (data ? '&' : '') + encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfHash); }
			return data;
		}
		data = data || {};
		data[csrfName] = csrfHash;
		return data;
	}

	App.post = function (url, data, done, fail) {
		return $.ajax({ url: /^https?:/.test(url) ? url : App.url(url), type: 'POST', data: withToken(data), dataType: 'json' })
			.done(function (r) { if (done) { done(r); } })
			.fail(function (xhr) {
				var r = xhr.responseJSON;
				if (xhr.status === 401) { window.location = (r && r.login) || App.url('login'); return; }
				var msg = (r && r.message) ? r.message :
					(xhr.status === 403 ? 'Security token expired or action not allowed. Please reload the page.' : 'Something went wrong. Please try again.');
				if (fail) { fail(r || { status: false, message: msg }); } else { App.toast(msg, 'error'); }
			});
	};

	App.get = function (url, params, done) {
		return $.get(/^https?:/.test(url) ? url : App.url(url), params || {}, done);
	};

	/* ---------- helpers ---------- */
	App.money = function (n) {
		n = parseFloat(n) || 0;
		return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	};
	App.num = function (v) { var n = parseFloat(String(v).replace(/,/g, '')); return isNaN(n) ? 0 : n; };

	App.initSelect2 = function (ctx) {
		$(ctx || document).find('select.select2').each(function () {
			var $s = $(this);
			if ($s.data('select2')) { return; }
			var $parent = $s.closest('.modal');
			$s.select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $parent.length ? $parent : $(document.body), placeholder: $s.data('placeholder') || '' });
		});
	};

	/* ---------- sidebar ---------- */
	$('#sidebarToggle').on('click', function () {
		if (window.innerWidth < 992) { $('body').toggleClass('side-open'); } else { $('body').toggleClass('side-collapsed'); }
	});
	$('#sidebarBackdrop').on('click', function () { $('body').removeClass('side-open'); });
	$(document).on('click', '.group-toggle', function () { $(this).closest('.nav-group').toggleClass('open'); });

	/* ---------- modal forms ---------- */
	App.modal = function (url, size) {
		var $m = $('#appModal');
		var $d = $m.find('.modal-dialog').removeClass('modal-sm modal-lg modal-xl');
		if (size) { $d.addClass('modal-' + size); }
		$m.find('.modal-content').html('<div class="p-5 text-center text-muted">Loading...</div>');
		bootstrap.Modal.getOrCreateInstance($m[0]).show();
		$.get(/^https?:/.test(url) ? url : App.url(url)).done(function (html) {
			$m.find('.modal-content').html(html);
			App.initSelect2($m);
			$m.find('[autofocus]').first().trigger('focus');
		}).fail(function (xhr) {
			bootstrap.Modal.getOrCreateInstance($m[0]).hide();
			if (xhr.status === 401) { window.location = App.url('login'); return; }
			App.toast(xhr.status === 403 ? 'You do not have permission to do this.' : 'Could not load the form.', 'error');
		});
	};
	App.closeModal = function () {
		var inst = bootstrap.Modal.getInstance($('#appModal')[0]);
		if (inst) { inst.hide(); }
	};
	$(document).on('click', '[data-modal]', function (e) {
		e.preventDefault();
		App.modal($(this).data('modal'), $(this).data('size'));
	});

	/* ---------- generic ajax form ---------- */
	function showErrors($f, errors) {
		$f.find('.is-invalid').removeClass('is-invalid');
		$f.find('.invalid-feedback.js').remove();
		$.each(errors || {}, function (name, msg) {
			var $i = $f.find('[name="' + name + '"]').first();
			if (!$i.length) { return; }
			$i.addClass('is-invalid');
			var $after = $i.hasClass('select2-hidden-accessible') ? $i.next('.select2') : $i;
			$('<div class="invalid-feedback js"></div>').text(msg).insertAfter($after);
		});
	}
	$(document).on('submit', 'form[data-ajax]', function (e) {
		e.preventDefault();
		var $f = $(this), $btn = $f.find('[type=submit]').first();
		var label = $btn.html();
		$btn.prop('disabled', true).html('Saving...');
		showErrors($f, {});
		App.post($f.attr('action'), $f.serialize(), function (r) {
			$btn.prop('disabled', false).html(label);
			if (r.status) {
				App.toast(r.message || 'Saved.', 'success');
				if (r.redirect) { window.location = r.redirect; return; }
				if (r.reload || $f.data('reloadPage')) { window.location.reload(); return; }
				App.closeModal();
				App.reloadTables();
				if (r.after && typeof window[r.after] === 'function') { window[r.after](r); }
			} else {
				showErrors($f, r.errors);
				App.toast(r.message || 'Please check the form.', 'error');
			}
		}, function (r) {
			$btn.prop('disabled', false).html(label);
			if (r && r.errors) { showErrors($f, r.errors); }
			App.toast((r && r.message) || 'Something went wrong.', 'error');
		});
	});

	/* ---------- confirm + action (delete / void / toggle) ---------- */
	$(document).on('click', '[data-action]', function (e) {
		e.preventDefault();
		var $b = $(this), url = $b.data('action');
		var needReason = !!$b.data('reason');
		var $m = $('#confirmModal');
		$('#confirmText').text($b.data('confirm') || 'Are you sure?');
		$('#confirmReason').val('').toggleClass('d-none', !needReason);
		$('#confirmOk').text($b.data('ok') || 'Yes, continue').removeClass('btn-danger btn-primary').addClass($b.data('btn') || 'btn-danger');
		$('#confirmOk').off('click').on('click', function () {
			var data = {};
			if (needReason) {
				data.reason = $.trim($('#confirmReason').val());
				if (!data.reason) { $('#confirmReason').addClass('is-invalid').trigger('focus'); return; }
			}
			var $ok = $(this).prop('disabled', true);
			App.post(url, data, function (r) {
				$ok.prop('disabled', false);
				bootstrap.Modal.getOrCreateInstance($m[0]).hide();
				if (r.status) {
					App.toast(r.message || 'Done.', 'success');
					if (r.redirect) { window.location = r.redirect; return; }
					if (r.reload || $b.data('reload')) { window.location.reload(); return; }
					App.reloadTables();
				} else { App.toast(r.message || 'Not allowed.', 'error'); }
			}, function (r) { $ok.prop('disabled', false); bootstrap.Modal.getOrCreateInstance($m[0]).hide(); App.toast((r && r.message) || 'Failed.', 'error'); });
		});
		bootstrap.Modal.getOrCreateInstance($m[0]).show();
	});

	/* ---------- ajax tables ---------- */
	App.loadTable = function ($t, page) {
		var $form = $($t.data('filter'));
		var params = $form.length ? $form.serialize() : '';
		params += (params ? '&' : '') + 'page=' + (page || $t.data('page') || 1);
		$t.data('page', page || 1).addClass('loading');
		$.get(App.url($t.data('url')), params).done(function (html) {
			$t.html(html).removeClass('loading');
		}).fail(function (xhr) {
			$t.removeClass('loading');
			if (xhr.status === 401) { window.location = App.url('login'); return; }
			$t.html('<div class="p-4 text-center text-danger">Could not load data. Please refresh.</div>');
		});
	};
	App.reloadTables = function () {
		$('.ajax-table').each(function () { App.loadTable($(this), $(this).data('page') || 1); });
	};
	$(document).on('click', '.ajax-table [data-page]', function (e) {
		e.preventDefault();
		if ($(this).closest('.disabled').length) { return; }
		App.loadTable($(this).closest('.ajax-table'), $(this).data('page'));
	});
	$(document).on('submit', 'form.table-filter', function (e) {
		e.preventDefault();
		var $t = $($(this).data('target'));
		App.loadTable($t, 1);
	});
	$(document).on('click', '.table-filter .btn-reset', function () {
		var $f = $(this).closest('form');
		$f.find('input[type=text],input[type=search]').val('');
		$f.find('select:not([data-keep])').val('').trigger('change');
		$f.trigger('submit');
	});
	var searchTimer;
	$(document).on('input', '.table-filter .live-search', function () {
		var $f = $(this).closest('form');
		clearTimeout(searchTimer);
		searchTimer = setTimeout(function () { $f.trigger('submit'); }, 350);
	});
	$(document).on('change', '.table-filter select.auto', function () { $(this).closest('form').trigger('submit'); });

	/* ---------- ready ---------- */
	$(function () {
		App.initSelect2(document);
		$('.ajax-table[data-auto!="0"]').each(function () { App.loadTable($(this), 1); });
		$('#confirmReason').on('input', function () { $(this).removeClass('is-invalid'); });
	});

})(jQuery);
