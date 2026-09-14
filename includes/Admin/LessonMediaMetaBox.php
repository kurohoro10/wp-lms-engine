<?php
/**
 * includes/Admin/LessonMediaMetaBox.php
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class LessonMediaMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnd_save_lesson_media';
	const NONCE_FIELD = 'fnr_lesson_media_nonce';

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
			'pdf_id'	   => (int) get_post_meta($post->ID, 'pdf_attachment_id', true),
			'audio_id' 	   => (int) get_post_meta($post->ID, 'audio_attachment_id', true),
			'transcript'   => get_post_meta($post->ID, 'audio_transcript', true),
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$media_type = in_array($_POST['fnr_media_type'] ?? '', ['none', 'video', 'pdf', 'audio'], true)
		? $_POST['fnr_media_type']
		: 'none';
		update_post_meta($post_id, 'media_type', $media_type);

		update_post_meta($post_id, 'video_url', esc_url_raw($_POST['fnr_video_url'] ?? ''));
		update_post_meta($post_id, 'pdf_attachment_id', absint($_POST['fnr_pdf_attachment_id'] ?? 0));
		update_post_meta($post_id, 'audio_attachment_id', absint($_POST['fnr_audio_attachnebt_id'] ?? 0));
		update_post_meta($post_id, 'audio_transcript', wp_kses_post($_POST['fnr_audio_transcript'] ?? ''));
	}
}
