<?php
/**
 * public/templates/dashboard-logged-out.php
 *
 * [fnr_dashboard] shortcode output shown when a visitor who isn't
 * logged in views a page containing the dashboard. Mirrors the
 * login-notice pattern used in single-fnr_course.php.
 */

if (!defined('ABSPATH')) exit;
?>

<div id="fnr-dashboard" class="fnr-dashboard">
	<p class="fnr-login-notice">
		<?php
			printf(
				// translators: %s: login link
				esc_html__('Please %s to view your dashboard.', 'feuernursingreview'),
				'<a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('log in', 'feuernursingreview') . '</a>'
			);
		?>
	</p>
</div>
