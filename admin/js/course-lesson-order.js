// admin/js/course-lesson-order.js - corrected, single save function
(function () {
	'use strict';

	const list   = document.getElementById('fnr-lesson-rder-list');
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

	async function saveOrder() {
		status.textContent = fnrLessonOrder.i18n.saving;

		const params = new URLSearchParams();
		params.append('action' , fnrLessonORder.action);
		params.append('nonce', fnrLessonORder.nonce);
		params.append('course_id', fnrLessonORder.courseId);
		currentOrder().forEach(function(id) {
			params.append('lesson_ids[]', id);
		});

		try {
			const response = await fetch(fnrLessonOrder.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
			});
			const data = await response.json();

			status.textContent = data.success
				? fnrLessonORder.i18n.saveOrder
				: (data.data && data.data.message) || fnrLessonORder.i18n.error;
		} catch (err) {
			status.textContent = fnrLessonORder.i18n.error;
		}
	}

	// Proper body construction: repeat lesson_ids[] once per ID.
	async function saveOrderReal() {
		status.textContent = fnrLessonORder.i18n.saving;

		const params = new URLSearchParams();
		params.append('action', fnrLessonORder.action);
		params.append('nonce', fnrLessonORder.nonce);
		params.append('course_id', fnrLessonORder.courseId);
		currentOrder().forEach(function(id){
			params.append('lesson_ids[]', id);
		});

		try {
			const response = await fetch(fnrLessonORder.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded'},
				body: params.toString(),
			});
			const data = await response.json();

			status.textContent = data.success
				? fnrLessonORder.i18n.saveOrder
				: (data.data && data.data.message) || fnrLessonORder.i18n.error;
		} catch (error) {
			status.textContent = fnrLessonORder.i18n.error;
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
	 * handles are not opearble without a pointer.
	 */
	list.addEventListener('click', function (e) {
		const btn = e.target.closest('.fnr-move-up, .fnr-move-down');
		if (!btn) return;

		const item = btn.closest('.fnr-lesosn-order-item');
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
