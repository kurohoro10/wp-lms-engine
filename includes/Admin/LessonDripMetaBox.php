<?php
/**
 * includes/Admin/LessonDripMetaBox.php
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class LessonDripMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_lesson_drip';
	const NONCE_FIELD  = 'fnr_lesson_drip_nonce';

	protected static function id(): string { return 'fnr_lesson_drip'; }
	protected static function title(): string { return __('Drip Schedule', 'feuernursingreview'); }
	protected static function post_type(): string { return LessonCPT::POST_TYPE; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render (\WP_Post $post) {
		static::render_template('lesson-drip-metabox', [
			'drip_days'    => (int) get_post_meta($post->ID, 'drip_days', true),
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$drip_days = isset($_POST['fnr_drip_days']) ? absint($_POST['fnr_drip_days']) : 0;
		update_post_meta($post_id, 'drip_days', $drip_days);
	}
}
