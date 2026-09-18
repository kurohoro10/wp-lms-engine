<?php
/**
 * public/templates/single-fnr_lessons.php
 *
 * Templates for a single fnr_lesson. Loaded via the template_include
 * filter (see Templates.php below) rather than relying on theme
 * fallback, so this CPT always get its own layout regardless of
 * the active theme.
 */

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\Database\UserProgressDB;

if (!defined('ABSPATH')) exit;

get_header();

$lesson_id = get_the_ID();
$course_id = wp_get_post_parent_id($lesson_id);
$course_exists = $course_id && get_post_status($course_id) !== false;
$user_id   = get_current_user_id();

$is_completed = $user_id
	? UserProgressDB::is_completed($user_id, $course_id, $lesson_id)
	: false;
?>

<main id="fnr-lesson" class="fnr-lesson" data-lesson-id="<?php echo esc_attr($lesson_id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>" >
	<?php if ($course_exists) : ?>
		<p class="fnr-lesson-breadcrumb">
			<a href="<?php echo esc_url(get_permalink($course_id)); ?>">
				&larr; <?php echo esc_html(get_the_title($course_id)); ?>
			</a>
		</p>
	<?php endif; ?>

	<article <?php post_class(); ?>>
		<h1><?php the_title(); ?></h1>

		<div class="fnr-lesson-content">
			<?php the_content(); ?>
		</div>
	</article>

	<?php
		$media_html = \Feuernursingreview\Frontend\MediaPlayer::render($lesson_id);
		if ($media_html) :
	?>
			<div class="fnr-lesson-media">
				<?php echo $media_html; ?>
			</div>
	<?php endif; ?>

	<?php if (is_user_logged_in()) : ?>
		<?php
			$is_bookmarked = $user_id
				? \Feuernursingreview\Core\Bookmarks::is_bookmarked($user_id, $lesson_id)
				: false;
		?>

		<button
			type="button"
			id="fnr-bookmark-toggle"
			class="fnr-bookmark-btn"
			aria-pressed="<?php echo $is_bookmarked ? 'true' : 'false'; ?>"
		>
			<span class="fnr-bookmark-icon" aria-hidden="true"><?php echo $is_bookmarked ? '★' : '☆' ?></span>
			<span class="fnr-bookmark-label">
				<?php
					echo $is_bookmarked
						? esc_html__('Bookmarked', 'feuernursingreview')
						: esc_html__('Bookmark this lesson', 'feuernursingreview');
				?>
			</span>
		</button>

		<div class="fnr-lesson-progress" role="status" aria-live="polite">
			<button
				type="button"
				id="fnr-mark-complete"
				class="button button-primary fnr-mark-complete-btn"
				aria-pressed="<?php echo $is_completed ? 'true' : 'false'; ?>"
				<?php disabled($is_completed);?>
			>
				<?php echo $is_completed
					? esc_html__('Completed ✓', 'feuernursingreview')
					: esc_html__('Mark as complete', 'feuernursingreview');
				?>
			</button>
			<p id="fnr-mark-complete-feedback" class="fnr-feedback" aria-live="polite"></p>
		</div>
	<?php else : ?>
		<p class="fnr-login-notice">
			<?php esc_html_e('Log in to track your progress through this lesson.', 'feuernursingreview'); ?>
		</p>
	<?php endif; ?>
</main>

<?php
get_footer();
