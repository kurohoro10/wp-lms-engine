<?php
/**
 * includes/Admin/LessonCourseColumn.php
 *
 * Adds a "Course" column to the All Lessons list table, showing each
 * lesson's parent course as a link (or "—" for unassigned, matching
 * LessonOrphanFilter's definition of orphaned: post_parent === 0).
 * Separate from LessonOrphanFilter on purpose - one renders a filter
 * control, this renders a column; different jobs, same screen.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\CourseCPT;

if (!defined('ABSPATH')) exit;

class LessonCourseColumn {

	public static function register() {
		add_filter('manage_' . LessonCPT::POST_TYPE . '_posts_columns', [__CLASS__, 'add_column']);
		add_action('manage_' . LessonCPT::POST_TYPE . '_posts_custom_column', [__CLASS__, 'render_column'], 10, 2);
		add_filter('manage_edit-' . LessonCPT::POST_TYPE . '_sortable_columns', [__CLASS__, 'make_sortable']);
		add_action('pre_get_posts', [__CLASS__, 'sort_by_course']);
	}

	/**
	 * Insert the column right after Title, not at the end - the course
	 * a lesson belongs to is core identifying info, not a minor detail.
	 */
	public static function add_column(array $columns): array {
		$new = [];

		foreach ($columns as $key => $label) {
			$new[$key] = $label;

			if ($key === 'title') {
				$new['fnr_course'] = __('Course', 'feuernursingreview');
			}
		}

		return $new;
	}

	public static function render_column(string $column, int $post_id): void {
		if ($column !== 'fnr_course') {
			return;
		}

		$course_id = wp_get_post_parent_id($post_id);

		if (!$course_id || get_post_status($course_id) === false) {
			echo '<span aria-hidden="true">&mdash;</span>';
			echo '<span class="screen-reader-text">' . esc_html__('No course assigned', 'feuernursingreview') . '</span>';
			return;
		}

		$edit_link = get_edit_post_link($course_id);

		if ($edit_link) {
			printf(
				'<a href="%s">%s</a>',
				esc_url($edit_link),
				esc_html(get_the_title($course_id))
			);
		} else {
			echo esc_html(get_the_title($course_id));
		}
	}

	public static function make_sortable(array $columns): array {
		$columns['fnr_course'] = 'parent';
		return $columns;
	}

	/**
	 * WordPress supports orderby=parent natively (sorts by the numeric
	 * post_parent ID, not the course title alphabetically) - grouping
	 * lessons under the same course together, though not in course-name
	 * order. Good enough to scan the list; a true alphabetical sort by
	 * course title would need a JOIN this doesn't attempt.
	 */
	public static function sort_by_course(\WP_Query $query): void {
		if (!is_admin() || !$query->is_main_query()) {
			return;
		}

		if ($query->get('post_type') !== LessonCPT::POST_TYPE) {
			return;
		}

		if ($query->get('orderby') === 'parent') {
			$query->set('orderby', 'parent');
		}
	}
}
