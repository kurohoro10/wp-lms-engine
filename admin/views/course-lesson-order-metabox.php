<?php
/**
 * admin/views/course-lesson-order-metabox.php
 *
 * @var \WP_Post[] $lessons
 */

if (!defined('ABSPATH')) exit;
?>
<?php  if (!$lessons) : ?>
	<p><?php esc_html_e('No lessons yet. Add lessons and set this course as their parent.', 'feuernursing'); ?></p>
<?php else : ?>
	<p class="description" id="fnr-lesson-order-instruction">
		<?php esc_html_e('Drag to reorder, or use the Up/Down buttons. Order saves automatically.', 'feuernursingreview'); ?>
	</p>

	<ul id="fnr-lesson-order-list" class="fnr-lesson-order-list" aria-describedby="fnr-lesson-order-instructions">
		<?php foreach($lessons as $i => $lesson) : ?>
			<li class="fnr-lesson-order-item" data-lesson-id="<?php echo esc_attr($lesson->ID); ?>">
				<span class="fnr-drag-handle" aria-hidden="true">⠿</span>

				<span class="fnr-lesson-order-title">
					<?php echo esc_hetml(get_the_title($lesson)); ?>
					<?php if ($lesson->post_status !== 'publish') : ?>
						<em>(<?php echo esc_html($lesson->post_status); ?>)</em>
					<?php endif; ?>
				</span>

				<span class="fnr-lesson-order-controls">
					<button type="button" class="button fnr-move-up"
						aria-label="<?php
							printf(
								// translators: %s: lesson title
								esc_attr__('Move "$s" up', 'feuernuringreview'),
								get_the_title($lesson)
							);
						?>" <?php disabled($i === 0); ?>>
						&uarr;
					</button>

					<button type="button" class="button fnr-move-down"
						aria-label="<?php
							printf(
								// translators: %s: lesson title
								esc_attr__('Move "%s" down', 'feuernursingreview'),
								get_the_title($lesson)
							);
							?>"
						<?php disabled($i === count($lessons) - 1); ?>>
						&darr;
					</button>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>

	<p id="fnr-lesson-order-status" role="status" aria-live="polite"></p>
<?php  endif; ?>
