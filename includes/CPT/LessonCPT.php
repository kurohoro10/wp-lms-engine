<?php
/**
 * File: includes/CPT/LessonsCPT.php
 *
 * Register the Lesson custom post type.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\CPT;

if (!defined('ABSPATH')) exit;

class LessonCPT {
	const POST_TYPE = 'fnr_lesson';

	public static function register() {
		register_post_type(self::POST_TYPE, [
			'labels' => [
				'name' 			 => __('Lessons', 'feuernursingreview'),
				'singular_name'  => __('Lesson', 'feuernursingreview'),
				'menu_name' 	 => __('Lessons', 'feuernursingreview'),
				'name_admin_bar' => __('Lesson', 'feuernursingreview'),
				'add_new' 		 => __('Add Lesson', 'feuernursingreview'),
				'add_new_item'	 => __('Add New Lesson', 'feuernursingreview'),
				'new_item'		 => __('New Lesson', 'feuernursingreview'),
				'edit_item'		 => __('Edit Lesson', 'feuernursingreview'),
				'view_item'		 => __('View Lesson', 'feuernursingreview'),
				'all_items'		 => __('All Lessons', 'feuernursingreview'),
				'search_items'	 => __('Search Lessons', 'feuernursingreviwe'),
				'not_found'		 => __('No lessons found.', 'feuernursingreview'),
				'not_found_in_trash' => __('No lessons found in Trash.', 'feuernursingreview'),
			],
			'public' 	   	  => true,
			'show_in_rest' 	  => true,
			'hierarchical'    => false, // disables Post Parent -> fnr_course
			'rewrite' 	      => ['slug' => ''],
			'supports' 		  => ['title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields'],
			'capability_type' => 'post',
		]);

		register_post_meta(self::POST_TYPE, 'drip_days', [
			'type' 		   => 'integer',
			'single' 	   => true,
			'default' 	   => 0, // 0 = available immediately on enrollment
			'show_in_rest' => function () {
				return current_user_can('edit_posts');
			}
		]);

		register_post_meta(self::POST_TYPE, 'media_type', [
			'type' 			=> 'string',
			'single' 		=> true,
			'default' 		=> 'none', // none | video | pdf | audio
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can('edit_posts'),
		]);

		register_post_meta(self::POST_TYPE, 'video_url', [
			'type' 			=> 'string',
			'single' 		=> true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can('edit_posts'),
		]);

		register_post_meta(self::POST_TYPE, 'pdf_attachment_id', [
			'type' 			=> 'integer',
			'single' 		=> true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can('edit_posts'),
		]);

		register_post_meta(self::POST_TYPE, 'audio_attachment_id', [
			'type' 			=> 'integer',
			'single' 		=> true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can('edit_posts'),
		]);

		register_post_meta(self::POST_TYPE, 'audio_transcript', [
			'type' 			=> 'string',
			'single' 		=> true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can('edit_posts'),
		]);

		register_post_meta(self::POST_TYPE, 'linked_quiz_id', [
			'type' 			=> 'integer',
			'single' 		=> true,
			'default' 		=> 0, // 0 = no quiz required for this lesson
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can('edit_posts'),
		]);
	}
}
