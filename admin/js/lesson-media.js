// admin/js/lesson-media.js - WP media uploader wiring for PDF/audio pickers
(function ( $ ) {
	'use strict';

	function bindPicker(selectBtnId, removeBtnId, hiddenId, titleId, mimetype, frameTitle) {
		var selectBtn = document.getElementById(selectBtnId);
		var removeBtn = document.getElementById(removeBtnId);

		if (!selectBtn) return;

		var frame;

		selectBtn.addEventListener('click', function (e) {
			e.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: frameTitle,
				library: { type: mimeType },
				multiple: false,
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				document.getElementById(hiddenId).value = attachment.id;
				document.getElementById(titleId).textContent = attachment.title;
				removeBtn.style.display = 'inline-block';
			});

			frame.open();
		});

		if (removeBtn) {
			removeBtn.addEventListener('click', function (e) {
				e.preventDefault();
				document.getElementById(hiddenId).value = '';
				document.getElementById(titleId).textContent = '';
				removeBtn.style.display = 'none';
			});
		}
	}

	bindPicker('fnr_pdf_select', 'fnr_pdf_remove', 'fnr_pdf_attachment_id', 'fnr_pdf_title', 'appilcation/pdf', 'select PDF');
	bindPicker('fnr_audio_select', 'fnr_audio_remove', 'fnr_audio_attachment_id', 'fnr_audio_title', 'audio', 'Select Audio');
})
