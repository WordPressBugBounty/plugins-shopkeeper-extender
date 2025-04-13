<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('getbowtied_license_content')) {
	/**
	 * Display license success notification with Tailwind styling
	 * 
	 * @param string $message The success message to display
	 */
	function display_license_success_notification($message)
	{
?>
		<div class="mt-3 rounded-md bg-green-50 p-4 border border-green-200">
			<div class="flex">
				<div class="flex-shrink-0">
					<svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
					</svg>
				</div>
				<div class="ml-3">
					<h3 class="text-sm font-medium text-green-800">Success</h3>
					<div class="mt-1 text-sm text-green-700">
						<?php echo esc_html($message); ?>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Display license error notification with Tailwind styling
	 * 
	 * @param string $message The error message to display
	 * @param string $title The title for the error notification
	 */
	function display_license_error_notification($message, $title = 'License Error')
	{
	?>
		<div class="mb-6 p-4 rounded-lg border border-red-200 bg-red-50">
			<div class="flex">
				<div class="flex-shrink-0">
					<svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
					</svg>
				</div>
				<div class="ml-3">
					<h3 class="text-sm leading-5 font-medium text-red-800">
						<?php echo esc_html($title); ?>
					</h3>
					<div class="mt-1 text-sm leading-5 text-red-700">
						<?php echo wp_kses_post($message); ?>
					</div>
					<?php
					// Show special instructions for domain restriction errors
					// Check for both activation and deactivation domain errors
					$is_domain_error = strpos($message, 'active on') !== false ||
						strpos($message, 'can only be deactivated from') !== false;

					if ($is_domain_error):
					?>
						<!-- Special instructions for domain restriction errors -->
						<div class="mt-3 text-sm leading-5 text-red-800">
							<strong>How to resolve this:</strong>
							<ol class="pl-5 mt-1 list-decimal">
								<li>Log in to the WordPress admin of the original site</li>
								<li>Navigate to Theme Dashboard > License</li>
								<li>Click the "Deactivate License" button</li>
								<li>Return to this site and try activating the license again</li>
							</ol>
							<p class="mt-2 text-sm">If you no longer have access to the original site, please contact support for assistance.</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Handle license operation result and display appropriate notification
	 * 
	 * @param array $result The license operation result
	 */
	function handle_license_notification($result)
	{
		if (!$result) {
			return;
		}

		if ($result['success'] === false) {
			$error_message = $result['message'];
			$error_title = strpos($error_message, 'not valid for this theme') !== false
				? 'Invalid License Key'
				: 'License Error';

			display_license_error_notification($error_message, $error_title);
		} else {
			display_license_success_notification($result['message']);
		}
	}

	function getbowtied_license_content()
	{
		// Theme and Dashboard Setup
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
		$base_paths = $gbt_dashboard_setup->get_base_paths();
		$theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
		$theme_slug_gbt_dash = $gbt_dashboard_setup->get_theme_slug();
		$theme_marketplace_id = $gbt_dashboard_setup->get_theme_marketplace_id();

		// Update theme prices directly - load price updater class if needed
		if (!class_exists('GBT_Theme_Price_Updater')) {
			require_once $base_paths['path'] . '/dashboard/inc/classes/class-theme-price-updater.php';
		}
		$price_updater = GBT_Theme_Price_Updater::get_instance();
		
		// Get theme pricing information from configuration
		$theme_default_price_regular_license = $gbt_dashboard_setup->get_theme_config('theme_default_price_regular_license');
		$theme_default_price_extended_license = $gbt_dashboard_setup->get_theme_config('theme_default_price_extended_license');
		
		// Update the price data if needed
		$last_verified = $price_updater->get_last_verification_time();
		$twenty_four_hours = 86400; // 24 hours in seconds
		if ((time() - $last_verified) > $twenty_four_hours) {
			$price_updater->update_theme_price(
				$theme_marketplace_id,
				$theme_default_price_regular_license,
				$theme_default_price_extended_license
			);
		}

		// Check for sales by comparing live prices with default prices
		$regular_license_is_sale = false;
		$extended_license_is_sale = false;
		$professional_license_is_sale = false; // Added for professional license

		// Get the price data using the updater class
		$price_data = $price_updater->get_current_price_data(
			$theme_default_price_regular_license,
			$theme_default_price_extended_license
		);
		
		if (isset($price_data['regular_license_price']) && isset($price_data['extended_license_price'])) {
			// Compare live prices with default prices to check for sales
			$regular_license_is_sale = $price_data['regular_license_price'] < $theme_default_price_regular_license;
			$extended_license_is_sale = $price_data['extended_license_price'] < $theme_default_price_extended_license;

			// If regular license is on sale, professional license is also on sale
			$professional_license_is_sale = $regular_license_is_sale;
		} else {
			// No live prices available, use default config prices
			$regular_license_is_sale = false;
			$extended_license_is_sale = false;
			$professional_license_is_sale = false;

			// Original and live prices are the same when no live data is available
			$original_price_regular_license_display = '';
			$original_price_extended_license_display = '';
		}

		// Get live prices
		$live_price_regular_license = $price_data['regular_license_price'];
		$live_price_extended_license = $price_data['extended_license_price'];

		// Format prices for display
		$live_price_regular_license_display = '$' . $live_price_regular_license;
		$live_price_extended_license_display = '$' . number_format($live_price_extended_license, 0, '.', ',');

		// Format original prices for comparison display
		$original_price_regular_license_display = '$' . $theme_default_price_regular_license;
		$original_price_extended_license_display = '$' . number_format($theme_default_price_extended_license, 0, '.', ',');

		// Use live prices for the main display
		$theme_default_price_regular_license = $live_price_regular_license;
		$theme_default_price_extended_license = $live_price_extended_license;

		// Get the last price verification time using the price updater instance
		$price_last_verified = $price_updater->get_last_verification_time();
		$price_last_verified_date = '';
		if ($price_last_verified) {
			$wp_timezone = wp_timezone();
			$datetime = new DateTime();
			$datetime->setTimestamp($price_last_verified);
			$datetime->setTimezone($wp_timezone);
			$price_last_verified_date = $datetime->format('F j, Y g:i a (T)');
		}

		// Get support price formula from config and apply it to the theme price
		$support_price_formula = $gbt_dashboard_setup->get_global_config('support_prices', 'support_price_formula');
		$support_price = is_callable($support_price_formula) ? $support_price_formula($theme_default_price_regular_license) : 0;

		// Format prices for display
		$theme_default_price_display = '$' . number_format($theme_default_price_regular_license, 0);
		$theme_default_price_extended_display = '$' . number_format($theme_default_price_extended_license, 0);

		// Original prices (before sale)
		$original_price_regular_license_display = $regular_license_is_sale ? '$' . number_format($gbt_dashboard_setup->get_theme_config('theme_default_price_regular_license'), 0) : '';
		$original_price_extended_license_display = $extended_license_is_sale ? '$' . number_format($gbt_dashboard_setup->get_theme_config('theme_default_price_extended_license'), 0) : '';

		// Professional license pricing - apply support price formula
		$professional_price = $support_price;
		$professional_price_display = '$' . $professional_price;
		$professional_price_text = 'for 6 months'; // Text used throughout the file

		// Original professional price (before sale) - apply support price formula to original price
		$original_professional_price = $professional_license_is_sale ?
			(is_callable($support_price_formula) ? $support_price_formula($gbt_dashboard_setup->get_theme_config('theme_default_price_regular_license')) : 0) : 0;
		$original_professional_price_display = $professional_license_is_sale ? '$' . $original_professional_price : '';

		// Load the License Manager class
		if (!class_exists('GBT_License_Manager')) {
			require_once $base_paths['path'] . '/dashboard/inc/classes/class-license-manager.php';
		}

		// Initialize License Manager
		$license_manager = GBT_License_Manager::get_instance();

		// License Data
		$stored_options = $license_manager->get_license_data();
		$license_key = $stored_options['license_key'] ?? '';
		$license_status = $stored_options['license_status'] ?? 'inactive';
		$license_info = $stored_options['license_info'] ?? [];
		$support_expiration_date = $stored_options['support_expiration_date'] ?? '';
		$is_license_active = $license_manager->is_license_active();
		$is_support_active = $license_manager->is_support_active();

		// Format support expiration date if available
		$support_expiration = isset($support_expiration_date) && !empty($support_expiration_date) ?
			date_i18n(get_option('date_format'), $support_expiration_date) : (isset($license_info['supported_until']) ? $license_info['supported_until'] : '');

		// Last verification time
		$last_verified = $license_manager->get_last_verified_time();
		$last_verified_date = '';
		if ($last_verified) {
			$wp_timezone = wp_timezone();
			$datetime = new DateTime();
			$datetime->setTimestamp($last_verified);
			$datetime->setTimezone($wp_timezone);
			$last_verified_date = $datetime->format('F j, Y g:i A (T)');
		}

		// Variables for preview processing
		$preview_variables = [
			'preview_type' => '',
			'preview_href' => '',
			'small_url' => '',
			'large_url' => '',
			'large_width' => 0,
			'large_height' => 0,
			'icon_url' => '',
			'landscape_url' => '',
			'square_url' => '',
			'mp3_url' => '',
			'mp3_id' => '',
			'length' => '',
			'thumbnail_url' => '',
			'thumbnail_width' => 0,
			'thumbnail_height' => 0,
			'video_url' => '',
			'display_url' => '',
			'link_url' => ''
		];

		// Process form submission
		$result = null;

		// Auto-verify license on page load if there's an active license
		if (!empty($stored_options['license_key']) && !isset($_GET['license_updated'])) {
			// Check when the license was last verified and only perform the check
			// if it has been more than 30 days since the last verification
			$last_verified = $license_manager->get_last_verified_time();
			$current_time = time();
			$interval_days = 30;
			$interval_seconds = $interval_days * DAY_IN_SECONDS;
			
			if ($current_time - $last_verified >= $interval_seconds) {
				// Only do this if not immediately after a license update to avoid duplicate checks
				$auto_check_result = $license_manager->cron_process_license();

				// If the check failed due to domain restriction, the license will be cleared
				// Update stored options to match the current state after verification
				if ($auto_check_result && !$auto_check_result['success']) {
					$stored_options = $auto_check_result['license_data'];
					$license_key = $stored_options['license_key'] ?? '';
					$license_status = $stored_options['license_status'] ?? 'inactive';
					$license_info = $stored_options['license_info'] ?? [];
					$support_expiration_date = $stored_options['support_expiration_date'] ?? '';

					// Set transient to show notification on this page load
					set_transient('gbt_license_result', $auto_check_result, 60);
				}
			}
		}

		// Handle form submission
		if (isset($_POST['save_license']) && check_admin_referer('getbowtied_license_nonce')) {
			$new_license_key = sanitize_text_field($_POST['license_key']);

			// Check if this is a deactivation (empty license key) or a change
			$is_deactivation = empty($new_license_key) && !empty($license_key);

			// Process license submission
			$result = $license_manager->process_license_submission(
				$new_license_key,
				$theme_slug_gbt_dash,
				$theme_marketplace_id
			);

			// Store the result in a transient to display after redirect
			set_transient('gbt_license_result', $result, 60);

			// Redirect to the same page to avoid form resubmission
			wp_redirect(add_query_arg('license_updated', '1', $_SERVER['REQUEST_URI']));
			exit;
		}

		// Check for redirected message from form submission
		if (isset($_GET['license_updated']) && get_transient('gbt_license_result')) {
			$result = get_transient('gbt_license_result');
			delete_transient('gbt_license_result');

			// Update stored options from result
			if (isset($result['license_data'])) {
				$stored_options = $result['license_data'];
				$license_key = $stored_options['license_key'] ?? '';
				$license_status = $stored_options['license_status'] ?? 'inactive';
				$license_info = $stored_options['license_info'] ?? [];
				$support_expiration_date = $stored_options['support_expiration_date'] ?? '';
				$is_license_active = $license_manager->is_license_active();
				$is_support_active = $license_manager->is_support_active();
			}
		}

		// Content Start
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';

		// Main content
	?>

		<!-- ================================================= -->
		<!-- License -->
		<!-- ================================================= -->

		<div class="pb-24 sm:pb-32">
			<div class="mx-auto max-w-7xl">
				<div class="mx-auto mt-16 max-w-2xl rounded-3xl ring-1 bg-white ring-gray-200 sm:mt-20 lg:mx-0 lg:flex lg:max-w-none">
					<div class="p-8 sm:p-10 lg:flex-auto">
						<div class="flex items-center gap-3 mt-2">
							<?php if (!$is_license_active): ?>
								<span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white">
									NO LICENSE
								</span>
								<span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white">
									NO UPDATES
								</span>
								<span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white">
									NO SUPPORT
								</span>
							<?php endif; ?>
						</div>
						<h3 class="mt-4 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl"><?php echo esc_html($theme_name_gbt_dash); ?> <span class="text-gray-700">v<?php echo esc_html($theme_version_gbt_dash); ?></span></h3>
						<p class="mt-5 text-base/7 text-gray-600">
							<?php if ($is_license_active): ?>
								<?php if ($is_support_active): ?>
									Thank you for activating your license. Your theme is now fully unlocked with premium features, support, and updates.
								<?php else: ?>
									Your license is active, but your Professional Plan plan <span class="text-red-600 font-medium">has expired</span>. Renew your support plan to access premium features, automatic updates, and priority assistance. <a id="learn-more-link" href="#professional-upgrade-section" class="text-wp-blue hover:underline">Learn more &darr;</a>
								<?php endif; ?>
							<?php else: ?>
								Activate your license to unlock premium features, automatic updates, and dedicated support.
							<?php endif; ?>
						</p>
						<form method="post" action="" class="space-y-6 mt-12">
							<?php wp_nonce_field('getbowtied_license_nonce'); ?>

							<div class="space-y-5 max-w-2xl">
								<div class="mb-1">
									<!-- License key label with key icon and inline help link -->
									<div class="flex items-center justify-between mb-2">
										<div class="flex items-center gap-2">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-600">
												<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
											</svg>
											<label for="license_key" class="text-base font-medium text-gray-700">Purchase Code:</label>
										</div>

										<?php if ($license_status !== 'active'): ?>
											<button type="button" id="show-license-help" class="text-wp-blue text-sm flex items-center focus:outline-none focus:ring-0 focus:ring-offset-0 focus-visible:outline-none focus-visible:ring-0 cursor-pointer">
												<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 mr-1.5 text-wp-blue">
													<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
												</svg>
												How to find your purchase code
											</button>
										<?php endif; ?>
									</div>

									<!-- Input field with question mark icon -->
									<?php if ($license_status === 'active'): ?>
										<div class="flex items-center gap-2 mt-1">
											<div class="bg-gray-50 border border-gray-200 px-4 py-3 rounded-md text-gray-700 flex items-center gap-2 w-full">
												<span class="font-medium"><?php echo esc_html(substr($license_key, 0, 4)) . '-****-****-****-' . esc_html(substr($license_key, -4)); ?></span>
												<span class="inline-flex items-center ml-auto font-medium text-green-600">
													<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
														<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
													</svg>
													Valid
												</span>
											</div>
											<input type="hidden" id="license_key" name="license_key" value="<?php echo esc_attr($license_key); ?>" />
										</div>
									<?php else: ?>
										<div class="relative rounded-md">
											<input type="text"
												id="license_key"
												name="license_key"
												class="block w-full px-4 py-3 text-gray-700 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-wp-blue focus:border-wp-blue"
												value="<?php echo esc_attr($license_key); ?>"
												placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
										</div>
									<?php endif; ?>
								</div>

								<?php
								// Display license operation notification if available
								if (isset($result) && $result !== null) {
									handle_license_notification($result);
								}
								?>

								<div class="pt-4 flex flex-wrap gap-3">
									<button type="submit"
										name="save_license"
										class="flex items-center justify-center w-auto rounded-md <?php echo $license_status === 'active'
																										? 'bg-white text-gray-700 ring-1 ring-gray-300 hover:ring-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2'
																										: 'bg-wp-blue text-white shadow-sm hover:bg-wp-blue/90 focus:outline-none focus:ring-2 focus:ring-wp-blue focus:ring-offset-2'; ?> px-4 py-2.5 text-base font-medium transition duration-150 ease-in-out cursor-pointer">
										<?php if ($license_status === 'active'): ?>
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-1.5">
												<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
											</svg>
										<?php else: ?>
											<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
											</svg>
										<?php endif; ?>
										<?php echo $license_status === 'active' ? 'Refresh License' : 'Activate License'; ?>
									</button>

									<?php if ($license_status === 'active'): ?>
										<button type="submit"
											name="save_license"
											class="flex items-center justify-center w-auto rounded-md bg-white px-4 py-2.5 text-base font-medium text-gray-700 ring-1 ring-gray-300 hover:ring-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition duration-150 ease-in-out cursor-pointer"
											onclick="const licenseKey = document.getElementById('license_key'); licenseKey.value = ''; jQuery(licenseKey).data('has-value', true);">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-1.5">
												<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
											</svg>
											Deactivate License
										</button>
									<?php endif; ?>
								</div>
								
								<div class="mt-40 text-sm text-gray-600">
									<br /><br />
									<strong>Having trouble with activation?</strong><br />Use the message box on <a href="https://1.envato.market/getbowtied-profile" target="_blank" class="text-wp-blue hover:text-wp-blue/90">our profile page</a> to get help with activation.
								</div>
							</div>

							<!-- License Help Modal -->
							<div id="license-help-modal" class="relative z-10 hidden" aria-labelledby="license-modal-title" role="dialog" aria-modal="true">
								<div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
								<div class="fixed inset-0 z-10 w-screen overflow-y-auto">
									<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
										<div class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
											<div class="sm:flex sm:items-start">
												<div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-wp-blue/10 sm:mx-0 sm:size-10">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-wp-blue">
														<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
													</svg>
												</div>
												<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
													<h3 class="text-base font-semibold text-gray-900" id="license-modal-title">Find Your Purchase Code</h3>
													<div class="mt-2">
														<p class="text-sm text-gray-500 mb-2">Follow these steps to locate your purchase code:</p>
														<ol class="text-sm text-gray-600 list-decimal ml-5 mt-2 space-y-2">
															<li>Go to <a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')); ?>" target="_blank" class="text-wp-blue hover:underline">downloads</a> on your Envato account</li>
															<li>Click the "Download" button next to <?php echo esc_html($theme_name_gbt_dash); ?> theme</li>
															<li>Select "License certificate & purchase code" from the dropdown</li>
															<li>Open the downloaded file and copy the <strong>Item Purchase Code</strong></li>
														</ol>
													</div>
												</div>
											</div>
											<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
												<button type="button" id="close-license-help" class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:ring-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 sm:mt-0 sm:w-auto transition duration-150 ease-in-out cursor-pointer">Close</button>
												<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')); ?>" target="_blank" class="mt-3 inline-flex w-full justify-center rounded-md bg-wp-blue px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-wp-blue/90 sm:mt-0 sm:w-auto sm:mr-3 transition duration-150 ease-in-out cursor-pointer">Get Your Purchase Code</a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
					<div class="-mt-2 p-2 lg:mt-0 lg:w-full lg:max-w-md lg:shrink-0 text-sm/6">
						<?php if ($is_license_active): ?>
							<!-- License Information for activated license -->
							<div class="rounded-2xl bg-gray-50 py-10 px-10 text-center ring-1 ring-gray-900/5 ring-inset h-full flex flex-col">
								<div class="mx-auto w-full">
									<div class="flex justify-center items-center mb-2">
										<svg class="w-8 h-8 text-wp-blue mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
										</svg>
										<h3 class="text-3xl font-semibold tracking-tight text-gray-900">
											Regular License
										</h3>
									</div>

									<?php
									// Check if support will expire soon (within 30 days)
									$expires_soon = false;
									$days_remaining = 0;

									if ($is_support_active && !empty($support_expiration_date) && is_numeric($support_expiration_date)) {
										$now = time();
										$seconds_remaining = $support_expiration_date - $now;
										// Use ceil to match the behavior in class-license-manager.php get_support_days_remaining()
										$days_remaining = ceil($seconds_remaining / DAY_IN_SECONDS);
										// Default expiring threshold is 14 days in notification_settings in class-license-subscription-checker.php
										$expires_soon = $days_remaining > 0 && $days_remaining <= 30;
									}

									if ($expires_soon): ?>
										<div class="mt-6 p-4 rounded-md bg-amber-50 border border-amber-200">
											<div class="flex">
												<div class="flex-shrink-0">
													<svg class="h-5 w-5 text-amber-600" viewBox="0 0 20 20" fill="currentColor">
														<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
													</svg>
												</div>
												<div class="ml-3 flex-1">
													<h3 class="text-sm font-medium text-amber-800">
														Professional Plan <?php
																				if ($days_remaining == 0) {
																					echo 'Expires Today!';
																				} elseif ($days_remaining == 1) {
																					echo 'Expires Tomorrow!';
																				} else {
																					echo 'Expires in ' . esc_html($days_remaining) . ' Days!';
																				}
																				?>
													</h3>
													<div class="mt-2 text-sm text-amber-700">
														<p>
															<?php
															if ($days_remaining == 0) {
																echo 'Today is your last day of coverage. After today, you will';
															} elseif ($days_remaining == 1) {
																echo 'Starting tomorrow, you will';
															} else {
																echo 'In ' . esc_html($days_remaining) . ' days, you will';
															}
															?> lose access to automatic updates, priority assistance, and security patches.
														</p>
													</div>
												</div>
											</div>
										</div>
									<?php endif; ?>

									<div class="mt-6 space-y-0 text-left">
										<!-- License details -->
										<div class="border-t border-gray-200 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-500">License Status</span>
												<span class="font-medium <?php echo $license_status === 'active' ? 'text-green-600' : 'text-red-600'; ?> flex items-center">
													<?php if ($license_status === 'active'): ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
														</svg>
														Valid
													<?php else: ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
														</svg>
														<?php echo ucfirst(esc_html($license_status)); ?>
													<?php endif; ?>
												</span>
											</div>
										</div>

										<div class="border-t border-gray-200 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-500">Purchase Date</span>
												<span class="font-medium text-gray-900">
													<?php
													// Format the purchase date to show only the date
													$purchase_date = isset($license_info['purchase_date']) ? $license_info['purchase_date'] : '';
													if (!empty($purchase_date)) {
														// Try to parse the date and reformat it
														$timestamp = strtotime($purchase_date);
														if ($timestamp) {
															echo esc_html(date_i18n(get_option('date_format'), $timestamp));
														} else {
															echo esc_html($purchase_date);
														}
													}
													?>
												</span>
											</div>
										</div>

										<div class="border-t border-gray-200 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-500 flex items-center">Professional Plan Until</span>
												<span class="font-medium <?php echo $is_support_active ? 'text-green-600' : 'text-red-600'; ?>"><?php echo esc_html($support_expiration); ?></span>
											</div>
										</div>

										<div class="border-t border-gray-200 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-500">Professional Plan Status</span>
												<span class="font-medium <?php echo $is_support_active ? 'text-green-600' : 'text-red-600'; ?> flex items-center">
													<?php if ($is_support_active): ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
														</svg>
														Active
													<?php else: ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
														</svg>
														Expired
													<?php endif; ?>
												</span>
											</div>
										</div>

										<div class="border-t border-gray-200 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-500 flex items-center">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 text-gray-400">
														<path stroke-linecap="round" stroke-linejoin="round" d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
													</svg>
													Priority Bug Fixes & Updates
												</span>
												<span class="font-medium <?php echo $is_support_active ? 'text-green-600' : 'text-red-600'; ?> flex items-center">
													<?php if ($is_support_active): ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
														</svg>
														Available
													<?php else: ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
														</svg>
														Expired
													<?php endif; ?>
												</span>
											</div>
										</div>

										<div class="border-t border-gray-200 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-500 flex items-center">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 text-gray-400">
														<path stroke-linecap="round" stroke-linejoin="round" d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
													</svg>
													Professional Technical Assistance
												</span>
												<span class="font-medium <?php echo $is_support_active ? 'text-green-600' : 'text-red-600'; ?> flex items-center">
													<?php if ($is_support_active): ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
														</svg>
														Available
													<?php else: ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
														</svg>
														Expired
													<?php endif; ?>
												</span>
											</div>
										</div>

										<div class="border-t border-gray-200 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-500">Auto-renewal</span>
												<span class="font-medium text-gray-900">No</span>
											</div>
										</div>

										<?php if (!empty($last_verified_date)): ?>
											<div class="border-t border-gray-200 py-3">
												<div class="flex items-center justify-between">
													<span class="text-gray-500">Last Verified</span>
													<span class="font-medium text-gray-900">
														<?php
														// Safely format the date to avoid parsing errors with complex formats
														try {
															// First try to get timestamp if it's not already one
															if (!is_numeric($last_verified)) {
																// Just display the original string if we can't parse
																echo esc_html($last_verified_date);
															} else {
																// Get WordPress timezone setting
																$wp_timezone = wp_timezone();

																// Create DateTime objects for comparison
																$datetime = new DateTime("@$last_verified");
																$datetime->setTimezone($wp_timezone);

																$today = new DateTime('now', $wp_timezone);
																$today->setTime(0, 0, 0); // Start of today

																$yesterday = clone $today;
																$yesterday->modify('-1 day');

																$verification_day = clone $datetime;
																$verification_day->setTime(0, 0, 0); // Start of verification day

																// Format output based on when it was verified
																if ($verification_day == $today) {
																	echo 'Today at ' . esc_html($datetime->format('g:i A'));
																} elseif ($verification_day == $yesterday) {
																	echo 'Yesterday at ' . esc_html($datetime->format('g:i A'));
																} else {
																	echo esc_html($datetime->format('F j, Y g:i A'));
																}
															}
														} catch (Exception $e) {
															// Fallback to original string if any error occurs
															echo esc_html($last_verified_date);
														}
														?>
													</span>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>

								<?php if ($expires_soon || !$is_support_active): ?>
									<div class="mt-auto pt-6">
										<button id="renew-support-button" class="block w-full rounded-md bg-wp-blue px-4 py-2.5 text-center text-base font-semibold text-white shadow-sm hover:bg-wp-blue/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wp-blue transition duration-150 ease-in-out cursor-pointer">
											<?php echo $expires_soon ? 'Extend before it expires' : 'Unlock your upgrade – See if you qualify'; ?>
										</button>
									</div>
								<?php endif; ?>
							</div>
						<?php else: ?>
							<!-- Default "Get a license" content -->
							<div class="rounded-2xl bg-gray-50 py-10 text-center ring-1 ring-gray-900/5 ring-inset lg:flex lg:flex-col lg:justify-center lg:py-16">
								<div class="mx-auto max-w-xs px-8">
									<?php if ($regular_license_is_sale): ?>
										<span class="inline-block mb-2 bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full">SALE</span>
									<?php endif; ?>
									<h3 class="text-3xl font-semibold tracking-tight text-gray-900 pb-2">
										Get a License
									</h3>
									<p class="text-base text-gray-600">Pay once, own it forever!</p>
									<div class="mt-6">
										<div class="relative mb-4">
											<?php if ($regular_license_is_sale): ?>
												<div class="flex items-baseline justify-center gap-x-2">
													<span class="text-5xl line-through text-gray-400"><?php echo esc_html($original_price_regular_license_display); ?></span>
													<span class="text-5xl font-bold tracking-tight text-gray-900"><?php echo esc_html($theme_default_price_display); ?></span>
													<span class="text-sm/6 font-semibold tracking-wide text-gray-600">USD</span>
												</div>
											<?php else: ?>
												<div class="flex items-baseline justify-center gap-x-2">
													<span class="text-5xl font-bold tracking-tight text-gray-900"><?php echo esc_html($theme_default_price_display); ?></span>
													<span class="text-sm/6 font-semibold tracking-wide text-gray-600">USD</span>
												</div>
											<?php endif; ?>
										</div>
										<div class="relative mt-12 mb-6">
											<div class="absolute -top-3 inset-x-0">
												<div class="flex justify-center">
													<span class="inline-flex rounded-full bg-yellow-300 px-4 py-1 text-xs font-semibold text-gray-900">Most Popular</span>
												</div>
											</div>
											<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="block w-full rounded-md bg-wp-blue px-4 pt-5 pb-5 text-center text-base font-semibold text-white shadow-sm hover:bg-wp-blue/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wp-blue transition duration-150 ease-in-out cursor-pointer">Get Regular License</a>
											<p class="mt-2 text-xs text-center text-gray-500 px-4">Ideal for most websites and client projects</p>
										</div>
									</div>

									<div class="relative mb-6 mt-6">
										<div class="absolute inset-0 flex items-center">
											<div class="w-full border-t border-gray-200"></div>
										</div>
										<div class="relative flex justify-center">
											<span class="bg-gray-50 px-3 text-sm text-gray-400">OR</span>
										</div>
									</div>

									<div>
										<div class="relative mb-4">
											<?php if ($extended_license_is_sale): ?>
												<div class="relative mb-1">
													<span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full absolute -top-3 right-0">SALE</span>
													<span class="text-lg line-through text-gray-400"><?php echo esc_html($original_price_extended_license_display); ?></span>
												</div>
												<div class="flex items-center justify-center gap-x-2">
													<span class="text-base font-bold tracking-wide text-gray-800"><?php echo esc_html($theme_default_price_extended_display); ?> USD</span>
													<button type="button" id="show-license-types-help" class="text-wp-blue focus:outline-none focus:ring-0 focus:ring-offset-0 focus-visible:outline-none focus-visible:ring-0 cursor-pointer">
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
															<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008Z" />
														</svg>
													</button>
												</div>
											<?php else: ?>
												<div class="flex items-center justify-center gap-x-2">
													<span class="text-base font-bold tracking-wide text-gray-800"><?php echo esc_html($theme_default_price_extended_display); ?> USD</span>
													<button type="button" id="show-license-types-help" class="text-wp-blue focus:outline-none focus:ring-0 focus:ring-offset-0 focus-visible:outline-none focus-visible:ring-0 cursor-pointer">
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
															<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008Z" />
														</svg>
													</button>
												</div>
											<?php endif; ?>
										</div>
										<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="block w-full rounded-md bg-white px-4 py-2.5 text-center text-base font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 hover:text-gray-900 transition duration-150 ease-in-out cursor-pointer">Get Extended License</a>
									</div>

									<p class="mt-6 text-xs/5 text-gray-500 flex items-center justify-start">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5 text-gray-500">
											<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
										</svg>
										Quality checked by Envato
									</p>
									<p class="mt-2 text-xs/5 text-gray-500 flex items-center justify-start">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5 text-gray-500">
											<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
										</svg>
										Power Elite Author
									</p>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- ================================================= -->
		<!-- Pricing -->
		<!-- ================================================= -->

		<?php if ($is_license_active): ?>
			<div class="relative isolate pb-24 sm:pb-32">
				<div class="absolute inset-x-0 -top-3 -z-10 transform-gpu overflow-hidden px-36 blur-3xl" aria-hidden="true">
					<div class="mx-auto aspect-1155/678 w-[72.1875rem] bg-linear-to-tr from-[#80a2ff] to-[#89a7fc] opacity-30" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
				</div>
				<div class="mx-auto max-w-4xl text-center">
					<h2 id="license-options" class="text-base/7 font-semibold text-wp-blue">Licensing Options</h2>
					<p class="mt-2 text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl">Find the Perfect License for You</p>
				</div>
				<p class="mx-auto mt-6 max-w-2xl text-center text-lg font-medium text-pretty text-gray-600 sm:text-xl/8">Pick the license tier that suits you best! Each option comes with unique features and support levels, so you can get exactly what you need to make your project a success.</p>

				<div class="mx-auto mt-16 grid max-w-lg grid-cols-1 items-center gap-y-6 sm:mt-20 sm:gap-y-0 lg:max-w-7xl lg:grid-cols-3">
					<!-- Regular License -->
					<div class="rounded-3xl rounded-t-3xl bg-white/60 p-8 ring-1 ring-gray-900/10 sm:mx-8 sm:rounded-b-none sm:p-10 lg:mx-0 lg:rounded-tr-none lg:rounded-bl-3xl">
						<h3 id="tier-regular" class="text-base/7 font-semibold text-wp-blue flex items-center">
							Regular License
							<?php if ($regular_license_is_sale): ?>
								<span class="ml-2 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">SALE</span>
							<?php endif; ?>
						</h3>
						<p class="mt-4 relative">
						<div class="flex flex-col">
							<div class="flex items-baseline gap-x-2">
								<?php if ($regular_license_is_sale): ?>
									<span class="text-5xl line-through text-gray-400"><?php echo esc_html($original_price_regular_license_display); ?></span>
								<?php endif; ?>
								<span class="text-5xl font-semibold tracking-tight text-gray-900"><?php echo esc_html($theme_default_price_display); ?></span>
							</div>
							<span class="text-base text-gray-500 mt-1">one-time</span>
						</div>
						</p>
						<div class="mt-6 flex gap-2 items-center">
							<svg class="h-6 w-6 text-green-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
								<path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
							</svg>
							<span class="text-sm font-medium text-gray-900">You already own this license</span>
						</div>
						<p class="mt-6 text-base/7 text-gray-600">Perfect for most websites and client projects with a lifetime license.</p>
						<ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-600 sm:mt-10">
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Single site usage
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Personal or client projects
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Manual compatibility updates
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								6 months support included
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
								</svg>
								<span class="text-gray-600">Built-in critical security updates</span>
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
								</svg>
								<span class="text-gray-600">Built-in priority bug fixes</span>
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
								</svg>
								<span class="text-gray-600">Dedicated developer assigned for assistance</span>
							</li>
						</ul>
					</div>

					<!-- Professional Upgrade -->
					<div id="professional-upgrade-section" class="relative rounded-3xl bg-gray-900 p-8 ring-1 shadow-2xl ring-gray-900/10 sm:p-10">
						<div class="absolute -top-4 inset-x-0">
							<div class="flex justify-center">
								<span class="inline-flex rounded-full bg-yellow-300 px-5 py-1.5 text-sm font-semibold text-gray-900 shadow-md ring-2 ring-gray-900">Most Popular</span>
							</div>
						</div>
						<h3 id="tier-professional" class="text-base/7 font-semibold text-wp-blue-lighter flex items-center">
							Professional Upgrade
							<?php if ($professional_license_is_sale): ?>
								<span class="ml-2 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">SALE</span>
							<?php endif; ?>
						</h3>
						
						<p class="mt-4 relative">
						<div class="flex flex-col">
							<div class="flex items-baseline gap-x-2">
								<?php if ($professional_license_is_sale): ?>
									<span class="text-5xl line-through text-gray-400"><?php echo esc_html($original_professional_price_display); ?></span>
								<?php endif; ?>
								<span class="text-5xl font-semibold tracking-tight text-white"><?php echo esc_html($professional_price_display); ?></span>
							</div>
							<span class="text-base text-gray-300 mt-1"><?php echo esc_html($professional_price_text); ?></span>
						</div>
						</p>

						<?php if ($is_support_active): ?>
							<div class="mt-6 flex gap-2 items-center">
								<svg class="h-6 w-6 text-green-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
							</svg>
							<span class="text-sm font-medium text-gray-100">Active until <?php echo esc_html($support_expiration); ?></span>
							</div>

							<?php if (isset($expires_soon) && $expires_soon): ?>
								<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="mt-4 inline-flex items-center justify-center rounded-md bg-amber-500 px-3.5 py-2 text-sm font-semibold text-gray-900 shadow hover:bg-amber-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-1.5">
										<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
									</svg>
									Expires soon - Extend now!
								</a>
							<?php endif; ?>
						<?php endif; ?>

						<p class="mt-6 text-base/7 text-gray-300">Enjoy exclusive benefits: Stay worry-free with automatic updates, priority bug fixes, and expert support whenever you need it.<?php if (!$is_support_active): ?> Renew today for a smoother, hassle-free experience.<?php endif; ?></p>

						<?php if (!$is_support_active): ?>
							<div class="mt-6 inline-flex items-center px-3 py-1.5 rounded-full bg-amber-300 text-amber-900 text-sm font-medium">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-1.5">
									<path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.75.75 0 00.674 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
								</svg>
								<span>Insane value, exclusive perks!</span>
							</div>
						<?php endif; ?>

						<ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-300 sm:mt-10">
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue-lighter" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Built-in automatic updates or manual updates
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue-lighter" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Built-in critical security updates
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue-lighter" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Automatic compatibility updates
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue-lighter" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Built-in priority bug fixes
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue-lighter" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Expert assistance from a dedicated developer
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue-lighter" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								For personal or client projects
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue-lighter" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Limited spots available
							</li>
						</ul>
						<?php if (!$is_support_active): ?>
							<!-- Upgrade eligibility confirmation -->
							<div class="mt-8 text-center">
								<div class="flex items-center justify-center mb-2">
									<svg class="h-5 w-5 text-green-400 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
									</svg>
									<span class="text-sm font-medium text-white">Congrats! Your license is eligible for renewal.</span>
								</div>
								<p class="text-xs text-green-300 px-8">Secure ongoing updates and premium support by renewing your license now.</p>
							</div>
							<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" aria-describedby="tier-professional" class="mt-6 block rounded-md bg-wp-blue px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-wp-blue/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wp-blue sm:mt-8">Upgrade Now – Renew Support</a>
							<p class="mt-2 text-xs text-center text-amber-600 font-medium px-4">Grab this amazing offer while you can! Availability and price may change in <span id="countdown-timer"></span> minutes. Don't miss out!</p>
						<?php endif; ?>
					</div>

					<!-- Extended License -->
					<div class="rounded-3xl rounded-b-3xl bg-white/60 p-8 ring-1 ring-gray-900/10 sm:mx-8 sm:rounded-t-none sm:p-10 lg:mx-0 lg:rounded-tr-3xl lg:rounded-bl-none">
						<h3 id="tier-extended" class="text-base/7 font-semibold text-wp-blue flex items-center">
							Extended License
							<?php if ($extended_license_is_sale): ?>
								<span class="ml-2 bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">SALE</span>
							<?php endif; ?>
						</h3>
						<p class="mt-4 relative">
						<div class="flex flex-col">
							<div class="flex items-baseline gap-x-2">
								<?php if ($extended_license_is_sale): ?>
									<span class="text-2xl line-through text-gray-400"><?php echo esc_html($original_price_extended_license_display); ?></span>
								<?php endif; ?>
								<span class="text-5xl font-semibold tracking-tight text-gray-900"><?php echo esc_html($theme_default_price_extended_display); ?></span>
							</div>
							<span class="text-base text-gray-500 mt-1">one-time + support plan</span>
						</div>
						</p>
						<p class="mt-6 text-base/7 text-gray-600">For commercial applications and products for resale with comprehensive rights.</p>
						<ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-600 sm:mt-10">
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Use in end products for sale
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Commercial applications
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								SaaS applications
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								6 months premium support
							</li>
						</ul>
						<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" aria-describedby="tier-extended" class="mt-8 block rounded-md px-3.5 py-2.5 text-center text-sm font-semibold text-wp-blue ring-1 ring-wp-blue/20 ring-inset hover:ring-wp-blue/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wp-blue sm:mt-10">Get Extended License</a>
						<p class="mt-2 text-xs text-center text-gray-500">Manually select "Extended License" from the dropdown in the marketplace.</p>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- ================================================= -->
		<!-- License Types Help Modal -->
		<!-- ================================================= -->

		<div id="license-types-help-modal" class="relative z-10 hidden" aria-labelledby="license-types-modal-title" role="dialog" aria-modal="true">
			<div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
			<div class="fixed inset-0 z-10 w-screen overflow-y-auto">
				<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
					<div class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
						<div class="sm:flex sm:items-start">
							<div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-wp-blue/10 sm:mx-0 sm:size-10">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-wp-blue">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
								</svg>
							</div>
							<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
								<h3 class="text-base font-semibold text-gray-900" id="license-types-modal-title">Envato License Types</h3>
								<div class="mt-2">
									<p class="text-sm text-gray-500 mb-4">There are two types of licenses available for our theme:</p>

									<div class="mb-4">
										<h4 class="text-sm font-medium text-gray-900 flex items-center">
											Regular License
											<?php if ($regular_license_is_sale): ?>
												<span class="ml-1 bg-red-600 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">SALE</span>
												<span class="ml-2">
													<span class="text-lg line-through text-gray-500 mr-1"><?php echo esc_html($original_price_regular_license_display); ?></span>
													<span class="text-lg font-semibold text-gray-900"><?php echo esc_html($theme_default_price_display); ?></span>
												</span>
											<?php else: ?>
												<span class="ml-1">(<?php echo esc_html($theme_default_price_display); ?>)</span>
											<?php endif; ?>
										</h4>
										<p class="text-sm text-gray-600 mt-1">Perfect for most websites, the Regular License includes:</p>
										<ul class="text-sm text-gray-600 list-disc ml-5 mt-2 space-y-1">
											<li>Use on a single end product</li>
											<li>Use in a personal project or on behalf of a client</li>
											<li>Professional Plan for 6 or 12 months </li>
											<li>All theme features and updates</li>
										</ul>
									</div>

									<div class="mb-4">
										<h4 class="text-sm font-medium text-gray-900 flex items-center">
											Professional Upgrade
											<?php if ($professional_license_is_sale): ?>
												<span class="ml-1 bg-red-600 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">SALE</span>
												<span class="ml-2">
													<span class="text-lg line-through text-gray-500 mr-1"><?php echo esc_html($original_professional_price_display); ?></span>
													<span class="text-lg font-semibold text-gray-900"><?php echo esc_html($professional_price_display); ?></span>
												</span>
										</h4>
										<p class="text-sm text-gray-600"><?php echo esc_html($professional_price_text); ?></p>

										<p class="text-sm text-gray-600 mt-1">The Professional Upgrade is our premium support and updates plan:</p>
									<?php else: ?>
										<span class="ml-1">(<?php echo esc_html($professional_price_display); ?>)</span>
										</h4>
										<p class="text-sm text-gray-600"><?php echo esc_html($professional_price_text); ?></p>
										<p class="text-sm text-gray-600 mt-1">The Professional Upgrade is our premium support and updates plan:</p>
									<?php endif; ?>
									<ul class="text-sm text-gray-600 list-disc ml-5 mt-2 space-y-1">
										<li>Automatic updates and security fixes</li>
										<li>Expert support from our dedicated team</li>
										<li>Priority bug fixes and new features</li>
										<li>All premium add-ons included</li>
									</ul>
									</div>

									<div>
										<h4 class="text-sm font-medium text-gray-900 flex items-center">
											Extended License
											<?php if ($extended_license_is_sale): ?>
												<span class="ml-1 bg-red-600 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">SALE</span>
												<span class="ml-2">
													<span class="text-lg line-through text-gray-500 mr-1"><?php echo esc_html($original_price_extended_license_display); ?></span>
													<span class="text-lg font-semibold text-gray-900"><?php echo esc_html($theme_default_price_extended_display); ?></span>
												</span>
											<?php else: ?>
												<span class="ml-1">(<?php echo esc_html($theme_default_price_extended_display); ?>)</span>
											<?php endif; ?>
										</h4>
										<p class="text-sm text-gray-600 mt-1">For commercial applications where you charge users, the Extended License includes:</p>
										<ul class="text-sm text-gray-600 list-disc ml-5 mt-2 space-y-1">
											<li>Everything in the Regular License</li>
											<li>Use in an end product that's sold to multiple customers</li>
											<li>Use in a commercial product where end users are charged</li>
											<li>Ideal for SaaS applications and products for resale</li>
										</ul>
									</div>

									<p class="mt-4 text-sm text-gray-500">For more detailed information, please refer to the <a href="https://1.envato.market/theme-license" target="_blank" class="text-wp-blue hover:underline">Envato License terms</a>.</p>
								</div>
							</div>
						</div>
						<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
							<button type="button" id="close-license-types-help" class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:ring-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 sm:ml-3 sm:w-auto transition duration-150 ease-in-out cursor-pointer">Close</button>
						</div>
					</div>
				</div>
			</div>
		</div>

<?php
		// Content End

		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-end.php';

		// Enqueue the license validator script
		wp_enqueue_script('getbowtied-license-validator', $base_paths['url'] . '/dashboard/js/license-validator.js', array('jquery'), $theme_version_gbt_dash, true);

		// Enqueue the license help modal script
		wp_enqueue_script('getbowtied-license-modal', $base_paths['url'] . '/dashboard/js/license-modal.js', array('jquery'), $theme_version_gbt_dash, true);

		// Enqueue the Professional Plan help modal script
		wp_enqueue_script('getbowtied-stress-free-modal', $base_paths['url'] . '/dashboard/js/stress-free-modal.js', array('jquery'), $theme_version_gbt_dash, true);

		// Enqueue the license types help modal script
		wp_enqueue_script('getbowtied-license-types-modal', $base_paths['url'] . '/dashboard/js/license-types-modal.js', array('jquery'), $theme_version_gbt_dash, true);

		// Enqueue the smooth scroll script
		wp_enqueue_script('getbowtied-smooth-scroll', $base_paths['url'] . '/dashboard/js/smooth-scroll.js', array('jquery'), $theme_version_gbt_dash, true);

		// Enqueue the countdown timer script
		wp_enqueue_script('getbowtied-countdown-timer', $base_paths['url'] . '/dashboard/js/countdown-timer.js', array('jquery'), $theme_version_gbt_dash, true);
	}
}
