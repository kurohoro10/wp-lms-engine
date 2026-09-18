<?php
/**
 * includes/Admin/QuizCourseMetaBox.php
 *
 * Picks which course a quiz belongs to (writes post_parent). Same
 * pattern as LessonCourseMetaBox: WP's own Page Attributes box only
 * offers same-post-type parents, so an fnr_quiz screen would otherwise
 * list other quizzes, never courses.
 *
 * Modules::get_course_quizzes() and the "Back to Course" links in
 * single-fnr_quiz.php / quiz-runner.js all read the quiz's post_parent
 * expecting it to be a course ID, so without this box there's no way
 * to ever set it.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\QuizCPT;
use Feuernursingreview\CPT\CourseCPT;

if (!defined('ABSPATH')) exit;

class QuizCourseMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_quiz_course';
	const NONCE_FIELD  = 'fnr_quiz_course_nonce';

	protected static function id(): string { return 'fnr_quiz_course'; }
	protected static function title(): string { return __('Course', 'feuernursingreview'); }
	protected static function post_type(): string { return QuizCPT::POST_TYPE; }
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

		static::render_template('quiz-course-metabox', [
			'courses'      => $courses,
			'selected'     => (int) $post->post_parent,
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$course_id = isset($_POST['fnr_quiz_course']) ? absint($_POST['fnr_quiz_course']) : 0;

		if ($course_id && get_post_type($course_id) !== CourseCPT::POST_TYPE) {
			$course_id = 0;
		}

		/**
		 * remove_action/add_action around wp_update_post: we're inside
		 * save_post_fnr_quiz, and wp_update_post() fires save_post
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
