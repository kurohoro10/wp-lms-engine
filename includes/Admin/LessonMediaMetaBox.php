<?php
/**
 * includes/Admin/LessonMediaMetaBox.php
 *
 * Media attached to a lesson: video URL, PDF, audio + transcript, or an
 * ordered slide deck.
 *
 * Three field-name typos used to live here and in the view - the audio
 * ID was read as 'fnr_audio_attachnebt_id' but posted as
 * 'fnr_audio_attachement_id', and the PDF had no hidden input at all, so
 * neither ever saved. Both are fixed; the names below are the canonical
 * ones and must match admin/views/lesson-media-metabox.php exactly.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\Frontend\MediaPlayer;

if (!defined('ABSPATH')) exit;

class LessonMediaMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_lesson_media';
	const NONCE_FIELD  = 'fnr_lesson_media_nonce';

	protected static function id(): string { return 'fnr_lesson_media'; }
	protected static function title(): string { return __('Lesson Media', 'feuernursingreview'); }
	protected static function post_type(): string { return LessonCPT::POST_TYPE; }
	protected static function context(): string { return 'normal'; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		static::render_template('lesson-media-metabox', [
			'media_type'   => get_post_meta($post->ID, 'media_type', true) ?: 'none',
			'video_url'    => get_post_meta($post->ID, 'video_url', true),
			'pdf_id'       => (int) get_post_meta($post->ID, 'pdf_attachment_id', true),
			'audio_id'     => (int) get_post_meta($post->ID, 'audio_attachment_id', true),
			'transcript'   => get_post_meta($post->ID, 'audio_transcript', true),
			'slide_ids'    => MediaPlayer::get_slide_ids($post->ID),
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$media_type = in_array($_POST['fnr_media_type'] ?? '', ['none', 'video', 'pdf', 'audio', 'slides'], true)
			? sanitize_key($_POST['fnr_media_type'])
			: 'none';

		update_post_meta($post_id, 'media_type', $media_type);

		update_post_meta($post_id, 'video_url', esc_url_raw($_POST['fnr_video_url'] ?? ''));
		update_post_meta($post_id, 'pdf_attachment_id', absint($_POST['fnr_pdf_attachment_id'] ?? 0));
		update_post_meta($post_id, 'audio_attachment_id', absint($_POST['fnr_audio_attachment_id'] ?? 0));
		update_post_meta($post_id, 'audio_transcript', wp_kses_post($_POST['fnr_audio_transcript'] ?? ''));
		update_post_meta($post_id, 'slide_ids', self::sanitize_slide_ids($_POST['fnr_slide_ids'] ?? ''));
	}

	/**
	 * The slide field posts a comma-separated ID list written by
	 * admin/js/lesson-media.js. Stored as a real array so REST consumers
	 * get a list rather than a string they have to re-split.
	 *
	 * @return int[]
	 */
	private static function sanitize_slide_ids($raw): array {
		if (is_array($raw)) {
			$ids = $raw;
		} else {
			$ids = explode(',', (string) $raw);
		}

		$ids = array_values(array_unique(array_filter(array_map('absint', $ids))));

		// Drop anything that isn't actually an image in the library.
		return array_values(array_filter($ids, fn($id) => wp_attachment_is_image($id)));
	}
}
