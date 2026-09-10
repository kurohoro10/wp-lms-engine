<?php
/**
 * includes/Database/QuestionLogsDB.php
 */
namespace Feuernursingreview\Database;

if (!defined('ABSPATH')) exit;

class QuestionLogsDB {
	private static function  table() {
		global $wpdb;
		return $wpdb->prefix . 'fnr_question_logs';
	}

	/**
	 * Insert a scored question response. Returns the new log row's ID,
	 * or 0 on failure.
	 */
	public static function log_response(
		int $attempt_id,
		int $question_id,
		int $user_id,
		array $user_response,
		float $points_earned,
		float $points_possible,
		bool $is_correct
	): int {
		global $wpdb;

		$inserted = $wpdb->insert(
			self::table(),
			[
				'attempt_id' => $attempt_id,
				'question_id' => $question_id,
				'user_id' => $user_id,
				'user_response_json' => wp_json_encode($user_response),
				'points_earned' => $points_earned,
				'points_possible' => $points_possible,
				'is_correct' => $is_correct ? 1 : 0,
			],
			['%d', '%d', '%d', '%s', '%f', '%f', '%d']
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Record how long a student viewed a question's rationale after
	 * answering - used for the "rationale_view_time" micro-analytic.
	 */
	public static function log_rationale_view(int $log_id, int $seconds_viewed): bool {
		global $wpdb;

		$updated = $wpdb->update(
			self::table(),
			['rationale_viewed_seconds' => $seconds_viewed],
			['id' => $log_id],
			['%d'],
			['%d']
		);

		return $updated !== false;
	}

	public static function get_attempt_logs(int $attempt_id): array {
		global $wpdb;
		$table = self::table();

		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $table WHERE attempt_id = %d", $attempt_id
		));
	}

	/**
	 * Per-question error rate across all students - feeds the Phase 5
	 * intructor "high error rate" diagnostic report.
	 */
	public static function get_question_error_rate(int $question_id): float {
		global $wpdb;
		$table = self::table();

		$stats = $wpdb->get_row($wpdb->prepare(
			"SELECT COUNT(*) AS total,  SUM(is_correct) AS correct FROM $table WHERE question_id = %d", $question_id
		));

		$total = (int) ($stats->correct ?? 0);
		if ($total === 0) {
			return 0.0;
		}

		$correct = (int) ($stats->correct ?? 0);
		return round((1 - ($correct / $total)) * 100, 2);
	}

	/**
	 * Per-user performance by NCLEX category - feeds the Phase 5 student
	 * radar chart of weak client-need areas. Requires a JOIN against
	 * wp_term_relationships since nclex_category lives on the question CPT,
	 * not in this table.
	 */
	public static function get_user_category_performance(int $user_id): array {
		global $wpdb;
		$table = self::table();

		return $wpdb->get_results($wpdb->prepare(
			"SELECT t.name AS category,
			SUM(l.points_earned) AS earned,
			SUM(l.points_possible) AS possible
			FROM $table l
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = l.question_id
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
			WHERE l.user_id = %d AND tt.taxonomy = 'fnr_nclex_category'
			GROUP BY t.term_id", $user_id
		));
	}
}
