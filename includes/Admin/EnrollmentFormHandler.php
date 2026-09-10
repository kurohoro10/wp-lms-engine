<?php
/**
 * includes/Admin/EnrollmentFormHandler.php
 *
 * Process the enroll-student form submission. Separate from
 * EnrollmentMetaBox so the "do the enrollment" action isn't tied
 * to any one specific piece of UI that triggers it.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\Core\Enrollment;
use Feuernursingreview\CPT\CourseCPT;

if (!defined('ABSPATH')) exit;

class EnrollmentFormHandler {
	const ACTION = 'fnr_enroll_student';
	const NONCE_ACTION = 'fnr_enroll_student';
	const NONCE_FIELD = 'fnr_enroll_nonce';

	public static function register() {
		add_action('admin_post_' . self::ACTION, [__CLASS__, 'handle']);
	}

	public static function handle() {
		if (
			!isset($_POST[self::NONCE_FIELD]) ||
			!wp_verify_nonce($_POST[self::NONCE_FIELD], self::NONCE_ACTION)
		) {
			wp_die(esc_html__('Security check failed.', 'feuernursingreview'), 403);
		}

		$course_id = absint($_POST['course_id'] ?? 0);
		$user_id   = absint($_POST['user_id'] ?? 0);

		if (!current_user_can('edit_post', $course_id)) {
			wp_die(esc_html__('You do not have permission to enroll students in this course.', 'feuernursingreview'), 403);
		}

		if (!$course_id || !$user_id || get_post_type($course_id) !== CourseCPT::POST_TYPE) {
			wp_safe_redirect(add_query_arg('fnr_enroll', 'invalid', get_edit_post_link($course_id, 'raw')));
			exit;
		}

		Enrollment::enroll($user_id, $course_id);
		wp_safe_redirect(add_query_arg('fnr_enroll', 'success', get_edit_post_link($course_id, 'raw')));
		exit;
	}
}
