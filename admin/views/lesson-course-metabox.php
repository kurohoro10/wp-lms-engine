<?php
/**
 * admin/views/lesson-course-metabox.php
 *
 * @var \WP_Post[] $courses
 * @var int        $selected
 * @var string     $nonce_action
 * @var string     $nonce_field
 */

if (!defined('ABSPATH')) exit;
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>

<p>
	<label for="fnr_lesson_course">
		<strong><?php esc_html_e('Course', 'feuernursingreview'); ?></strong>
	</label>
	<br>
	<select name="fnr_lesson_course" id="fnr_lesson_course" class="widefat">
		<option value="0"><?php esc_html_e('— Not assigned —', 'feuernursingreview'); ?></option>
		<?php foreach ($courses as $course) : ?>
			<option value="<?php echo esc_attr($course->ID); ?>" <?php selected($selected, $course->ID); ?>>
				<?php
					echo esc_html(get_the_title($course));
					if ($course->post_status !== 'publish') {
						echo ' (' . esc_html($course->post_status) . ')';
					}
				?>
			</option>
		<?php endforeach; ?>
	</select>
</p>

<?php if (!$courses) : ?>
	<p class="description">
		<?php esc_html_e('No courses exist yet. Create a course first, then come back and assign this lesson to it.', 'feuernursingreview'); ?>
	</p>
<?php else : ?>
	<p class="description">
		<?php esc_html_e('Save this before assigning a module below - the module list depends on the course.', 'feuernursingreview'); ?>
	</p>
<?php endif; ?>
