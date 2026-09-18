<?php
/**
 * includes/Frontend/Templates.php
 */
namespace Feuernursingreview\Frontend;

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\CourseCPT;
use Feuernursingreview\CPT\QuizCPT;

if (!defined('ABSPATH')) exit;

class Templates {
	public static function register() {
		add_filter('template_include', [__CLASS__, 'load_template']);
	}

	public static function load_template(string $template): string {
		if (is_singular(LessonCPT::POST_TYPE)) {
			return self::locate('single-fnr_lesson.php') ?: $template;
		}

		if (is_singular(CourseCPT::POST_TYPE)) {
			return self::locate('single-fnr_course.php') ?: $template;
		}

		if (is_singular(QuizCPT::POST_TYPE)) {
			return self::locate('single-fnr_quiz.php') ?: $template;
		}

		return $template;
	}

	/**
	 * Lets a theme override the plugin's template by placing a file of
	 * the same name in its own root - standard WP template-override
	 * convention, matches what §2.2 of the spec calls for.
	 */
	private static function locate(string $filename) {
		$theme_override = locate_template($filename);
		if ($theme_override) {
			return $theme_override;
		}

		$plugin_path = FNR_PLUGIN_DIR . 'public/templates/' . $filename;
		return file_exists($plugin_path) ? $plugin_path : false;
	}
}
