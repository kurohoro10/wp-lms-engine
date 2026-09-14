<?php
/**
 * File: includes/Admin/Assets.php
 *
 * Handles wp-admin CSS/JS assets for the Feuer Nursing Review plugin.
 * Mirrors Frontend/Assets.php: metabox classes stay focused on
 * rendering fields and saving them, every wp_enqueue_script/style()
 * call for the admin side lives here so admin and frontend asset
 * loading stay cleanly separated.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\CourseCPT;
use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class Assets {
	/**
	 * Register admin asset hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
	}

	/**
	 * Enqueue admin JS/CSS for the current screen.
	 *
	 * @param string $hook Current admin page hook, e.g. 'post.php'.
	 * @return void
	 */
	public static function enqueue(string $hook) {
		if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
			return;
		}

		global $post_type;

		if ($post_type === CourseCPT::POST_TYPE) {
			self::enqueue_course_screen();
		}

		if ($post_type === LessonCPT::POST_TYPE) {
			self::enqueue_lesson_screen();
		}
	}

	/**
	 * Assets for the fnr_course edit screen: the Enrolled Students
	 * dropdown (EnrollmentMetaBox) and the Lesson Order drag-and-drop
	 * list (CourseLessonOrderMetaBox).
	 *
	 * @return void
	 */
	private static function enqueue_course_screen() {
		global $post;

		self::enqueue_style('fnr-enrollment-admin', 'enrollment-metabox.css');
		self::enqueue_script('fnr-enrollment-admin', 'enrollment-metabox.js');

		/**
		 * SortableJS via CDN - small, no build step, matches the
		 * vanilla-JS-first approach used for the frontend so far.
		 */
		wp_enqueue_script(
			'sortablejs',
			'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js',
			[],
			'1.15.2',
			true
		);
		self::enqueue_script('fnr-course-lesson-order', 'course-lesson-order.js', ['sortablejs']);

		wp_localize_script('fnr-course-lesson-order', 'fnrLessonOrder', [
			'ajaxUrl'   => admin_url('admin-ajax.php'),
			'action'    => CourseLessonOrderMetaBox::AJAX_ACTION,
			'nonce'     => wp_create_nonce(CourseLessonOrderMetaBox::NONCE_ACTION),
			'courseId'  => $post ? $post->ID : 0,
			'i18n'      => [
				'saving' => __('Saving order…', 'feuernursingreview'),
				'saved'  => __('Order saved.', 'feuernursingreview'),
				'error'  => __('Could not save order - try again.', 'feuernursingreview'),
			],
		]);
	}

	/**
	 * Assets for the fnr_lesson edit screen: the media library uploader
	 * used by LessonMediaMetaBox.
	 *
	 * @return void
	 */
	private static function enqueue_lesson_screen() {
		wp_enqueue_media();
		self::enqueue_script('fnr-lesson-media-admin', 'lesson-media.js', ['jquery']);
	}

	/**
	 * Enqueue a plugin admin Javascript file.
	 *
	 * @param string   $handle       Script handle.
	 * @param string   $filename     Javascript filename inside admin/js/.
	 * @param string[] $dependencies Script dependencies.
	 * @return void
	 */
	private static function enqueue_script($handle, $filename, $dependencies = []) {
		wp_enqueue_script(
			$handle,
			FNR_PLUGIN_URL . 'admin/js/' . $filename,
			$dependencies,
			FNR_VERSION,
			true
		);
	}

	/**
	 * Enqueue a plugin admin stylesheet.
	 *
	 * @param string   $handle       Style handle.
	 * @param string   $filename     CSS filename inside admin/css/.
	 * @param string[] $dependencies Style dependencies.
	 * @return void
	 */
	private static function enqueue_style($handle, $filename, $dependencies = []) {
		wp_enqueue_style(
			$handle,
			FNR_PLUGIN_URL . 'admin/css/' . $filename,
			$dependencies,
			FNR_VERSION
		);
	}
}
