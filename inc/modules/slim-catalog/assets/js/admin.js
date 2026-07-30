(function ($) {
	'use strict';

	var frame;

	function renderPreview(ids) {
		var $preview = $('#slim-gallery-preview');
		$preview.empty();

		ids.forEach(function (id) {
			var attachment = wp.media.attachment(id);
			attachment.fetch();
			attachment.done(function () {
				var sizes = attachment.get('sizes') || {};
				var url = sizes.thumbnail ? sizes.thumbnail.url : attachment.get('url');
				$preview.append($('<img>', { src: url, alt: '' }));
			});
		});
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

		frame.on('select', function () {
			var selection = frame.state().get('selection');
			var ids = selection.map(function (attachment) {
				return attachment.id;
			});

			$('#slim_gallery').val(ids.join(','));
			renderPreview(ids);
		});

		frame.open();
	});

	$('#slim-gallery-clear').on('click', function (event) {
		event.preventDefault();
		$('#slim_gallery').val('');
		$('#slim-gallery-preview').empty();
	});
})(jQuery);