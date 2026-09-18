<?php
/**
 * includes/Core/LessonQuiz.php
 *
 * Small helper around the Lesson -> Quiz relationship (stored as
 * 'linked_quiz_id' meta on the lesson). Centralizes both the
 * forward lookup and the reverse one (which lesson a quiz belongs
 * to, for the quiz template's "Back to Lesson" link) so nothing
 * else has to know the meta key or query shape.
 */
namespace Feuernursingreview\Core;

use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class LessonQuiz {
	public static function get_quiz_id_for_lesson(int $lesson_id): int {
		return (int) get_post_meta($lesson_id, 'linked_quiz_id', true);
	}

	/**
	 * Reverse lookup: which lesson (if any) links to this quiz. Only
	 * meaningful while a quiz is linked from at most one lesson;
	 * returns the first match if more than one somehow points to it.
	 */
	public static function get_lesson_id_for_quiz(int $quiz_id): int {
		if (!$quiz_id) {
			return 0;
		}

		$lessons = get_posts([
			'post_type'      => LessonCPT::POST_TYPE,
			'post_status'    => ['publish', 'draft', 'pending'],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [ // phpcs:ignore -- small, infrequent, admin/template-context lookup
				[
					'key'   => 'linked_quiz_id',
					'value' => $quiz_id,
				],
			],
		]);

		return $lessons ? (int) $lessons[0] : 0;
	}
}
