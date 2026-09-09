<?php
namespace Feuernursingreview\Core;

if (!defined('ABSPATH')) exit;

class Deactivator {
	public static function deactivate() {
		flush_rewrite_rules();
		// Intentionally does NOT drop custom tables or remove roles -
		// that's uninstall.php's job (hard delete), not deactivation.
	}
}
