// public/js/bookmark-toggle.js
(function() {
	'use strict';

	const lessonEl = document.getElementById('fnr-lesson');
	const btn = document.getElementById('fnr-bookmark-toggle');
	if (!lessonEl || !btn) return;

	const icon = btn.querySelector('.fnr-bookmark-icon');
	const label = btn.querySelector('.fnr-bookmark-label');

	btn.addEventListener('click', async function () {
		btn.disabled = true;
		btn.setAttribute('aria-busy', 'true');

		try {
			const response = await fetch(fnrLessonProgress.restUrl + 'toggle-bookmark', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': fnrLessonProgress.nonce,
				},
				body: JSON.stringify({lesson_id: lessonEl.dataset.lessonId}),
			});

			const data = await response.json();
			if (!response.ok) throw new Error (data.error || fnrLessonProgress.i18n.error);

			btn.setAttribute('aria-pressed', data.bookmarked ? 'true' : 'false');
			icon.textContent = data.bookmarked ? '★' : '☆';
			label.textContent = data.bookmarked
				? fnrLessonProgress.i18n.bookmarked
				: fnrLessonProgress.i18n.bookmarkThis;
		} catch (error) {
			/**
			 * Silent-ish failure is acceptable here - bookmarking is a
			 * low-stakes convenience action, not something that needs
			 * the same visible error banner as mark-complete/drip.
			 */
			console.error(error.message);
		} finally {
			btn.disabled = false;
			btn.removeAttribute('aria-busy');
		}
	});
})();
