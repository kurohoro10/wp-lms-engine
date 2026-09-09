<?php
namespace Feuernursingreview\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use Feuernursingreview\Scoring\NGNScorer;

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

		// Retrieve stored question schema
		$schema_json = get_post_meta($question_id, '_fnr_question_schema', true);
		if(!$schema_json) {
			return new WP_REST_Response(['error' => 'Invalid question ID '], 404);
		}

		$schema 	  = json_decode($schema_json, true);
		$scoring_rule = $schema['scoring_rule'];
		$result 	  = [];

		// Route scoring to corresponding NGN algorithm
		if ($scoring_rule === 'plus_minus') {
			$result = NGNScorer::score_plus_minus(
				$user_input['selections'],
				$schema['content']['correct_values'],
				count($schema['content']['options'])
			);
		} elseif ($scoring_rule === 'zero_one') {
			$result = NGNScorer::score_zero_one(
				$user_input['selections'],
				$schema['content']['correct_values']
			);
		}

		// Log result directly into custom $wpdb table
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'fnr_question_logs',
			[
				'attempt_id' 		 => $attempt_id,
				'question_id' 		 => $question_id,
				'user_id' 			 => get_current_user_id(),
				'user_response_json' => wp_json_encode($user_input),
				'points_earned' 	 => $result['points_earned'],
				'points_possible' 	 => $result['max_points'],
				'is_correct' 		 => $result['is_correct'] ? 1 : 0,
			]
		);

		return new WP_REST_Response([
			'success' => true,
			'result' => $result,
			'rationale' => $schema['rationale'] // Render immediate feedback
		], 200);
	}
}
