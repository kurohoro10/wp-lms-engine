<?php
/**
 * includes/Core/Bookmarks.php
 */
namespace Feuernursingreview\Core;

use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

/**
 * Bookmarked lessons per student. Stored  as a single serialized array on
 * user meta rather than per-lesson keys (contrast with Enrollment, which
 * uses one meta key per course) - the primary access pattern here is
 * "show me all of this student's bookmarks," so one key holding a list
 * is the simpler read, at the cost of a read-modify-write on toggle.
 */
class Bookmarks {
	const META_KEY = '_fnr_bookmarked_lessons';

	public static function get_bookmarked_lesson_ids(int $user_id): array {
		$ids = get_user_meta($user_id, self::META_KEY, true);
		return is_array($ids) ? array_map('absint', $ids) : [];
	}

	public static function is_bookmarked(int $user_id, int $lesson_id): bool {
		return in_array($lesson_id, self::get_bookmarked_lesson_ids($user_id), true);
	}

	/**
	 * Adds or removes the lesson from the user's bookmark list.
	 * Returns the new state (true = now bookmarked).
	 */
	public static function toggle(int $user_id, int $lesson_id): bool {
		$ids = self::get_bookmarked_lesson_ids($user_id);
		$is_bookmarked = in_array($lesson_id, $ids, true);

		if ($is_bookmarked) {
			$ids = array_values(array_diff($ids, [$lesson_id]));
		} else {
			$ids[] = $lesson_id;
		}

		update_user_meta($user_id, self::META_KEY, $ids);

		return !$is_bookmarked;
	}

	/**
	 * Full lesson post objects for a user's bookmarks, in bookmark order
	 * (not publish date) - used by the student dashboard's bookmark list.
	 * Silently drops IDs pointing at deleted/unpublished lessons.
	 */
	public static function get_bookmarked_lessons(int $user_id): array {
		$ids = self::get_bookmarked_lesson_ids($user_id);
		if (!$ids) return [];

		$lessons = get_posts([
			'post_type' 	=> LessonCPT::POST_TYPE,
			'post__in' 		=> $ids,
			'post_status' 	=> 'publish',
			'post_per_page' => -1,
			'orderby' 		=> 'post__in', // preserve bookmark order
		]);

		return $lessons;
	}
}
