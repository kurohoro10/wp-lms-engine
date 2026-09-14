<?php
/**
 * includes/Admin/QuizSettingsMetaBox.php
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\QuizCPT;

if (!defined('ABSPATH')) exit;

class QuizSettingsMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_quiz_settings';
	const NONCE_FIELD  = 'fnr_quiz_settings_nonce';

	protected static function id(): string { return 'fnr_quiz_settings'; }
	protected static function title(): string { return __('Quiz Settings', 'feuernursingreview'); }
	protected static function post_type(): string { return QuizCPT::POST_TYPE; }
	protected static function context(): string { return 'normal'; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		$pass_threshold = get_post_meta($post->ID, 'pass_threshold', true);

		static::render_template('quiz-settings-metabox', [
			'time_limit' 	 =>  (int) get_post_meta($post->ID, 'time_limit_minutes', true),
			'pass_threshold' => $pass_threshold !== '' ? (float) $pass_threshold : 75.0,
			'ngn_mode' 		 => (bool) get_post_meta($post->ID, 'ngn_mode', true),
			'nonce_action' 	 => self::NONCE_ACTION,
			'nonce_field' 	 => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		$time_limit = isset($_POST['fnr_time_limit_minutes'])
			? absint($_POST['fnr_time_limit_minutes'])
			: 0;
		update_post_meta($post_id, 'time_limit_minutes', $time_limit);

		$pass_threshold = isset($_POST['fnr_pass_threshold'])
			? (float) $_POST['fnr_pass_threshold']
			: 75.0;
		$pass_threshold = max(0, min(100, $pass_threshold));
		update_post_meta($post_id, 'pass_threshold', $pass_threshold);

		update_post_meta($post_id, 'ngn_mode', isset($_POST['fnr_ngn_mode']) ? 1 : 0);
	}
}
