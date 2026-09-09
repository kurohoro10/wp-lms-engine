<?php
namespace Feuernursingreview\Core;

if (!defined('ABSPATH')) exit;

class Roles {
	public static function register_roles() {
		self::register_instructor_role();
		self::register_student_role();
	}

	private static function register_instructor_role() {
		add_role('fnr_intructor', __('Nursing Instructor', 'feuernursingreview'), [
			'read' 				 => true,
			'upload_files' 		 => true,
			'edit_posts' 		 => true,
			'publish_posts' 	 => true,
			'edit_fnr_questions' => true,
			'create_fnr_courses' => true,
			'view_fnr_reports' 	 => true,
		]);
	}

	private static function register_student_role() {
		$subscriber = get_role('subscriber');
		$capabilities = $subscriber ? $subscriber->capabilities : ['read' => true];
		$capabilities['access_fnr_courses'] = true;

		add_role('fnr_student', __('Nursing Student', 'feuernursingreview'), $capabilities);
	}
}
