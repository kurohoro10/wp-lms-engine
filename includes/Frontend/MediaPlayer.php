<?php
/**
 * includes/Frontend/MediaPlayer.php
 *
 * Renders the media block for a lesson: video, PDF, audio, or a slide
 * deck.
 *
 * NOTE: this previously read the meta key 'meta_type' while everything
 * else in the plugin wrote 'media_type', so render() always fell through
 * to the default branch and no lesson ever showed its media. Fixed here.
 */
namespace Feuernursingreview\Frontend;

if (!defined('ABSPATH')) exit;

class MediaPlayer {
	public static function render(int $lesson_id): string {
		$type = get_post_meta($lesson_id, 'media_type', true) ?: 'none';

		switch ($type) {
			case 'video'  : return self::render_video($lesson_id);
			case 'pdf'    : return self::render_pdf($lesson_id);
			case 'audio'  : return self::render_audio($lesson_id);
			case 'slides' : return self::render_slides($lesson_id);
			default       : return '';
		}
	}

	/**
	 * Slide deck: an ordered list of image attachments, rendered as a
	 * single-slide-at-a-time viewer.
	 *
	 * Every slide is emitted into the DOM up front rather than fetched on
	 * demand. It keeps the markup static (no JSON blob, no REST round
	 * trip), it degrades to a plain vertical list of captioned images
	 * when JS is off, and the images past the first are lazy-loaded so a
	 * 40-slide deck doesn't cost 40 requests on page load.
	 */
	private static function render_slides(int $lesson_id): string {
		$slide_ids = self::get_slide_ids($lesson_id);

		if (!$slide_ids) {
			return '';
		}

		$total = count($slide_ids);
		$slides_html = '';

		foreach ($slide_ids as $index => $attachment_id) {
			$image = wp_get_attachment_image($attachment_id, 'large', false, [
				'class'   => 'fnr-slide__image',
				'loading' => $index === 0 ? 'eager' : 'lazy',
				'decoding' => 'async',
			]);

			if (!$image) {
				continue;
			}

			$caption = wp_get_attachment_caption($attachment_id);

			$slides_html .= sprintf(
				'<li class="fnr-slide" id="fnr-slide-%1$d" data-slide-index="%2$d" role="group" aria-roledescription="%3$s" aria-label="%4$s"%5$s>
					%6$s
					%7$s
				</li>',
				$index + 1,
				$index,
				esc_attr__('slide', 'feuernursingreview'),
				esc_attr(sprintf(
					// translators: 1: current slide number, 2: total slides
					__('Slide %1$d of %2$d', 'feuernursingreview'),
					$index + 1,
					$total
				)),
				$index === 0 ? '' : ' hidden',
				$image,
				$caption
					? '<figcaption class="fnr-slide__caption">' . wp_kses_post($caption) . '</figcaption>'
					: ''
			);
		}

		if (!$slides_html) {
			return '';
		}

		return sprintf(
			'<div class="fnr-slides" data-fnr-slides data-total="%1$d" data-lesson-id="%2$d">
				<div class="fnr-slides__stage">
					<ol class="fnr-slides__list">%3$s</ol>
				</div>

				<div class="fnr-slides__controls">
					<button type="button" class="button fnr-slides__prev" disabled>
						<span aria-hidden="true">&larr;</span> %4$s
					</button>

					<p class="fnr-slides__counter" role="status" aria-live="polite">
						<span class="fnr-slides__current">1</span> / <span class="fnr-slides__total">%1$d</span>
					</p>

					<button type="button" class="button fnr-slides__next">
						%5$s <span aria-hidden="true">&rarr;</span>
					</button>
				</div>

				<p class="fnr-slides__hint description">%6$s</p>
			</div>',
			$total,
			$lesson_id,
			$slides_html,
			esc_html__('Previous', 'feuernursingreview'),
			esc_html__('Next', 'feuernursingreview'),
			esc_html__('Use the arrow keys to move between slides.', 'feuernursingreview')
		);
	}

	/**
	 * Slide IDs, normalized and filtered down to attachments that still
	 * exist. An editor deleting an image from the media library shouldn't
	 * leave a blank slide in the deck.
	 *
	 * @return int[]
	 */
	public static function get_slide_ids(int $lesson_id): array {
		$raw = get_post_meta($lesson_id, 'slide_ids', true);

		if (is_string($raw)) {
			$raw = array_filter(explode(',', $raw));
		}

		if (!is_array($raw)) {
			return [];
		}

		$ids = array_values(array_unique(array_filter(array_map('absint', $raw))));

		return array_values(array_filter($ids, fn($id) => wp_attachment_is_image($id)));
	}

	private static function render_video(int $lesson_id): string {
		$url = get_post_meta($lesson_id, 'video_url', true);
		if (!$url) return '';

		$embed = self::to_embed_url($url);
		if (!$embed) {
			return '<p class="fnr-media-error">' . esc_html__('Unable to load video.', 'feuernursingreview') . '</p>';
		}

		return sprintf(
			'<div class="fnr-video-wrapper" data-fnr-video-provider="%s">
				<iframe
					id="fnr-video-player"
					src="%s"
					title="%s"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowfullscreen
					loading="lazy"
				></iframe>
			</div>',
			esc_attr($embed['provider']),
			esc_url($embed['src']),
			esc_attr(get_the_title($lesson_id))
		);
	}

	private static function render_pdf(int $lesson_id): string {
		$attachment_id = (int) get_post_meta($lesson_id, 'pdf_attachment_id', true);
		if (!$attachment_id) return '';

		$url = wp_get_attachment_url($attachment_id);
		if (!$url) return '';

		return sprintf(
			'<div class="fnr-pdf-wrapper">
				<embed src="%1$s" type="application/pdf" width="100%%" height="600" aria-label="%2$s" />
				<p class="fnr-pdf-fallback">
					<a href="%1$s" target="_blank" rel="noopener noreferrer">%3$s</a>
				</p>
			</div>',
			esc_url($url),
			esc_attr(get_the_title($attachment_id)),
			esc_html__('Open PDF in a new tab', 'feuernursingreview')
		);
	}

	private static function render_audio(int $lesson_id): string {
		$attachment_id = (int) get_post_meta($lesson_id, 'audio_attachment_id', true);
		if (!$attachment_id) return '';

		$url = wp_get_attachment_url($attachment_id);
		if (!$url) return '';

		$transcript = get_post_meta($lesson_id, 'audio_transcript', true);

		$html = sprintf(
			'<div class="fnr-audio-wrapper">
				<audio controls preload="metadata" style="width:100%%;">
					<source src="%s" />
					%s
				</audio>',
			esc_url($url),
			esc_html__('Your browser does not support the audio element.', 'feuernursingreview')
		);

		if ($transcript) {
			$html .= sprintf(
				'<details class="fnr-audio-transcript">
					<summary>%s</summary>
					<div>%s</div>
				</details>',
				esc_html__('Show transcript', 'feuernursingreview'),
				wp_kses_post(wpautop($transcript))
			);
		} else {
			$html .= sprintf(
				'<p class="fnr-media-warning">%s</p>',
				esc_html__('No transcript provided for this audio.', 'feuernursingreview')
			);
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Converts a YouTube/Vimeo watch URL into an embeddable player URL.
	 * Returns null for unrecognized URLs rather than guessing.
	 */
	private static function to_embed_url(string $url): ?array {
		if (preg_match('~youtu(?:\.be/|be\.com/watch\?v=)([\w-]+)~', $url, $m)) {
			return [
				'provider' => 'youtube',
				'src'      => 'https://www.youtube.com/embed/' . $m[1] . '?enablejsapi=1',
			];
		}

		if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
			return [
				'provider' => 'vimeo',
				'src'      => 'https://player.vimeo.com/video/' . $m[1] . '?api=1',
			];
		}

		return null;
	}
}
