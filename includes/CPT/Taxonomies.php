<?php
/**
 * File: includes/CPT/Taxonomies.php
 *
 * Registers custom taxonomies used by Courses and Questions.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\CPT;

if (!defined('ABSPATH')) exit;

class Taxonomies {
	const SUBJECT 		 = 'fnr_subject';
	const NCLEX_CATEGORY = 'fnr_nclex_category';

	public static function register() {
		self::register_subject();
		self::register_nclex_category();
	}

	private static function register_subject() {
		register_taxonomy(self::SUBJECT, [
			CourseCPT::POST_TYPE,
			QuestionCPT::POST_TYPE,
		], [
			'labels' => [
				'name'          => __('Subjects', 'feuernursingreview'),
				'singular_name' => __('Subject', 'feuernursingreview'),
				'menu_name'     => __('Subjects', 'feuernursingreview'),
				'all_items'     => __('All Subjects', 'feuernursingreview'),
				'edit_item'     => __('Edit Subject', 'feuernursingreview'),
				'view_item'     => __('View Subject', 'feuernursingreview'),
				'update_item'   => __('Update Subject', 'feuernursingreview'),
				'add_new_item'  => __('Add New Subject', 'feuernursingreview'),
				'new_item_name' => __('New Subject Name', 'feuernursingreview'),
				'search_items'  => __('Search Subjects', 'feuernursingreview'),
			],
			'public' 	   => true,
			'show_in_rest' => true,
			'hierarchical' => true, // Fundamentals, Pharmacology, Med-Surg
			'rewrite' 	   => ['slug' => 'subject'],
		]);
	}

	private static function register_nclex_category() {
		register_taxonomy(self::NCLEX_CATEGORY, [
			CourseCPT::POST_TYPE,
			QuestionCPT::POST_TYPE,
		], [
			'labels' => [
				'name'          => __('NCLEX Categories', 'feuernursingreview'),
				'singular_name' => __('NCLEX Category', 'feuernursingreview'),
				'menu_name'     => __('NCLEX Categories', 'feuernursingreview'),
				'all_items'     => __('All NCLEX Categories', 'feuernursingreview'),
				'edit_item'     => __('Edit NCLEX Category', 'feuernursingreview'),
				'view_item'     => __('View NCLEX Category', 'feuernursingreview'),
				'update_item'   => __('Update NCLEX Category', 'feuernursingreview'),
				'add_new_item'  => __('Add New NCLEX Category', 'feuernursingreview'),
				'new_item_name' => __('New NCLEX Category Name', 'feuernursingreview'),
				'search_items'  => __('Search NCLEX Categories', 'feuernursingreview'),
			],
			'public' 	   => true,
			'show_in_rest' => true,
			'hierarchical' => true, // Management of Care, Safety & Infection Control, etc.
			'rewrite' 	   => ['slug' => 'nclex-category'],
		]);
	}
}
