<?php
/**
 * includes/Frontend/MediaPlayer.php
 */
namespace Feuernursingreview\Frontend;

if (!defined('ABSPATH')) exit;

class MediaPlayer {
	public static function render(int $lesson_id): string {
		$type = get_post_meta($lesson_id, 'meta_type', true) ?: 'none';

		switch ($type) {
			case 'video' : return self::render_video($lesson_id);
			case 'pdf'   : return self::render_pdf($lesson_id);
			case 'audio' : return self::render_audio($lesson_id);
			default 	 : return '';
		}
	}

	private static function render_video(int $lesson_id): string {
		$url = get_post_meta($lesson_id, 'video_url', true);
		if (!$url) return '';

		$embed = self::to_embed_url($url);
		if (!$embed) {
			return '<p class="fnr-media-error">' . esc_html__('Unable to load video.', 'feuernursingreview') . '</p>';
		}

		return sprintf(
			'div class="fnr-video-wrapper" data-fnr-video-provider="%s">
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
		if (!$attachment_id)return '';

		$url = wp_get_attachment_url($attachment_id);
		if(!$url) return '';

		return sprintf(
			'<div class="fnr-pdf-wrapper">
				embed src="%1$s" type="application/pdf" width="100%%" height="600" aria-label="%2$s" />
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
				'src' 	   => 'https://www.youtube.com/embed/' . $m[1] . '?enablejsapi=1',
			];
		}

		if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
			return [
				'provider' => 'vimeo',
				'src' 	   => 'https://player.vimeo.com/video/' . $m[1] . '?api=1',
			];
		}

		return null;
	}
}
