(function ($) {
	'use strict';

	var $root = $('[data-slim-variations]');

	if (!$root.length) {
		return;
	}

	var state = {
		attributes: [],
		variations: []
	};

	function slugify(value) {
		return String(value || '')
			.toLowerCase()
			.trim()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	function readState() {
		try {
			state.attributes = JSON.parse($('#slim_attributes_json').val() || '[]') || [];
		} catch (error) {
			state.attributes = [];
		}

		try {
			state.variations = JSON.parse($('#slim_variations_json').val() || '[]') || [];
		} catch (error) {
			state.variations = [];
		}
	}

	function writeState() {
		$('#slim_attributes_json').val(JSON.stringify(state.attributes));
		$('#slim_variations_json').val(JSON.stringify(state.variations));
	}

	function renderAttributes() {
		var $list = $('[data-slim-attributes-list]');
		$list.empty();

		state.attributes.forEach(function (attribute, index) {
			var $row = $('<div class="slim-catalog-variations__attribute-row"></div>');
			$row.append(
				'<p><label><strong>Attribute name</strong></label>' +
				'<input type="text" class="widefat" data-attr-name value="' + (attribute.name || '') + '"></p>'
			);
			$row.append(
				'<p><label><strong>Options (comma separated)</strong></label>' +
				'<input type="text" class="widefat" data-attr-options value="' + ((attribute.options || []).join(', ')) + '"></p>'
			);
			$row.append('<p><button type="button" class="button-link-delete" data-remove-attribute data-index="' + index + '">Remove attribute</button></p>');
			$list.append($row);
		});
	}

	function renderVariations() {
		var $list = $('[data-slim-variations-list]');
		$list.empty();

		state.variations.forEach(function (variation, index) {
			var $row = $('<tr></tr>');
			var attrFields = '';

			state.attributes.forEach(function (attribute) {
				var slug = attribute.slug || slugify(attribute.name);
				var value = variation.attributes && variation.attributes[slug] ? variation.attributes[slug] : '';
				attrFields +=
					'<label style="display:block;margin-bottom:6px;">' + attribute.name +
					'<select class="widefat" data-variation-attr data-slug="' + slug + '">' +
					'<option value="">Select</option>' +
					(attribute.options || []).map(function (option) {
						return '<option value="' + option + '"' + (value === option ? ' selected' : '') + '>' + option + '</option>';
					}).join('') +
					'</select></label>';
			});

			$row.append('<td>' + attrFields + '</td>');
			$row.append('<td><input type="number" step="0.01" class="small-text" data-variation-price value="' + (variation.price ?? '') + '"></td>');
			$row.append('<td><input type="number" step="0.01" class="small-text" data-variation-sale value="' + (variation.sale_price ?? '') + '"></td>');
			$row.append('<td><input type="text" class="regular-text" data-variation-sku value="' + (variation.sku || '') + '"></td>');
			$row.append(
				'<td><input type="hidden" data-variation-image value="' + (variation.image_id || '') + '">' +
				'<button type="button" class="button" data-variation-image-select>Select</button>' +
				'<div data-variation-image-preview></div></td>'
			);
			$row.append('<td><input type="checkbox" data-variation-enabled ' + (variation.enabled === false ? '' : 'checked') + '></td>');
			$row.append('<td><button type="button" class="button-link-delete" data-remove-variation data-index="' + index + '">Remove</button></td>');
			$row.attr('data-variation-index', index);
			$list.append($row);
		});
	}

	function syncAttributesFromDom() {
		var attributes = [];

		$('[data-slim-attributes-list] .slim-catalog-variations__attribute-row').each(function () {
			var name = $(this).find('[data-attr-name]').val();
			var options = $(this).find('[data-attr-options]').val();

			if (!name) {
				return;
			}

			attributes.push({
				name: name,
				slug: slugify(name),
				options: options.split(',').map(function (item) {
					return item.trim();
				}).filter(Boolean)
			});
		});

		state.attributes = attributes;
	}

	function syncVariationsFromDom() {
		var variations = [];

		$('[data-slim-variations-list] tr').each(function () {
			var attributes = {};

			$(this).find('[data-variation-attr]').each(function () {
				var slug = $(this).data('slug');
				var value = $(this).val();

				if (slug && value) {
					attributes[slug] = value;
				}
			});

			variations.push({
				id: 'variation_' + (variations.length + 1),
				attributes: attributes,
				price: $(this).find('[data-variation-price]').val(),
				sale_price: $(this).find('[data-variation-sale]').val(),
				sku: $(this).find('[data-variation-sku]').val(),
				image_id: parseInt($(this).find('[data-variation-image]').val(), 10) || 0,
				enabled: $(this).find('[data-variation-enabled]').is(':checked')
			});
		});

		state.variations = variations;
	}

	function cartesian(attributes) {
		if (!attributes.length) {
			return [];
		}

		return attributes.reduce(function (acc, attribute) {
			var slug = attribute.slug || slugify(attribute.name);
			var next = [];

			acc.forEach(function (combo) {
				(attribute.options || []).forEach(function (option) {
					var copy = Object.assign({}, combo);
					copy[slug] = option;
					next.push(copy);
				});
			});

			return next;
		}, [{}]);
	}

	readState();
	renderAttributes();
	renderVariations();

	$('#slim_product_type').on('change', function () {
		$('[data-slim-variable-panel]').prop('hidden', $(this).val() !== 'variable');
	});

	$('[data-slim-add-attribute]').on('click', function (event) {
		event.preventDefault();
		syncAttributesFromDom();
		state.attributes.push({ name: '', slug: '', options: [] });
		writeState();
		renderAttributes();
	});

	$root.on('click', '[data-remove-attribute]', function (event) {
		event.preventDefault();
		syncAttributesFromDom();
		state.attributes.splice(parseInt($(this).data('index'), 10), 1);
		writeState();
		renderAttributes();
	});

	$('[data-slim-add-variation]').on('click', function (event) {
		event.preventDefault();
		syncAttributesFromDom();
		syncVariationsFromDom();
		state.variations.push({ id: 'variation_' + (state.variations.length + 1), attributes: {}, enabled: true });
		writeState();
		renderVariations();
	});

	$('[data-slim-generate-variations]').on('click', function (event) {
		event.preventDefault();
		syncAttributesFromDom();
		var combos = cartesian(state.attributes);

		state.variations = combos.map(function (combo, index) {
			return {
				id: 'variation_' + (index + 1),
				attributes: combo,
				price: '',
				sale_price: '',
				sku: '',
				image_id: 0,
				enabled: true
			};
		});

		writeState();
		renderVariations();
	});

	$root.on('click', '[data-remove-variation]', function (event) {
		event.preventDefault();
		syncVariationsFromDom();
		state.variations.splice(parseInt($(this).data('index'), 10), 1);
		writeState();
		renderVariations();
	});

	$root.on('click', '[data-variation-image-select]', function (event) {
		event.preventDefault();

		var $row = $(this).closest('tr');
		var frame = wp.media({
			title: 'Select variation image',
			button: { text: 'Use image' },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$row.find('[data-variation-image]').val(attachment.id);
			$row.find('[data-variation-image-preview]').html('<img src="' + attachment.url + '" style="max-width:60px;height:auto;" />');
		});

		frame.open();
	});

	$('#post').on('submit', function () {
		syncAttributesFromDom();
		syncVariationsFromDom();
		writeState();
	});
})(jQuery);