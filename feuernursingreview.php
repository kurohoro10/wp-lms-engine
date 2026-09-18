<?php
/**
 * Plugin Name:     Feuer Nursing Review
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     A WordPress-powered NCLEX learning platform featuring course, module and lesson management, interactive quizzes, NGN question types, case studies, student progress tracking, scoring, analytics, and instructor reporting, with REST API, Gutenberg/React, e-commerce, and LMS integration support.
 * Author:
 * Author URI:      YOUR SITE HERE
 * Text Domain:     feuernursingreview
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Feuernursingreview
 */

if (!defined('ABSPATH')) exit;

define('FNR_VERSION', '0.1.0');
define('FNR_PLUGIN_FILE', __FILE__);
define('FNR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FNR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once FNR_PLUGIN_DIR . 'includes/Autoloader.php';
\Feuernursingreview\Autoloader::register();

register_activation_hook(__FILE__, ['Feuernursingreview\Core\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Feuernursingreview\Core\Deactivator', 'deactivate']);

add_action('init', ['Feuernursingreview\CPT\CourseCPT', 'register']);
add_action('init', ['Feuernursingreview\CPT\ModuleCPT', 'register']);
add_action('init', ['Feuernursingreview\CPT\LessonCPT', 'register']);
add_action('init', ['Feuernursingreview\CPT\QuizCPT', 'register']);
add_action('init', ['Feuernursingreview\CPT\QuestionCPT', 'register']);
add_action('init', ['Feuernursingreview\Core\Activator', 'maybe_flush_rewrite_rules'], 20);
add_action('init', ['Feuernursingreview\Core\Roles', 'maybe_grant_question_capabilities'], 20);
add_action('init', ['Feuernursingreview\CPT\Taxonomies', 'register']);
add_action('init', ['Feuernursingreview\Core\DripEngine', 'register']);
add_action('init', ['Feuernursingreview\Core\Modules', 'register']);
add_action('init', ['Feuernursingreview\Core\CourseCascade', 'register']);
add_action('init', ['Feuernursingreview\Frontend\Templates', 'register']);
add_action('init', ['Feuernursingreview\Frontend\Assets', 'register']);
add_action('init', ['Feuernursingreview\Frontend\StudentDashboard', 'register']);

/**
 * Modules hang off the Courses menu. This has to run on admin_menu (not
 * admin_init) so the parent Courses menu already exists, and it's done by
 * hand rather than via register_post_type's show_in_menu string because
 * that form only ever generates an "All Modules" link, never "Add New".
 */
add_action('admin_menu', ['Feuernursingreview\CPT\ModuleCPT', 'add_admin_menu']);
add_filter('parent_file', ['Feuernursingreview\CPT\ModuleCPT', 'highlight_parent_menu']);

add_action('rest_api_init', function () {
	(new \Feuernursingreview\API\RESTController())->register_routes();
	(new \Feuernursingreview\API\ProgressController())->register_routes();
	(new \Feuernursingreview\API\BookmarksController())->register_routes();
	(new \Feuernursingreview\API\QuizController())->register_routes();
});

add_action('admin_init', ['Feuernursingreview\Admin\Assets', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\EnrollmentMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\QuizSettingsMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\QuizQuestionsMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\LessonDripMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\QuestionSchemaMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\LessonMediaMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\LessonCourseMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\LessonOrphanFilter', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\LessonCourseColumn', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\QuizCourseMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\CourseLessonOrderMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\ModuleCourseMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\LessonModuleMetaBox', 'register']);
