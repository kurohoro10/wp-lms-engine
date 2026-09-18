<?php
/**
 * includes/Admin/QuizQuestionsMetaBox.php
 *
 * Attaches an ordered list of fnr_question IDs to a fnr_quiz.
 *
 * This is a deliberately simple stopgap - a comma-separated ID list,
 * same spirit as QuestionSchemaMetaBox's raw-JSON box - rather than a
 * drag-and-drop picker. The question bank is shown below the field for
 * reference so an instructor doesn't have to leave the screen to look
 * IDs up. A proper picker/reorder UI is Phase 3 UI polish, not part of
 * wiring the quiz-taking flow itself.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\QuizCPT;
use Feuernursingreview\CPT\QuestionCPT;

if (!defined('ABSPATH')) exit;

class QuizQuestionsMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_quiz_questions';
	const NONCE_FIELD  = 'fnr_quiz_questions_nonce';

	protected static function id(): string { return 'fnr_quiz_questions'; }
	protected static function title(): string { return __('Quiz Questions', 'feuernursingreview'); }
	protected static function post_type(): string { return QuizCPT::POST_TYPE; }
	protected static function context(): string { return 'normal'; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		$question_ids = get_post_meta($post->ID, 'question_ids', true);
		$question_ids = is_array($question_ids) ? $question_ids : [];

		$bank = get_posts([
			'post_type' 	 => QuestionCPT::POST_TYPE,
			'post_status' 	 => ['publish', 'draft', 'pending'],
			'orderby' 		 => 'ID',
			'order' 		 => 'ASC',
			'posts_per_page' => -1,
		]);

		static::render_template('quiz-questions-metabox', [
			'question_ids' => $question_ids,
			'bank' 		   => $bank,
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		if (!isset($_POST['fnr_quiz_question_ids'])) {
			return;
		}

		$raw = wp_unslash($_POST['fnr_quiz_question_ids']);

		$ids = array_filter(array_map('absint', explode(',', $raw)));

		// Only keep IDs that are actually fnr_question posts.
		$ids = array_values(array_filter(
			$ids,
			fn($id) => get_post_type($id) === QuestionCPT::POST_TYPE
		));

		update_post_meta($post_id, 'question_ids', $ids);
	}
}
