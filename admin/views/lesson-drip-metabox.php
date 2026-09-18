<?php
/**
 * admin/views/lesson-drip-metabox.php
 *
 * @var string     $type       immediate|days|date|quiz
 * @var int        $days
 * @var string     $date       Y-m-d
 * @var string     $time       H:i
 * @var int        $quiz_id
 * @var float      $min_score
 * @var \WP_Post[] $quizzes
 * @var string     $nonce_action
 * @var string     $nonce_field
 */

if (!defined('ABSPATH')) exit;

$options = [
	'immediate' => __('Available immediately on enrollment', 'feuernursingreview'),
	'days'      => __('A number of days after enrollment', 'feuernursingreview'),
	'date'      => __('On a fixed calendar date', 'feuernursingreview'),
	'quiz'      => __('After passing a quiz', 'feuernursingreview'),
];
?>
<?php wp_nonce_field($nonce_action, $nonce_field); ?>

<div class="fnr-drip-box">
	<p>
		<label for="fnr_drip_type"><strong><?php esc_html_e('Unlock this lesson', 'feuernursingreview'); ?></strong></label>
		<br>
		<select name="fnr_drip_type" id="fnr_drip_type" class="widefat">
			<?php foreach ($options as $value => $label) : ?>
				<option value="<?php echo esc_attr($value); ?>" <?php selected($type, $value); ?>>
					<?php echo esc_html($label); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<div class="fnr-drip-field" data-drip-type="days">
		<p>
			<label for="fnr_drip_days">
				<input type="number" min="0" step="1" class="small-text"
					name="fnr_drip_days" id="fnr_drip_days"
					value="<?php echo esc_attr($days); ?>" />
				<?php esc_html_e('day(s) after enrollment', 'feuernursingreview'); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e('Counted from the start of the enrollment day, so it unlocks at midnight rather than at the exact hour they enrolled.', 'feuernursingreview'); ?>
		</p>
	</div>

	<div class="fnr-drip-field" data-drip-type="date">
		<p>
			<label for="fnr_drip_date" class="screen-reader-text"><?php esc_html_e('Unlock date', 'feuernursingreview'); ?></label>
			<input type="date" name="fnr_drip_date" id="fnr_drip_date" value="<?php echo esc_attr($date); ?>" />

			<label for="fnr_drip_time" class="screen-reader-text"><?php esc_html_e('Unlock time', 'feuernursingreview'); ?></label>
			<input type="time" name="fnr_drip_time" id="fnr_drip_time" value="<?php echo esc_attr($time ?: '00:00'); ?>" />
		</p>
		<p class="description">
			<?php
				printf(
					// translators: %s: the site's timezone string, e.g. Asia/Manila
					esc_html__('Same moment for every student, in site time (%s). Leave the time blank for midnight.', 'feuernursingreview'),
					esc_html(wp_timezone_string())
				);
			?>
		</p>
	</div>

	<div class="fnr-drip-field" data-drip-type="quiz">
		<?php if (!$quizzes) : ?>

			<p class="description">
				<?php esc_html_e('No quizzes exist yet. Create one first, then come back and pick it here.', 'feuernursingreview'); ?>
			</p>

		<?php else : ?>

			<p>
				<label for="fnr_drip_quiz_id"><?php esc_html_e('Quiz', 'feuernursingreview'); ?></label>
				<br>
				<select name="fnr_drip_quiz_id" id="fnr_drip_quiz_id" class="widefat">
					<option value="0"><?php esc_html_e('— Select a quiz —', 'feuernursingreview'); ?></option>
					<?php foreach ($quizzes as $quiz) : ?>
						<option value="<?php echo esc_attr($quiz->ID); ?>" <?php selected($quiz_id, $quiz->ID); ?>>
							<?php echo esc_html(get_the_title($quiz)); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="fnr_drip_quiz_min_score">
					<?php esc_html_e('Minimum score', 'feuernursingreview'); ?>
					<input type="number" min="0" max="100" step="0.5" class="small-text"
						name="fnr_drip_quiz_min_score" id="fnr_drip_quiz_min_score"
						value="<?php echo esc_attr($min_score); ?>" />
					%
				</label>
			</p>
			<p class="description">
				<?php esc_html_e('Measured against the student\'s best completed attempt, so retakes count. This is independent of the quiz\'s own pass threshold — set it higher or lower as needed.', 'feuernursingreview'); ?>
			</p>

		<?php endif; ?>
	</div>
</div>
