<?php
/**
 * includes/Admin/QuestionSchemaMetaBox.php
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\QuestionCPT;

if (!defined('ABSPATH')) exit;

class QuestionSchemaMetaBox extends AbstractSaveableMetaBox {
	const NONCE_ACTION = 'fnr_save_question_schema';
	const NONCE_FIELD  = 'fnr_question_schema_nonce';

	protected static function id(): string { return 'fnr_question_schema'; }
	protected static function title(): string { return __('Question Schema (JSON)', 'feuernursingreview'); }
	protected static function post_type(): string { return QuestionCPT::POST_TYPE; }
	protected static function context(): string { return 'normal'; }
	protected static function nonce_action(): string { return self::NONCE_ACTION; }
	protected static function nonce_field(): string { return self::NONCE_FIELD; }

	public static function render(\WP_Post $post) {
		$raw = get_post_meta($post->ID, '_fnr_question_schema', true);
		$pretty = '';

		if ($raw) {
			$decoded = json_decode($raw, true);
			/**
			 * Pretty-print if valid; otherwise show the raw text as-is so
			 * the user can see and fix whatever's broker, rather than losing it.
			 */
			$pretty = (json_last_error() === JSON_ERROR_NONE)
				? wp_json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
				: $raw;
		}

		$error_key = 'fnr_question_schema_error_' . $post->ID . '_' . get_current_user_id();
		$error = get_transient($error_key);
		delete_transient($error_key);

		static::render_template('question-schema-metabox', [
			'schema_json'  => $pretty,
			'error' 	   => $error,
			'nonce_action' => self::NONCE_ACTION,
			'nonce_field'  => self::NONCE_FIELD,
		]);
	}

	protected static function save_fields(int $post_id): void {
		if (!isset($_POST['fnr_question_schema'])) {
			return;
		}

		$raw = wp_unslash( $_POST['fnr_question_schema']);

		if (trim($raw) === '') {
			update_post_meta($post_id, '_fnr_question_schema', '');
			return;
		}

		$decoded = json_decode($raw, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			/**
			 * Don't save invalid JSON over a previously valid schema -
			 * leave the last-good version in place and surface the error.
			 */
			set_transient(
				'fnr_question_schema_error_' . $post_id . '_' . get_current_user_id(),
				sprintf(__('Schema not saved - invalid JSON: %s', 'feuernursingreview')),
				30
			);
			return;
		}

		update_post_meta($post_id, '_fnr_question_schema', wp_json_encode($decoded));
	}
}
