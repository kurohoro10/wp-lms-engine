<?php
/**
 * includes/API/BookmarksController.php
 * Registers REST API endpoints for managing lesson bookmarks.
 */
namespace Feuernursingreview\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use Feuernursingreview\Core\Bookmarks;
use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class BookmarksController extends WP_REST_Controller {
	/**
	 * Register REST API routes.
	 *
	 * @return void
	*/
	public function register_routes() {
		register_rest_route('fnr/v1', '/toggle-bookmark', [
			'methods' 			  => 'POST',
			'callback' 			  => [$this, 'toggle_bookmark'],
			'permission_callback' => [$this, 'check_user_permission'],
			'args' 				  => [
				'lesson_id' 			=> [
					'required' 			=> true,
					'type' 				=> 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]);
	}

	/**
	 * Check whether the current user is allowed to use the endpoint.
	 *
	 * @return bool
	 */
	public function check_user_permission() {
		return is_user_logged_in();
	}

	/**
	 * Toggle a bookmark for the current user.
	 *
	 * @param WP_REST_Request $request REST API request.
	 * @return WP_REST_Response
	 */
	public function toggle_bookmark(WP_REST_Request $request) {
		$lesson_id = absint($request->get_param('lesson_id'));

		if (get_post_type($lesson_id) !== LessonCPT::POST_TYPE) {
			return new WP_REST_Response(['error' => 'Invalid lesson ID'], 404);
		}

		$bookmarked = Bookmarks::toggle(get_current_user_id(), $lesson_id);

		return new WP_REST_Response([
			'success' 	 => true,
			'lesson_id'  => $lesson_id,
			'bookmarked' => $bookmarked,
		], 200);
	}
}
