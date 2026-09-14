<?php
/**
 * admin/vies/lesson-drip-metabox.php
 *
 * @var int $drip_days
 * @var string $nonce_action
 * @var string $nonce_field
 */

if (!defined('ABSPATH')) exit;
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>
<p>
	<label for="fnr_drip_days">
		<?php esc_html_e('Unlock this lesson', 'feuernursingreview'); ?>
		<input type="number" min="0" step="1" name="fnr_drip_days" id="fnr_drip_days" class="small-text" value="<?php echo esc_attr( $drip_days ); ?>" aria-describedby="fnr_drip_days_desc" />
		<?php esc_html_e('day(s) after course enrollment.', 'feuernursingreview'); ?>
	</label>
</p>
<p class="description" id="fnr_drip_days_desc">
	<?php esc_html_e('0 = available immediately on enrollment.', 'feuernursingreview'); ?>
</p>
