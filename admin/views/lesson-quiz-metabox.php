<?php
/**
 * @var \WP_Post[] $quizzes
 * @var int $selected_quiz_id
 * @var string $nonce_action
 * @var string $nonce_field
 */
if (!defined('ABSPATH')) exit;
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>
<p>
	<label for="fnr_linked_quiz_id"><?php esc_html_e('Quiz for this lesson:', 'feuernursingreview'); ?></label>
	<br>
	<select name="fnr_linked_quiz_id" id="fnr_linked_quiz_id" class="widefat">
		<option value="0"><?php esc_html_e('— None —', 'feuernursingreview'); ?></option>
		<?php foreach ($quizzes as $quiz) : ?>
			<option value="<?php echo esc_attr($quiz->ID); ?>" <?php selected($selected_quiz_id, $quiz->ID); ?>>
				<?php echo esc_html(get_the_title($quiz)); ?>
				<?php if ($quiz->post_status !== 'publish') : ?>
					(<?php echo esc_html($quiz->post_status); ?>)
				<?php endif; ?>
			</option>
		<?php endforeach; ?>
	</select>
</p>
<p class="description">
	<?php esc_html_e('When set, a "Take the Quiz" link appears on the lesson, and the student must pass this quiz before the lesson can be marked complete.', 'feuernursingreview'); ?>
</p>
