<?php
/**
 * includes/API/RESTController.php
 */
namespace Feuernursingreview\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use Feuernursingreview\Scoring\NGNScorer;
use Feuernursingreview\Database\QuestionLogsDB;
use Feuernursingreview\Database\QuizAttemptsDB;
use Feuernursingreview\CPT\QuestionCPT;

if (!defined('ABSPATH')) exit;

class RESTController extends WP_REST_Controller {
	public function register_routes() {
		$namespace = 'fnr/v1';

		// Submit quiz attempt endpoint
		register_rest_route($namespace, '/submit-question', [
			'methods' 			  => 'POST',
			'callback' 			  => [$this, 'submit_question'],
			'permission_callback' => [$this, 'check_user_permissions'],
		]);
	}

	public function check_user_permissions() {
		return is_user_logged_in(); // Enforces authenticated student access
	}

	public function submit_question(WP_REST_Request $request) {
		$attempt_id  = absint($request->get_param('attempt_id'));
		$question_id = absint($request->get_param('question_id'));
		$user_input  = $request->get_param('user_response'); // Array/JSON

		/**
		 * Shape varies by scoring_rule: zero_one/plus_minus carry
		 * 'selections', rationale carries per-slot keys like
		 * cause/effect[/outcome], numeric carries 'value'. Only the
		 * presence of the object is validated here - score() below
		 * treats a missing/wrong-shaped field as an incorrect answer
		 * rather than erroring, so a malformed request just scores 0
		 * instead of 400ing the whole attempt.
		 */
		if (!is_array($user_input)) {
			return new WP_REST_Response(['error' => 'Missing or invalid user_response'], 400);
		}

		// Matches the meta key QuestionCPT::register() registers via register_post_meta()
		$schema_json = get_post_meta($question_id, '_fnr_question_schema', true);
		if(!$schema_json) {
			return new WP_REST_Response(['error' => 'Invalid question ID '], 404);
		}

		$schema = json_decode($schema_json, true);
		if (!$schema || empty($schema['scoring_rule'])) {
			return new WP_REST_Response(['error' => 'Malformed question schema'], 500);
		}

		$result = $this->score($schema, $user_input);
		if ($result === null) {
			return new WP_REST_Response(['error' => 'Unsupported scoring_rule: ' . $schema['scoring_rule']], 500);
		}

		$log_id = QuestionLogsDB::log_response(
			$attempt_id,
			$question_id,
			get_current_user_id(),
			$user_input,
			$result['points_earned'],
			$result['max_points'],
			$result['is_correct']
		);

		if (!$log_id) {
			return new WP_REST_Response(['error' => 'Failed to log response'], 500);
		}

		// Keep the parent attempt's running total in sync as each question is scored
		QuizAttemptsDB::recalculate_score($attempt_id);

		return new WP_REST_Response([
			'success' 	=> true,
			'log_id' 	=> $log_id,
			'result' 	=> $result,
			'rationale' => $schema['rationale'] ?? null,
		], 200);
	}

	/**
	 * Route to the correct NGNScorer method based on the question's
	 * scoring_rule. Returns null if the rule isn't recognized.
	 */
	private function score(array $schema, array $user_input): ?array {
		$rule = $schema['scoring_rule'];
		$content =$schema['content'] ?? [];

		switch ($rule) {
			case 'zero_one':
				return NGNScorer::score_zero_one(
					(array) ($user_input['selections'] ?? []),
					$content['correct_values'] ?? []
				);

			case 'plus_minus':
				return NGNScorer::score_plus_minus(
					(array) ($user_input['selections'] ?? []),
					$content['correct_values'] ?? [],
					count($content['options'] ?? [])
				);

			case 'rationale':
				/**
				 * Dyad (cause/effect) or Triad (condition/intervention/
				 * outcome) - correct_links' own keys decide which shape
				 * this question is, so both work through the same call.
				 * Falls back to the older correct_cause/correct_effect
				 * fields for questions authored before correct_links
				 * existed.
				 */
				$correct_links = $content['correct_links'] ?? array_filter([
					'cause'  => $content['correct_cause'] ?? null,
					'effect' => $content['correct_effect'] ?? null,
				], fn($v) => $v !== null);

				$user_links = $user_input;
				unset($user_links['selections']);

				return NGNScorer::score_rationale($user_links, $correct_links);

			case 'numeric':
				if (!isset($user_input['value']) || !is_numeric($user_input['value'])) {
					return ['points_earned' => 0.0, 'max_points' => 1.0, 'is_correct' => false];
				}

				return NGNScorer::score_numeric(
					(float) $user_input['value'],
					(float) ($content['correct_value'] ?? 0),
					(float) ($content['tolerance'] ?? 0)
				);

			default:
				return null;
		}
	}
}
