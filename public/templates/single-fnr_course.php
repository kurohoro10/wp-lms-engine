<?php
/**
 * public/templates/single-fnr_course.php
 *
 * Course landing page: lessons grouped by module, per-lesson lock state
 * with the reason it's locked, course progress bar, enrollment gate.
 */

use Feuernursingreview\Core\Enrollment;
use Feuernursingreview\Core\DripEngine;
use Feuernursingreview\Core\Modules;
use Feuernursingreview\Database\UserProgressDB;
use Feuernursingreview\Database\QuizAttemptsDB;

if (!defined('ABSPATH')) exit;

get_header();

$course_id    = get_the_ID();
$user_id      = get_current_user_id();
$is_logged_in = is_user_logged_in();
$is_enrolled  = $is_logged_in && Enrollment::is_enrolled($user_id, $course_id);

$groups  = Modules::get_course_tree($course_id);
$lessons = Modules::get_ordered_lessons($course_id);
$quizzes = Modules::get_course_quizzes($course_id);

$completed_count = 0;

if ($is_enrolled) {
	/**
	 * Refresh each lesson's unlock status as the page loads - the same
	 * check template_redirect runs - so the list reflects reality even
	 * for lessons the student hasn't clicked into yet.
	 */
	foreach ($lessons as $lesson) {
		DripEngine::is_accessible($user_id, $course_id, $lesson->ID);
	}

	$progress_rows   = UserProgressDB::get_course_progress($user_id, $course_id);
	$completed_count = count(array_filter($progress_rows, fn($row) => $row->status === 'completed'));
}

$total_lessons = count($lessons);
$percent = $total_lessons > 0 ? round(($completed_count / $total_lessons) * 100) : 0;

// Set by DripEngine::enforce_drip() when it bounced someone off a lesson.
$blocked_lesson = isset($_GET['fnr_locked']) ? absint($_GET['fnr_locked']) : 0;
?>

<main id="fnr-course" class="fnr-course">
	<article <?php post_class(); ?>>
		<h1><?php the_title(); ?></h1>

		<div class="fnr-course-content">
			<?php the_content(); ?>
		</div>
	</article>

	<?php if ($blocked_lesson && $is_enrolled) : ?>
		<p class="fnr-locked-notice" role="status">
			<?php
				printf(
					// translators: %s: lesson title
					esc_html__('"%s" isn\'t available yet.', 'feuernursingreview'),
					esc_html(get_the_title($blocked_lesson))
				);
				$reason = DripEngine::reason($user_id, $course_id, $blocked_lesson);
				if ($reason) {
					echo ' ' . esc_html($reason);
				}
			?>
		</p>
	<?php endif; ?>

	<?php if (!$is_logged_in) : ?>

		<p class="fnr-login-notice">
			<?php
				printf(
					// translators: %s: login link
					esc_html__('Please %s to access this course.', 'feuernursingreview'),
					'<a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('log in', 'feuernursingreview') . '</a>'
				);
			?>
		</p>

	<?php elseif (!$is_enrolled) : ?>

		<p class="fnr-enrollment-notice">
			<?php esc_html_e('You are not yet enrolled in this course. Contact your instructor for access.', 'feuernursingreview'); ?>
		</p>

	<?php else : ?>

		<section class="fnr-course-progress" aria-label="<?php esc_attr_e('Course progress', 'feuernursingreview'); ?>">
			<div
				class="fnr-progress-bar"
				role="progressbar"
				aria-valuenow="<?php echo esc_attr($percent); ?>"
				aria-valuemin="0"
				aria-valuemax="100"
				aria-label="<?php
					printf(
						// translators: 1: completed lessons, 2: total lessons
						esc_attr__('%1$d of %2$d lessons completed', 'feuernursingreview'),
						$completed_count,
						$total_lessons
					);
				?>"
			>
				<div class="fnr-progress-bar-fill" style="width:<?php echo esc_attr($percent); ?>%"></div>
			</div>

			<p class="fnr-progress-label">
				<?php
					printf(
						// translators: 1: completed lessons, 2: total lessons, 3: percent
						esc_html__('%1$d of %2$d lessons complete (%3$d%%)', 'feuernursingreview'),
						$completed_count,
						$total_lessons,
						$percent
					);
				?>
			</p>
		</section>

		<?php if ($groups) : ?>

			<nav class="fnr-course-outline" aria-label="<?php esc_attr_e('Course outline', 'feuernursingreview'); ?>">

				<?php foreach ($groups as $group_index => $group) :
					$module = $group['module'];

					/**
					 * Per-module completion, so a student can see which
					 * module they're in the middle of rather than only a
					 * course-wide number.
					 */
					$module_total = count($group['lessons']);
					$module_done  = count(array_filter(
						$group['lessons'],
						fn($lesson) => UserProgressDB::is_completed($user_id, $course_id, $lesson->ID)
					));

					$heading_id = 'fnr-module-' . ($module ? $module->ID : 'other');
				?>

					<section class="fnr-module" aria-labelledby="<?php echo esc_attr($heading_id); ?>">

						<h2 class="fnr-module__title" id="<?php echo esc_attr($heading_id); ?>">
							<?php
								echo $module
									? esc_html(get_the_title($module))
									: esc_html__('Other lessons', 'feuernursingreview');
							?>
							<span class="fnr-module__count">
								<?php
									printf(
										// translators: 1: completed lessons in this module, 2: total lessons in this module
										esc_html__('%1$d/%2$d', 'feuernursingreview'),
										$module_done,
										$module_total
									);
								?>
							</span>
						</h2>

						<?php if ($module && $module->post_content) : ?>
							<div class="fnr-module__description">
								<?php echo wp_kses_post(apply_filters('the_content', $module->post_content)); ?>
							</div>
						<?php endif; ?>

						<ol class="fnr-lesson-list">
							<?php foreach ($group['lessons'] as $lesson) :
								$row    = UserProgressDB::get_row($user_id, $course_id, $lesson->ID);
								$status = $row->status ?? 'locked';
							?>

								<li class="fnr-lesson-item fnr-lesson-item--<?php echo esc_attr($status); ?>">

									<?php if ($status === 'locked') : ?>

										<span class="fnr-lesson-title fnr-lesson-title--locked" aria-disabled="true">
											<?php echo esc_html(get_the_title($lesson)); ?>
										</span>
										<span class="fnr-lesson-status">
											<?php esc_html_e('Locked', 'feuernursingreview'); ?>
											<?php
												$reason = DripEngine::reason($user_id, $course_id, $lesson->ID);
												if ($reason) {
													echo ' — ' . esc_html($reason);
												}
											?>
										</span>

									<?php else : ?>

										<a href="<?php echo esc_url(get_permalink($lesson)); ?>" class="fnr-lesson-title">
											<?php echo esc_html(get_the_title($lesson)); ?>
										</a>
										<span class="fnr-lesson-status">
											<?php
												echo $status === 'completed'
													? esc_html__('Completed ✓', 'feuernursingreview')
													: esc_html__('Available', 'feuernursingreview');
											?>
										</span>

									<?php endif; ?>
								</li>

							<?php endforeach; ?>
						</ol>
					</section>

				<?php endforeach; ?>

			</nav>

		<?php else : ?>

			<p><?php esc_html_e('No lessons have been published for this course yet.', 'feuernursingreview'); ?></p>

		<?php endif; ?>

		<?php if ($quizzes) : ?>

			<section class="fnr-course-quizzes" aria-labelledby="fnr-course-quizzes-heading">
				<h2 id="fnr-course-quizzes-heading"><?php esc_html_e('Quizzes', 'feuernursingreview'); ?></h2>

				<ul class="fnr-quiz-list">
					<?php foreach ($quizzes as $quiz) :
						$best = QuizAttemptsDB::get_best_percentage($user_id, $quiz->ID);
					?>
						<li class="fnr-quiz-item">
							<a href="<?php echo esc_url(get_permalink($quiz)); ?>" class="fnr-quiz-title">
								<?php echo esc_html(get_the_title($quiz)); ?>
							</a>
							<?php if ($best !== null) : ?>
								<span class="fnr-quiz-status">
									<?php
										printf(
											// translators: %d: best score percentage on this quiz
											esc_html__('Best score: %d%%', 'feuernursingreview'),
											round($best)
										);
									?>
								</span>
							<?php else : ?>
								<span class="fnr-quiz-status"><?php esc_html_e('Not attempted', 'feuernursingreview'); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

		<?php endif; ?>
	<?php endif; ?>

</main>

<?php
get_footer();
