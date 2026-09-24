(function () {
	'use strict';

	var root = document.querySelector('[data-tnstack-dashboard]');
	var chart = root ? root.querySelector('[data-tnstack-chart]') : null;
	var period = root ? root.querySelector('[data-tnstack-period]') : null;
	var config = window.TNStackDashboardData || {};
	var activity = Array.isArray(config.activity) ? config.activity : [];

	if (!chart) {
		return;
	}

	function render(days) {
		var data = activity.slice(-days);
		var maximum = data.reduce(function (max, item) {
			return Math.max(max, Number(item.value) || 0);
		}, 0);

		chart.textContent = '';

		if (!maximum) {
			var empty = document.createElement('p');
			empty.className = 'tnstack-dashboard__chart-empty';
			empty.textContent = config.labels && config.labels.empty ? config.labels.empty : '';
			chart.appendChild(empty);
			return;
		}

		var bars = document.createElement('div');
		bars.className = 'tnstack-dashboard__bars';

		data.forEach(function (item) {
			var value = Number(item.value) || 0;
			var wrap = document.createElement('div');
			var bar = document.createElement('span');
			var suffix = config.labels && config.labels.items ? config.labels.items : '';

			wrap.className = 'tnstack-dashboard__bar-wrap';
			bar.className = 'tnstack-dashboard__bar';
			bar.style.height = Math.max(2, Math.round((value / maximum) * 100)) + '%';
			bar.title = item.label + ': ' + value + ' ' + suffix;
			bar.setAttribute('aria-label', bar.title);
			wrap.appendChild(bar);
			bars.appendChild(wrap);
		});

		chart.appendChild(bars);
	}

	if (period) {
		period.addEventListener('change', function () {
			render(parseInt(period.value, 10) || 30);
		});
	}

	render(period ? parseInt(period.value, 10) : 30);
}());
