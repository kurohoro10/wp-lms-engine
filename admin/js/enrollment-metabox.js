/**
 * admin/js/enrollment-metabox.js
 *
 * Behaviour for the "Enrolled Students" meta box on the fnr_course
 * edit screen: opens/closes the checkbox panel, filters the list via
 * the search field, and keeps the toggle label's enrolled count in
 * sync as boxes are checked.
 *
 * No form submission happens here - the checkboxes are part of the
 * main #post form and save on Update/Publish via
 * AbstractSaveableMetaBox::save().
 */
(function () {
	'use strict';

	function init(root) {
		var toggle = root.querySelector('.fnr-enroll-dropdown__toggle');
		var panel = root.querySelector('.fnr-enroll-dropdown__panel');
		var label = root.querySelector('.fnr-enroll-dropdown__label');
		var search = root.querySelector('.fnr-enroll-dropdown__search');
		var items = root.querySelectorAll('.fnr-enroll-dropdown__item');

		if (!toggle || !panel) {
			return;
		}

		function isOpen() {
			return !panel.hasAttribute('hidden');
		}

		function open() {
			panel.removeAttribute('hidden');
			toggle.setAttribute('aria-expanded', 'true');
			if (search) {
				search.focus();
			}
		}

		function close() {
			panel.setAttribute('hidden', '');
			toggle.setAttribute('aria-expanded', 'false');
		}

		function countChecked() {
			return root.querySelectorAll('input[name="fnr_enrolled_users[]"]:checked').length;
		}

		function updateLabel() {
			if (!label) {
				return;
			}
			var n = countChecked();
			if (n === 0) {
				label.textContent = 'Select students…';
			} else if (n === 1) {
				label.textContent = '1 student enrolled';
			} else {
				label.textContent = n + ' students enrolled';
			}
		}

		toggle.addEventListener('click', function (e) {
			e.preventDefault();
			if (isOpen()) {
				close();
			} else {
				open();
			}
		});

		// Keep the panel open while interacting with it.
		panel.addEventListener('click', function (e) {
			e.stopPropagation();
		});

		document.addEventListener('click', function (e) {
			if (isOpen() && !root.contains(e.target)) {
				close();
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && isOpen()) {
				close();
				toggle.focus();
			}
		});

		if (search) {
			search.addEventListener('input', function () {
				var term = search.value.trim().toLowerCase();

				Array.prototype.forEach.call(items, function (item) {
					var nameEl = item.querySelector('.fnr-enroll-dropdown__name');
					var name = nameEl ? nameEl.textContent.toLowerCase() : '';
					item.style.display = (!term || name.indexOf(term) !== -1) ? '' : 'none';
				});
			});
		}

		root.addEventListener('change', function (e) {
			if (e.target && e.target.name === 'fnr_enrolled_users[]') {
				updateLabel();
			}
		});

		updateLabel();
	}

	function boot() {
		Array.prototype.forEach.call(
			document.querySelectorAll('.fnr-enrollment-box'),
			init
		);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
