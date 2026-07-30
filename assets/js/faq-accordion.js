(function () {
	'use strict';
	document.querySelectorAll('.tnstack-faq__item').forEach(function (item) {
		item.addEventListener('toggle', function () {
			if (!item.open) return;
			var parent = item.closest('.tnstack-faq');
			if (!parent) return;
			parent.querySelectorAll('.tnstack-faq__item').forEach(function (sibling) {
				if (sibling !== item) sibling.open = false;
			});
		});
	});
})();