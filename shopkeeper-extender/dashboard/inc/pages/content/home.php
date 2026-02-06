<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('getbowtied_home_content')) {
	function getbowtied_home_content()
	{
		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();

		// Get the base paths
		$base_paths = $gbt_dashboard_setup->get_base_paths();

		// Get the values using the getter methods
		$theme_slug_gbt_dash = $gbt_dashboard_setup->get_theme_slug();
		$theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
		$theme_url_docs_gbt_dash = $gbt_dashboard_setup->get_theme_url_docs();
		$theme_url_changelog_gbt_dash = $gbt_dashboard_setup->get_theme_url_changelog();
		$theme_url_support_gbt_dash = $gbt_dashboard_setup->get_theme_url_support();
		$theme_child_download_link_gbt_dash = $gbt_dashboard_setup->get_theme_child_download_link();

		// Content Start
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';
		
		// Include badges component
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/badges.php';
?>

		<div class="overflow-hidden py-24 sm:py-32">
			<div class="mx-auto max-w-7xl px-6 lg:px-8">

				<div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">
					<div class="lg:pr-8">
						<div class="lg:max-w-lg">
							<?php gbt_display_version_badge(); ?>
							<h2 class="mt-4 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl leading-14"><?php echo esc_html($theme_name_gbt_dash); ?></h2>
							<dl class="mt-10 max-w-xl space-y-8 text-base/7 text-gray-600 lg:max-w-none">
								<?php 
								$auto_update_status = $gbt_dashboard_setup->get_theme_auto_update_status();
								$success_content = $gbt_dashboard_setup->get_auto_update_content('success');
								$disabled_content = $gbt_dashboard_setup->get_auto_update_content('disabled');
								if (!$auto_update_status['is_enabled']): 
								?>
										<div class="relative pl-9 gbt-auto-update-section rounded-lg" data-success-title="<?php echo esc_attr($success_content['title']); ?>" data-success-description="<?php echo esc_attr($success_content['description']); ?>" data-success-icon="<?php echo esc_attr($success_content['icon']); ?>">
									<dt class="inline font-semibold">
										<svg class="gbt-auto-update-icon absolute top-1 left-1 size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
												<path stroke-linecap="round" stroke-linejoin="round" d="<?php echo esc_attr($disabled_content['icon']); ?>" />
										</svg>
												<span class="gbt-auto-update-title"><?php echo esc_html($disabled_content['title']); ?></span>
									</dt>
												<dd class="gbt-auto-update-description inline"><?php echo esc_html($disabled_content['description']); ?></dd>
									<dd class="mt-3"><?php echo wp_kses_post($gbt_dashboard_setup->get_auto_update_enable_button()); ?></dd>
								</div>
								<?php else: ?>
								<div class="relative pl-9">
									<dt class="inline font-semibold text-gray-900">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute top-1 left-1 size-5">
												<path stroke-linecap="round" stroke-linejoin="round" d="<?php echo esc_attr($success_content['icon']); ?>" />
										</svg>
												<?php echo esc_html($success_content['title']); ?>
									</dt>
												<dd class="inline"><?php echo esc_html($success_content['description']); ?></dd>
								</div>
								<?php endif; ?>
								<div class="relative pl-9">
									<dt class="inline font-semibold">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute top-1 left-1 size-5">
											<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 14.5M14.25 3.104c.251.023.501.05.75.082M19.8 14.5l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 1-6.23-.693L5 14.5m14.8.5 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.611L5 14.5" />
										</svg>
										Want to customize files?
									</dt>
									<dd class="inline">Use the <a href="<?php echo esc_html($theme_child_download_link_gbt_dash); ?>" class="underline">Child Theme</a> to safely customize your website's code without losing changes when the theme updates.</dd>
								</div>
								<div class="relative pl-9">
									<dt class="inline font-semibold">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute top-1 left-1 size-5">
											<path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
										</svg>
										Personalize your website.
									</dt>
									<dd class="inline">Use the <a href="<?php echo esc_url(admin_url('customize.php')); ?>" class="underline">Customizer</a> to effortlessly tailor your website's look and feel to match your unique brand. Your customizations will not be reset on theme updates.</dd>
								</div>
								<div class="relative pl-9">
									<dt class="inline font-semibold text-gray-900">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute top-1 left-1 size-5">
											<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
										</svg>
										Need Support?
									</dt>
									<dd class="inline">Our team is ready to <a href="<?php echo esc_url(admin_url('admin.php?page=getbowtied-help')); ?>" class="underline">help you</a> overcome challenges and unlock your website's full potential.</dd>
								</div>
							</dl>
						</div>
					</div>
					<img src="<?php echo esc_url($base_paths['url'] . '/dashboard/assets/img/theme/' . $theme_slug_gbt_dash . '/screenshot.png'); ?>" alt="<?php echo esc_attr($theme_name_gbt_dash); ?> Screenshot" class="w-full h-auto object-cover rounded-xl shadow-xl ring-1 ring-gray-400/10 lg:w-full">
				</div>

				<hr class="mt-24 border-t border-gray-200" />

				<div class="mx-auto max-w-7xl mt-16">
					<div class="text-center mb-8">
						<h3 class="text-2xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-3xl">Supercharge Your Online Sales with Essential Extensions</h3>
						<div class="mx-auto max-w-2xl">
							<p class="mt-4 text-base/7 text-gray-600">These powerful extensions will help you boost conversions, simplify checkout, and deliver a better customer experience.</p>
						</div>
					</div>

					<ul role="list" class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">

						<li class="col-span-1 flex flex-col rounded-lg bg-white text-center shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition-all duration-300 group">
							<a href="https://automattic.pxf.io/woocommerce-woopayments" target="_blank" class="flex flex-col flex-1">
								<div class="flex flex-1 flex-col p-8">
									<img class="mx-auto size-20 shrink-0 rounded-lg shadow-sm transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:-translate-y-2 group-hover:shadow-md will-change-transform" src="<?php echo esc_url($base_paths['url'] . '/dashboard/assets/img/extensions/woopayments.webp'); ?>" alt="WooPayments">
									<h3 class="mt-6 text-lg font-medium text-gray-900">WooPayments</h3>
									<dl class="mt-1 flex grow flex-col justify-between">
										<dt class="sr-only">Title</dt>
										<dd class="text-sm text-gray-500">Increase your revenue by offering all popular payment methods with no monthly fees or setup costs.</dd>
										<dt class="sr-only">Role</dt>
										<dd class="mt-3">
											<span class="inline-flex items-center rounded-full bg-[var(--color-wp-green)]/10 px-2 py-1 text-xs font-medium text-[var(--color-wp-green)] ring-1 ring-[var(--color-wp-green)]/20 ring-inset">Revenue Booster</span>
										</dd>
									</dl>
								</div>
								<div class="mt-auto border-t border-gray-200 bg-gray-50">
									<div class="flex items-center justify-center gap-x-3 py-4">
										<svg class="size-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
										</svg>
										<span class="text-sm font-semibold text-gray-900">View Extension</span>
									</div>
								</div>
							</a>
						</li>

						<li class="col-span-1 flex flex-col rounded-lg bg-white text-center shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition-all duration-300 group">
							<a href="https://automattic.pxf.io/woocommerce-product-bundles" target="_blank" class="flex flex-col flex-1">
								<div class="flex flex-1 flex-col p-8">
									<img class="mx-auto size-20 shrink-0 rounded-lg shadow-sm transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:-translate-y-2 group-hover:shadow-md will-change-transform" src="<?php echo esc_url($base_paths['url'] . '/dashboard/assets/img/extensions/product-bundles.webp'); ?>" alt="Product Bundles">
									<h3 class="mt-6 text-lg font-medium text-gray-900">Product Bundles</h3>
									<dl class="mt-1 flex grow flex-col justify-between">
										<dt class="sr-only">Title</dt>
										<dd class="text-sm text-gray-500">Boost your average order value by creating compelling product packages that encourage larger purchases.</dd>
										<dt class="sr-only">Role</dt>
										<dd class="mt-3">
											<span class="inline-flex items-center rounded-full bg-[var(--color-wp-green)]/10 px-2 py-1 text-xs font-medium text-[var(--color-wp-green)] ring-1 ring-[var(--color-wp-green)]/20 ring-inset">Revenue Booster</span>
										</dd>
									</dl>
								</div>
								<div class="mt-auto border-t border-gray-200 bg-gray-50">
									<div class="flex items-center justify-center gap-x-3 py-4">
										<svg class="size-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
										</svg>
										<span class="text-sm font-semibold text-gray-900">View Extension</span>
									</div>
								</div>
							</a>
						</li>

						<li class="col-span-1 flex flex-col rounded-lg bg-white text-center shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition-all duration-300 group">
							<a href="https://automattic.pxf.io/woocommerce-shipment-tracking" target="_blank" class="flex flex-col flex-1">
								<div class="flex flex-1 flex-col p-8">
									<img class="mx-auto size-20 shrink-0 rounded-lg shadow-sm transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:-translate-y-2 group-hover:shadow-md will-change-transform" src="<?php echo esc_url($base_paths['url'] . '/dashboard/assets/img/extensions/shipment-tracking.webp'); ?>" alt="Shipment Tracking">
									<h3 class="mt-6 text-lg font-medium text-gray-900">Shipment Tracking</h3>
									<dl class="mt-1 flex grow flex-col justify-between">
										<dt class="sr-only">Title</dt>
										<dd class="text-sm text-gray-500">Reduce support inquiries and enhance customer satisfaction by providing real-time delivery updates.</dd>
										<dt class="sr-only">Role</dt>
										<dd class="mt-3">
											<span class="inline-flex items-center rounded-full bg-[var(--color-wp-green)]/10 px-2 py-1 text-xs font-medium text-[var(--color-wp-green)] ring-1 ring-[var(--color-wp-green)]/20 ring-inset">Revenue Booster</span>
										</dd>
									</dl>
								</div>
								<div class="mt-auto border-t border-gray-200 bg-gray-50">
									<div class="flex items-center justify-center gap-x-3 py-4">
										<svg class="size-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
										</svg>
										<span class="text-sm font-semibold text-gray-900">View Extension</span>
									</div>
								</div>
							</a>
						</li>

						<li class="col-span-1 flex flex-col rounded-lg bg-white text-center shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition-all duration-300 group">
							<a href="https://automattic.pxf.io/woocommerce-gift-cards" target="_blank" class="flex flex-col flex-1">
								<div class="flex flex-1 flex-col p-8">
									<img class="mx-auto size-20 shrink-0 rounded-lg shadow-sm transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:-translate-y-2 group-hover:shadow-md will-change-transform" src="<?php echo esc_url($base_paths['url'] . '/dashboard/assets/img/extensions/gift-cards.webp'); ?>" alt="Gift Cards">
									<h3 class="mt-6 text-lg font-medium text-gray-900">Gift Cards</h3>
									<dl class="mt-1 flex grow flex-col justify-between">
										<dt class="sr-only">Title</dt>
										<dd class="text-sm text-gray-500">Generate additional revenue streams and attract new customers by offering flexible digital gift options.</dd>
										<dt class="sr-only">Role</dt>
										<dd class="mt-3">
											<span class="inline-flex items-center rounded-full bg-[var(--color-wp-green)]/10 px-2 py-1 text-xs font-medium text-[var(--color-wp-green)] ring-1 ring-[var(--color-wp-green)]/20 ring-inset">Revenue Booster</span>
										</dd>
									</dl>
								</div>
								<div class="mt-auto border-t border-gray-200 bg-gray-50">
									<div class="flex items-center justify-center gap-x-3 py-4">
										<svg class="size-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
										</svg>
										<span class="text-sm font-semibold text-gray-900">View Extension</span>
									</div>
								</div>
							</a>
						</li>
					</ul>

					<div class="mt-12 text-center">
						<a href="https://automattic.pxf.io/woocommerce-extensions" target="_blank" class="inline-flex items-center gap-x-2 rounded-md bg-[var(--color-wp-blue)] px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-[var(--color-wp-blue-darker)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-blue)] transition-all duration-300 cursor-pointer">
							<svg class="-ml-0.5 size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
								<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
							</svg>
							More Essential Extensions
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
