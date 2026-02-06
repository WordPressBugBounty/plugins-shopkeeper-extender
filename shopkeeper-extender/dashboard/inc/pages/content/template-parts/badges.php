<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('gbt_display_version_badge')) {
	/**
	 * Display version badge with update status
	 * All variables are handled internally
	 */
	function gbt_display_version_badge() {
		// Add coin flip animation CSS
		?>
		<style>
			@keyframes coin-flip {
				0% { transform: rotateY(0deg); }
				20% { transform: rotateY(360deg); }
				100% { transform: rotateY(360deg); }
			}
		</style>
		<?php
		// Get theme information
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
		$theme_version = $gbt_dashboard_setup->get_theme_version();
		$theme_slug = $gbt_dashboard_setup->get_theme_slug();
		$changelog_url = $gbt_dashboard_setup->get_theme_url_changelog();
		
		// Check if theme update is available
		$update_available = false;
		$update_version = '';
		$updates = get_site_transient('update_themes');
		if ($updates && isset($updates->response[$theme_slug])) {
			$update_available = true;
			$update_version = isset($updates->response[$theme_slug]['new_version']) 
				? $updates->response[$theme_slug]['new_version'] 
				: '';
		}
		// Default classes for the badge container
		$container_class_string = 'mt-4 mb-2 flex flex-wrap items-center gap-3';
		
		?>
		<div class="<?php echo esc_attr($container_class_string); ?>">
			<!-- Version Status Badge -->
			<span class="inline-flex items-center gap-2 rounded-full <?php echo $update_available ? 'bg-[var(--color-wp-red)]' : 'bg-[var(--color-wp-green)]'; ?> px-3 py-1.5 text-sm font-medium text-white shadow-sm" aria-label="Theme version">
				<?php if ($update_available): ?>
					<!-- Warning icon for outdated version -->
					<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0V7zM10 14a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path>
					</svg>
				<?php else: ?>
					<!-- Check icon for up-to-date version -->
					<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
					</svg>
				<?php endif; ?>
				<span class="uppercase tracking-wide text-[10px] text-white">VERSION <?php echo esc_html($theme_version); ?></span>
				<span class="hidden sm:inline-block h-3 w-px bg-white"></span>
				<span class="hidden sm:inline uppercase tracking-wide text-[10px] font-semibold text-white">
					<?php echo $update_available ? 'Outdated' : "You're up to date"; ?>
				</span>
			</span>
			
			<?php if ($update_available): ?>
				<!-- Update Available Badge -->
				<a href="<?php echo esc_url( admin_url('update-core.php#update-themes-table') ); ?>" class="inline-flex items-center gap-2 rounded-full bg-[var(--color-wp-yellow)] px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition animate-pulse" role="button" aria-label="Update available to version <?php echo esc_attr($update_version ?: 'latest'); ?>">
					<!-- Download/Update icon -->
					<svg class="w-4 h-4 [transform-style:preserve-3d] [animation:coin-flip_4s_ease-in-out_infinite]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v8.19l2.22-2.22a.75.75 0 1 1 1.06 1.06l-3.5 3.5a.75.75 0 0 1-1.06 0l-3.5-3.5a.75.75 0 1 1 1.06-1.06l2.22 2.22V3.75A.75.75 0 0 1 10 3Zm-6.25 13a.75.75 0 0 1 .75-.75h11a.75.75 0 0 1 0 1.5h-11a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"></path>
					</svg>
					<span class="uppercase tracking-wide text-[10px]">Update to <?php echo !empty($update_version) ? 'v' . esc_html($update_version) : 'latest'; ?></span>
				</a>
			<?php endif; ?>
			
			<!-- Changelog Badge with same design as version badge -->
			<a href="<?php echo esc_url($changelog_url); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-[var(--color-wp-gray-lighter)] px-3 py-1.5 text-sm font-medium text-white shadow-sm" role="button" aria-label="View changelog">
				<!-- Document icon -->
				<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
				</svg>
				<span class="uppercase tracking-wide text-[10px] text-white">CHANGELOG</span>
			</a>
		</div>
		<?php
	}
}


