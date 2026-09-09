<?php
namespace Feuernursingreview;

if (!defined('ABSPATH')) exit;

class Autoloader {
	public static function register() {
		spl_autoload_register([__CLASS__, 'autoload']);
	}

	public static function autoload($class) {
		$prefix = __NAMESPACE__ . '\\'; // "Feuernursingreview\"
		$len = strlen($prefix);

		if (strncmp($prefix, $class, $len) !== 0) {
			return; // not our namespace, let another autoloader handle it
		}

		$relative_class = substr($class, $len);
		$file = __DIR__ . '/' . str_replace('\\', '/', $relative_class) . '.php';

		if (file_exists($file)) {
			require $file;
		}
	}
}
