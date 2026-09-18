<?php
/**
 * includes/Core/CourseCascade.php
 *
 * Course-level cascade for its modules and quizzes only (both linked
 * via post_parent, same relationship as lessons). Lessons are
 * deliberately NOT cascaded - a course and its modules/quizzes are a
 * structural unit, but a lesson is content that happens to be placed
 * in a course, so deleting the course unassigns its lessons (clears
 * post_parent to 0) rather than trashing or deleting them. This
 * mirrors Modules::detach_lessons(), which does the same thing one
 * level down when a module is removed.
 */
namespace Feuernursingreview\Core;

use Feuernursingreview\CPT\CourseCPT;
use Feuernursingreview\CPT\ModuleCPT;
use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\QuizCPT;

if (!defined('ABSPATH')) exit;

class CourseCascade {

	const CASCADE_FLAG = '_fnr_cascade_trashed_by';

	public static function register() {
		add_action('trashed_post', [__CLASS__, 'cascade_trash']);
		add_action('untrashed_post', [__CLASS__, 'cascade_untrash']);
		add_action('before_delete_post', [__CLASS__, 'cascade_delete']);
	}

	public static function cascade_trash($course_id) {
		if (get_post_type($course_id) !== CourseCPT::POST_TYPE) {
			return;
		}

		foreach (self::get_structural_children($course_id) as $child) {
			if ($child->post_status === 'trash') {
				continue; // already trashed on its own - don't claim it
			}

			update_post_meta($child->ID, self::CASCADE_FLAG, $course_id);
			wp_trash_post($child->ID);
		}

		self::unassign_lessons($course_id);
	}

	public static function cascade_untrash($course_id) {
		if (get_post_type($course_id) !== CourseCPT::POST_TYPE) {
			return;
		}

		$children = get_posts([
			'post_type'      => [ModuleCPT::POST_TYPE, QuizCPT::POST_TYPE],
			'post_status'    => 'trash',
			'posts_per_page' => -1,
			'meta_key'       => self::CASCADE_FLAG,
			'meta_value'     => $course_id,
		]);

		foreach ($children as $child) {
			wp_untrash_post($child->ID);
			delete_post_meta($child->ID, self::CASCADE_FLAG);
		}

		// Lessons are intentionally NOT reattached here - once
		// unassigned they stay unassigned until an instructor picks a
		// course again via LessonCourseMetaBox, same as a lesson whose
		// module was deleted doesn't auto-rejoin the module if it
		// somehow came back.
	}

	public static function cascade_delete($course_id) {
		if (get_post_type($course_id) !== CourseCPT::POST_TYPE) {
			return;
		}

		foreach (self::get_structural_children($course_id, ['publish', 'draft', 'pending', 'private', 'trash']) as $child) {
			wp_delete_post($child->ID, true); // true = skip trash, permanent
		}

		self::unassign_lessons($course_id);
		self::cleanup_course_data((int) $course_id);
	}

	/**
	 * Modules and quizzes only - the structural unit that goes with the
	 * course. Lessons are handled separately by unassign_lessons().
	 */
	private static function get_structural_children(int $course_id, array $statuses = ['publish', 'draft', 'pending', 'private']): array {
		return get_posts([
			'post_type'      => [ModuleCPT::POST_TYPE, QuizCPT::POST_TYPE],
			'post_parent'    => $course_id,
			'post_status'    => $statuses,
			'posts_per_page' => -1,
		]);
	}

	/**
	 * Detach every lesson pointed at this course: clear post_parent (so
	 * it stops resolving to a dead course ID) and clear its module_id
	 * meta too, since a module assignment is meaningless without the
	 * course it belonged to.
	 */
	private static function unassign_lessons(int $course_id): void {
		$lessons = get_posts([
			'post_type'      => LessonCPT::POST_TYPE,
			'post_parent'    => $course_id,
			'post_status'    => ['publish', 'draft', 'pending', 'private'],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]);

		foreach ($lessons as $lesson_id) {
			wp_update_post(['ID' => $lesson_id, 'post_parent' => 0]);
			delete_post_meta($lesson_id, Modules::LESSON_MODULE_META);
		}
	}

	/**
	 * Custom-table rows and enrollment usermeta don't live in wp_posts,
	 * so wp_delete_post() never touches them - this is what
	 * uninstall.php does for the whole plugin, scoped to one course.
	 */
	private static function cleanup_course_data(int $course_id): void {
		global $wpdb;

		$wpdb->delete($wpdb->prefix . 'fnr_user_progress', ['course_id' => $course_id], ['%d']);

		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
			"_fnr_enrolled_at_{$course_id}"
		));
	}
}
