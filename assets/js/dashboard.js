(function () {
	var el = document.getElementById('monthChart');
	if (!el || typeof Chart === 'undefined') { return; }
	var months = JSON.parse(el.getAttribute('data-months') || '[]');
	new Chart(el, {
		type: 'bar',
		data: {
			labels: months.map(function (m) { return m.label; }),
			datasets: [
				{ label: 'Income', data: months.map(function (m) { return m.income; }), backgroundColor: '#22c55e', borderRadius: 4 },
				{ label: 'Expense', data: months.map(function (m) { return m.expense; }), backgroundColor: '#ef4444', borderRadius: 4 }
			]
		},
		options: {
			responsive: true,
			plugins: { legend: { position: 'bottom' } },
			scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return v.toLocaleString(); } } } }
		}
	});
})();
