// admin/js/enrollment-metabox.js
// Toggle + live label + search filter for the "Enrolled Students"
// checkbox dropdown. No form submission here - the checkboxes are
// plain fields inside WP's main #post form and save with the normal
// Update/Publish action (see EnrollmentMetabox::save_fields).
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var box = document.querySelector('.fnr-enrollment-box');
		if (!box) return;

		var toggle = box.querySelector('.fnr-enroll-dropdown__toggle');
		var panel  = box.querySelector('.fnr-enroll-dropdown__panel');
		var label  = box.querySelector('.fnr-enroll-dropdown__label');
		var search = box.querySelector('.fnr-enroll-dropdown__search');
		var checkboxes = box.querySelectorAll('input[name="fnr_enrolled_users[]"]');

		if (!toggle || !panel) return;

		function closePanel() {
			panel.hidden = true;
			toggle.setAttribute('aria-expanded', 'false');
		}

		function openPanel() {
			panel.hidden = false;
			toggle.setAttribute('aria-expanded', 'true');
			if (search) search.focus();
		}

		toggle.addEventListener('click', function (e) {
			e.preventDefault();
			if (panel.hidden) {
				openPanel();
			} else {
				closePanel();
			}
		});

		// Close when clicking outside the widget.
		document.addEventListener('click', function (e) {
			if (!box.contains(e.target)) {
				closePanel();
			}
		});

		// Don't let a click inside the panel bubble up and close itself.
		panel.addEventListener('click', function (e) {
			e.stopPropagation();
		});

		function updateLabel() {
			if (!label) return;
			var count = 0;
			checkboxes.forEach(function (cb) {
				if (cb.checked) count++;
			});
			label.textContent = count
				? count + (count === 1 ? ' student enrolled' : ' students enrolled')
				: 'Select students…';
		}

		checkboxes.forEach(function (cb) {
			cb.addEventListener('change', updateLabel);
		});

		if (search) {
			search.addEventListener('input', function () {
				var query = search.value.trim().toLowerCase();
				var items = box.querySelectorAll('.fnr-enroll-dropdown__item');
				items.forEach(function (item) {
					var name = item.querySelector('.fnr-enroll-dropdown__name');
					var text = name ? name.textContent.toLowerCase() : '';
					item.style.display = text.indexOf(query) !== -1 ? '' : 'none';
				});
			});
		}
	});
})();
