<?php
/**
 * public/templates/dashboard.php
 *
 * [fnr_dashboard] shortcode output - student facing progress page
 * (spec §2.2 "Frontend Student Portal" / Phase 2.4 "Student Dashboard
 * & Analytics"). Rendered as a fragment inside whatever page/post the
 * shortcode is placed on, so no get_header()/get_footer() here - see
 * single-fnr_course.php for the full page template_include variant.
 *
 * Expects (via StudentDashboard::render()):
 * @var array $courses Per-enrolled-course stats: id, title, permalink,
 * 						completed, total, percent, badge (nullable).
 * @var array $bookmarks WP_Post[] of bookmarked fnr_lesson posts.
 */

if (!defined('ABSPATH')) exit;
?>

<div id="fnr-dashboard" class="fnr-dashboard">
	<h1 class="fnr-dashboard-title"><?php esc_html_e('My Dashboard', 'feuernursingreview'); ?></h1>

	<section class="fnr-dashboard-courses" aria-labelledby="fnr-dashboard-courses-heading">
		<h2 id="fnr-dashboard-courses-heading"><?php esc_html_e('My Courses', 'feuernursingreview'); ?></h2>

		<?php if ($courses) : ?>

			<ul class="fnr-dashboard-course-list">

				<?php foreach($courses as $course) : ?>

					<li class="fnr-dashboard-course-card">
						<h3 class="fnr-dashboard-course-title">
							<a href="<?php echo esc_url($course['permalink']); ?>">
								<?php echo esc_html($course['title']); ?>
							</a>
						</h3>

						<div
							class="fnr-progress-bar"
							role="progressbar"
							aria-valuenow="<?php echo esc_attr( $course['percent'] ); ?>"
							aria-valuemin="0"
							aria-valuemax="100"
							aria-label="<?php
								printf(
									// translators: 1: completed lessons, 2: total lessons
									esc_attr__('%1$d of %2$d lessons completed', 'feuernursingreview'),
									$course['completed'],
									$course['total']
								);
							?>"
						>
							<div class="fnr-progress-bar-fill" style="width:<?php echo esc_attr( $course['percent'] ); ?>%"></div>
						</div>

						<p class="fnr-progress-label">
							<?php
								printf(
									// translators: 1: completed lessons, 2: total lessons, 3: percent
									esc_html__('%1$d of %2$d lessons complete (%3$d%%)', 'feuernursingreview'),
									$course['completed'],
									$course['total'],
									$course['percent']
								);
							?>
						</p>

						<?php if ($course['badge']) : ?>

							<p class="fnr-dashboard-badge">
								<span class="fnr-dashboard-badge-label">
									<?php echo esc_html($course['badge']); ?>
								</span>
							</p>

						<?php endif; ?>

					</li>

				<?php endforeach; ?>
			</ul>

		<?php else : ?>

			<p class="fnr-dashboard-empty">
				<?php esc_html_e('You are not enrolled in any courses yet. Contact your instructor for access.', 'feuernursingreview'); ?>
			</p>

		<?php endif; ?>

	</section>

	<section class="fnr-dashboard-bookmarks" aria-labelledby="fnr-dashboard-bookmarks-heading">
		<h2 id="fnr-dashboard-bookmarks-heading"><?php esc_html_e('Bookmarked Lessons', 'feuernursingreview'); ?></h2>

		<?php if ($bookmarks) : ?>

			<ul class="fnr-dashboard-bookmark-list">

				<?php foreach($bookmarks as $lesson) : ?>

					<li class="fnr-dashboard-bookmark-item">
						<a href="<?php echo esc_url(get_permalink($lesson)); ?>">
							<?php echo esc_html(get_the_title($lesson)); ?>
						</a>
					</li>

				<?php endforeach; ?>

			</ul>

		<?php else : ?>

			<p class="fnr-dashboard-empty">
				<?php esc_html_e('You haven\'t bookmarked any lessons yet.', 'feuernursingreview'); ?>
			</p>

		<?php endif; ?>
	</section>
</div>
