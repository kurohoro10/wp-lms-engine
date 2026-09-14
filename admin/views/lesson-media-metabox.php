<?php
/**
 * admin/views/lesson-media-metabox.php
 *
 * @var string $media_type
 * @var string $video_url
 * @var int $pdf_id
 * @var int $audio_id
 * @var string $transcript
 * @var string $nonce_action
 * @var string $nonce_field
 */

if (!defined('ABSPATH')) exit;

$pdf_title   = $pdf_id ? get_the_title($pdf_id) : '';
$audio_title = $audio_id ? get_the_title($audio_id) : '';
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>

<p>
	<label for="fnr_media_type">
		<strong><?php esc_html_e('Media Type', 'feuernursingreview') ?></strong>
	</label>

	<br>

	<select name="fnr_media_type" id="fnr_media_type">
		<option value="none" <?php selected($media_type, 'none'); ?>>
			<?php esc_html_e('None (text only)', 'feuernursingreview'); ?>
		</option>

		<option value="video" <?php selected($media_type, 'video'); ?>>
			<?php esc_html_e('Video (Youtube/Vimeo)', 'feuernursingreview'); ?>
		</option>

		<option value="pdf" <?php selected($media_type, 'pdf'); ?>>
			<?php esc_html_e('PDF / Study Guide', 'feuernursingreview'); ?>
		</option>

		<option value="audio" <?php selected($media_type, 'audio'); ?>>
			<?php esc_html_e('Audio', 'feuernursingreview'); ?>
		</option>
	</select>
</p>

<div class="fnr-media-field fnr-media-field--video" data-media-type="video">
	<p>
		<label for="fnr_video_url"><?php esc_html_e('Video URL', 'feuernursingreview') ?></label>

		<br>

		<input type="url" name="fnr_video_url" id="fnr_video_url" class="widefat"
			value="<?php echo esc_attr($video_url); ?>"
			placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..." />
	</p>
</div>

<div class="fnr-media-field fnr-media-field--pdf" data-media-type="pdf">
	<p>
		<label><?php esc_html_e('PDF / Study Guide', 'feuernursingreview'); ?></label>

		<br>

		<span id="fnr_pdf_title"><?php echo esc_html($pdf_title ?: __('No file selected.', 'feuernursingreview')); ?></span>

		<button type="button" class="button" id="fnr_pdf_select">
			<?php esc_html_e('Select PDF', 'feuernursingreview'); ?>
		</button>

		<button type="button" class="button" id="fnr_pdf_remove" <?php echo $pdf_id ? '' : 'style="display:none;"' ?>>
			<?php esc_html_e('Remove', 'feuernursingreview'); ?>
		</button>
	</p>
</div>

<div class="fnr-media-field fnr-media-field--audio" data-media-type="audio">
	<p>
		<label><?php esc_html_e('Audio File', 'feuernursingreview'); ?></label>

		<br>

		<input type="hidden" name="fnr_audio_attachement_id" id="fnr_audio_attachment_id" value="<?php echo esc_attr($audio_id); ?>" />

		<span id="fnr_audio_title"><?php echo esc_html($audio_title ?: __('No file selected.', 'feuernursingreview')) ?></span>

		<button type="button" class="button" id="fnr-audio-select">
			<?php esc_html_e('Select Audio', 'feuernursingreview'); ?>
		</button>

		<button type="button" class="button" id="fnr_audio_remove" <?php echo $audio_id ? '' : 'style="display:none;"' ?>>
			<?php esc_html_e('Remove', 'feuernursingreview'); ?>
		</button>
	</p>
	<p>
		<label for="fnr_audio_transcript"><?php esc_html_e('Transcript (required for accessibility)', 'feuernursingreview'); ?></label>

		<br>

		<textarea name="fnr_audio_transcript" id="fnr_audio_transcript" rows="6" class="widefat">
			<?php echo esc_textarea( $transcript ); ?>
		</textarea>
	</p>
</div>

<script>
	document.getElementById('fnr_media_type').addEventListener('change', function () {
		document.queryselectorAll('.fnr-media-field').forEach(function (el) {
			el.style.display = el.dataset.mediaType === this.value ? 'block' : 'none';
		}.bind(this));
	}.bind(document.getElementById('fnr_media_type'))());
</script>
