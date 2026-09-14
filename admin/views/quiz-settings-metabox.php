<?php
/**
 * @var int $time_limit
 * @var float $pass_threshold
 * @var bool $ngn_mode
 * @var string $nonce_action
 * @var string $nonce_field
 */
if (!defined('ABSPATH')) exit;
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<label for="fnr_time_limit_minutes">
				<?php esc_html_e('Time Limit (minutes)', 'feuernursingreview'); ?>
			</label>
		</th>
		<td>
			<input type="number" min="0" step="1" name="fnr_time_limit_minutes" id="fnr_time_limit_minutes" value="<?php echo esc_attr( $time_limit ); ?>" class="small-text" aria-describedby="fnr_time_limit-desc" />
			<p class="description" id="fnr_time_limit_desc"><?php esc_html_e( '0 = untimed practice mode.', 'feuernursingreview' ) ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="fnr_pass_threshold">
				<?php esc_html_e( 'Passing Score (%)', 'feuernursingreview' ); ?>
			</label>
		</th>
		<td>
			<input type="number" name="fnr_pass_threshold" id="fnr_pass_threshold" min="0" max="100" step="0.1" value="<?php echo esc_attr( $pass_threshold ); ?>" class="small-text" />
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e('NGN MODE', 'feuernursingreview') ?></th>
		<td>
			<label for="fnr_ngn_mode">
				<input type="checkbox" name="fnr_ngn_mode" id="fnr_ngn_mode" value="1" <?php checked($ngn_mode); ?> />
				<?php esc_html_e('Enable Next Generation NCLEX item types and 3-rule scoring for this quiz.', 'feuernursingreview'); ?>
			</label>
		</td>
	</tr>
</table>
