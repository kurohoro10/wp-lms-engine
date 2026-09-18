<?php
/**
 * admin/views/quiz-questions-metabox.php
 *
 * @var int[]      $question_ids
 * @var \WP_Post[] $bank
 * @var string     $nonce_action
 * @var string     $nonce_field
 */
if (!defined('ABSPATH')) exit;
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>

<label for="fnr_quiz_question_ids">
	<?php esc_html_e('Question IDs, in the order students should see them (comma-separated):', 'feuernursingreview'); ?>
</label>
<input
	type="text"
	name="fnr_quiz_question_ids"
	id="fnr_quiz_question_ids"
	class="widefat"
	value="<?php echo esc_attr(implode(',', $question_ids)); ?>"
	aria-describedby="fnr_quiz_question_ids_desc"
/>
<p class="description" id="fnr_quiz_question_ids_desc">
	<?php esc_html_e('Example: 12,15,9,20. IDs that are not published questions are dropped on save.', 'feuernursingreview'); ?>
</p>

<p><strong><?php esc_html_e('Question bank (for reference):', 'feuernursingreview'); ?></strong></p>

<?php if (!$bank) : ?>
	<p><?php esc_html_e('No questions in the bank yet.', 'feuernursingreview'); ?></p>
<?php else : ?>
	<ul class="fnr-question-bank-list">
		<?php foreach ($bank as $question) : ?>
			<li>
				#<?php echo esc_html($question->ID); ?> —
				<?php echo esc_html(get_the_title($question)); ?>
				<?php if ($question->post_status !== 'publish') : ?>
					<em>(<?php echo esc_html($question->post_status); ?>)</em>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
