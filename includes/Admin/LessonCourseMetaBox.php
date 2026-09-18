<?php
/**
 * includes/Admin/LessonCourseMetaBox.php
 *
 * Picks which course a lesson belongs to (writes post_parent).
 *
 * Core's Page Attributes box can't do this - it only offers parents of
 * the SAME post type, so on an fnr_lesson screen it would list other
 * lessons, never courses. Same problem ModuleCourseMetaBox solves for
 * fnr_module -> fnr_course; this is the lesson -> course counterpart.
 *
 * ProgressController::mark_complete(), DripEngine, and
 * CourseLessonOrderMetaBox all read the lesson's post_parent expecting
 * it to be a course ID, so without this box there was no way to ever
 * set it.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\CourseCPT;

if (!defined('ABSPATH')) exit;

class LessonCourseMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_lesson_course';
	const NONCE_FIELD  = 'fnr_lesson_course_nonce';

	protected static function id(): string { return 'fnr_lesson_course'; }
	protected static function title(): string { return __('Course', 'feuernursingreview'); }
	protected static function post_type(): string { return LessonCPT::POST_TYPE; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		$courses = get_posts([
			'post_type'      => CourseCPT::POST_TYPE,
			'post_status'    => ['publish', 'draft', 'pending', 'private'],
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		]);

		static::render_template('lesson-course-metabox', [
			'courses'      => $courses,
			'selected'     => (int) $post->post_parent,
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$course_id = isset($_POST['fnr_lesson_course']) ? absint($_POST['fnr_lesson_course']) : 0;

		if ($course_id && get_post_type($course_id) !== CourseCPT::POST_TYPE) {
			$course_id = 0;
		}

		/**
		 * remove_action/add_action around wp_update_post: we're inside
		 * save_post_fnr_lesson, and wp_update_post() fires save_post
		 * again. Without unhooking, save() re-enters, the nonce is
		 * still valid, and you get an infinite loop on this screen.
		 */
		remove_action('save_post_' . static::post_type(), [static::class, 'save']);

		wp_update_post([
			'ID'          => $post_id,
			'post_parent' => $course_id,
		]);

		add_action('save_post_' . static::post_type(), [static::class, 'save']);
	}
}
