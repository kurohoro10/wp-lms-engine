<?php
/**
 * admin/views/lesson-module-metabox.php
 *
 * @var int        $course_id
 * @var string     $course_title
 * @var \WP_Post[] $modules
 * @var int        $selected
 * @var string     $nonce_action
 * @var string     $nonce_field
 */

if (!defined('ABSPATH')) exit;
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>

<?php if (!$course_id) : ?>

	<p class="description">
		<?php esc_html_e('Set this lesson\'s Course above and save, then choose a module here.', 'feuernursingreview'); ?>
	</p>

<?php elseif (!$modules) : ?>

	<p class="description">
		<?php
			printf(
				// translators: %s: course title
				esc_html__('%s has no modules yet. Add one under Courses → Add Module.', 'feuernursingreview'),
				esc_html($course_title)
			);
		?>
	</p>

<?php else : ?>

	<select name="fnr_lesson_module" id="fnr_lesson_module" class="widefat">
		<option value="0"><?php esc_html_e('— Unassigned —', 'feuernursingreview'); ?></option>
		<?php foreach ($modules as $module) : ?>
			<option value="<?php echo esc_attr($module->ID); ?>" <?php selected($selected, $module->ID); ?>>
				<?php
					echo esc_html(get_the_title($module));
					if ($module->post_status !== 'publish') {
						echo ' (' . esc_html($module->post_status) . ')';
					}
				?>
			</option>
		<?php endforeach; ?>
	</select>

	<p class="description">
		<?php esc_html_e('Unassigned lessons still appear on the course page, grouped at the end under "Other lessons".', 'feuernursingreview'); ?>
	</p>

<?php endif; ?>
