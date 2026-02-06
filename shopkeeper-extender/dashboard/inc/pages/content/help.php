<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('getbowtied_help_content')) {
	function getbowtied_help_content()
	{
		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();

		// Get the base paths
		$base_paths = $gbt_dashboard_setup->get_base_paths();

		// Get the URLs using the getter methods
		$theme_url_docs_gbt_dash = $gbt_dashboard_setup->get_theme_url_docs();
		$theme_url_changelog_gbt_dash = $gbt_dashboard_setup->get_theme_url_changelog();
		$theme_url_support_gbt_dash = $gbt_dashboard_setup->get_theme_url_support();
		$theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
		// Content Start
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';
		
		// Include badges component
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/badges.php';
?>

		<div class="overflow-hidden py-24 sm:py-32">
			<div class="mx-auto max-w-7xl px-6 lg:px-8">
				<div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-1">
					<div>
						<div class="lg:max-w-lg">
							<?php gbt_display_version_badge(); ?>
							<h2 class="mt-4 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl leading-14"><?php echo esc_html($theme_name_gbt_dash); ?> Help</h2>
							<p class="mt-6 text-lg leading-8 text-gray-600">
								Everything you need to make the most of your theme and create a stunning website.
							</p>
						</div>
					</div>

					<div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 mt-4">
						<!-- Documentation Card -->
						<a href="<?php echo esc_url($theme_url_docs_gbt_dash); ?>"
							target="_blank"
							class="flex flex-col h-full bg-white p-6 rounded-lg border border-gray-200 hover:border-gray-300 shadow-sm hover:shadow transition-all no-underline">
							<div class="flex items-center justify-center w-12 h-12 rounded-full bg-wp-blue/10 text-wp-blue mb-4">
								<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
								</svg>
							</div>
							<h3 class="text-xl font-medium text-gray-900 mb-2">Documentation</h3>
							<p class="text-gray-600 mb-4 flex-grow">Comprehensive guides and tutorials to help you set up and customize your theme exactly how you want it.</p>
							<div class="flex items-center text-wp-blue font-medium">
								<span>Read documentation</span>
								<svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
								</svg>
							</div>
						</a>

						<!-- Support Card -->
						<a href="<?php echo esc_url($theme_url_support_gbt_dash); ?>"
							target="_blank"
							class="flex flex-col h-full bg-white p-6 rounded-lg border border-gray-200 hover:border-gray-300 shadow-sm hover:shadow transition-all no-underline">
							<div class="flex items-center justify-center w-12 h-12 rounded-full bg-wp-blue/10 text-wp-blue mb-4">
								<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
								</svg>
							</div>
							<h3 class="text-xl font-medium text-gray-900 mb-2">Customer Support</h3>
							<p class="text-gray-600 mb-4 flex-grow">Our dedicated support team is ready to help you overcome any challenges and answer your questions.</p>
							<div class="flex items-center text-wp-blue font-medium">
								<span>Get support</span>
								<svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
								</svg>
							</div>
						</a>

						<!-- Changelog Card -->
						<a href="<?php echo esc_url($theme_url_changelog_gbt_dash); ?>"
							target="_blank"
							class="flex flex-col h-full bg-white p-6 rounded-lg border border-gray-200 hover:border-gray-300 shadow-sm hover:shadow transition-all no-underline">
							<div class="flex items-center justify-center w-12 h-12 rounded-full bg-wp-blue/10 text-wp-blue mb-4">
								<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
								</svg>
							</div>
							<h3 class="text-xl font-medium text-gray-900 mb-2">Changelog</h3>
							<p class="text-gray-600 mb-4 flex-grow">Stay up-to-date with the latest theme improvements, bug fixes, and new features in each release.</p>
							<div class="flex items-center text-wp-blue font-medium">
								<span>View changelog</span>
								<svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
								</svg>
							</div>
						</a>
					</div>
				</div>
			</div>
		</div>

<?php
		// Content End
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-end.php';
	}
}
