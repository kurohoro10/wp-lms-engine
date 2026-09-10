<?php
/**
 * includes/Core/Enrollment.php
 */
namespace Feuernursingreview\Core;

if (!defined('ABSPATH')) exit;

/**
 * Minimal enrollment tracking for Phase 2's drip logic to depend on.
 * This is intentionally NOT the final enrollment mechanism - Phase 5
 * will replace/extend this with Woocommerce/EDD purchase hooks and
 * LearnDash/LifterLMS integration. For now it just gives Drip/Engine
 * a real date to compare against instead of a TODO.
 */

class Enrollment {
	private static function meta_key(int $course_id): string {
		return "_fnr_enrolled_at{$course_id}";
	}

	public static function enroll(int $user_id, int $course_id): bool {
		if (self::is_enrolled($user_id, $course_id)) {
			return true; // don't reset an existing enrollment date
		}

		return (bool) update_user_meta(
			$user_id,
			self::meta_key($course_id),
			current_time('mysql')
		);
	}

	public static function is_enrolled(int $user_id, int $course_id): bool {
		return (bool) get_user_meta($user_id, self::meta_key($course_id), true);
	}

	public static function get_enrolled_at(int $user_id, int $course_id) {
		return get_user_meta($user_id, self::meta_key($course_id), true) ?: null;
	}

	/**
	 * All users enrolled in a course. Queries by meta_key since enrollment
	 * is stored as per-user meta, not a dedicated table.
	 */
	public static function get_enrolled_users(int $course_id): array {
		return get_users([
			'meta_key' => self::meta_key($course_id),
			'role'	   => 'fnr_student',
		]);
	}
}
