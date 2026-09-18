<?php
/**
 * File: includes/CPT/ModuleCPT.php
 *
 * Registers the Module custom post type - the organizational layer
 * between a Course and its Lessons (Course -> Module -> Lesson).
 *
 * Modules are not independently browsable on the frontend; they group
 * and order lessons within a course. A module's owning course is stored
 * in post_parent (set by Admin\ModuleCourseMetaBox); a lesson's owning
 * module is stored in the 'module_id' post meta, NOT post_parent -
 * lesson post_parent stays pointed at the course so DripEngine,
 * ProgressController and CourseLessonOrderMetaBox keep working
 * unchanged.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\CPT;

if (!defined('ABSPATH')) exit;

class ModuleCPT {
	const POST_TYPE = 'fnr_module';

	public static function register() {
		register_post_type(self::POST_TYPE, [
			'labels' => [
				'name'               => __('Modules', 'feuernursingreview'),
				'singular_name'      => __('Module', 'feuernursingreview'),
				'menu_name'          => __('Modules', 'feuernursingreview'),
				'name_admin_bar'     => __('Module', 'feuernursingreview'),
				'add_new'            => __('Add Module', 'feuernursingreview'),
				'add_new_item'       => __('Add New Module', 'feuernursingreview'),
				'new_item'           => __('New Module', 'feuernursingreview'),
				'edit_item'          => __('Edit Module', 'feuernursingreview'),
				'view_item'          => __('View Module', 'feuernursingreview'),
				'all_items'          => __('All Modules', 'feuernursingreview'),
				'search_items'       => __('Search Modules', 'feuernursingreview'),
				'not_found'          => __('No modules found.', 'feuernursingreview'),
				'not_found_in_trash' => __('No modules found in Trash.', 'feuernursingreview'),
			],
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => true,
			'hierarchical'    => true,
			'supports'        => ['title', 'editor', 'page-attributes', 'custom-fields'],
			'capability_type' => 'post',
			'menu_icon'       => 'dashicons-category',
			/**
			 * Deliberately NOT using the show_in_menu string form. WP only
			 * generates an "All Modules" link for that, never an "Add New",
			 * Both submenu entries are added by hand in add_admin_menu().
			 */
			'show_in_menu'    => false,
		]);
	}

	/**
	 * Hang "All Modules" + "Add Module" off the Courses menu.
	 *
	 * Hooked on admin_menu (see feuernursingreview.php) so the parent
	 * Courses menu already exists by the time we attach to it.
	 */
	public static function add_admin_menu() {
		$parent = 'edit.php?post_type=' . CourseCPT::POST_TYPE;
		$obj    = get_post_type_object(self::POST_TYPE);

		if (!$obj) {
			return;
		}

		add_submenu_page(
			$parent,
			$obj->labels->name,
			$obj->labels->all_items,
			$obj->cap->edit_posts,
			'edit.php?post_type=' . self::POST_TYPE
		);

		add_submenu_page(
			$parent,
			$obj->labels->add_new_item,
			$obj->labels->add_new,
			$obj->cap->create_posts,
			'post-new.php?post_type=' . self::POST_TYPE
		);
	}

	/**
	 * Keep the Courses menu highlighted while editing a module, so the
	 * admin doesn't look like it jumped somewhere unrelated.
	 */
	public static function highlight_parent_menu($parent_file) {
		global $current_screen;

		if ($current_screen && $current_screen->post_type === self::POST_TYPE) {
			return 'edit.php?post_type=' . CourseCPT::POST_TYPE;
		}

		return $parent_file;
	}
}
