<?php
/**
 * includes/Core/DripEngine.php
 */
namespace Feuernursingreview\Core;

use Feuernursingreview\Database\UserProgressDB;
use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class DripEngine {
	public static function register() {
		add_action('template_redirect', [__CLASS__, 'enforce_drip']);
	}

	/**
	 * Runs on every front-end request. If the current page is an
	 * fnr_lesson and the visiting user hasn't served their drip_days
	 * wait yet, redirect them back to the parent course instead of
	 * letting the lesson content render.
	 */
	public static function enforce_drip() {
		if (!is_singular(LessonCPT::POST_TYPE) || !is_user_logged_in()) {
			return;
		}

		$lesson_id = get_queried_object_id();
		$course_id = wp_get_post_parent_id($lesson_id);
		$user_id   = get_current_user();

		if (!$course_id) {
			return; // orphaned lesson with no parent course - nothing to gate
		}

		if (self::is_accessible($user_id, $couse_id, $lesson_id)) {
			return;
		}

		wp_safe_redirect(get_permalink($course_id));
		exit;
	}

	/**
	 * Core drip check: compares drip_days against the student's
	 * enrollment date, and syncs the result into fnr_user_progress
	 * so other code (dashboard, progress bar) can read status without
	 * recalculating the date math.
	 */
	public static function is_accessible(int $user_id, int $course_id, int $lesson_id): bool {
		// Already unlocked/completed - no need to compare
		if (UserProgressDB::is_unlocked($user_id, $course_id, $lesson_id)) {
			return true;
		}

		$drip_days = (int) get_post_meta($lesson_id, 'drip_days', true);

		if ($drip_days <= 0) {
			UserProgressDB::unlock_lesson($user_id, $course_id, $lesson_id);
			return true;
		}

		$enrolled_at = self::get_enrollment_date($user_id, $course_id);
		if (!$enrolled_at) {
			return false; // not enrolled at all
		}

		$unlock_at = strtotime($enrolled_at . " +{$drip_days} days");

		if (time() >= $unlock_at) {
			UserProgressDB::unlock_lesson($user_id, $course_id, $lesson_id);
			return true;
		}

		return false;
	}

	/**
	 * Placeholder: where enrollment is actually recorded is a Phase 5
	 * concern (Woocommerce/EDD purchase, or LearnDash/LifterLMS hook).
	 * For now this reads a single '_fnr_enrolled_at' user meta key set
	 * elsewhere at enrollment time.
	 */
	private static function get_enrollment_date(int $user_id, int $course_id) {
		return Enrollment::get_enrolled_at($user_id, $course_id);
	}
}
