<?php
/**
 * includes/Admin/TemplateLoader.php
 *
 * Loads a plain-PHP template file from admin/views/, with variables
 * extracted into scope for it to use directly. Keeps HTML out of
 * class files entirely.
 */
namespace Feuernursingreview\Core;

if (!defined('ABSPATH')) exit;

class TemplateLoader {
	public static function render(string $template, array $args = [], string $base_dir = 'admin/views/'): void {
		$path = FNR_PLUGIN_DIR . $base_dir . $template . '.php';

		if (!file_exists($path)) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				printf(
					'<p>%s</p>',
					esc_html(sprintf('Missing admin template: %s', $template))
				);
			}
			return;
		}

		extract($args, EXTR_SKIP);
		include $path;
	}
}
