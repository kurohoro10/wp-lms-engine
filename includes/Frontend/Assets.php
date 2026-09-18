<?php
/**
 * File: includes/Frontend/Assets.php
 *
 * Frontend CSS/JS for the plugin.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\Frontend;

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\QuizCPT;

if (!defined('ABSPATH')) exit;

class Assets {
	public static function register() {
		add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
	}

	public static function enqueue() {
		if (is_singular(QuizCPT::POST_TYPE)) {
			self::enqueue_quiz_assets();
			return;
		}

		if (!is_singular(LessonCPT::POST_TYPE)) {
			return;
		}

		self::enqueue_script('fnr-lesson-progress', 'lesson-progress.js');
		self::enqueue_script('fnr-bookmark-toggle', 'bookmark-toggle.js', ['fnr-lesson-progress']);

		wp_localize_script('fnr-lesson-progress', 'fnrLessonProgress', [
			'restUrl' => esc_url_raw(rest_url('fnr/v1/')),
			'nonce'   => wp_create_nonce('wp_rest'),
			'i18n'    => [
				'saving'       => __('Saving…', 'feuernursingreview'),
				'saved'        => __('Progress saved.', 'feuernursingreview'),
				'completed'    => __('Completed ✓', 'feuernursingreview'),
				'bookmarked'   => __('Bookmarked', 'feuernursingreview'),
				'bookmarkThis' => __('Bookmark this lesson', 'feuernursingreview'),
				'error'        => __('Something went wrong — try again.', 'feuernursingreview'),
			],
		]);

		$media_type = get_post_meta(get_the_ID(), 'media_type', true);

		if ('video' === $media_type) {
			self::enqueue_script('fnr-video-progress', 'video-progress.js', ['fnr-lesson-progress']);
		}

		if ('slides' === $media_type) {
			self::enqueue_style('fnr-slide-viewer', 'slide-viewer.css');
			self::enqueue_script('fnr-slide-viewer', 'slide-viewer.js', ['fnr-lesson-progress']);
		}
	}

	private static function enqueue_quiz_assets() {
		self::enqueue_style('fnr-quiz-runner', 'quiz-runner.css');
		self::enqueue_script('fnr-quiz-runner', 'quiz-runner.js');

		$course_id = wp_get_post_parent_id(get_the_ID());

		wp_localize_script('fnr-quiz-runner', 'fnrQuizRunner', [
			'restUrl'     => esc_url_raw(rest_url('fnr/v1/')),
			'nonce'       => wp_create_nonce('wp_rest'),
			// Empty when the quiz isn't assigned to a course (see
			// QuizCourseMetaBox) - quiz-runner.js skips the "Back to
			// Course" link in that case rather than linking nowhere.
			'courseUrl'   => $course_id ? esc_url_raw(get_permalink($course_id)) : '',
			'courseTitle' => $course_id ? get_the_title($course_id) : '',
			'i18n'    => [
				'saving'        => __('Saving…', 'feuernursingreview'),
				'error'         => __('Something went wrong — try again.', 'feuernursingreview'),
				'questionOf'    => __('Question %1$d of %2$d', 'feuernursingreview'),
				'submitAnswer'  => __('Submit Answer', 'feuernursingreview'),
				'nextQuestion'  => __('Next Question', 'feuernursingreview'),
				'viewResults'   => __('View Results', 'feuernursingreview'),
				'correct'       => __('Correct', 'feuernursingreview'),
				'incorrect'     => __('Incorrect', 'feuernursingreview'),
				'passed'        => __('You passed!', 'feuernursingreview'),
				'failed'        => __('Not quite — review the material and try again.', 'feuernursingreview'),
				'scoreDetail'   => __('%1$s of %2$s points (%3$s%% required to pass)', 'feuernursingreview'),
				'orderedHint'   => __('Use the Up/Down buttons to put these in the correct order.', 'feuernursingreview'),
				'moveUp'        => __('Move up:', 'feuernursingreview'),
				'moveDown'      => __('Move down:', 'feuernursingreview'),
				'yourAnswer'    => __('Your answer', 'feuernursingreview'),
				'hotspotHint'   => __('Click the area(s) of the image that answer the question.', 'feuernursingreview'),
				'selectOne'     => __('— Select —', 'feuernursingreview'),
				'flipCard'      => __('Flip card to see the rationale', 'feuernursingreview'),
				'previous'      => __('Previous', 'feuernursingreview'),
				'next'          => __('Next', 'feuernursingreview'),
				'noRationale'   => __('No rationale was written for this question yet.', 'feuernursingreview'),
				'noFlashcards'  => __('This quiz has no questions to review yet.', 'feuernursingreview'),
				'exitFlashcards' => __('Exit Flashcard Review', 'feuernursingreview'),
				'timeRemaining' => __('Time remaining:', 'feuernursingreview'),
				'reviewAnswers' => __('Review Answers', 'feuernursingreview'),
				'hideReview'    => __('Hide Review', 'feuernursingreview'),
				'retakeQuiz'    => __('Retake Quiz', 'feuernursingreview'),
				'backToCourse'  => __('Back to %s', 'feuernursingreview'),
				'noReview'      => __('No answers were recorded for this attempt.', 'feuernursingreview'),
				'yourAnswerLabel'    => __('Your answer:', 'feuernursingreview'),
				'correctAnswerLabel' => __('Correct answer:', 'feuernursingreview'),
			],
		]);
	}

	private static function enqueue_script($handle, $filename, $dependencies = []) {
		wp_enqueue_script(
			$handle,
			FNR_PLUGIN_URL . 'public/js/' . $filename,
			$dependencies,
			FNR_VERSION,
			true
		);
	}

	private static function enqueue_style($handle, $filename, $dependencies = []) {
		wp_enqueue_style(
			$handle,
			FNR_PLUGIN_URL . 'public/css/' . $filename,
			$dependencies,
			FNR_VERSION
		);
	}
}
