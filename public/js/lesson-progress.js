( function () {
	'use strict';

	const lessonEl = document.getElementById('fnr-lesson');
	const button   = document.getElementById('fnr-mark-complete');
	const feedback = document.getElementById('fnr-mark-complete-feedback');

	if (!lessonEl) return;

	async function markComplete() {
		if (button && button.disabled) return;

		if (button) {
			button.disabled = true;
			button.setAttribute('aria-busy', 'true');
		}

		if (feedback) feedback.textContent = fnrLessonProgress.i18n.saving;

		try {
			const response = await fetch (fnrLessonProgress.restUrl + 'mark-complete', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce'  : fnrLessonProgress.nonce,
				},
				body: JSON.stringify({lesson_id: lessonEl.dataset.lessonId}),
			});

			const data = await response.json();

			if (!response.ok) throw new Error(data.error || fnrLessonProgress.i18n.error);

			if (button) {
				button.textContent   = fnrLessonProgress.i18n.completed;
				button.setAttribute('aria-pressed', 'true');
				button.removeAttribute('aria-busy');
			}

			if (feedback) feedback.textContent = fnrLessonProgress.i18n.saved;
		} catch (err) {
			if (button) {
				button.disabled = false;
				button.removeAttribute('aria-busy');
			}

			if (feedback) feedback.textContent = err.message;
		}
	}

	if (button) button.addEventListener('click', markComplete);

	document.addEventListener('fnr:video-watched', markComplete);
	document.addEventListener('fnr:slides-viewed', markComplete);
})();
