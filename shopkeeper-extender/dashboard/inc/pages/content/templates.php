<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('getbowtied_templates_content')) {
	function getbowtied_templates_content()
	{
		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();

		// Get the base paths
		$base_paths = $gbt_dashboard_setup->get_base_paths();

		// Get the values using the getter methods
		$theme_slug_gbt_dash = $gbt_dashboard_setup->get_theme_slug();
		$theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();

		// Content Start
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';
?>

		<div class="overflow-hidden py-24 sm:py-32">
			<div class="mx-auto max-w-7xl px-6 lg:px-8">
				<div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-1">
					<div>
						<div class="lg:max-w-lg">
							<div class="flex items-center gap-3">
								<span class="inline-flex items-center gap-x-1.5 rounded-md bg-[var(--color-wp-gray-dark)] px-3 py-1.5 text-xs font-medium text-white">
									<svg class="size-2 fill-[var(--color-wp-green)]" viewBox="0 0 6 6" aria-hidden="true">
										<circle cx="3" cy="3" r="3" />
									</svg>
									VERSION <?php echo esc_html($theme_version_gbt_dash); ?>
								</span>
							</div>
							<h2 class="mt-4 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl leading-14"><?php echo esc_html($theme_name_gbt_dash); ?> Templates</h2>
							<p class="mt-6 text-lg leading-8 text-gray-600">
								Install the <span class="font-semibold text-gray-700">"Kits, Templates and Patterns"</span> plugin and give our Starter Templates a try!
								They'll help you launch your project faster.
							</p>
							<p class="mt-4 text-lg leading-8 text-gray-600">
								These templates are built with Elementor, and you will have access to all the widgets in Elementor PRO without a subscription.
							</p>

							<div class="mt-8">
								<a href="<?php echo esc_url(admin_url('admin.php?page=getbowtied-plugins')); ?>"
									class="inline-flex items-center rounded-md px-3.5 py-2 text-sm font-medium text-white bg-wp-blue hover:bg-wp-blue/90 transition-colors no-underline">
									<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
									</svg>
									Get "Kits, Templates and Patterns" plugin
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

<?php
		// Content End
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-end.php';
	}
}
