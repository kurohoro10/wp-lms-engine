// public/js/slide-viewer.js
//
// Single-slide-at-a-time viewer for lessons with media_type = slides.
//
// All slides are already in the DOM (see MediaPlayer::render_slides);
// this only manages which one is visible, the counter, and the
// "student reached the last slide" signal that lesson-progress.js
// listens for.
(function () {
	'use strict';

	const deck = document.querySelector('[data-fnr-slides]');
	if (!deck) return;

	const slides  = Array.prototype.slice.call(deck.querySelectorAll('.fnr-slide'));
	const prevBtn = deck.querySelector('.fnr-slides__prev');
	const nextBtn = deck.querySelector('.fnr-slides__next');
	const current = deck.querySelector('.fnr-slides__current');

	if (slides.length === 0) return;

	const seen = new Set([0]);
	let index = 0;
	let fired = false;

	function announceViewed() {
		if (fired) return;
		// Require every slide to have been shown, not just the last one -
		// jumping straight to the end via the hash shouldn't count as
		// having worked through the deck.
		if (seen.size < slides.length) return;

		fired = true;
		document.dispatchEvent(new CustomEvent('fnr:slides-viewed', {
			detail: { lessonId: deck.dataset.lessonId, total: slides.length }
		}));
	}

	function show(next, opts) {
		opts = opts || {};

		if (next < 0 || next >= slides.length || next === index) return;

		slides[index].hidden = true;
		slides[next].hidden = false;
		index = next;
		seen.add(next);

		if (current) current.textContent = String(next + 1);
		if (prevBtn) prevBtn.disabled = next === 0;
		if (nextBtn) nextBtn.disabled = next === slides.length - 1;

		// Move focus to the slide itself so screen readers and keyboard
		// users land on the new content rather than staying on a button
		// that may have just become disabled.
		if (opts.focus !== false) {
			slides[next].setAttribute('tabindex', '-1');
			slides[next].focus({ preventScroll: true });
		}

		if (history.replaceState) {
			history.replaceState(null, '', '#fnr-slide-' + (next + 1));
		}

		announceViewed();
	}

	if (prevBtn) prevBtn.addEventListener('click', function () { show(index - 1); });
	if (nextBtn) nextBtn.addEventListener('click', function () { show(index + 1); });

	deck.addEventListener('keydown', function (e) {
		switch (e.key) {
			case 'ArrowLeft':
				e.preventDefault();
				show(index - 1);
				break;
			case 'ArrowRight':
				e.preventDefault();
				show(index + 1);
				break;
			case 'Home':
				e.preventDefault();
				show(0);
				break;
			case 'End':
				e.preventDefault();
				show(slides.length - 1);
				break;
		}
	});

	// Basic swipe support. Deliberately not a full gesture library -
	// horizontal intent only, so a vertical page scroll that drifts
	// sideways doesn't flip the slide.
	let touchStartX = null;
	let touchStartY = null;

	deck.addEventListener('touchstart', function (e) {
		if (e.touches.length !== 1) return;
		touchStartX = e.touches[0].clientX;
		touchStartY = e.touches[0].clientY;
	}, { passive: true });

	deck.addEventListener('touchend', function (e) {
		if (touchStartX === null) return;

		const dx = e.changedTouches[0].clientX - touchStartX;
		const dy = e.changedTouches[0].clientY - touchStartY;

		if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) {
			show(dx < 0 ? index + 1 : index - 1, { focus: false });
		}

		touchStartX = null;
		touchStartY = null;
	}, { passive: true });

	// Deep link: /lesson/#fnr-slide-4 opens on that slide.
	const match = /^#fnr-slide-(\d+)$/.exec(window.location.hash);
	if (match) {
		const target = parseInt(match[1], 10) - 1;
		if (target > 0 && target < slides.length) {
			show(target, { focus: false });
		}
	}

	// Single-slide decks are "viewed" the moment they render.
	announceViewed();
})();
