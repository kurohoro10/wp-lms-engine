<?php
/**
 * admin/views/enrollment-metabox.php
 *
 * @var \WP_Post $post
 * @var \WP_User[] $enrolled
 * @var array<int,string|null> $enrolled_at   user ID => enrolled_at timestamp
 * @var string $action
 * @var string $nonce_action
 * @var string $nonce_field
 */
if (!defined('ABSPATH')) exit;
?>
<div class="fnr-enrollment-box">
	<?php if ($enrolled) : ?>
		<ul class="fnr-enrolled-list">
			<?php foreach ($enrolled as $user) : ?>
				<li>
					<?php echo esc_html($user->display_name); ?>
					<span class="description">
						(<?php echo esc_html($enrolled_at[$user->ID] ?? ''); ?>)
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p><?php esc_html_e('No students enrolled yet.', 'feuernursingreview'); ?></p>
	<?php endif; ?>

	<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
		<input type="hidden" name="action" value="<?php echo esc_attr($action); ?>" />
		<input type="hidden" name="course_id" value="<?php echo esc_attr($post->ID); ?>" />
		<?php wp_nonce_field($nonce_action, $nonce_field); ?>

		<label for="fnr-enroll-user" class="screen-reader-text">
			<?php esc_html_e('Select a student to enroll', 'feuernursingreview'); ?>
		</label>
		<?php
		wp_dropdown_users([
			'id'                => 'fnr-enroll-user',
			'name'              => 'user_id',
			'role'              => 'fnr_student',
			'show_option_none'  => __('Select a student…', 'feuernursingreview'),
			'option_none_value' => '',
		]);
		?>

		<p>
			<button type="submit" class="button button-secondary">
				<?php esc_html_e('Enroll Student', 'feuernursingreview'); ?>
			</button>
		</p>
	</form>
</div>
