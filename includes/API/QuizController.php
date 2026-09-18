<?php
/**
 * includes/API/QuizController.php
 *
 * Owns the quiz *attempt lifecycle* - starting an attempt and completing
 * one. Per-question scoring during an attempt stays in RESTController's
 * /submit-question, which already exists; this controller just brackets
 * that with the session start/end so QuizAttemptsDB::start_attempt() and
 * complete_attempt() (previously dead code with no caller) are reachable.
 */
namespace Feuernursingreview\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use Feuernursingreview\CPT\QuizCPT;
use Feuernursingreview\CPT\QuestionCPT;
use Feuernursingreview\Database\QuizAttemptsDB;
use Feuernursingreview\Database\QuestionLogsDB;

if (!defined('ABSPATH')) exit;

class QuizController extends WP_REST_Controller {
	public function register_routes() {
		$namespace = 'fnr/v1';

		register_rest_route($namespace, '/start-attempt', [
			'methods' 			  => 'POST',
			'callback' 			  => [$this, 'start_attempt'],
			'permission_callback' => [$this, 'check_user_permission'],
			'args' 				  => [
				'quiz_id' => [
					'required' 			=> true,
					'type' 				=> 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]);

		register_rest_route($namespace, '/complete-attempt', [
			'methods' 			  => 'POST',
			'callback' 			  => [$this, 'complete_attempt'],
			'permission_callback' => [$this, 'check_user_permission'],
			'args' 				  => [
				'attempt_id' => [
					'required' 			=> true,
					'type' 				=> 'integer',
					'sanitize_callback' => 'absint',
				],
				'time_spent_seconds' => [
					'required' 			=> false,
					'type' 				=> 'integer',
					'default' 			=> 0,
					'sanitize_callback' => 'absint',
				],
			],
		]);

		register_rest_route($namespace, '/quiz-flashcards', [
			'methods' 			  => 'GET',
			'callback' 			  => [$this, 'get_flashcards'],
			'permission_callback' => [$this, 'check_user_permission'],
			'args' 				  => [
				'quiz_id' => [
					'required' 			=> true,
					'type' 				=> 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]);
	}

	public function check_user_permission() {
		return is_user_logged_in();
	}

	public function start_attempt(WP_REST_Request $request) {
		$quiz_id = absint($request->get_param('quiz_id'));
		$user_id = get_current_user_id();

		if (get_post_type($quiz_id) !== QuizCPT::POST_TYPE || get_post_status($quiz_id) !== 'publish') {
			return new WP_REST_Response(['error' => 'Invalid quiz ID'], 404);
		}

		$questions = $this->get_public_questions($quiz_id);

		if (!$questions) {
			return new WP_REST_Response(['error' => 'This quiz has no questions yet'], 400);
		}

		$attempt_id = QuizAttemptsDB::start_attempt($user_id, $quiz_id);

		if (!$attempt_id) {
			return new WP_REST_Response(['error' => 'Failed to start attempt'], 500);
		}

		return new WP_REST_Response([
			'success' 	 => true,
			'attempt_id' => $attempt_id,
			'quiz_id' 	 => $quiz_id,
			'questions'  => $questions,
		], 200);
	}

	public function complete_attempt(WP_REST_Request $request) {
		$attempt_id 		 = absint($request->get_param('attempt_id'));
		$time_spent_seconds  = absint($request->get_param('time_spent_seconds'));
		$user_id 			 = get_current_user_id();

		$attempt = QuizAttemptsDB::get_attempt($attempt_id);

		if (!$attempt) {
			return new WP_REST_Response(['error' => 'Invalid attempt ID'], 404);
		}

		// Students can only complete their own attempts.
		if ((int) $attempt->user_id !== $user_id) {
			return new WP_REST_Response(['error' => 'This attempt does not belong to you'], 403);
		}

		if ($attempt->completed_at) {
			return new WP_REST_Response(['error' => 'This attempt has already been completed'], 400);
		}

		$pass_threshold = (float) get_post_meta((int) $attempt->quiz_id, 'pass_threshold', true);

		$success = QuizAttemptsDB::complete_attempt($attempt_id, $pass_threshold, $time_spent_seconds);

		if (!$success) {
			return new WP_REST_Response(['error' => 'Failed to complete attempt'], 500);
		}

		$final = QuizAttemptsDB::get_attempt($attempt_id);

		return new WP_REST_Response([
			'success' 		  => true,
			'attempt_id' 	  => $attempt_id,
			'score_earned' 	  => (float) $final->score_earned,
			'total_possible'  => (float) $final->total_possible,
			'percentage' 	  => (float) $final->percentage,
			'passing_status'  => $final->passing_status,
			'pass_threshold'  => $pass_threshold,
			'review' 		  => $this->build_review($attempt_id),
		], 200);
	}

	/**
	 * Per-question breakdown for the post-attempt review screen: what the
	 * student answered vs. the correct answer, plus the rationale. Safe
	 * to reveal now - the attempt this belongs to is already completed,
	 * so unlike get_public_questions() there's nothing left to protect.
	 *
	 * Reads from fnr_question_logs (already written by
	 * RESTController::submit_question() as the student worked through
	 * the attempt) rather than re-scoring anything.
	 */
	private function build_review(int $attempt_id): array {
		$logs = QuestionLogsDB::get_attempt_logs($attempt_id);
		$review = [];

		foreach ($logs as $log) {
			$question_id = (int) $log->question_id;
			$schema_json = get_post_meta($question_id, '_fnr_question_schema', true);
			$schema 	 = $schema_json ? json_decode($schema_json, true) : null;

			if (!$schema) {
				continue; // question deleted/retyped since the attempt - skip rather than error the whole review
			}

			$content 	   = $schema['content'] ?? [];
			$user_response = json_decode($log->user_response_json, true) ?: [];

			$review[] = [
				'question_id' 	 => $question_id,
				'prompt' 		 => $schema['prompt'] ?? get_the_title($question_id),
				'type' 			 => $schema['type'] ?? $schema['scoring_rule'],
				'is_correct' 	 => (bool) $log->is_correct,
				'points_earned'  => (float) $log->points_earned,
				'points_possible' => (float) $log->points_possible,
				'your_answer' 	 => $this->describe_answer($schema['scoring_rule'], $content, $user_response),
				'correct_answer' => $this->describe_correct_answer($schema['scoring_rule'], $content),
				'rationale' 	 => $schema['rationale'] ?? '',
			];
		}

		return $review;
	}

	/**
	 * Turns a raw user_response (option values, link keys, etc.) into
	 * option labels for display, falling back to the raw value where no
	 * label lookup applies (numeric, hotspot region IDs).
	 */
	private function label_for(array $content): callable {
		$choices = $content['options'] ?? $content['regions'] ?? [];

		return function ($value) use ($choices) {
			foreach ($choices as $choice) {
				$choiceValue = is_array($choice) ? ($choice['value'] ?? $choice['id'] ?? null) : $choice;
				$choiceLabel = is_array($choice) ? ($choice['label'] ?? $choice['text'] ?? $choiceValue) : $choice;
				if ((string) $choiceValue === (string) $value) {
					return (string) $choiceLabel;
				}
			}
			return (string) $value;
		};
	}

	private function describe_answer(string $rule, array $content, array $response): string {
		$label_for = $this->label_for($content);

		if ($rule === 'rationale') {
			$parts = [];
			foreach ($response as $slot => $value) {
				if ($value === '') continue;
				$parts[] = ucfirst($slot) . ': ' . $label_for($value);
			}
			return $parts ? implode(', ', $parts) : __('No answer submitted', 'feuernursingreview');
		}

		if (isset($response['value'])) {
			return (string) $response['value'];
		}

		$selections = (array) ($response['selections'] ?? []);
		if (!$selections) {
			return __('No answer submitted', 'feuernursingreview');
		}

		return implode(', ', array_map($label_for, $selections));
	}

	private function describe_correct_answer(string $rule, array $content): string {
		$label_for = $this->label_for($content);

		if ($rule === 'rationale') {
			$links = $content['correct_links'] ?? [
				'cause'  => $content['correct_cause'] ?? null,
				'effect' => $content['correct_effect'] ?? null,
			];
			$parts = [];
			foreach ($links as $slot => $value) {
				if ($value === null) continue;
				$parts[] = ucfirst($slot) . ': ' . $label_for($value);
			}
			return implode(', ', $parts);
		}

		if (isset($content['correct_value'])) {
			$tolerance = $content['tolerance'] ?? 0;
			return $tolerance ? $content['correct_value'] . ' \u00b1 ' . $tolerance : (string) $content['correct_value'];
		}

		$correct = (array) ($content['correct_values'] ?? []);
		return implode(', ', array_map($label_for, $correct));
	}

	/**
	 * Flashcard Review (roadmap §3.1): prompt on the front, the
	 * authored rationale on the back. This is study, not an attempt -
	 * nothing is scored or logged, so it's fine to hand over the
	 * rationale text up front instead of withholding it like
	 * get_public_questions() does for a real attempt.
	 */
	public function get_flashcards(WP_REST_Request $request) {
		$quiz_id = absint($request->get_param('quiz_id'));

		if (get_post_type($quiz_id) !== QuizCPT::POST_TYPE || get_post_status($quiz_id) !== 'publish') {
			return new WP_REST_Response(['error' => 'Invalid quiz ID'], 404);
		}

		$question_ids = get_post_meta($quiz_id, 'question_ids', true);
		$question_ids = is_array($question_ids) ? $question_ids : [];

		$cards = [];

		foreach ($question_ids as $question_id) {
			$question_id = absint($question_id);

			if (get_post_type($question_id) !== QuestionCPT::POST_TYPE) {
				continue;
			}

			$schema_json = get_post_meta($question_id, '_fnr_question_schema', true);
			$schema 	 = $schema_json ? json_decode($schema_json, true) : null;

			if (!$schema) {
				continue;
			}

			$cards[] = [
				'question_id' => $question_id,
				'prompt' 	  => $schema['prompt'] ?? get_the_title($question_id),
				'rationale'   => $schema['rationale'] ?? '',
			];
		}

		return new WP_REST_Response(['success' => true, 'cards' => $cards], 200);
	}

	/**
	 * The quiz's question list with answer keys stripped - only what the
	 * frontend runner needs to render each item type and post an answer.
	 * Correct values, correct cause/effect, and the rationale text are
	 * withheld until RESTController::submit_question() scores the item.
	 */
	private function get_public_questions(int $quiz_id): array {
		$question_ids = get_post_meta($quiz_id, 'question_ids', true);
		$question_ids = is_array($question_ids) ? $question_ids : [];

		$questions = [];

		foreach ($question_ids as $question_id) {
			$question_id = absint($question_id);

			if (get_post_type($question_id) !== QuestionCPT::POST_TYPE) {
				continue; // stale ID (question was deleted/retyped) - skip rather than error the whole quiz
			}

			$schema_json = get_post_meta($question_id, '_fnr_question_schema', true);
			$schema 	 = $schema_json ? json_decode($schema_json, true) : null;

			if (!$schema || empty($schema['scoring_rule'])) {
				continue; // question has no valid schema yet - skip rather than error the whole quiz
			}

			// Strip every answer-key field so the runner gets what it
			// needs to render (options, image_url, regions, link
			// choices...) without ever seeing the correct answer.
			$public_content = $schema['content'] ?? [];
			unset(
				$public_content['correct_values'],
				$public_content['correct_cause'],
				$public_content['correct_effect'],
				$public_content['correct_links'],
				$public_content['correct_value'],
				$public_content['tolerance']
			);

			$questions[] = [
				'question_id'  => $question_id,
				'type' 		   => $schema['type'] ?? $schema['scoring_rule'],
				'scoring_rule' => $schema['scoring_rule'],
				'prompt' 	   => $schema['prompt'] ?? get_the_title($question_id),
				'content' 	   => $public_content,
			];
		}

		return $questions;
	}
}
