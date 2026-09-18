<?php
/**
 * File: includes/CPT/CourseCPT.php
 *
 * Registers the Course custom post type.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\CPT;

if (!defined('ABSPATH')) exit;

class CourseCPT {
	const POST_TYPE = 'fnr_course';

	public static function register() {
		register_post_type(self::POST_TYPE, [
			'labels' => [
				'name'               => __('Courses', 'feuernursingreview'),
				'singular_name'      => __('Course', 'feuernursingreview'),
				'menu_name'          => __('Courses', 'feuernursingreview'),
				'name_admin_bar'     => __('Course', 'feuernursingreview'),
				'add_new'            => __('Add Course', 'feuernursingreview'),
				'add_new_item'       => __('Add New Course', 'feuernursingreview'),
				'new_item'           => __('New Course', 'feuernursingreview'),
				'edit_item'          => __('Edit Course', 'feuernursingreview'),
				'view_item'          => __('View Course', 'feuernursingreview'),
				'all_items'          => __('All Courses', 'feuernursingreview'),
				'search_items'       => __('Search Courses', 'feuernursingreview'),
				'not_found'          => __('No courses found.', 'feuernursingreview'),
				'not_found_in_trash' => __('No courses found in Trash.', 'feuernursingreview'),
			],
			'public' 	   => true,
			'show_in_rest' => true, // enables Gutenberg
			'has_archive'  => true,
			'rewrite' 	   => ['slug' => 'nclex-courses'],
			'supports'	   => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
			'menu_icon'    => 'dashicons-welcome-learn-more',
		]);
	}
}
