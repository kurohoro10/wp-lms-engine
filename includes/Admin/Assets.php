<?php
/**
 * File: includes/Admin/Assets.php
 *
 * All wp-admin CSS/JS enqueues for the plugin. Metabox classes render
 * and save fields; asset loading lives here.
 *
 * @package Feuernursingreview
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\CourseCPT;
use Feuernursingreview\CPT\LessonCPT;
use Feuernursingreview\CPT\ModuleCPT;

if (!defined('ABSPATH')) exit;

class Assets {
	public static function register() {
		add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
	}

	/**
	 * @param string $hook Current admin page hook, e.g. 'post.php'.
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

	private static function enqueue_course_screen() {
		global $post;

		self::enqueue_style('fnr-enrollment-admin', 'enrollment-metabox.css');
		self::enqueue_script('fnr-enrollment-admin', 'enrollment-metabox.js');

		wp_enqueue_script(
			'sortablejs',
			'https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js',
			[],
			'1.15.2',
			true
		);
		self::enqueue_script('fnr-course-lesson-order', 'course-lesson-order.js', ['sortablejs']);

		wp_localize_script('fnr-course-lesson-order', 'fnrLessonOrder', [
			'ajaxUrl'  => admin_url('admin-ajax.php'),
			'action'   => CourseLessonOrderMetaBox::AJAX_ACTION,
			'nonce'    => wp_create_nonce(CourseLessonOrderMetaBox::NONCE_ACTION),
			'courseId' => $post ? $post->ID : 0,
			'i18n'     => [
				'saving' => __('Saving order…', 'feuernursingreview'),
				'saved'  => __('Order saved.', 'feuernursingreview'),
				'error'  => __('Could not save order — try again.', 'feuernursingreview'),
			],
		]);
	}

	/**
	 * Lesson screen: media library uploader for the PDF/audio/slide
	 * pickers, plus the conditional-field logic shared by the Lesson
	 * Media and Unlock Rule boxes.
	 */
	private static function enqueue_lesson_screen() {
		wp_enqueue_media();

		self::enqueue_style('fnr-lesson-media-admin', 'lesson-media.css');
		self::enqueue_script('fnr-lesson-media-admin', 'lesson-media.js');

		wp_localize_script('fnr-lesson-media-admin', 'fnrLessonMedia', [
			'i18n' => [
				'noFile'       => __('No file selected.', 'feuernursingreview'),
				'selectPdf'    => __('Select PDF', 'feuernursingreview'),
				'usePdf'       => __('Use this PDF', 'feuernursingreview'),
				'selectAudio'  => __('Select Audio', 'feuernursingreview'),
				'useAudio'     => __('Use this audio', 'feuernursingreview'),
				'selectSlides' => __('Select slide images', 'feuernursingreview'),
				'useSlides'    => __('Add to deck', 'feuernursingreview'),
				'confirmClear' => __('Remove all slides from this lesson?', 'feuernursingreview'),
			],
		]);
	}

	private static function enqueue_script($handle, $filename, $dependencies = []) {
		wp_enqueue_script(
			$handle,
			FNR_PLUGIN_URL . 'admin/js/' . $filename,
			$dependencies,
			FNR_VERSION,
			true
		);
	}

	private static function enqueue_style($handle, $filename, $dependencies = []) {
		wp_enqueue_style(
			$handle,
			FNR_PLUGIN_URL . 'admin/css/' . $filename,
			$dependencies,
			FNR_VERSION
		);
	}
}
