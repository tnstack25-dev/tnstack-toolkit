(function ($) {
	'use strict';

	var frame;

	function committedIds() {
		return $('#slim_gallery').val().split(',').filter(Boolean);
	}

	function renderPreview(selection) {
		var $preview = $('#slim-gallery-preview');
		$preview.empty();

		selection.each(function (attachment) {
			var sizes = attachment.get('sizes') || {};
			var url = sizes.thumbnail ? sizes.thumbnail.url : attachment.get('url');
			var $image = $('<img>', {
				alt: attachment.get('alt') || attachment.get('title') || ''
			});

			$preview.append($image);

			if (url) {
				$image.attr('src', url);
			} else {
				attachment.fetch().done(function () {
					var sizes = attachment.get('sizes') || {};
					var fetchedUrl = sizes.thumbnail ? sizes.thumbnail.url : attachment.get('url');

					if (fetchedUrl) {
						$image.attr('src', fetchedUrl);
					}
				});
			}
		});
	}

	function renderSelectionPreview() {
		renderPreview(frame.state().get('selection'));
	}

	$('#slim-gallery-add').on('click', function (event) {
		event.preventDefault();

		if (frame) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: 'Select product images',
			button: { text: 'Use images' },
			multiple: true
		});

		frame.on('open', function () {
			var selection = frame.state().get('selection');
			var selected = committedIds().map(function (id) {
				return wp.media.attachment(id);
			});

			selection.off('add remove reset', renderSelectionPreview);
			selection.on('add remove reset', renderSelectionPreview);
			selection.reset(selected);
		});

		frame.on('select', function () {
			var selection = frame.state().get('selection');
			var ids = selection.pluck('id');

			$('#slim_gallery').val(ids.join(','));
			renderPreview(selection);
		});

		frame.on('close', function () {
			var selection = frame.state().get('selection');
			var ids = committedIds().map(function (id) {
				return wp.media.attachment(id);
			});

			selection.reset(ids);
			renderPreview(selection);
		});

		frame.open();
	});

	$('#slim-gallery-clear').on('click', function (event) {
		event.preventDefault();
		$('#slim_gallery').val('');
		$('#slim-gallery-preview').empty();
	});
})(jQuery);
