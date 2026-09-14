<?php
/**
 * includes/Admin/EnrollmentMetoBox.php
 *
 * Manual enrollment UI on the fnr_course edit screen: a multi-select
 * checkbox dropdown of fnr_student users. Saves together with the
 * course post itself (standard AbstractSaveableMetabox / save_post
 * flow) - this is the Phase 2 stand-in the Enrollment class comment
 * flagged, ahead of the WooCommerce/EDD/LMS wiring in Phase 5.
 *
 * Note: this box intentionally does NOT render its own <form> tag.
 * Meta boxes are rendered insider Wordpress's main #post edit form,
 * so a nested <form> here is invalid HTML - browser will mangle or
 * merge it with the outer form, which is why "Enroll Student" used
 * to appear to do nothing (and why admin-post.php + a separate
 * EnrollmentFormHandler have been removed in favor of saving on the
 * normal Update/Publish action).
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\Core\Enrollment;
use Feuernursingreview\CPT\CourseCPT;

if (!defined('ABSPATH')) exit;

Class EnrollmentMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_enrollment';
	const NONCE_FIELD  = 'fnr_enrollment_nonce';

	protected static function id(): string { return 'fnr_enrollment'; }
	protected static function title(): string { return __('Enrolled Students', 'feuernursingreview'); }
	protected static function post_type(): string { return CourseCPT::POST_TYPE; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		if (!current_user_can( 'edit_post', $post->ID )) {
			return;
		}

		$enrolled = Enrollment::get_enrolled_users($post->ID);
		$enrolled_ids = wp_list_pluck($enrolled, 'ID');

		/**
		 * Everyone the box can offer to (un)check - all fnr_student users,
		 * including one not yet enrolled.
		 */
		$students = get_users(['role' => 'fnr_student', 'orderby' => 'display_name']);

		static::render_template('enrollment-metabox', [
			'students' 		  => $students,
			'enrolled_ids' 	  => $enrolled_ids,
			'enrolled_at' => array_combine(
				$enrolled_ids,
				array_map(
					fn($user) => Enrollment::get_enrolled_at($user->ID, $post->ID),
					$enrolled
				)
			),
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$submitted = isset($_POST['fnr_enrolled_users'])
			? array_map('absint', (array) $_POST['fnr_enrolled_users'])
			: [];

		/**
		 * Only ever enroll/unenroll real fnr_student user IDs - never trust
		 * the submitted list on its own, since it's just checkbox values.
		 */
		$eligible_ids = array_map('absint', wp_list_pluck(get_users(['role' => 'fnr_student', 'fields' => ['ID']]), 'ID'));

		foreach ($eligible_ids as $user_id) {
			$should_be_enrolled = in_array($user_id, $submitted, true);

			if ($should_be_enrolled) {
				Enrollment::enroll($user_id, $post_id);
			} else {
				Enrollment::unenroll($user_id, $post_id);
			}
		}
	}
}
