<?php
/**
 * includes/Admin/AbstractSaveableMetaBox.php
 */
namespace Feuernursingreview\Admin;

if (!defined('ABSPATH')) exit;

abstract class AbstractSaveableMetaBox extends AbstractMetaBox {
	abstract protected static function nonce_action(): string;
	abstract protected static function nonce_field(): string;
	abstract protected static function save_fields(int $post_id): void;

	public static function register() {
		parent::register();
		add_action('save_post_' . static::post_type(), [static::class, 'save']);
	}

	public static function save(int $post_id) {
		if (
			!isset($_POST[static::nonce_field()]) ||
			!wp_verify_nonce($_POST[static::nonce_field()], static::nonce_action())
		) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		static::save_fields($post_id);
	}
}
