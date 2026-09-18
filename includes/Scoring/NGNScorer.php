<?php
namespace Feuernursingreview\Scoring;

if (!defined('ABSPATH')) exit;

class NGNScorer {
	/**
	 * 1. The 0/1 Scoring Rule
	 * Used for: Traditional MCQ, Matrix Single Choice, Drop-Down Cloze,
	 * Bow-Tie, and Ordered Response.
	 *
	 * Deliberately does NOT sort either array before comparing. For MCQ
	 * that has no effect (a single-answer selection sorts to itself),
	 * but for Ordered Response the sequence IS the answer - sorting
	 * would make any permutation of the right options score as correct.
	 */
	public static function score_zero_one(array $user_answers, array $correct_answers): array {
		$is_correct = ($user_answers === $correct_answers);
		return [
			'points_earned' => $is_correct ? 1.0 : 0.0,
			'max_points' 	=> 1.0,
			'is_correct' 	=> $is_correct
		];
	}

	/**
	 * 2. The Plus/Minus(+/-) Scoring Rule
	 * Used for: Select All that Apply (SATA), Matrix Multiple Response.
	 * Rule: +1 point for correct choices, -1 point for incorrect choices. Lowest score is 0.
	 */
	public static function score_plus_minus(array $user_selections, array $correct_options, int $total_possibel_options): array {
		$score = 0;

		foreach ($user_selections as $selection) {
			if (in_array($selection, $correct_options, true)) {
				$score += 1; // +1 for correct selection
			} else {
				$score -= 1; // -1 for selecting an incorrect answer
			}
		}

		// Score cannot drop below zero
		$final_score = max(0, $score);
		$max_possible = count($correct_options);

		return [
			'points_earned' => (float) $final_score,
			'max_points' 	=> (float) $max_possible,
			'is_correct' 	=> ($final_score === $max_possible)
		];
	}

	/**
	 * 3. The Rationale Scoring Rule - dyad AND triad.
	 * Used for: Drag-and-Drop Dyads (cause -> effect) and Triads
	 * (condition -> intervention -> outcome).
	 * Rule: points are awarded ONLY if every linked slot is correct -
	 * a 2-of-3 triad is worth the same as 0-of-3.
	 *
	 * $correct_links defines which slots exist (its keys), so the same
	 * method handles a 2-key dyad or a 3-key triad without the caller
	 * needing to know which shape it's scoring.
	 */
	public static function score_rationale(array $user_links, array $correct_links): array {
		$all_correct = true;

		foreach ($correct_links as $slot => $correct_value) {
			if (($user_links[$slot] ?? null) !== $correct_value) {
				$all_correct = false;
				break;
			}
		}

		$points = $all_correct ? 1.0 : 0.0;

		return [
			'points_earned' => $points,
			'max_points' 	=> 1.0,
			'is_correct' 	=> $all_correct,
		];
	}

	/**
	 * Back-compat wrapper for the old dyad-only signature. New code
	 * should call score_rationale() directly (it also handles triads).
	 */
	public static function score_rationale_dyad(string $user_cause, string $user_effect, string $correct_cause, string $correct_effect): array {
		return self::score_rationale(
			['cause' => $user_cause, 'effect' => $user_effect],
			['cause' => $correct_cause, 'effect' => $correct_effect]
		);
	}

	/**
	 * 4. Numeric Tolerance Rule
	 * Used for: Numeric Fill-in-the-Blank (dosage calculations, IV drip
	 * rates). An exact-match array comparison is too brittle for numeric
	 * answers ("2.5" vs "2.50" vs floating-point rounding), so this
	 * compares within an allowed tolerance instead.
	 */
	public static function score_numeric(float $user_value, float $correct_value, float $tolerance = 0.0): array {
		$is_correct = abs($user_value - $correct_value) <= abs($tolerance);

		return [
			'points_earned' => $is_correct ? 1.0 : 0.0,
			'max_points' 	=> 1.0,
			'is_correct' 	=> $is_correct,
		];
	}
}
