<?php
/**
 * include/Database/QuizAttempsDB.php
 */
namespace Feuernursingreview\Database;

if (!defined('ABSPATH')) exit;

class QuizAttemptsDB {
	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'fnr_quiz_attempts';
	}

	/**
	 * Start a new attempt session. Returns the new attempt's ID.
	 */
	public static function start_attempt(int $user_id, int $quiz_id): int {
		global $wpdb;

		$wpdb->insert(self::table(), [
			'user_id' 	 => $user_id,
			'quiz_id' 	 => $quiz_id,
			'started_at' => current_time('mysql'),
		], ['%d', '%d', '%s']);

		return (int) $wpdb->insert_id;
	}

	public static function get_attempt(int $attempt_id) {
		global $wpdb;
		$table = self::table();

		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d",
			$attempt_id
		));
	}

	/**
	 * Recalculate and store score_earned/total_possible/percentage from the
	 * attempt's question_logs rows. Called after each question is scored,
	 * or once at final submission - caller decides which.
	 */
	public static function recalculate_score(int $attempt_id): bool {
		global $wpdb;
		$logs_table = $wpdb->prefix . 'fnr_question_logs';

		$totals = $wpdb->get_row($wpdb->prepare(
			"SELECT SUM(points_earned) AS earned, SUM(points_possible) AS possible FROM $logs_table WHERE attempt_id = %d", $attempt_id
		));

		$earned 	= (float) ($totals->earned ?? 0);
		$possible 	= (float) ($totals->possible ?? 0);
		$percentage = $possible > 0 ? round(($earned / $possible) * 100, 2) : 0.0;

		$updated = $wpdb->update(
			self::table(),
			[
				'score_earned' 	 => $earned,
				'total_possible' => $possible,
				'percentage' 	 => $percentage,
			],
			['id' => $attempt_id],
			['%f', '%f', '%f'],
			['%d']
		);

		return $updated !==  false;
	}

	/**
	 * Finalize the attempt: stamp completed_at, set passing_status against
	 * the quiz's pass threshold, and lock in the final score.
	 */
	public static function complete_attempt (int $attempt_id, float $pass_threshold, int $time_spent_seconds): bool {
		global $wpdb;

		self::recalculate_score($attempt_id);
		$attempt = self::get_attempt($attempt_id);

		if (!$attempt) {
			return false;
		}

		$passing_status = ($attempt->percentage >= $pass_threshold) ? 'passed' : 'failed';

		$updated = $wpdb->update(
			self::table(),
			[
				'passing_status' 	 => $passing_status,
				'time_spent_seconds' => $time_spent_seconds,
				'completed_at' 		 => current_time('mysql'),
			],
			['id' => $attempt_id],
			['%s', '%d', '%s'],
			['%d']
		);

		if ($updated !== false) {
			/**
			 * Lets DripEngine unlock any quiz-gated lessons straight away
			 * rather than on the student's next page load.
			 */
			do_action('fnr_quiz_attempt_completed', $attempt_id, (int) $attempt->user_id);
		}

		return $updated !== false;
	}

	/**
	 * All attempts for a user on a given quiz - used for "best attemp" /
	 * retake history displays.
	 */
	public static function get_user_attempts(int $user_id, int $quiz_id): array {
		global $wpdb;
		$table = self::table();

		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $table WHERE user_id = %d AND quiz_id = %d ORDER BY started_at DESC", $user_id, $quiz_id
		));
	}

	/**
	 * The student's best percentage across all COMPLETED attempts on a
	 * quiz, or null if they've never finished one.
	 *
	 * Only completed attempts count - an abandoned attempt sitting at 0%
	 * shouldn't drag a gate down, and an in-progress one that happens to
	 * be at 100% after one question shouldn't open it early.
	 */
	public static function get_best_percentage(int $user_id, int $quiz_id): ?float {
		global $wpdb;
		$table = self::table();

		$best = $wpdb->get_var($wpdb->prepare(
			"SELECT MAX(percentage) FROM $table
			WHERE user_id = %d AND quiz_id = %d AND completed_at IS NOT NULL",
			$user_id,
			$quiz_id
		));

		return $best === null ? null : (float) $best;
	}
}
