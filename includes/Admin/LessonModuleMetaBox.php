<?php
/**
 * includes/Admin/LessonModuleMetaBox.php
 *
 * On the lesson edit screen: which module within the lesson's parent
 * course this lesson sits in.
 *
 * Only modules belonging to the lesson's own course are offered. If the
 * lesson has no parent course yet (brand new draft), the box says so
 * rather than offering every module on the site.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\Core\Modules;

if (!defined('ABSPATH')) exit;

class LessonModuleMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_lesson_module';
	const NONCE_FIELD  = 'fnr_lesson_module_nonce';

	protected static function id(): string { return 'fnr_lesson_module'; }
	protected static function title(): string { return __('Module', 'feuernursingreview'); }
	protected static function post_type(): string { return LessonCPT::POST_TYPE; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		$course_id = (int) $post->post_parent;

		static::render_template('lesson-module-metabox', [
			'course_id'    => $course_id,
			'course_title' => $course_id ? get_the_title($course_id) : '',
			'modules'      => $course_id
				? Modules::get_course_modules($course_id, ['publish', 'draft', 'pending', 'private'])
				: [],
			'selected'     => (int) get_post_meta($post->ID, Modules::LESSON_MODULE_META, true),
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$module_id = isset($_POST['fnr_lesson_module']) ? absint($_POST['fnr_lesson_module']) : 0;

		/**
		 * Modules::assign_lesson() re-validates that the module belongs to
		 * this lesson's course, so a tampered select can't move a lesson
		 * into another course's module.
		 */
		Modules::assign_lesson($post_id, $module_id);
	}
}
