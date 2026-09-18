<?php
/**
 * admin/views/module-course-metabox.php
 *
 * @var \WP_Post[] $courses
 * @var int        $selected
 * @var int        $menu_order
 * @var string     $nonce_action
 * @var string     $nonce_field
 */

if (!defined('ABSPATH')) exit;
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>

<p>
	<label for="fnr_module_course">
		<strong><?php esc_html_e('Course', 'feuernursingreview'); ?></strong>
	</label>
	<br>
	<select name="fnr_module_course" id="fnr_module_course" class="widefat">
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
		<?php esc_html_e('No courses exist yet. Create a course first, then come back and assign this module to it.', 'feuernursingreview'); ?>
	</p>
<?php endif; ?>

<p>
	<label for="fnr_module_order">
		<strong><?php esc_html_e('Order', 'feuernursingreview'); ?></strong>
	</label>
	<br>
	<input type="number" min="0" step="1" class="small-text"
		name="fnr_module_order" id="fnr_module_order"
		value="<?php echo esc_attr($menu_order); ?>"
		aria-describedby="fnr_module_order_desc" />
</p>
<p class="description" id="fnr_module_order_desc">
	<?php esc_html_e('Lower numbers appear first on the course page. Ties fall back to alphabetical order.', 'feuernursingreview'); ?>
</p>
