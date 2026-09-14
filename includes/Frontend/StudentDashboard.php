<?php
/**
 * includes/Frontend/StudentDashboard.php
 */
namespace Feuernursingreview\Frontend;

use Feuernursingreview\Core\TemplateLoader;
use Feuernursingreview\Core\Enrollment;
use Feuernursingreview\Core\Bookmarks;
use Feuernursingreview\Database\UserProgressDB;
use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\CourseCPT;

if (!defined('ABSPATH')) exit;

/**
 * [fnr_dashboard] shortcode - the student-facing progress page the spec
 * calls for in §2.2. Not tied to a single CPT, so it's a shortcode rather
 * rather than a template_include target like the course/lesson templates.
 */
class StudentDashboard {
	const SHORTCODE = 'fnr_dashboard';

	public static function register() {
		add_shortcode(self::SHORTCODE, [__CLASS__, 'render']);
	}

	public static function render(): string {
		if (!is_user_logged_in()) {
			return self::render_template('dashboard-logged-out', []);
		}

		$user_id   = get_current_user_id();
		$courses   = self::build_course_progress($user_id);
		$bookmarks = Bookmarks::get_bookmarked_lessons($user_id);

		return self::render_template('dashboard', [
			'courses'   => $courses,
			'bookmarks' => $bookmarks,
		]);
	}

	/**
	 * Per-enrolled-course stats: title, permalink, completed/total lesson
	 * counts, percentage, and a completion badge if applicable.
	 */
	private static function build_course_progress(int $user_id): array {
		$course_ids = Enrollment::get_enrolled_course_ids($user_id);
		$courses = [];

		foreach ($course_ids as $course_id) {
			if (get_post_status($course_id) !== 'publish') {
				continue; // course was unpublished/deleted - skip silently
			}

			$total_lessons = count(get_posts([
				'post_type' 	=>LessonCPT::POST_TYPE,
				'post_parent' 	=> $course_id,
				'post_status' 	=> 'publish',
				'posts_per_page' => -1,
				'fields' 		=> 'ids',
			]));

			$progress_rows = UserProgressDB::get_course_progress($user_id, $course_id);
			$completed = count(array_filter($progress_rows, fn($row) => $row->status === 'completed'));

			$percent = $total_lessons > 0 ? round(($completed / $total_lessons) * 100) : 0;

			$courses[] = [
				'id' 		=> $course_id,
				'title' 	=> get_the_title( $course_id ),
				'permalink' => get_permalink( $course_id ),
				'completed' => $completed,
				'total' 	=> $total_lessons,
				'percent' 	=> $percent,
				'badge' 	=> self::badge_for_percent($percent),
			];
		}

		return $courses;
	}

	private static function badge_for_percent(int $percent): ?string {
		if ($percent >= 100) return __('Course Complete', 'feuernursingreview');
		if ($percent >= 75) return __('Almost There', 'feuernursingreview');
		if ($percent >= 25) return __('In Progress', 'feuernursingreview');
		return null; // no badge below 25% - avoids a "badge for barely starting
	}

	private static function render_template(string $template, array $args): string {
		ob_start();
		TemplateLoader::render($template, $args, 'public/templates/');
		return ob_get_clean();
	}
}
