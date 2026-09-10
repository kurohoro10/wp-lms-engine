<?php
namespace Feuernursingreview\Core;

if (!defined('ABSPATH')) exit;

class Activator {
	public static function activate() {
		self::create_tables();
		self::register_roles();
		flush_rewrite_rules();
	}

	private static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// 1. User Progress Table (Lessons & Course Drip tracking)
		// Double space for primary key for all table since its a wp quirk for dbDelta()
		$table_progress = $wpdb->prefix . 'fnr_user_progress';
		$sql_progress = "CREATE TABLE $table_progress (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			course_id bigint(20) UNSIGNED NOT NULL,
			lesson_id bigint(20) UNSIGNED NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'locked',
			unlocked_at datetime NULL,
			completed_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_course_lesson (user_id, course_id, lesson_id),
			KEY user_course (user_id, course_id),
			KEY user_lesson (user_id, lesson_id)
		) $charset_collate;";

		// 2. Quiz Attempt Sessions Table
		$table_attempts = $wpdb->prefix . 'fnr_quiz_attempts';
		$sql_attempts = "CREATE TABLE $table_attempts (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			quiz_id bigint(20) UNSIGNED NOT NULL,
			score_earned decimal(5,2) DEFAULT '0.00' NOT NULL,
			total_possible decimal(5,2) DEFAULT '0.00' NOT NULL,
			percentage decimal(5,2) DEFAULT '0.00' NOT NULL,
			passing_status varchar(20) DEFAULT 'failed' NOT NULL,
			time_spent_seconds int(10) UNSIGNED DEFAULT 0 NOT NULL,
			started_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			completed_at datetime NULL,
			PRIMARY KEY  (id),
			KEY user_quiz (user_id, quiz_id)
		) $charset_collate;";

		//  3. Question Micro-Analytics Logs Table
		$table_logs = $wpdb->prefix . 'fnr_question_logs';
		$sql_logs = "CREATE TABLE $table_logs (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			attempt_id bigint(20) UNSIGNED NOT NULL,
			question_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			user_response_json longtext NULL,
			points_earned decimal(4,2) DEFAULT '0.00' NOT NULL,
			points_possible decimal(4,2) DEFAULT '0.00' NOT NULL,
			is_correct tinyint(1) DEFAULT 0 NOT NULL,
			rationale_viewed_seconds int(10) UNSIGNED DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY attempt_question (attempt_id, question_id),
			KEY user_question (user_id, question_id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql_progress);
		dbDelta($sql_attempts);
		dbDelta($sql_logs);
	}

	private static function register_roles() {
		Roles::register_roles();
	}
}
