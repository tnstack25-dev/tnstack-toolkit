(function () {
	'use strict';

	function pad(n) {
		return n < 10 ? '0' + n : String(n);
	}

	function tick(el) {
		var deadline = parseInt(el.getAttribute('data-deadline'), 10) * 1000;
		var diff = deadline - Date.now();

		if (diff <= 0) {
			el.querySelectorAll('.tnstack-countdown__num').forEach(function (n) {
				n.textContent = '00';
			});
			return;
		}

		var days = Math.floor(diff / 86400000);
		var hours = Math.floor((diff % 86400000) / 3600000);
		var minutes = Math.floor((diff % 3600000) / 60000);
		var seconds = Math.floor((diff % 60000) / 1000);

		var map = { days: days, hours: hours, minutes: minutes, seconds: seconds };
		el.querySelectorAll('.tnstack-countdown__num').forEach(function (node) {
			var unit = node.getAttribute('data-unit');
			if (map[unit] !== undefined) {
				node.textContent = unit === 'days' ? String(map[unit]) : pad(map[unit]);
			}
		});
	}

	document.querySelectorAll('.tnstack-countdown').forEach(function (el) {
		tick(el);
		setInterval(function () {
			tick(el);
		}, 1000);
	});
})();