(function () {
	'use strict';

	var wrap = document.querySelector('.ttk-wrap');
	if (!wrap) {
		return;
	}

	var modules = wrap.querySelectorAll('.ttk-module');
	var navItems = wrap.querySelectorAll('.ttk-nav__item');
	var searchInput = wrap.querySelector('.ttk-search__input');
	var enabledEl = wrap.querySelector('[data-ttk-enabled]');
	var totalEl = wrap.querySelector('[data-ttk-total]');
	var saveInfo = wrap.querySelector('[data-ttk-save-info]');

	function countEnabled() {
		var enabled = 0;
		modules.forEach(function (mod) {
			if (mod.classList.contains('is-active')) {
				enabled++;
			}
		});
		if (enabledEl) {
			enabledEl.textContent = String(enabled);
		}
		if (saveInfo) {
			saveInfo.innerHTML = enabled + ' / ' + (totalEl ? totalEl.textContent : '?') + ' module đang bật';
		}
		updateNavCounts();
	}

	function updateNavCounts() {
		navItems.forEach(function (item) {
			var group = item.getAttribute('data-group');
			if (!group) {
				return;
			}
			var section = wrap.querySelector('#ttk-group-' + group);
			if (!section) {
				return;
			}
			var active = section.querySelectorAll('.ttk-module.is-active:not(.is-hidden)').length;
			var countEl = item.querySelector('.ttk-nav__count');
			if (countEl) {
				countEl.textContent = String(active);
			}
		});
	}

	modules.forEach(function (mod) {
		var input = mod.querySelector('.ttk-module__input');
		if (!input) {
			return;
		}
		input.addEventListener('change', function () {
			mod.classList.toggle('is-active', input.checked);
			countEnabled();
		});
	});

	if (searchInput) {
		searchInput.addEventListener('input', function () {
			var query = searchInput.value.toLowerCase().trim();
			modules.forEach(function (mod) {
				var text = mod.getAttribute('data-search') || '';
				mod.classList.toggle('is-hidden', query !== '' && text.indexOf(query) === -1);
			});
			updateNavCounts();
		});
	}

	navItems.forEach(function (item) {
		item.addEventListener('click', function () {
			var target = item.getAttribute('data-target');
			if (!target) {
				return;
			}
			var el = document.getElementById(target);
			if (el) {
				el.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
			navItems.forEach(function (n) {
				n.classList.remove('is-active');
			});
			item.classList.add('is-active');
		});
	});

	var sections = wrap.querySelectorAll('.ttk-section[id]');
	if (sections.length && 'IntersectionObserver' in window) {
		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						var id = entry.target.id;
						navItems.forEach(function (item) {
							item.classList.toggle('is-active', item.getAttribute('data-target') === id);
						});
					}
				});
			},
			{ rootMargin: '-20% 0px -60% 0px', threshold: 0 }
		);
		sections.forEach(function (section) {
			observer.observe(section);
		});
	}

	countEnabled();
})();