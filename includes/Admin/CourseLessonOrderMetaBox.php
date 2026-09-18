<?php
/**
 * includes/Admin/CourseLessonOrderMetaBox.php
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\CourseCPT;
use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

/**
 * Drag-and-drop (+ keyboard-accessible up/down fallback) lesson reordering
 * on the fnr_course edit screen. Saves via AJAX, not the normal save_post
 * flow - this isn't a field on the course post itself, it's an action
 * against each child lesson's menu_order.
 */
class CourseLessonOrderMetaBox extends AbstractMetaBox {
	const NONCE_ACTION = 'fnr_reorder_lessons';
	const AJAX_ACTION  = 'fnr_reorder_lessons';

	protected static function id(): string { return 'fnr_lesson_order'; }
	protected static function title(): string { return __('Lesson Order', 'feuernursingreview'); }
	protected static function post_type(): string { return CourseCPT::POST_TYPE; }
	protected static function context(): string {return 'normal'; }

	public static function register() {
		parent::register();
		add_action('wp_ajax_' . self::AJAX_ACTION, [__CLASS__, 'handle_reorder']);
	}

	public static function render(\WP_Post $post) {
		$lessons = get_posts([
			'post_type'   	 => LessonCPT::POST_TYPE,
			'post_parent' 	 => $post->ID,
			'post_status' 	 => ['publish', 'draft', 'pending'],
			'orderby' 	  	 => 'menu_order',
			'order' 		 => 'ASC',
			'posts_per_page' => -1,
		]);

		static::render_template('course-lesson-order-metabox', [
			'lessons' => $lessons,
		]);
	}

	/**
	 * AJAX handler: receives an ordered array of lesson IDs and writes
	 * menu_order to match their position in that array.
	 */
	public static function handle_reorder() {
		check_ajax_referer(self::NONCE_ACTION, 'nonce');

		$course_id = absint($_POST['course_id'] ?? 0);
		$lesson_ids = array_map('absint', (array) ($_POST['lesson_ids'] ?? []));

		if (!current_user_can('edit_post', $course_id)) {
			wp_send_json_error(['message' => __('Permission denied.', 'feuernursingreview')], 403);
		}

		if (!$lesson_ids) {
			wp_send_json_error(['message' => __('No lessons provided.', 'feuernursingreview')], 400);
		}

		foreach ($lesson_ids as $position => $lesson_id) {
			/**
			 * Guard: only reorder lessons that actually belong to this
			 * course - prevents a tampered request from reordering (or
			 * re-parenting the ordering of) another course's lessons.
			 */
			if ((int) wp_get_post_parent_id($lesson_id) !== $course_id) {
				continue;
			}

			wp_update_post([
				'ID' => $lesson_id,
				'menu_order' => $position,
			]);
		}

		wp_send_json_success(['message' => __('Order saved.', 'feuernursingreview')]);
	}
}
