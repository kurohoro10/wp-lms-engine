<?php
namespace Feuernursingreview\Core;

if (!defined('ABSPATH')) exit;

class Roles {
	/**
	 * The primitive capabilities WordPress needs when a post type
	 * declares capability_type => ['fnr_question','fnr_questions'] with
	 * map_meta_cap => true (see QuestionCPT). Custom capability
	 * strings like these are NOT auto-granted to Administrator the way
	 * built-in ones are - without this list, admins are silently
	 * blocked from the Question edit screen itself.
	 */
	const QUESTION_CAPS_FULL = [
		'edit_fnr_question',
		'read_fnr_question',
		'delete_fnr_question',
		'edit_fnr_questions',
		'edit_others_fnr_questions',
		'edit_private_fnr_questions',
		'edit_published_fnr_questions',
		'publish_fnr_questions',
		'read_private_fnr_questions',
		'delete_fnr_questions',
		'delete_private_fnr_questions',
		'delete_published_fnr_questions',
		'delete_others_fnr_questions',
	];

	const QUESTION_CAPS_AUTHOR = [
		'edit_fnr_question',
		'read_fnr_question',
		'delete_fnr_question',
		'edit_fnr_questions',
		'publish_fnr_questions',
		'edit_published_fnr_questions',
		'delete_published_fnr_questions',
	];

	public static function register_roles() {
		self::register_instructor_role();
		self::register_student_role();
		self::grant_question_capabilities();
	}

	/**
	 * Retrofit hook (see maybe_flush_rewrite_rules() for the identical
	 * pattern) - runs on 'init' guarded by a version flag so existing
	 * installs get the missing capabilities on their next request
	 * without needing to deactivate/reactivate the plugin.
	 */
	public static function maybe_grant_question_capabilities() {
		if (get_option('fnr_question_caps_granted')) {
			return;
		}

		self::grant_question_capabilities();
		update_option('fnr_question_caps_granted', 1);
	}

	private static function grant_question_capabilities() {
		$administrator = get_role('administrator');
		if ($administrator) {
			foreach (self::QUESTION_CAPS_FULL as $cap) {
				$administrator->add_cap($cap);
			}
		}

		$instructor = get_role('fnr_instructor');
		if ($instructor) {
			foreach (self::QUESTION_CAPS_AUTHOR as $cap) {
				$instructor->add_cap($cap);
			}
		}
	}

	private static function register_instructor_role() {
		add_role('fnr_instructor', __('Nursing Instructor', 'feuernursingreview'), [
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
