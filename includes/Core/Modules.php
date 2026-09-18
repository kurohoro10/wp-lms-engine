<?php
/**
 * includes/Core/Modules.php
 *
 * Read helpers for the Course -> Module -> Lesson hierarchy.
 *
 * Storage model:
 *   - Module -> Course : $module->post_parent
 *   - Module ordering  : $module->menu_order
 *   - Lesson -> Module : 'module_id' post meta on the lesson (0 = unassigned)
 *   - Lesson ordering  : $lesson->menu_order (unchanged, still course-wide)
 *
 * Lesson post_parent deliberately still points at the COURSE, not the
 * module. That keeps DripEngine::enforce_drip(), ProgressController and
 * CourseLessonOrderMetaBox working without a data migration, and means an
 * unassigned lesson still belongs to a course rather than falling out of
 * the tree entirely.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\Core;

use Feuernursingreview\CPT\ModuleCPT;
use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\QuizCPT;

if (!defined('ABSPATH')) exit;

class Modules {
	const LESSON_MODULE_META = 'module_id';

	/**
	 * Modules belonging to a course, in menu_order.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_course_modules(int $course_id, array $statuses = ['publish']): array {
		if (!$course_id) {
			return [];
		}

		return get_posts([
			'post_type'        => ModuleCPT::POST_TYPE,
			'post_parent'      => $course_id,
			'post_status'      => $statuses,
			'orderby'          => ['menu_order' => 'ASC', 'title' => 'ASC'],
			'posts_per_page'   => -1,
			'suppress_filters' => false,
		]);
	}

	/**
	 * Lessons assigned to a module, in menu_order.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_module_lessons(int $module_id, array $statuses = ['publish']): array {
		if (!$module_id) {
			return [];
		}

		return get_posts([
			'post_type'      => LessonCPT::POST_TYPE,
			'post_status'    => $statuses,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'posts_per_page' => -1,
			'meta_query'     => [[
				'key'   => self::LESSON_MODULE_META,
				'value' => $module_id,
				'type'  => 'NUMERIC',
			]],
		]);
	}

	/**
	 * Lessons in a course that aren't in any module yet - either because
	 * the course predates the module layer, or because the module they
	 * pointed at was deleted. Rendered as a trailing "Other lessons"
	 * group so content never silently disappears from the course page.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_unassigned_lessons(int $course_id, array $statuses = ['publish']): array {
		$lessons = get_posts([
			'post_type'      => LessonCPT::POST_TYPE,
			'post_parent'    => $course_id,
			'post_status'    => $statuses,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		]);

		$valid_module_ids = wp_list_pluck(self::get_course_modules($course_id, ['publish', 'draft', 'pending']), 'ID');

		return array_values(array_filter($lessons, function ($lesson) use ($valid_module_ids) {
			$module_id = (int) get_post_meta($lesson->ID, self::LESSON_MODULE_META, true);
			return $module_id <= 0 || !in_array($module_id, $valid_module_ids, true);
		}));
	}

	/**
	 * Quizzes assigned directly to a course (QuizCourseMetaBox writes
	 * post_parent, same relationship lessons use). Ordered by
	 * menu_order so an instructor can control the listed sequence,
	 * falling back to title for quizzes that haven't been reordered.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_course_quizzes(int $course_id, array $statuses = ['publish']): array {
		if (!$course_id) {
			return [];
		}

		return get_posts([
			'post_type'      => QuizCPT::POST_TYPE,
			'post_parent'    => $course_id,
			'post_status'    => $statuses,
			'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
			'posts_per_page' => -1,
		]);
	}

	/**
	 * The full course tree, ready for a template to loop over.
	 *
	 * Returns a list of groups, each ['module' => \WP_Post|null, 'lessons' => \WP_Post[]].
	 * The module is null for the trailing unassigned group. Empty modules
	 * are dropped so the course page doesn't render bare headings.
	 */
	public static function get_course_tree(int $course_id, array $statuses = ['publish']): array {
		$groups = [];

		foreach (self::get_course_modules($course_id, $statuses) as $module) {
			$lessons = self::get_module_lessons($module->ID, $statuses);

			if (!$lessons) {
				continue;
			}

			$groups[] = ['module' => $module, 'lessons' => $lessons];
		}

		$orphans = self::get_unassigned_lessons($course_id, $statuses);
		if ($orphans) {
			$groups[] = ['module' => null, 'lessons' => $orphans];
		}

		return $groups;
	}

	/**
	 * Flat, module-ordered lesson list for a course. Used anywhere that
	 * needs "the Nth lesson" semantics (progress counts, next/prev links)
	 * rather than the grouped display.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_ordered_lessons(int $course_id, array $statuses = ['publish']): array {
		$out = [];

		foreach (self::get_course_tree($course_id, $statuses) as $group) {
			foreach ($group['lessons'] as $lesson) {
				$out[] = $lesson;
			}
		}

		return $out;
	}

	/**
	 * The module a lesson belongs to, or null.
	 */
	public static function get_lesson_module(int $lesson_id) {
		$module_id = (int) get_post_meta($lesson_id, self::LESSON_MODULE_META, true);

		if ($module_id <= 0) {
			return null;
		}

		$module = get_post($module_id);

		return ($module && $module->post_type === ModuleCPT::POST_TYPE) ? $module : null;
	}

	/**
	 * Assign a lesson to a module, validating that the module actually
	 * belongs to the lesson's own course. Passing 0 clears the assignment.
	 */
	public static function assign_lesson(int $lesson_id, int $module_id): bool {
		if ($module_id <= 0) {
			return delete_post_meta($lesson_id, self::LESSON_MODULE_META);
		}

		$module = get_post($module_id);

		if (!$module || $module->post_type !== ModuleCPT::POST_TYPE) {
			return false;
		}

		if ((int) $module->post_parent !== (int) wp_get_post_parent_id($lesson_id)) {
			return false; // module belongs to a different course
		}

		return (bool) update_post_meta($lesson_id, self::LESSON_MODULE_META, $module_id);
	}

	/**
	 * When a module is trashed or deleted its lessons would otherwise keep
	 * a dangling module_id. get_unassigned_lessons() already tolerates
	 * that, but clearing the meta keeps the data honest.
	 */
	public static function register() {
		add_action('trashed_post', [__CLASS__, 'detach_lessons']);
		add_action('before_delete_post', [__CLASS__, 'detach_lessons']);
	}

	public static function detach_lessons($post_id) {
		if (get_post_type($post_id) !== ModuleCPT::POST_TYPE) {
			return;
		}

		foreach (self::get_module_lessons((int) $post_id, ['publish', 'draft', 'pending', 'private']) as $lesson) {
			delete_post_meta($lesson->ID, self::LESSON_MODULE_META);
		}
	}
}
