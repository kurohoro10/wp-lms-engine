<?php
/**
 * includes/Admin/LessonDripMetaBox.php
 *
 * Unlock rule for a single lesson: immediate, N days after enrollment,
 * a fixed calendar date, or a minimum score on a chosen quiz.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\QuizCPT;
use Feuernursingreview\Core\DripEngine;

if (!defined('ABSPATH')) exit;

class LessonDripMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_lesson_drip';
	const NONCE_FIELD  = 'fnr_lesson_drip_nonce';

	protected static function id(): string { return 'fnr_lesson_drip'; }
	protected static function title(): string { return __('Unlock Rule', 'feuernursingreview'); }
	protected static function post_type(): string { return LessonCPT::POST_TYPE; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		$config = DripEngine::get_config($post->ID);

		static::render_template('lesson-drip-metabox', [
			'type'         => $config['type'],
			'days'         => $config['days'],
			// stored as Y-m-d H:i:s, but the two inputs want them split
			'date'         => $config['date'] ? substr($config['date'], 0, 10) : '',
			'time'         => $config['date'] ? substr($config['date'], 11, 5) : '',
			'quiz_id'      => $config['quiz_id'],
			'min_score'    => $config['quiz_min_score'],
			'quizzes'      => get_posts([
				'post_type'      => QuizCPT::POST_TYPE,
				'post_status'    => ['publish', 'draft', 'pending', 'private'],
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => -1,
			]),
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$type = isset($_POST['fnr_drip_type']) ? sanitize_key($_POST['fnr_drip_type']) : DripEngine::TYPE_IMMEDIATE;

		if (!in_array($type, DripEngine::types(), true)) {
			$type = DripEngine::TYPE_IMMEDIATE;
		}

		update_post_meta($post_id, 'drip_type', $type);

		/**
		 * Every branch's field is written on every save, not just the
		 * selected one. The inactive inputs are still submitted (they're
		 * only hidden with CSS), so writing them all means switching
		 * away from a rule and back doesn't lose what you had configured.
		 */
		update_post_meta($post_id, 'drip_days', isset($_POST['fnr_drip_days']) ? absint($_POST['fnr_drip_days']) : 0);
		update_post_meta($post_id, 'drip_date', self::sanitize_datetime($_POST['fnr_drip_date'] ?? '', $_POST['fnr_drip_time'] ?? ''));
		update_post_meta($post_id, 'drip_quiz_id', self::sanitize_quiz_id($_POST['fnr_drip_quiz_id'] ?? 0));

		$min_score = isset($_POST['fnr_drip_quiz_min_score']) ? (float) $_POST['fnr_drip_quiz_min_score'] : 75.0;
		update_post_meta($post_id, 'drip_quiz_min_score', max(0, min(100, $min_score)));
	}

	/**
	 * Combine the date + time inputs into the 'Y-m-d H:i:s' site-local
	 * string DripEngine expects. Returns '' for anything malformed rather
	 * than storing a half-parsed date that would silently gate content.
	 */
	private static function sanitize_datetime($date, $time): string {
		$date = trim((string) $date);

		if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return '';
		}

		$time = trim((string) $time);

		if ($time === '' || !preg_match('/^\d{2}:\d{2}$/', $time)) {
			$time = '00:00';
		}

		return $date . ' ' . $time . ':00';
	}

	private static function sanitize_quiz_id($value): int {
		$quiz_id = absint($value);

		if ($quiz_id && get_post_type($quiz_id) !== QuizCPT::POST_TYPE) {
			return 0;
		}

		return $quiz_id;
	}
}
