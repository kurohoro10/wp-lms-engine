<?php
/**
 * includes/Admin/LessonQuizMetaBox.php
 *
 * Links a Lesson to the Quiz a student should take for it. This is
 * the forward reference (Lesson -> Quiz); ProgressController::
 * mark_complete() reads it to require a passing attempt before the
 * lesson can be marked complete, and the lesson template uses it to
 * render a "Take the Quiz" link.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\QuizCPT;

if (!defined('ABSPATH')) exit;

class LessonQuizMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_lesson_quiz';
	const NONCE_FIELD  = 'fnr_lesson_quiz_nonce';

	protected static function id(): string { return 'fnr_lesson_quiz'; }
	protected static function title(): string { return __('Linked Quiz', 'feuernursingreview'); }
	protected static function post_type(): string { return LessonCPT::POST_TYPE; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		$quizzes = get_posts([
			'post_type'      => QuizCPT::POST_TYPE,
			'post_status'    => ['publish', 'draft', 'pending'],
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		]);

		static::render_template('lesson-quiz-metabox', [
			'quizzes'          => $quizzes,
			'selected_quiz_id' => (int) get_post_meta($post->ID, 'linked_quiz_id', true),
			'nonce_action'     => self::NONCE_ACTION,
			'nonce_field'      => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$quiz_id = isset($_POST['fnr_linked_quiz_id']) ? absint($_POST['fnr_linked_quiz_id']) : 0;

		// Guard against a tampered/stale post value pointing at
		// something that isn't (or no longer is) a quiz.
		if ($quiz_id && get_post_type($quiz_id) !== QuizCPT::POST_TYPE) {
			$quiz_id = 0;
		}

		update_post_meta($post_id, 'linked_quiz_id', $quiz_id);
	}
}
