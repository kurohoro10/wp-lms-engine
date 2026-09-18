<?php
/**
 * includes/Admin/ModuleCourseMetaBox.php
 *
 * The box the old ModuleCPT docblock referenced but that was never
 * written: picks which course a module belongs to, and its position
 * within that course.
 *
 * Core's Page Attributes box can't do this - it only offers parents of
 * the SAME post type, so on an fnr_module screen it would list other
 * modules, never courses.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\ModuleCPT;
use Feuernursingreview\CPT\CourseCPT;

if (!defined('ABSPATH')) exit;

class ModuleCourseMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_module_course';
	const NONCE_FIELD  = 'fnr_module_course_nonce';

	protected static function id(): string { return 'fnr_module_course'; }
	protected static function title(): string { return __('Course & Order', 'feuernursingreview'); }
	protected static function post_type(): string { return ModuleCPT::POST_TYPE; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		$courses = get_posts([
			'post_type'      => CourseCPT::POST_TYPE,
			'post_status'    => ['publish', 'draft', 'pending', 'private'],
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		]);

		static::render_template('module-course-metabox', [
			'courses'      => $courses,
			'selected'     => (int) $post->post_parent,
			'menu_order'   => (int) $post->menu_order,
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$course_id  = isset($_POST['fnr_module_course']) ? absint($_POST['fnr_module_course']) : 0;
		$menu_order = isset($_POST['fnr_module_order']) ? absint($_POST['fnr_module_order']) : 0;

		if ($course_id && get_post_type($course_id) !== CourseCPT::POST_TYPE) {
			$course_id = 0;
		}

		/**
		 * remove_action/add_action around wp_update_post: we're inside
		 * save_post_fnr_module, and wp_update_post() fires save_post again.
		 * Without unhooking, save() re-enters, the nonce is still valid,
		 * and you get an infinite loop on this screen.
		 */
		remove_action('save_post_' . static::post_type(), [static::class, 'save']);

		wp_update_post([
			'ID'          => $post_id,
			'post_parent' => $course_id,
			'menu_order'  => $menu_order,
		]);

		add_action('save_post_' . static::post_type(), [static::class, 'save']);
	}
}
