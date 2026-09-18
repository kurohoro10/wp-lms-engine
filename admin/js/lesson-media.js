// admin/js/lesson-media.js
//
// Lesson edit screen behaviour:
//   - show only the fields belonging to the selected media type
//   - show only the fields belonging to the selected unlock rule
//   - media library pickers for PDF, audio, and the slide deck
//
// This file was enqueued by Admin\Assets but never existed in the
// plugin, which is why the media type switcher and the file pickers
// silently did nothing.
(function () {
	'use strict';

	const i18n = (window.fnrLessonMedia && window.fnrLessonMedia.i18n) || {};

	function text(key, fallback) {
		return i18n[key] || fallback;
	}

	/* ------------------------------------------------------------------
	 * Conditional field groups
	 * ---------------------------------------------------------------- */

	function bindSwitcher(selectId, fieldSelector, datasetKey) {
		const select = document.getElementById(selectId);
		if (!select) return;

		const fields = document.querySelectorAll(fieldSelector);

		function apply() {
			Array.prototype.forEach.call(fields, function (el) {
				el.style.display = el.dataset[datasetKey] === select.value ? '' : 'none';
			});
		}

		select.addEventListener('change', apply);
		apply();
	}

	bindSwitcher('fnr_media_type', '.fnr-media-field', 'mediaType');
	bindSwitcher('fnr_drip_type', '.fnr-drip-field', 'dripType');

	/* ------------------------------------------------------------------
	 * Single-file pickers (PDF, audio)
	 * ---------------------------------------------------------------- */

	function bindSingleFilePicker(config) {
		const input    = document.getElementById(config.inputId);
		const titleEl  = document.getElementById(config.titleId);
		const selectBtn = document.getElementById(config.selectId);
		const removeBtn = document.getElementById(config.removeId);

		if (!input || !selectBtn) return;

		let frame = null;

		selectBtn.addEventListener('click', function (e) {
			e.preventDefault();

			// Rebuilding the frame each time is wasteful, so keep one and
			// reopen it - wp.media remembers the last selection that way.
			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: config.frameTitle,
				button: { text: config.buttonText },
				library: { type: config.mimeType },
				multiple: false
			});

			frame.on('select', function () {
				const attachment = frame.state().get('selection').first().toJSON();

				input.value = attachment.id;
				if (titleEl) titleEl.textContent = attachment.title || attachment.filename;
				if (removeBtn) removeBtn.style.display = '';
			});

			frame.open();
		});

		if (removeBtn) {
			removeBtn.addEventListener('click', function (e) {
				e.preventDefault();
				input.value = '';
				if (titleEl) titleEl.textContent = text('noFile', 'No file selected.');
				removeBtn.style.display = 'none';
			});
		}
	}

	bindSingleFilePicker({
		inputId: 'fnr_pdf_attachment_id',
		titleId: 'fnr_pdf_title',
		selectId: 'fnr_pdf_select',
		removeId: 'fnr_pdf_remove',
		mimeType: 'application/pdf',
		frameTitle: text('selectPdf', 'Select PDF'),
		buttonText: text('usePdf', 'Use this PDF')
	});

	bindSingleFilePicker({
		inputId: 'fnr_audio_attachment_id',
		titleId: 'fnr_audio_title',
		selectId: 'fnr_audio_select',
		removeId: 'fnr_audio_remove',
		mimeType: 'audio',
		frameTitle: text('selectAudio', 'Select Audio'),
		buttonText: text('useAudio', 'Use this audio')
	});

	/* ------------------------------------------------------------------
	 * Slide deck
	 * ---------------------------------------------------------------- */

	(function slideDeck() {
		const hidden  = document.getElementById('fnr_slide_ids');
		const list    = document.getElementById('fnr_slide_list');
		const empty   = document.getElementById('fnr_slide_empty');
		const addBtn  = document.getElementById('fnr_slide_add');
		const clearBtn = document.getElementById('fnr_slide_clear');

		if (!hidden || !list || !addBtn) return;

		let frame = null;

		function sync() {
			const items = Array.prototype.slice.call(list.children);

			hidden.value = items.map(function (li) { return li.dataset.attachmentId; }).join(',');

			items.forEach(function (li, i) {
				const number = li.querySelector('.fnr-slide-admin-number');
				if (number) number.textContent = String(i + 1);

				const up = li.querySelector('.fnr-slide-up');
				const down = li.querySelector('.fnr-slide-down');
				if (up) up.disabled = i === 0;
				if (down) down.disabled = i === items.length - 1;
			});

			if (empty) empty.style.display = items.length ? 'none' : '';
			if (clearBtn) clearBtn.style.display = items.length ? '' : 'none';
		}

		function addSlide(attachment) {
			// Don't add the same image twice - slide order is a list, not a set,
			// but a duplicated slide is almost always a misclick.
			if (list.querySelector('[data-attachment-id="' + attachment.id + '"]')) return;

			const thumb = (attachment.sizes && attachment.sizes.thumbnail)
				? attachment.sizes.thumbnail.url
				: attachment.url;

			const li = document.createElement('li');
			li.className = 'fnr-slide-admin-item';
			li.dataset.attachmentId = attachment.id;
			li.innerHTML =
				'<span class="fnr-slide-admin-number"></span>' +
				'<img class="fnr-slide-admin-thumb" src="' + thumb + '" alt="" width="96" height="96" />' +
				'<span class="fnr-slide-admin-title"></span>' +
				'<span class="fnr-slide-admin-controls">' +
					'<button type="button" class="button fnr-slide-up">&uarr;</button>' +
					'<button type="button" class="button fnr-slide-down">&darr;</button>' +
					'<button type="button" class="button fnr-slide-remove">&times;</button>' +
				'</span>';

			// textContent, not innerHTML - filenames can contain markup.
			li.querySelector('.fnr-slide-admin-title').textContent = attachment.title || attachment.filename || '';

			list.appendChild(li);
		}

		addBtn.addEventListener('click', function (e) {
			e.preventDefault();

			if (!frame) {
				frame = wp.media({
					title: text('selectSlides', 'Select slide images'),
					button: { text: text('useSlides', 'Add to deck') },
					library: { type: 'image' },
					multiple: 'add'
				});

				frame.on('select', function () {
					frame.state().get('selection').forEach(function (model) {
						addSlide(model.toJSON());
					});
					sync();
				});
			}

			frame.open();
		});

		if (clearBtn) {
			clearBtn.addEventListener('click', function (e) {
				e.preventDefault();
				if (!window.confirm(text('confirmClear', 'Remove all slides from this lesson?'))) return;
				list.innerHTML = '';
				sync();
			});
		}

		list.addEventListener('click', function (e) {
			const btn = e.target.closest('button');
			if (!btn) return;

			const li = btn.closest('.fnr-slide-admin-item');
			if (!li) return;

			e.preventDefault();

			if (btn.classList.contains('fnr-slide-remove')) {
				li.remove();
			} else if (btn.classList.contains('fnr-slide-up') && li.previousElementSibling) {
				list.insertBefore(li, li.previousElementSibling);
			} else if (btn.classList.contains('fnr-slide-down') && li.nextElementSibling) {
				list.insertBefore(li.nextElementSibling, li);
			}

			sync();
		});

		sync();
	})();
})();
