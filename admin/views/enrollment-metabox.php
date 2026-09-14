<?php
/**
 * admin/views/enrollment-metabox.php
 *
 * @var \WP_User[] 				$students		all fnr_student users, orderby display_name
 * @var int[] 					$enrolled_ids	user IDs currently enrolled in this course
 * @var array<int, string|null>	$enrolled_at	user ID => enrolled_at timestamp
 * @var string 					$nonce_action
 * @var string					$nonce_field
 */
if (!defined('ABSPATH')) exit;

$enrolled_count = count($enrolled_ids);
?>

<div class="fnr-enrollment-box">
	<?php wp_nonce_field($nonce_action, $nonce_field); ?>

	<div class="fnr-enroll-dropdown">
		<button
			type="button"
			class="button fnr-enroll-dropdown__toggle"
			aria-expanded="false"
			aria-controls="fnr-enroll-dropdown-panel"
		>
			<span class="fnr-enroll-dropdown__label">

				<?php
					echo esc_html(
						$enrolled_count
							? sprintf(
								// translators: %d: number of enrolled students
								_n('%d student enrolled', '%d students enrolled', $enrolled_count, 'feuernursingreview'),
								$enrolled_count
							)
							: __('Select students…', 'feuernursingreview')
					);
				?>

			</span>
			<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
		</button>

		<div id="fnr-enroll-dropdown-panel" class="fnr-enroll-dropdown__panel" hidden>

			<?php if (!empty($students)) : ?>

				<input
					type="search"
					class="fnr-enroll-dropdown__search"
					placeholder="<?php esc_attr_e('Search students…', 'feuernursingreview', 'feuernursingreview'); ?>"
				/>

				<ul class="fnr-enroll-dropdown__list">

					<?php foreach($students as $student) :
						$checked = in_array($student->ID, $enrolled_ids, true);
					?>

						<li class="fnr-enroll-dropdown__item">
							<label>
								<input
									type="checkbox"
									name="fnr_enrolled_users[]"
									value="<?php echo esc_attr($student->ID); ?>"
									<?php checked($checked); ?>
								/>
								<span class="fnr-enroll-dropdown__name"><?php echo esc_html($student->display_name); ?></span>

								<?php if ($checked && !empty($enrolled_at[$student->ID])) : ?>
									<span class="description">
										(<?php echo esc_html($enrolled_at[$student->ID]); ?>)
									</span>
								<?php endif; ?>

							</label>
						</li>

					<?php endforeach; ?>

				</ul>

			<?php else : ?>

				<p class="fnr-enroll-dropdown__empty">
					<?php esc_html_e('No students found. Create a user with the Student role first.' , 'feuernursingreview'); ?>
				</p>

			<?php endif; ?>

		</div>
	</div>

	<p class="description">
		<?php esc_html_e('Check the students who should have access to this course, then Update/Publish to save.', 'feuernursingreview'); ?>
	</p>
</div>
