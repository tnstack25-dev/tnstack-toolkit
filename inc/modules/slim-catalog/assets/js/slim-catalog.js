(function () {
	'use strict';

	function initGallery(root) {
		var mainImage = root.querySelector('[data-sc-main-image]');
		var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-sc-thumb]'));
		var previousButton = root.querySelector('[data-sc-gallery-prev]');
		var nextButton = root.querySelector('[data-sc-gallery-next]');
		var activeIndex = Math.max(0, thumbs.findIndex(function (thumb) {
			return thumb.classList.contains('is-active');
		}));
		var touchStartX = null;

		if (!mainImage || !thumbs.length) {
			return;
		}

		function showImage(index) {
			var normalizedIndex = (index + thumbs.length) % thumbs.length;
			var thumb = thumbs[normalizedIndex];
			var fallbackImage = thumb.querySelector('img');
			var fullSrc = thumb.getAttribute('data-image-src') || (fallbackImage ? fallbackImage.src : '');

			if (!fullSrc) {
				return;
			}

			mainImage.classList.add('is-changing');
			mainImage.src = fullSrc;
			mainImage.srcset = thumb.getAttribute('data-image-srcset') || '';
			mainImage.alt = thumb.getAttribute('data-image-alt') || '';

			thumbs.forEach(function (item, itemIndex) {
				var isActive = itemIndex === normalizedIndex;
				item.classList.toggle('is-active', isActive);
				item.setAttribute('aria-current', isActive ? 'true' : 'false');
			});

			activeIndex = normalizedIndex;
			thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

			window.setTimeout(function () {
				mainImage.classList.remove('is-changing');
			}, 180);
		}

		thumbs.forEach(function (thumb, index) {
			thumb.addEventListener('click', function () {
				showImage(index);
			});
		});

		if (previousButton) {
			previousButton.addEventListener('click', function () {
				showImage(activeIndex - 1);
			});
		}

		if (nextButton) {
			nextButton.addEventListener('click', function () {
				showImage(activeIndex + 1);
			});
		}

		mainImage.addEventListener('touchstart', function (event) {
			touchStartX = event.changedTouches[0].clientX;
		}, { passive: true });

		mainImage.addEventListener('touchend', function (event) {
			if (touchStartX === null) {
				return;
			}

			var distance = event.changedTouches[0].clientX - touchStartX;
			touchStartX = null;

			if (Math.abs(distance) < 45) {
				return;
			}

			showImage(distance > 0 ? activeIndex - 1 : activeIndex + 1);
		}, { passive: true });

		root.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') {
				showImage(activeIndex - 1);
			} else if (event.key === 'ArrowRight') {
				showImage(activeIndex + 1);
			}
		});
	}

	function initVariations(root) {
		var dataNode = root.querySelector('[data-sc-variations-data]');
		var detail = root.closest('[data-sc-product-detail]');

		if (!dataNode || !detail) {
			return;
		}

		var payload;
		try {
			payload = JSON.parse(dataNode.textContent || '{}');
		} catch (error) {
			return;
		}

		var selected = {};
		var priceEl = detail.querySelector('[data-sc-product-price]');
		var skuWrap = detail.querySelector('[data-sc-product-sku]');
		var skuValue = detail.querySelector('[data-sc-product-sku-value]');
		var notice = root.querySelector('[data-sc-variation-notice]');
		var gallery = detail.querySelector('[data-sc-gallery]');
		var mainImage = gallery ? gallery.querySelector('[data-sc-main-image]') : null;
		var options = root.querySelectorAll('[data-sc-variation-option]');

		function findVariation() {
			return (payload.variations || []).find(function (variation) {
				return Object.keys(variation.attributes).every(function (slug) {
					return selected[slug] === variation.attributes[slug];
				});
			});
		}

		function allSelected() {
			return (payload.attributes || []).every(function (attribute) {
				return !!selected[attribute.slug];
			});
		}

		function applyDefault() {
			if (priceEl && payload.default) {
				priceEl.innerHTML = payload.default.price_html || '';
			}

			if (skuWrap && skuValue && payload.default) {
				var sku = payload.default.sku || '';

				skuValue.textContent = sku;
				skuWrap.hidden = !sku;
			}

			if (notice) {
				notice.hidden = true;
			}
		}

		function applyVariation(variation) {
			if (priceEl) {
				priceEl.innerHTML = variation.price_html || '';
			}

			if (skuWrap && skuValue) {
				var sku = variation.sku || '';
				skuValue.textContent = sku;
				skuWrap.hidden = !sku;
			}

			if (mainImage && variation.image_url) {
				mainImage.src = variation.image_url;
				mainImage.srcset = '';
			}

			if (notice) {
				notice.hidden = true;
			}
		}

		options.forEach(function (button) {
			button.addEventListener('click', function () {
				var slug = button.getAttribute('data-attribute');
				var value = button.getAttribute('data-value');
				var group = button.closest('.sc-variations__options');

				selected[slug] = value;

				if (group) {
					group.querySelectorAll('[data-sc-variation-option]').forEach(function (item) {
						item.classList.toggle('is-active', item === button);
					});
				}

				if (!allSelected()) {
					applyDefault();
					return;
				}

				var variation = findVariation();

				if (!variation) {
					if (notice) {
						notice.hidden = false;
					}
					return;
				}

				applyVariation(variation);
			});
		});
	}

	document.querySelectorAll('[data-sc-gallery]').forEach(initGallery);
	document.querySelectorAll('[data-sc-variations]').forEach(initVariations);
})();
