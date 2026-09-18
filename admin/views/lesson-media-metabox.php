<?php
/**
 * admin/views/lesson-media-metabox.php
 *
 * @var string $media_type  none|video|pdf|audio|slides
 * @var string $video_url
 * @var int    $pdf_id
 * @var int    $audio_id
 * @var string $transcript
 * @var int[]  $slide_ids
 * @var string $nonce_action
 * @var string $nonce_field
 *
 * The inline <script> that used to live at the bottom of this file has
 * been removed - it called document.queryselectorAll (wrong case) inside
 * a malformed IIFE, so it threw on load and the type switcher never
 * worked. That behaviour now lives in admin/js/lesson-media.js.
 */

if (!defined('ABSPATH')) exit;

$pdf_title   = $pdf_id ? get_the_title($pdf_id) : '';
$audio_title = $audio_id ? get_the_title($audio_id) : '';
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>

<div class="fnr-media-box" data-active-type="<?php echo esc_attr($media_type); ?>">

	<p>
		<label for="fnr_media_type">
			<strong><?php esc_html_e('Media Type', 'feuernursingreview'); ?></strong>
		</label>
		<br>
		<select name="fnr_media_type" id="fnr_media_type">
			<option value="none" <?php selected($media_type, 'none'); ?>>
				<?php esc_html_e('None (text only)', 'feuernursingreview'); ?>
			</option>
			<option value="video" <?php selected($media_type, 'video'); ?>>
				<?php esc_html_e('Video (YouTube/Vimeo)', 'feuernursingreview'); ?>
			</option>
			<option value="pdf" <?php selected($media_type, 'pdf'); ?>>
				<?php esc_html_e('PDF / Study Guide', 'feuernursingreview'); ?>
			</option>
			<option value="audio" <?php selected($media_type, 'audio'); ?>>
				<?php esc_html_e('Audio', 'feuernursingreview'); ?>
			</option>
			<option value="slides" <?php selected($media_type, 'slides'); ?>>
				<?php esc_html_e('Slide Deck', 'feuernursingreview'); ?>
			</option>
		</select>
	</p>

	<div class="fnr-media-field" data-media-type="video">
		<p>
			<label for="fnr_video_url"><?php esc_html_e('Video URL', 'feuernursingreview'); ?></label>
			<br>
			<input type="url" name="fnr_video_url" id="fnr_video_url" class="widefat"
				value="<?php echo esc_attr($video_url); ?>"
				placeholder="https://www.youtube.com/watch?v=… or https://vimeo.com/…" />
		</p>
	</div>

	<div class="fnr-media-field" data-media-type="pdf">
		<p>
			<strong><?php esc_html_e('PDF / Study Guide', 'feuernursingreview'); ?></strong>
			<br>
			<input type="hidden" name="fnr_pdf_attachment_id" id="fnr_pdf_attachment_id"
				value="<?php echo esc_attr($pdf_id); ?>" />

			<span id="fnr_pdf_title"><?php echo esc_html($pdf_title ?: __('No file selected.', 'feuernursingreview')); ?></span>

			<button type="button" class="button" id="fnr_pdf_select">
				<?php esc_html_e('Select PDF', 'feuernursingreview'); ?>
			</button>

			<button type="button" class="button" id="fnr_pdf_remove" <?php echo $pdf_id ? '' : 'style="display:none;"'; ?>>
				<?php esc_html_e('Remove', 'feuernursingreview'); ?>
			</button>
		</p>
	</div>

	<div class="fnr-media-field" data-media-type="audio">
		<p>
			<strong><?php esc_html_e('Audio File', 'feuernursingreview'); ?></strong>
			<br>
			<input type="hidden" name="fnr_audio_attachment_id" id="fnr_audio_attachment_id"
				value="<?php echo esc_attr($audio_id); ?>" />

			<span id="fnr_audio_title"><?php echo esc_html($audio_title ?: __('No file selected.', 'feuernursingreview')); ?></span>

			<button type="button" class="button" id="fnr_audio_select">
				<?php esc_html_e('Select Audio', 'feuernursingreview'); ?>
			</button>

			<button type="button" class="button" id="fnr_audio_remove" <?php echo $audio_id ? '' : 'style="display:none;"'; ?>>
				<?php esc_html_e('Remove', 'feuernursingreview'); ?>
			</button>
		</p>
		<p>
			<label for="fnr_audio_transcript"><?php esc_html_e('Transcript (required for accessibility)', 'feuernursingreview'); ?></label>
			<br>
			<?php /* No newline or indentation inside the textarea - it would be saved as leading whitespace. */ ?>
			<textarea name="fnr_audio_transcript" id="fnr_audio_transcript" rows="6" class="widefat"><?php echo esc_textarea($transcript); ?></textarea>
		</p>
	</div>

	<div class="fnr-media-field" data-media-type="slides">
		<p>
			<strong><?php esc_html_e('Slide Deck', 'feuernursingreview'); ?></strong>
		</p>

		<input type="hidden" name="fnr_slide_ids" id="fnr_slide_ids"
			value="<?php echo esc_attr(implode(',', $slide_ids)); ?>" />

		<ul id="fnr_slide_list" class="fnr-slide-admin-list">
			<?php foreach ($slide_ids as $i => $slide_id) : ?>
				<li class="fnr-slide-admin-item" data-attachment-id="<?php echo esc_attr($slide_id); ?>">
					<span class="fnr-slide-admin-number"><?php echo esc_html($i + 1); ?></span>

					<?php echo wp_get_attachment_image($slide_id, [96, 96], false, ['class' => 'fnr-slide-admin-thumb']); ?>

					<span class="fnr-slide-admin-title"><?php echo esc_html(get_the_title($slide_id)); ?></span>

					<span class="fnr-slide-admin-controls">
						<button type="button" class="button fnr-slide-up" <?php disabled($i === 0); ?>
							aria-label="<?php esc_attr_e('Move slide earlier', 'feuernursingreview'); ?>">&uarr;</button>

						<button type="button" class="button fnr-slide-down" <?php disabled($i === count($slide_ids) - 1); ?>
							aria-label="<?php esc_attr_e('Move slide later', 'feuernursingreview'); ?>">&darr;</button>

						<button type="button" class="button fnr-slide-remove"
							aria-label="<?php esc_attr_e('Remove slide', 'feuernursingreview'); ?>">&times;</button>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>

		<p id="fnr_slide_empty" <?php echo $slide_ids ? 'style="display:none;"' : ''; ?> class="description">
			<?php esc_html_e('No slides yet.', 'feuernursingreview'); ?>
		</p>

		<p>
			<button type="button" class="button" id="fnr_slide_add">
				<?php esc_html_e('Add Slides', 'feuernursingreview'); ?>
			</button>

			<button type="button" class="button" id="fnr_slide_clear" <?php echo $slide_ids ? '' : 'style="display:none;"'; ?>>
				<?php esc_html_e('Remove All', 'feuernursingreview'); ?>
			</button>
		</p>

		<p class="description">
			<?php esc_html_e('Images only — export your deck to PNG or JPG, one file per slide. Selection order in the media library becomes slide order; reorder with the arrows. Use each image\'s Caption field for speaker notes shown beneath the slide.', 'feuernursingreview'); ?>
		</p>
	</div>

</div>
