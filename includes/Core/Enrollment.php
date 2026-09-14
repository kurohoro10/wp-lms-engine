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
		return "_fnr_enrolled_at_{$course_id}";
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

	public static function unenroll(int $user_id, int $course_id): bool {
		return (bool) delete_user_meta($user_id, self::meta_key($course_id));
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

	/**
	 * All course IDs a user is enrolled in. Queries usermeta directly since
	 * enrollment keys are per-course (_fnr_enrolled_at_{course_id}) and
	 * get_user_meta() has no wildcard/LIKE lookup - this is the one place
	 * that needs to go around it.
	 */
	public static function get_enrolled_course_ids(int $user_id): array {
		global $wpdb;

		$keys = $wpdb->get_col($wpdb->prepare(
			"SELECT meta_key FROM {$wpdb->usermeta}
			WHERE user_id = %d AND meta_key LIKE %s",
			$user_id,
			$wpdb->esc_like('_fnr_enrolled_at_') . '%'
		));

		$course_ids = [];
		foreach ($keys as $key) {
			$course_id = (int) str_replace('_fnr_enrolled_at_', '', $key);
			if ($course_id > 0) {
				$course_ids[] = $course_id;
			}
		}

		return $course_ids;
	}
}
