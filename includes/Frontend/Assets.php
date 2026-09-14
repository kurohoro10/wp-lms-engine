<?php
/**
 * File: includes/Frontend/Assets.php
 *
 * Handles frontend JavaScript assets for the Feuer Nursing Review plugin.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\Frontend;

use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class Assets {
	/**
	 * Register frontend asset hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
	}

	/**
	 * Enqueue frontend JavaScript assets.
	 *
	 * @return void
	 */
	public static function enqueue() {
		if (!is_singular(LessonCPT::POST_TYPE)) {
			return;
		}

		self::enqueue_script('fnr-lesson-progress','lesson-progress.js');
		self::enqueue_script('fnr-bookmark-toggle','bookmark-toggle.js', ['fnr-lesson-progress']);

		wp_localize_script('fnr-lesson-progress', 'fnrLessonProgress', [
			'restUrl' => esc_url_raw(rest_url('fnr/v1/')),
			'nonce'   => wp_create_nonce('wp_rest'),
			'i18n' 	  => [
				'saving' 		=> __('Saving…', 'feuernursingreview'),
				'saved'  		=> __('Progress saved.', 'feuernursingreview'),
				'completed' 	=> __('Completed ✓', 'feuernursingreview'),
				'bookmarked' 	=> __('Bookmarked', 'feuernursingreview'),
				'bookmarkThis'  => __('Bookmark this lesson', 'feuernursingreview'),
				'error' 		=> __('Something went wrong — try again.', 'feuernursingreview'),
			],
		]);

		if ('video' === get_post_meta(get_the_ID(), 'media_type', true)) {
			self::enqueue_script('fnr-video-progress', 'video-progress.js', ['fnr-lesson-progress']);
		}
	}

	/**
	 * Enqueue a plugin Javascript file.
	 *
	 * @param string	$handle			Script handle.
	 * @param string	$filename		Javascript filename inside public/js/.
	 * @param string[]	$dependencies 	Script dependencies.
	 *
	 *
	 * @return void
	 */
	private static function enqueue_script($handle, $filename, $dependencies = []) {
		wp_enqueue_script(
			$handle,
			FNR_PLUGIN_URL . 'public/js/' . $filename,
			$dependencies,
			FNR_VERSION,
			true
		);
	}
}
