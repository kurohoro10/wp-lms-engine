<?php
/**
 * admin/views/question-schema-metabox.php
 *
 * @var string $schema_json
 * @var string|false $error
 * @var string $nonce_action
 * @var string $nonce_field
 */
if (!defined('ABSPATH')) exit;
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>
<?php if ($error) : ?>
	<div class="notice notice-error inline" role="alert">
		<p><?php echo esc_html($error); ?></p>
	</div>
<?php endif; ?>
<label for="fnr_question_schema" class="screen-reader-text">
	<?php esc_html_e('Question schema JSON', 'feuernursingreview'); ?>
</label>
<textarea name="fnr_question_schema" id="fnr_question_schema" rows="200" style="width:100%;font-family:monospace;" aria-describedby="fnr_question_schema_desc">
	<?php echo esc_textarea( $schema_json ); ?>
</textarea>
<p class="description" id="fnr_question_schema_desc">
	<?php esc_html_e('Raw JSON matching the question schema format. Invalid JSON will not be saved - your last valid schema is kept.', 'feuernursingreview'); ?>
</p>
