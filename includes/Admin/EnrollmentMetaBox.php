<?php
/**
 * includes/Admin/EnrollmentMetoBox.php
 *
 * View-only: register and renders the meat box. Does NOT process
 * the form submission  - that's EnrollmentFormHandler's job.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\Core\Enrollment;
use Feuernursingreview\CPT\CourseCPT;

if (!defined('ABSPATH')) exit;

/**
 * Manual enrollment UI on the fnr_edit screen. This is the Phase 2
 * stand-in the Enrollment class comment flagged - lets an instructor
 * enroll a student without WooCommerce/EDD/LMS wiring (Phase 5).
 */
Class EnrollmentMetaBox extends AbstractMetaBox {

	protected static function id(): string { return 'fnr_enrollment'; }
	protected static function title(): string { return __('Enrolled Students', 'feuernursingreview'); }
	protected static function post_type(): string { return CourseCPT::POST_TYPE; }

	public static function render(\WP_Post $post) {
		if (!current_user_can( 'edit_post', $post->ID )) {
			return;
		}

		$enrolled = Enrollment::get_enrolled_users($post->ID);

		static::render_template('enrollment-metabox', [
			'post' 		  => $post,
			'enrolled' 	  => $enrolled,
			'enrolled_at' => array_combine(
				wp_list_pluck($enrolled, 'ID'),
				array_map(
					fn($user) => Enrollment::get_enrolled_at($user->ID, $post->ID),
					$enrolled
				)
			),
			'action' 	   => EnrollmentFormHandler::ACTION,
			'nonce_action' => EnrollmentFormHandler::NONCE_ACTION,
			'nonce_field'  => EnrollmentFormHandler::NONCE_FIELD
		]);
	}
}
