<?php
/**
 * includes/API/ProgressController.php
 */
namespace Feuernursingreview\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use Feuernursingreview\Database\UserProgressDB;
use Feuernursingreview\Core\DripEngine;
use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class ProgressController extends WP_REST_Controller {
	public function register_routes() {
		$namespace = 'fnr/v1';

		register_rest_route($namespace, '/mark-complete', [
			'methods' 			  => 'POST',
			'callback' 			  => [$this, 'mark_complete'],
			'permission_callback' => [$this, 'check_user_permission'],
			'args' 				  =>[
				'lesson_id' => [
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

	public function mark_complete(WP_REST_Request $request) {
		$lesson_id = absint($request->get_param('lesson_id'));
		$user_id = get_current_user_id();

		if (get_post_type($lesson_id) !== LessonCPT::POST_TYPE) {
			return new WP_REST_Response(['error' => 'Invalid lesson ID'], 404);
		}

		$course_id = wp_get_post_parent_id($lesson_id);
		if (!$course_id) {
			return new WP_REST_Response(['error' => 'Lesson has no parent course'], 400);
		}

		/**
		 * Don't let a student mark a still-locked lesson complete by
		 * hitting the endpoint directly - same gate template_redirect uses.
		 */
		if (!DripEngine::is_accessible($user_id, $course_id, $lesson_id)) {
			return new WP_REST_Response(['error' => 'Lesson is not yet unlocked'], 403);
		}

		$success = UserProgressDB::mark_completed($user_id, $course_id, $lesson_id);
		if (!$success) {
			return new WP_REST_Response(['error' => 'Failed to record completion'], 500);
		}

		$course_progress = UserProgressDB::get_course_progress($user_id, $course_id);
		$completed_count = count(array_filter($course_progress, fn($row) => $row->status === 'completed'));

		return new WP_REST_Response([
			'success' 		  => true,
			'lesson_id' 	  => $lesson_id,
			'course_id' 	  => $course_id,
			'completed_at' 	  => current_time('mysql'),
			'course_progress' => [
				'completed'   => $completed_count,
				'total' 	  => count($course_progress),
			],
		], 200);
	}
}
