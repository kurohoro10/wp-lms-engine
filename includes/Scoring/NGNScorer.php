<?php
namespace Feuernursingreview\Scoring;

if (!defined('ABSPATH')) exit;

class NGNScorer {
	/**
	 * 1. The 0/1 Scoring Rule
	 * Used for: Traditional MCQ Bow-Tie, Drop-Down Cloze.
	 */
	public static function score_zero_one(array $user_answers, array $correct_answers): array {
		sort($user_answers);
		sort($correct_answers);

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
	 * 3. The Rationale Scoring Rule (Dyads & Triads)
	 * Used for: Drag-and-Drop Dyads/Triads, Linked Reasoning Chains.
	 * Rule: Points are awarded ONLY if BOTH cause and effect are correctly linked.
	 */
	public static function score_rationale_dyad(string $user_cause, string $user_effect, string $correct_cause, string $correct_effect): array {
		$cause_correct  = ($user_cause 	=== $correct_cause);
		$effect_correct = ($user_effect === $correct_effect);

		// Both items in the pair must be correct to earn the point
		$points = ($cause_correct && $effect_correct) ? 1.0 : 0.0;

		return [
			'points_earned' => $points,
			'max_points' 	=> 1.0,
			'is_correct' 	=> ($points === 1.0)
		];
	}
}
