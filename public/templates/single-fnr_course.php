<?php
/**
 * public/templates/single-fnr_course.php
 *
 * Course landing page: lesson  list (with per-lesson drip status),
 * course-level progress bar, enrollment gate.
 */

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\Core\Enrollment;
use Feuernursingreview\Core\DripEngine;
use Feuernursingreview\Database\UserProgressDB;

if (!defined('ABSPATH')) exit;

$course_id    = get_the_ID();
$user_id	  = get_current_user_id();
$is_logged_in = is_user_logged_in();
$is_enrolled  = $is_logged_in && Enrollment::is_enrolled($user_id, $course_id);

$lessons = get_posts([
	'post_type' 	 => LessonCPT::POST_TYPE,
	'post_parent' 	 => $course_id,
	'post_status' 	 => 'publish',
	'orderby' 	  	 => 'menu_order',
	'order' 	  	 => 	'ASC',
	'posts_per_page' => -1,
]);

$completed_count = 0;

if ($is_enrolled) {
	/**
	 * Refresh each lesson's unlock status against drip_days as the page
	 * loads - same check template_redirect runs, so the list reflects
	 * reality even for lessons the student hasn't clicked into yet.
	 */
	foreach ($lessons as $lesson) {
		DripEngine::is_accessible($user_id, $course_id, $lesson->ID);
	}

	$progress_rows 	 = UserProgressDB::get_course_progress($user_id, $course_id);
	$completed_count = count(array_filter($progress_rows, fn($row) => $row->status === 'completed'));
}

$total_lessons = count($lessons);
$percent = $total_lessons > 0 ? round(($completed_count / $total_lessons) * 100) : 0;
?>

<main id="fnr-course" class="fnr-course">
	<article <?php post_class(); ?>>
		<h1><?php the_title(); ?></h1>

		<div class="fnr-course-content">
			<?php the_content(); ?>
		</div>
	</article>

	<?php if (!$is_logged_in) : ?>

		<p class="fnr-login-notice">
			<?php
				printf(
					// translators: %s: login URL
					esc_html__('Please %s to access this course.', 'feuernursingreview'),
					'<a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html('log in', 'feuernursingreview') . '</a>'
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
				aria-label="
				<?php
					printf(
						// translator: 1: completed lessons, 2: total lessons
						esc_attr__('%1$d of %2$d lessons completed', 'feuernursingreview'),
						$completed_count,
						$total_lessons
					);
				?>"
			>
				<div class="fnr-progress-bar-fill" style="width:<?php echo esc_attr($percent); ?>"></div>
			</div>

			<p class="fnr-progress-label">

				<?php
					printf(
						// translator: 1: completed lessons, 2: total lessons, 3: percent
						esc_html__('%1$d of %2$d lessons complete (%3$d%%)', 'feuernursingreview'),
						$completed_count,
						$total_lessons,
						$percent
					);
				?>

			</p>
		</section>

		<?php if ($lessons) : ?>

			<nav aria-label="<?php esc_attr_e('Lessons', 'feuernursingreview'); ?>">
				<ol class="fnr-lesson-list">

					<?php
						foreach ($lessons as $lesson) :
							$row = UserProgressDB::get_row($user_id, $course_id, $lesson->ID);
							$status = $row->status ?? 'locked';
					?>

						<li class="fnr-lesson-item fnr-lesson-item--<?php echo esc_attr($status); ?>">

							<?php if ($status === 'locked') : ?>

								<span class="fnr-lesson-title fnr-lesson-title--locked" aria-disabled="true">
									<?php echo esc_html(get_the_title($lesson)); ?>
								</span>
								<span class="fnr-lesosn-status">
									<?php esc_html_e('Locked', 'feuernursingreview'); ?>
									<?php
										$drip_days = (int) get_post_meta($lesson->ID, 'drip_days', true);
										if ($drip_days > 0) {
											printf(
												// translators: %d: number of days
												' — ' . esc_html__('unlocks %d day(s) after enrollment', 'feuernursingreview'),
												$drip_days
											);
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
			</nav>

		<?php else : ?>

			<p><?php esc_html_e('No lessons have been published for this course yet.', 'feuernursingreview'); ?></p>

		<?php endif; ?>
	<?php endif; ?>

</main>

<?php
get_footer();
