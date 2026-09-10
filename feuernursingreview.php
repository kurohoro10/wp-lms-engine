<?php
/**
 * Plugin Name:     Feuer Nursing Review
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     A WordPress-powered NCLEX learning platform featuring course and lesson management, interactive quizzes, NGN question types, case studies, student progress tracking, scoring, analytics, and instructor reporting, with REST API, Gutenberg/React, e-commerce, and LMS integration support.
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
add_action('init', ['Feuernursingreview\CPT\LessonCPT', 'register']);
add_action('init', ['Feuernursingreview\CPT\QuizCPT', 'register']);
add_action('init', ['Feuernursingreview\CPT\QuestionCPT', 'register']);
add_action('init', ['Feuernursingreview\CPT\Taxonomies', 'register']);
add_action('init', ['Feuernursingreview\Core\DripEngine', 'register']);
add_action('rest_api_init', function () {
	(new \Feuernursingreview\API\RESTController())->register_routes();
	(new \Feuernursingreview\API\ProgressController())->register_routes();
});
add_action('admin_init', ['Feuernursingreview\Admin\EnrollmentMetaBox', 'register']);
add_action('admin_init', ['Feuernursingreview\Admin\EnrollmentFormHandler', 'register']);
