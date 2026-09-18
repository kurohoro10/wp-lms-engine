<?php
/**
 * includes/Admin/LessonOrphanFilter.php
 *
 * Adds an "Unassigned (no course)" filter dropdown to the All Lessons
 * screen. Needed because CourseCascade::unassign_lessons() (and any
 * lesson orphaned before that fix existed) clears post_parent to 0
 * with no other trace - without this, an unassigned lesson is only
 * findable by scrolling the entire list and checking each one's
 * Course meta box.
 */
namespace Feuernursingreview\Admin;

use Feuernursingreview\CPT\LessonCPT;

if (!defined('ABSPATH')) exit;

class LessonOrphanFilter {

	const QUERY_VAR = 'fnr_orphan_filter';

	public static function register() {
		add_action('restrict_manage_posts', [__CLASS__, 'render_filter']);
		add_action('pre_get_posts', [__CLASS__, 'apply_filter']);
	}

	public static function render_filter(string $post_type = ''): void {
		global $typenow;

		// restrict_manage_posts doesn't reliably pass $post_type on
		// every WP version - check $typenow too.
		if ($typenow !== LessonCPT::POST_TYPE && $post_type !== LessonCPT::POST_TYPE) {
			return;
		}

		$selected = isset($_GET[self::QUERY_VAR]) ? sanitize_text_field($_GET[self::QUERY_VAR]) : '';
		$orphan_count = self::count_orphaned_lessons();
		?>
		<label for="<?php echo esc_attr(self::QUERY_VAR); ?>" class="screen-reader-text">
			<?php esc_html_e('Filter by course assignment', 'feuernursingreview'); ?>
		</label>
		<select name="<?php echo esc_attr(self::QUERY_VAR); ?>" id="<?php echo esc_attr(self::QUERY_VAR); ?>">
			<option value=""><?php esc_html_e('All courses', 'feuernursingreview'); ?></option>
			<option value="orphaned" <?php selected($selected, 'orphaned'); ?>>
				<?php
				printf(
					/* translators: %d: number of unassigned lessons */
					esc_html__('Unassigned (no course) (%d)', 'feuernursingreview'),
					$orphan_count
				);
				?>
			</option>
		</select>
		<?php
	}

	public static function apply_filter(\WP_Query $query): void {
		if (!is_admin() || !$query->is_main_query()) {
			return;
		}

		if ($query->get('post_type') !== LessonCPT::POST_TYPE) {
			return;
		}

		if (($_GET[self::QUERY_VAR] ?? '') !== 'orphaned') {
			return;
		}

		$query->set('post_parent', 0);
	}

	private static function count_orphaned_lessons(): int {
		$counts = get_posts([
			'post_type'      => LessonCPT::POST_TYPE,
			'post_parent'    => 0,
			'post_status'    => ['publish', 'draft', 'pending', 'private'],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]);

		return count($counts);
	}
}
