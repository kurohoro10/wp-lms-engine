<?php
/**
 * incluedes/Database/UserProgressDB.php
 */
namespace Feuernursingreview\Database;

if (!defined('ABSPATH')) exit;

class UserProgressDB {

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'fnr_user_progress';
	}

	/**
	 * Fetch a single progress row, or null if it doesn't exist yet.
	 */
	public static function get_row(int $user_id, int $course_id, int $lesson_id) {
		global $wpdb;
		$table = self::table();

		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table WHERE user_id = %d AND course_id = %d AND lesson_id = %d", $user_id, $course_id, $lesson_id
		));
	}

	/**
	 * Ensure a row exists for this user/course/lesson. Creates it as 'locked'
	 * if missing. Returns the row.
	 */
	public static function get_or_create(int $user_id, int $course_id, int $lesson_id) {
		$row = self::get_row($user_id, $course_id, $lesson_id);
		if ($row) {
			return $row;
		}

		global $wpdb;
		$wpdb->insert(self::table(), [
			'user_id' 	=> $user_id,
			'course_id' => $course_id,
			'lesson_id' => $lesson_id,
			'status' 	=> 'locked',
		]);

		return self::get_row($user_id, $course_id, $lesson_id);
	}

	/**
	 * Transition a row to 'unlocked' and stamp unlocked_at, but only if it
	 * isn't already unlocked or completed (avoids overwriting the original
	 * unlock timestamp on repeat checks).
	 */
	public static function unlock_lesson(int $user_id, int $course_id, int $lesson_id): bool {
		global $wpdb;
		$row = self::get_or_create($user_id, $course_id, $lesson_id);

		if ($row->status !== 'locked') {
			return true; // already unlocked or completed
		}

		$updated = $wpdb->update(
			self::table(),
			['status' => 'unlocked', 'unlocked_at' => current_time('mysql')],
			['id' => $row->id],
			['%s', '%s'],
			['%d']
		);

		return $updated !== false;
	}

	/**
	 * Transition a row to 'completed' and stamp completed_at.
	 */
	public static function mark_completed(int $user_id, int $course_id, int $lesson_id): bool {
		global $wpdb;
		$row = self::get_or_create($user_id, $course_id, $lesson_id);

		$updated = $wpdb->update(
			self::table(),
			['status' => 'completed', 'completed_at' => current_time('mysql')],
			['id' => $row->id],
			['%s', '%s'],
			['%d']
		);

		return $updated !== false;
	}

	public static function is_unlocked(int $user_id, int $course_id, int $lesson_id): bool {
		$row = self::get_row($user_id, $course_id, $lesson_id);
		return $row && in_array($row->status, ['unlocked', 'completed'], true);
	}

	public static function is_completed(int $user_id, int $course_id, int $lesson_id): bool {
		$row = self::get_row($user_id, $course_id, $lesson_id);
		return $row && $row->status === 'completed';
	}

	/**
	 * All progress rows for a user within a course - used for the student
	 * dashboard's "X of Y lessons complete" progress bar.
	 */
	public static function get_course_progress(int $user_id, int $course_id): array {
		global $wpdb;
		$table = self::table();

		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $table WHERE user_id = %d AND course_id = %d", $user_id, $course_id
		));
	}
}
