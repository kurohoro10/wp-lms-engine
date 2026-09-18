<?php
/**
 * includes/Core/DripEngine.php
 *
 * Decides whether a student may open a given lesson yet.
 *
 * A lesson carries a 'drip_type' meta which selects one of four gates:
 *
 *   immediate  available as soon as the student is enrolled
 *   days       available N days after that student's enrollment date
 *   date       available on/after a fixed calendar date, same for everyone
 *   quiz       available once the student's best attempt on a chosen quiz
 *              reaches a minimum percentage
 *
 * Unlocking is one-way and sticky: once fnr_user_progress records a row
 * as 'unlocked' we never re-lock it, so moving a drip date forward can't
 * yank content back from a student who already had access.
 *
 * Backwards compatibility: lessons saved before drip_type existed have no
 * such meta. Those fall back to 'days' when drip_days > 0, otherwise
 * 'immediate', which is exactly the old behaviour.
 */
namespace Feuernursingreview\Core;

use Feuernursingreview\Database\UserProgressDB;
use Feuernursingreview\Database\QuizAttemptsDB;
use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\QuizCPT;

if (!defined('ABSPATH')) exit;

class DripEngine {
	const TYPE_IMMEDIATE = 'immediate';
	const TYPE_DAYS      = 'days';
	const TYPE_DATE      = 'date';
	const TYPE_QUIZ      = 'quiz';

	public static function types(): array {
		return [self::TYPE_IMMEDIATE, self::TYPE_DAYS, self::TYPE_DATE, self::TYPE_QUIZ];
	}

	public static function register() {
		add_action('template_redirect', [__CLASS__, 'enforce_drip']);

		/**
		 * Quiz gates are normally resolved lazily, the next time the
		 * student loads the course or lesson page. This hook just makes
		 * the unlock immediate on submission so the course page doesn't
		 * still say "locked" when they navigate straight back to it.
		 *
		 * Fired by QuizAttemptsDB::complete_attempt().
		 */
		add_action('fnr_quiz_attempt_completed', [__CLASS__, 'on_quiz_completed'], 10, 2);
	}

	/**
	 * Runs on every front-end request. If the current page is an
	 * fnr_lesson the visiting user hasn't unlocked yet, redirect them
	 * back to the parent course instead of rendering the lesson.
	 */
	public static function enforce_drip() {
		if (!is_singular(LessonCPT::POST_TYPE) || !is_user_logged_in()) {
			return;
		}

		$lesson_id = get_queried_object_id();
		$course_id = wp_get_post_parent_id($lesson_id);
		$user_id   = get_current_user_id();

		if (!$course_id) {
			return; // orphaned lesson with no parent course - nothing to gate
		}

		if (self::is_accessible($user_id, $course_id, $lesson_id)) {
			return;
		}

		wp_safe_redirect(add_query_arg('fnr_locked', $lesson_id, get_permalink($course_id)));
		exit;
	}

	/**
	 * Core gate. Syncs the result into fnr_user_progress so the dashboard
	 * and progress bar can read status without repeating the date math.
	 */
	public static function is_accessible(int $user_id, int $course_id, int $lesson_id): bool {
		if (UserProgressDB::is_unlocked($user_id, $course_id, $lesson_id)) {
			return true; // sticky - already unlocked or completed
		}

		/**
		 * Every gate below is relative to enrollment in some way: even a
		 * fixed date shouldn't hand content to someone who was never
		 * enrolled in the course.
		 */
		$enrolled_at = Enrollment::get_enrolled_at($user_id, $course_id);
		if (!$enrolled_at) {
			return false;
		}

		if (!self::gate_passed($user_id, $lesson_id, $enrolled_at)) {
			return false;
		}

		UserProgressDB::unlock_lesson($user_id, $course_id, $lesson_id);
		return true;
	}

	/**
	 * The gate itself, with no progress-table side effects. Split out so
	 * reason() and the course template can ask "would this pass?" without
	 * writing rows.
	 */
	private static function gate_passed(int $user_id, int $lesson_id, string $enrolled_at): bool {
		$config = self::get_config($lesson_id);

		switch ($config['type']) {
			case self::TYPE_DAYS:
				if ($config['days'] <= 0) {
					return true;
				}
				return current_time('timestamp') >= self::unlock_timestamp_for($enrolled_at, $config['days']);

			case self::TYPE_DATE:
				if (!$config['date']) {
					return true; // no date set - don't lock content behind a blank field
				}
				return current_time('timestamp') >= self::to_timestamp($config['date']);

			case self::TYPE_QUIZ:
				if (!$config['quiz_id']) {
					return true;
				}
				$best = QuizAttemptsDB::get_best_percentage($user_id, $config['quiz_id']);
				return $best !== null && $best >= $config['quiz_min_score'];

			case self::TYPE_IMMEDIATE:
			default:
				return true;
		}
	}

	/**
	 * Normalized drip settings for a lesson, with the pre-drip_type
	 * fallback applied.
	 */
	public static function get_config(int $lesson_id): array {
		$type = (string) get_post_meta($lesson_id, 'drip_type', true);
		$days = (int) get_post_meta($lesson_id, 'drip_days', true);

		if (!in_array($type, self::types(), true)) {
			$type = $days > 0 ? self::TYPE_DAYS : self::TYPE_IMMEDIATE;
		}

		$min_score = get_post_meta($lesson_id, 'drip_quiz_min_score', true);

		return [
			'type'           => $type,
			'days'           => $days,
			'date'           => (string) get_post_meta($lesson_id, 'drip_date', true),
			'quiz_id'        => (int) get_post_meta($lesson_id, 'drip_quiz_id', true),
			'quiz_min_score' => $min_score !== '' ? (float) $min_score : 75.0,
		];
	}

	/**
	 * Human-readable explanation of why a lesson is still locked, for the
	 * course page. Returns an empty string when the lesson isn't gated or
	 * the reason can't be phrased usefully.
	 */
	public static function reason(int $user_id, int $course_id, int $lesson_id): string {
		$config = self::get_config($lesson_id);

		switch ($config['type']) {
			case self::TYPE_DAYS:
				$enrolled_at = Enrollment::get_enrolled_at($user_id, $course_id);

				if (!$enrolled_at) {
					return __('Enroll to start the unlock schedule.', 'feuernursingreview');
				}

				$unlock_ts = self::unlock_timestamp_for($enrolled_at, $config['days']);
				$remaining = (int) ceil(($unlock_ts - current_time('timestamp')) / DAY_IN_SECONDS);

				if ($remaining <= 0) {
					return '';
				}

				return sprintf(
					// translators: %d: number of days
					_n('Unlocks in %d day', 'Unlocks in %d days', $remaining, 'feuernursingreview'),
					$remaining
				);

			case self::TYPE_DATE:
				if (!$config['date']) {
					return '';
				}

				return sprintf(
					// translators: %s: formatted date
					__('Unlocks on %s', 'feuernursingreview'),
					wp_date(get_option('date_format'), self::to_timestamp($config['date']))
				);

			case self::TYPE_QUIZ:
				if (!$config['quiz_id']) {
					return '';
				}

				$best = QuizAttemptsDB::get_best_percentage($user_id, $config['quiz_id']);

				if ($best === null) {
					return sprintf(
						// translators: 1: quiz title, 2: required percentage
						__('Score %2$s%% or higher on "%1$s" to unlock.', 'feuernursingreview'),
						get_the_title($config['quiz_id']),
						round($config['quiz_min_score'])
					);
				}

				return sprintf(
					// translators: 1: quiz title, 2: required percentage, 3: the student's best score so far
					__('Score %2$s%% or higher on "%1$s" to unlock — your best so far is %3$s%%.', 'feuernursingreview'),
					get_the_title($config['quiz_id']),
					round($config['quiz_min_score']),
					round($best)
				);

			default:
				return '';
		}
	}

	/**
	 * Re-run the gate for every quiz-gated lesson in the courses this
	 * student is enrolled in, so passing a quiz unlocks its dependants
	 * right away rather than on next page load.
	 */
	public static function on_quiz_completed(int $attempt_id, int $user_id) {
		$attempt = QuizAttemptsDB::get_attempt($attempt_id);

		if (!$attempt) {
			return;
		}

		$quiz_id = (int) $attempt->quiz_id;

		$lessons = get_posts([
			'post_type'      => LessonCPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				'relation' => 'AND',
				['key' => 'drip_type', 'value' => self::TYPE_QUIZ],
				['key' => 'drip_quiz_id', 'value' => $quiz_id, 'type' => 'NUMERIC'],
			],
		]);

		foreach ($lessons as $lesson_id) {
			$course_id = wp_get_post_parent_id($lesson_id);

			if ($course_id) {
				self::is_accessible($user_id, (int) $course_id, (int) $lesson_id);
			}
		}
	}

	/**
	 * Day-offset unlocks land at local midnight rather than at the exact
	 * clock time of enrollment. Someone who enrolled at 11pm shouldn't
	 * have to wait until 11pm on day N - "3 days" should mean the start
	 * of that day.
	 */
	private static function unlock_timestamp_for(string $enrolled_at, int $days): int {
		$base = self::to_timestamp($enrolled_at);
		$midnight = self::to_timestamp(wp_date('Y-m-d 00:00:00', $base));

		return $midnight + ($days * DAY_IN_SECONDS);
	}

	/**
	 * Parse a 'Y-m-d H:i:s' string stored in site-local time into a real
	 * UTC timestamp. Plain strtotime() would read it as server time,
	 * which drifts from the site's timezone setting.
	 */
	private static function to_timestamp(string $local_datetime): int {
		$ts = strtotime(get_gmt_from_date($local_datetime) . ' UTC');

		return $ts ?: 0;
	}
}
