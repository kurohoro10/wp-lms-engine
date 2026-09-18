// admin/js/course-lesson-order.js - corrected, single save function
(function () {
	'use strict';

	const list   = document.getElementById('fnr-lesson-order-list');
	const status = document.getElementById('fnr-lesson-order-status');
	if (!list) return;

	function currentOrder() {
		return Array.from(list.querySelectorAll('.fnr-lesson-order-item'))
			.map(function (el) { return el.dataset.lessonId;});
	}

	function refreshButtonStates() {
		const items = list.querySelectorAll('.fnr-lesson-order-item');
		items.forEach(function (item, index) {
			item.querySelector('.fnr-move-up').disabled = index === 0;
			item.querySelector('.fnr-move-down').disabled = index === items.length - 1;
		});
	}

	// Proper body construction: repeat lesson_ids[] once per ID.
	async function saveOrderReal() {
		status.textContent = fnrLessonOrder.i18n.saving;

		const params = new URLSearchParams();
		params.append('action', fnrLessonOrder.action);
		params.append('nonce', fnrLessonOrder.nonce);
		params.append('course_id', fnrLessonOrder.courseId);
		currentOrder().forEach(function(id){
			params.append('lesson_ids[]', id);
		});

		try {
			const response = await fetch(fnrLessonOrder.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded'},
				body: params.toString(),
			});
			const data = await response.json();

			status.textContent = data.success
				? fnrLessonOrder.i18n.saved
				: (data.data && data.data.message) || fnrLessonOrder.i18n.error;
		} catch (error) {
			status.textContent = fnrLessonOrder.i18n.error;
		}
	}

	// Drag-and-drop via SortableJS
	if (window.Sortable) {
		Sortable.create(list, {
			handle: '.fnr-drag-handle',
			animation: 150,
			onEnd: function () {
				refreshButtonStates();
				saveOrderReal();
			},
		});
	}

	/**
	 * Keyboard-accessible fallback: Up/Down buttons move the item and
	 * save the same way drag-and-drop does. This is the primary path
	 * for keyboard and screen-reader users, not an afterthought - drag
	 * handles are not operable without a pointer.
	 */
	list.addEventListener('click', function (e) {
		const btn = e.target.closest('.fnr-move-up, .fnr-move-down');
		if (!btn) return;

		const item = btn.closest('.fnr-lesson-order-item');
		const isUp = btn.classList.contains('fnr-move-up');
		const sibling = isUp ? item.previousElementSibling : item.nextElementSibling;

		if (!sibling) return;
		if (isUp) list.insertBefore(item, sibling);
		else list.insertBefore(sibling, item);

		refreshButtonStates();
		btn.focus(); // keep focus on the button the user just activated
		saveOrderReal();
	});
})();
