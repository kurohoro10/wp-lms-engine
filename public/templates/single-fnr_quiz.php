<?php
/**
 * public/templates/single-fnr_quiz.php
 *
 * Loaded via the template_include filter (Templates.php) so this CPT
 * gets its own layout regardless of the active theme, same pattern as
 * single-fnr_lesson.php and single-fnr_course.php.
 *
 * This template only renders the intro screen (question count, time
 * limit, pass threshold, practice/exam mode choice) plus empty mount
 * points. Once "Start Quiz" is clicked, public/js/quiz-runner.js takes
 * over: it calls /start-attempt, renders each question, and posts
 * answers to /submit-question and /complete-attempt.
 */

use Feuernursingreview\CPT\QuizCPT;

if (!defined('ABSPATH')) exit;

get_header();

$quiz_id        = get_the_ID();
$course_id      = wp_get_post_parent_id($quiz_id);
$time_limit     = (int) get_post_meta($quiz_id, 'time_limit_minutes', true);
$pass_threshold = get_post_meta($quiz_id, 'pass_threshold', true);
$pass_threshold = $pass_threshold !== '' ? (float) $pass_threshold : 75.0;

$question_ids   = get_post_meta($quiz_id, 'question_ids', true);
$question_count = is_array($question_ids) ? count($question_ids) : 0;
?>

<main id="fnr-quiz" class="fnr-quiz" data-quiz-id="<?php echo esc_attr($quiz_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>" data-time-limit="<?php echo esc_attr($time_limit); ?>">
	<?php if ($course_id) : ?>
		<p class="fnr-lesson-breadcrumb">
			<a href="<?php echo esc_url(get_permalink($course_id)); ?>">
				&larr; <?php echo esc_html(get_the_title($course_id)); ?>
			</a>
		</p>
	<?php endif; ?>

	<article <?php post_class(); ?>>
		<h1><?php the_title(); ?></h1>

		<div class="fnr-quiz-content">
			<?php the_content(); ?>
		</div>
	</article>

	<?php if (!is_user_logged_in()) : ?>

		<p class="fnr-login-notice">
			<?php esc_html_e('Log in to take this quiz.', 'feuernursingreview'); ?>
		</p>

	<?php elseif ($question_count === 0) : ?>

		<p class="fnr-quiz-empty">
			<?php esc_html_e('This quiz has no questions yet — check back soon.', 'feuernursingreview'); ?>
		</p>

	<?php else : ?>

		<div id="fnr-quiz-intro" class="fnr-quiz-intro">
			<ul class="fnr-quiz-meta">
				<li>
					<?php
						printf(
							/* translators: %d: number of questions in the quiz */
							esc_html(_n('%d question', '%d questions', $question_count, 'feuernursingreview')),
							$question_count
						);
					?>
				</li>
				<li>
					<?php
						if ($time_limit > 0) {
							printf(
								/* translators: %d: time limit in minutes */
								esc_html__('%d minute time limit', 'feuernursingreview'),
								$time_limit
							);
						} else {
							esc_html_e('Untimed practice mode', 'feuernursingreview');
						}
					?>
				</li>
				<li>
					<?php
						printf(
							/* translators: %s: passing score percentage */
							esc_html__('%s%% to pass', 'feuernursingreview'),
							esc_html(rtrim(rtrim(number_format($pass_threshold, 1), '0'), '.'))
						);
					?>
				</li>
			</ul>

			<fieldset class="fnr-quiz-mode-choice">
				<legend><?php esc_html_e('Quiz mode', 'feuernursingreview'); ?></legend>

				<label>
					<input type="radio" name="fnr_quiz_mode" value="practice" checked />
					<?php esc_html_e('Practice mode — see the rationale after each answer', 'feuernursingreview'); ?>
				</label>
				<label>
					<input type="radio" name="fnr_quiz_mode" value="exam" />
					<?php esc_html_e('Simulated exam — see your results at the end', 'feuernursingreview'); ?>
				</label>
			</fieldset>

			<div class="fnr-quiz-intro-actions">
				<button type="button" id="fnr-quiz-start" class="button button-primary">
					<?php esc_html_e('Start Quiz', 'feuernursingreview'); ?>
				</button>
				<button type="button" id="fnr-quiz-flashcards-start" class="button">
					<?php esc_html_e('Flashcard Review', 'feuernursingreview'); ?>
				</button>
			</div>
		</div>

		<div id="fnr-quiz-timer" class="fnr-quiz-timer-bar" hidden></div>

		<div id="fnr-quiz-runner" class="fnr-quiz-runner" hidden></div>

		<div id="fnr-quiz-flashcards" class="fnr-quiz-flashcards" hidden></div>

		<div id="fnr-quiz-results" class="fnr-quiz-results" hidden role="status" aria-live="polite"></div>

	<?php endif; ?>
</main>

<?php
get_footer();
