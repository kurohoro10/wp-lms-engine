<?php
namespace Feuernursingreview\CPT;

if (!defined('ABSPATH')) exit;

class QuestionCPT {
	const POST_TYPE = 'fnr_question';

	public static function register() {
		register_post_type(self::POST_TYPE, [
			'labels' => [
				'name'               => __('Questions', 'feuernursingreview'),
				'singular_name'      => __('Question', 'feuernursingreview'),
				'menu_name'          => __('Questions', 'feuernursingreview'),
				'name_admin_bar'     => __('Question', 'feuernursingreview'),
				'add_new'            => __('Add Question', 'feuernursingreview'),
				'add_new_item'       => __('Add New Question', 'feuernursingreview'),
				'new_item'           => __('New Question', 'feuernursingreview'),
				'edit_item'          => __('Edit Question', 'feuernursingreview'),
				'view_item'          => __('View Question', 'feuernursingreview'),
				'all_items'          => __('All Questions', 'feuernursingreview'),
				'search_items'       => __('Search Questions', 'feuernursingreview'),
				'not_found'          => __('No questions found.', 'feuernursingreview'),
				'not_found_in_trash' => __('No questions found in Trash.', 'feuernursingreview'),
			],
			'public' 		  => false, // question bank, not publicly browsable
			'show_ui' 		  => true,
			'show_in_rest' 	  => true,
			'supports' 		  => ['title', 'custom-fields'],
			'capability_type' => ['fnr_question', 'fnr_questions'],
			'map_meta_cap' 	  => true, // respects fnr_instructor's edit_fnr_questions cap
		]);

		register_post_meta(self::POST_TYPE, '_fnr_question_schema', [
			'type' 		   => 'string',
			'single' 	   => true,
			'show_in_rest' => true,
		]);
	}
}
