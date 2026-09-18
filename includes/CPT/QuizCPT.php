<?php
/**
 * File: includes/CPT/QuizCPT.php
 *
 * Registers the Quiz custom post type.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\CPT;

if (!defined('ABSPATH')) exit;

class QuizCPT {
	const POST_TYPE = 'fnr_quiz';

	public static function register() {
		register_post_type(self::POST_TYPE, [
			'labels' => [
				'name'               => __('Quizzes', 'feuernursingreview'),
				'singular_name'      => __('Quiz', 'feuernursingreview'),
				'menu_name'          => __('Quizzes', 'feuernursingreview'),
				'name_admin_bar'     => __('Quiz', 'feuernursingreview'),
				'add_new'            => __('Add Quiz', 'feuernursingreview'),
				'add_new_item'       => __('Add New Quiz', 'feuernursingreview'),
				'new_item'           => __('New Quiz', 'feuernursingreview'),
				'edit_item'          => __('Edit Quiz', 'feuernursingreview'),
				'view_item'          => __('View Quiz', 'feuernursingreview'),
				'all_items'          => __('All Quizzes', 'feuernursingreview'),
				'search_items'       => __('Search Quizzes', 'feuernursingreview'),
				'not_found'          => __('No quizzes found.', 'feuernursingreview'),
				'not_found_in_trash' => __('No quizzes found in Trash.', 'feuernursingreview'),
			],
			'public' 	   => true,
			'show_in_rest' => true,
			'rewrite' 	   => ['slug' => 'nclex-quizzes'],
			'supports' 	   => ['title', 'custom-fields'],
			/*
			* Quiz settings such as time_limit, pass_threshold, and ngn_mode
			* should be registered separately using register_post_meta()
			* with show_in_rest enabled for Gutenberg/React integration.
			*/
		]);

		foreach (['time_limit_minutes', 'pass_threshold', 'ngn_mode'] as $key) {
			register_post_meta(self::POST_TYPE, $key, [
				'type' 		   => in_array($key, ['time_limit_minutes', 'pass_threshold'], true) ? 'number' : 'boolean',
				'single' 	   => true,
				'show_in_rest' => true,
				'aut_callback' => fn() => current_user_can('edit_posts'),
			]);
		}
	}
}
