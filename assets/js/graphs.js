(function () {
	if (typeof Chart === 'undefined' || typeof jQuery === 'undefined') { return; }
	var $ = jQuery, charts = {};
	var COL = ['#366a4f', '#f59e0b', '#2563eb', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'];
	var money = function (v) { return Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }); };
	var axis = { y: { beginAtZero: true, ticks: { callback: money } } };

	function draw(id, cfg, empty, note) {
		if (charts[id]) { charts[id].destroy(); delete charts[id]; }
		var el = document.getElementById(id);
		$(el).toggle(!empty);
		if (note) { $('#' + id + 'Note').text(empty ? note : ''); }
		if (empty) { return; }
		cfg.options = $.extend(true, { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }, cfg.options || {});
		charts[id] = new Chart(el, cfg);
	}
	function pair(list) { return { labels: list.map(function (x) { return x[0]; }), values: list.map(function (x) { return x[1]; }) }; }
	function pie(id, list, empty) {
		var p = pair(list);
		draw(id, { type: 'doughnut', data: { labels: p.labels, datasets: [{ data: p.values, backgroundColor: COL }] } }, !list.length, empty);
	}
	function sum(a) { return a.reduce(function (s, v) { return s + v; }, 0); }

	function load() {
		$.getJSON(App.url('graphs/data'), { fy: $('#gFy').val() }).done(function (d) {
			if (!d.status) { return; }
			var m = d.months;
			draw('gMonths', { type: 'bar', data: { labels: m.map(function (x) { return x.label; }), datasets: [
				{ label: 'Income', data: m.map(function (x) { return x.income; }), backgroundColor: '#22c55e', borderRadius: 4 },
				{ label: 'Expense', data: m.map(function (x) { return x.expense; }), backgroundColor: '#ef4444', borderRadius: 4 }] }, options: { scales: axis } }, false);
			var c = pair(d.cash);
			draw('gCash', { type: 'bar', data: { labels: c.labels, datasets: [{ label: 'Balance', data: c.values, backgroundColor: '#366a4f', borderRadius: 4 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } } } }, !d.cash.length);
			pie('gInc', d.income); pie('gExp', d.expense);
			draw('gBud', { type: 'bar', data: { labels: m.map(function (x) { return x.label; }), datasets: [
				{ label: 'Budget', data: d.budget_exp, backgroundColor: '#94a3b8', borderRadius: 4 },
				{ label: 'Actual', data: m.map(function (x) { return x.expense; }), backgroundColor: '#ef4444', borderRadius: 4 }] }, options: { scales: axis } }, sum(d.budget_exp) === 0, 'No expense budget has been entered for this year.');
			var p = pair(d.payables);
			draw('gPay', { type: 'bar', data: { labels: p.labels, datasets: [{ label: 'Outstanding', data: p.values, backgroundColor: '#f59e0b', borderRadius: 4 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } } } }, !d.payables.length, 'Nothing is payable to suppliers.');
			var pj = d.projects;
			draw('gProj', { type: 'bar', data: { labels: pj.map(function (x) { return x.label; }), datasets: [
				{ label: 'Budget', data: pj.map(function (x) { return x.budget; }), backgroundColor: '#94a3b8', borderRadius: 4 },
				{ label: 'Spent', data: pj.map(function (x) { return x.spent; }), backgroundColor: '#ef4444', borderRadius: 4 },
				{ label: 'Income', data: pj.map(function (x) { return x.income; }), backgroundColor: '#22c55e', borderRadius: 4 }] }, options: { scales: axis } }, !pj.length, 'No projects yet.');
			var pr = d.payroll;
			draw('gPr', { type: 'bar', data: { labels: pr.map(function (x) { return x.label; }), datasets: [
				{ label: 'Gross', data: pr.map(function (x) { return x.gross; }), backgroundColor: '#8b5cf6', borderRadius: 4 },
				{ label: 'Net', data: pr.map(function (x) { return x.net; }), backgroundColor: '#06b6d4', borderRadius: 4 }] }, options: { scales: axis } }, !pr.length, 'No payroll has been run in this year.');
		});
	}
	$('#gFy').on('change', load);
	load();
})();
