<?php
/**
 * includes/Admin/AbstractMetaBox.php
 */
namespace Feuernursingreview\Admin;

if (!defined('ABSPATH')) exit;

abstract class AbstractMetaBox {
	abstract protected static function id(): string;
	abstract protected static function title(): string;
	abstract protected static function post_type(): string;
	abstract public static function render(\WP_Post $post);

	/**
	 * Defines where the meta box should appear.
	 *
	 * Defaults to the WordPress sidebar.
	 */
	protected static function context(): string {
		return 'side';
	}

	public static function register() {
		add_action('add_meta_boxes', [static::class, 'add_meta_box']);
	}

	public static function add_meta_box() {
		add_meta_box(
			static::id(),
			static::title(),
			[static::class, 'render'],
			static::post_type(),
			static::context(),
			'default'
		);
	}

	protected static function render_template(string $template, array $args = []): void {
		TemplateLoader::render($template, $args);
	}
}
