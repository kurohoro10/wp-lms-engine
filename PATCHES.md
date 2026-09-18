# Two small edits to existing files

Everything else in this bundle is a drop-in replacement or a new file.
These two need a manual edit because they're single additions to files
you already have.

---

## 1. `includes/Database/QuizAttemptsDB.php`

Add this method to the class. `DripEngine` calls it for the quiz gate.

```php
	/**
	 * The student's best percentage across all COMPLETED attempts on a
	 * quiz, or null if they've never finished one.
	 *
	 * Only completed attempts count - an abandoned attempt sitting at 0%
	 * shouldn't drag a gate down, and an in-progress one that happens to
	 * be at 100% after one question shouldn't open it early.
	 */
	public static function get_best_percentage(int $user_id, int $quiz_id): ?float {
		global $wpdb;
		$table = self::table();

		$best = $wpdb->get_var($wpdb->prepare(
			"SELECT MAX(percentage) FROM $table
			WHERE user_id = %d AND quiz_id = %d AND completed_at IS NOT NULL",
			$user_id,
			$quiz_id
		));

		return $best === null ? null : (float) $best;
	}
```

Then, at the end of `complete_attempt()`, fire the action `DripEngine`
listens for. Replace the closing `return` with:

```php
		if ($updated !== false) {
			/**
			 * Lets DripEngine unlock any quiz-gated lessons straight away
			 * rather than on the student's next page load.
			 */
			do_action('fnr_quiz_attempt_completed', $attempt_id, (int) $attempt->user_id);
		}

		return $updated !== false;
```

The hook is an optimisation, not a requirement — the gate is also
evaluated lazily whenever the course or lesson page loads, so quiz drip
still works correctly without it.

---

## 2. `public/js/lesson-progress.js`

One line, next to the existing video listener at the bottom:

```js
	document.addEventListener('fnr:video-watched', markComplete);
	document.addEventListener('fnr:slides-viewed', markComplete);   // <- add
```

Without it the slide viewer still works; it just won't auto-mark the
lesson complete when the student reaches the end of the deck.

While you're in that file, line ~44 has `button.removedAttribute(...)`
in the catch block — should be `removeAttribute`. As written, any failed
save throws a second error and swallows the message.
