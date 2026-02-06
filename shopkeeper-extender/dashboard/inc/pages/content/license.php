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
		<div class="mt-3 rounded-md bg-[var(--color-wp-green)]/10 p-4 border border-[var(--color-wp-green)]/20">
			<div class="flex">
				<div class="flex-shrink-0">
					<svg class="h-5 w-5 text-[var(--color-wp-green)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
						<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
					</svg>
				</div>
				<div class="ml-3">
					<h3 class="text-sm font-medium text-[var(--color-wp-green)]">Success</h3>
					<div class="mt-1 text-sm text-[var(--color-wp-green)]">
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
		<div class="mb-6 p-4 rounded-lg border border-[var(--color-wp-red)]/20 bg-[var(--color-wp-red)]/10">
			<div class="flex">
				<div class="flex-shrink-0">
					<svg class="h-5 w-5 text-[var(--color-wp-red)]" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
					</svg>
				</div>
				<div class="ml-3">
					<h3 class="text-sm leading-5 font-medium text-[var(--color-wp-red)]">
						<?php echo esc_html($title); ?>
					</h3>
					<div class="mt-1 text-sm leading-5 text-[var(--color-wp-red)]">
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
						<div class="mt-3 text-sm leading-5 text-[var(--color-wp-red)]">
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

		// Check if theme update is available
		$update_available = false;
		$update_version = '';
		$updates = get_site_transient('update_themes');
		if ($updates && isset($updates->response[$theme_slug_gbt_dash])) {
			$update_available = true;
			$update_version = isset($updates->response[$theme_slug_gbt_dash]['new_version']) 
				? $updates->response[$theme_slug_gbt_dash]['new_version'] 
				: '';
		}

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

		// Get support expiration dates from centralized License Manager
		$envato_support_expiration = $stored_options['envato_support_expiration'] ?? '';
		$bonus_updates_expiration = $stored_options['bonus_updates_expiration'] ?? '';
		$has_bonus_updates = $stored_options['has_bonus_updates'] ?? false;
		$envato_support_expired = $stored_options['envato_support_expired'] ?? false;
		$bonus_updates_expired = $stored_options['bonus_updates_expired'] ?? false;

		// Get the final support expiration date (same for both Subscription until and Built-in Updates Until)
		$final_support_expiration_date = $license_manager->get_built_in_updates_until_date();

		// Get technical support status using the new functions
		$technical_support_active = $license_manager->is_technical_support_active();
		$technical_support_expired = $license_manager->is_technical_support_expired();
		$support_until_date = $license_manager->get_support_until_date();
		$built_in_updates_until_date = $license_manager->get_built_in_updates_until_date();
		
		// Get special license data DIRECTLY from API for display purposes (ignoring low star reviews)
		// This shows the actual special benefits data even if they are functionally ignored
		$display_has_bonus_updates = false;
		$display_has_bonus_support = false;
		$display_bonus_updates_expiration = '';
		$display_bonus_support_expiration = '';
		$display_bonus_updates_expired = false;
		$display_bonus_support_expired = false;
		
		if (!empty($license_key) && class_exists('GBT_Special_License_Manager')) {
				$special_license_manager = GBT_Special_License_Manager::get_instance();
				$special_license_data = $special_license_manager->get_special_license_data($license_key);
				
			if ($special_license_data) {
				// Get bonus updates data for display
				if (isset($special_license_data['data']['bonus_updates']['until_date']) && 
					!empty($special_license_data['data']['bonus_updates']['until_date'])) {
					
					$bonus_updates_date = $special_license_data['data']['bonus_updates']['until_date'];
					$bonus_timestamp = is_numeric($bonus_updates_date) ? 
						(int)$bonus_updates_date : 
						strtotime($bonus_updates_date);
					
					$envato_timestamp = strtotime($envato_support_expiration);
					
					// Show bonus updates if it extends beyond Envato
					if ($bonus_timestamp !== false && $bonus_timestamp > $envato_timestamp) {
						$display_has_bonus_updates = true;
						$display_bonus_updates_expiration = date_i18n(get_option('date_format'), $bonus_timestamp);
						$display_bonus_updates_expired = (time() > $bonus_timestamp);
					}
				}
				
				// Get bonus support data for display
				if (isset($special_license_data['data']['bonus_support']['until_date']) && 
					!empty($special_license_data['data']['bonus_support']['until_date'])) {
					
					$bonus_support_date = $special_license_data['data']['bonus_support']['until_date'];
					$bonus_support_timestamp = is_numeric($bonus_support_date) ? 
						(int)$bonus_support_date : 
						strtotime($bonus_support_date);
					
					$envato_timestamp = strtotime($envato_support_expiration);
					
					// Show bonus support if it extends beyond Envato
					if ($bonus_support_timestamp !== false && $bonus_support_timestamp > $envato_timestamp) {
						$display_has_bonus_support = true;
						$display_bonus_support_expiration = date_i18n(get_option('date_format'), $bonus_support_timestamp);
						$display_bonus_support_expired = (time() > $bonus_support_timestamp);
					}
				}
			}
		}
		
		// For display: always use the actual special benefits data (not filtered by low star reviews)
		$has_bonus_updates = $display_has_bonus_updates;
		$has_bonus_support = $display_has_bonus_support;
		$bonus_updates_expiration = $display_bonus_updates_expiration;
		$bonus_updates_expired = $display_bonus_updates_expired;
		
		// Use display dates for the "Special benefits from author" section
		if ($display_has_bonus_support) {
			$support_until_date = $display_bonus_support_expiration;
			$technical_support_expired = $display_bonus_support_expired;
		}

		// Format support expiration date if available (this is the final date after special license override)
		$support_expiration = isset($support_expiration_date) && !empty($support_expiration_date) ?
			date_i18n(get_option('date_format'), $support_expiration_date) : (isset($license_info['supported_until']) ? $license_info['supported_until'] : '');

		// Check for buyer reviews
		$has_reviews = false;
		$review_count = 0;
		$reviews_checked = false;
		$review_details = [];
		$buyer_username_for_reviews = '';
		$api_message = '';
		$api_check_successful = false;
		$has_low_star_reviews = false;
		$low_star_reviews = [];
		$should_disable_special_benefits = false;
		$has_outdated_rating = false;
		if (!empty($license_key) && class_exists('GBT_Buyer_Review_Checker')) {
			$review_checker = GBT_Buyer_Review_Checker::get_instance();
			$reviews_checked = true;
			$api_check_successful = $review_checker->is_check_successful($license_key);
			$has_reviews = $review_checker->has_reviews($license_key);
			$review_count = $review_checker->get_review_count($license_key);
			$buyer_username_for_reviews = $review_checker->get_buyer_username($license_key);
			$api_message = $review_checker->get_api_message($license_key);
			$review_details = $review_checker->get_reviews($license_key);
			$has_low_star_reviews = $review_checker->has_low_star_reviews($license_key);
			$should_disable_special_benefits = $review_checker->should_disable_special_benefits($license_key);
			$has_outdated_rating = method_exists($review_checker, 'has_outdated_rating') ? $review_checker->has_outdated_rating($license_key) : false;
			
			// Filter low-star reviews (1-3 stars) for modal display
			if (!empty($review_details)) {
				foreach ($review_details as $review) {
					$rating = (int)($review['rating'] ?? 0);
					if ($rating >= 1 && $rating <= 3) {
						$low_star_reviews[] = $review;
					}
				}
			}
		}

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
			
			// Check if this is a reload (same license key being resubmitted while already active)
			$is_reload = !empty($new_license_key) && 
						 $new_license_key === $license_key && 
						 $license_manager->is_license_active();

			// Process license submission
			$result = $license_manager->process_license_submission(
				$new_license_key,
				$theme_slug_gbt_dash,
				$theme_marketplace_id
			);
			
			// If this is a reload and the result is successful, update the message
			if ($is_reload && $result['success']) {
				$result['message'] = 'License data has been refreshed successfully.';
			}

			// Store the result in a transient to display after redirect
			set_transient('gbt_license_result', $result, 60);

			// Redirect to the same page to avoid form resubmission
			wp_safe_redirect(add_query_arg('license_updated', '1', esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))));
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
		
		// Include badges component
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/badges.php';

		// Main content
	?>

		<!-- ================================================= -->
		<!-- License -->
		<!-- ================================================= -->

		<div id="license-area" aria-hidden="true"></div>
		<div class="pb-24 sm:pb-32">
			<div class="mx-auto max-w-7xl">

				<div class="mx-auto mt-16 max-w-2xl rounded-3xl ring-1 bg-[var(--color-wp-gray-dark)] ring-[var(--color-wp-gray-dark)]/20 shadow-2xl sm:mt-20 lg:mx-0 lg:flex lg:max-w-none relative overflow-hidden">
					<!-- Blue Nebula Background with Top Glow -->
					<div class="absolute inset-0 z-0" style="background: radial-gradient(ellipse 80% 60% at 0% 0%, rgba(34, 113, 177, 0.5), transparent 70%), var(--color-wp-gray-dark);"></div>
					<!-- Crosshatch Texture Pattern with Diagonal Fade -->
					<div class="absolute inset-0 z-0 pointer-events-none" style="background-image: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0) 100%), repeating-linear-gradient(22.5deg, transparent, transparent 2px, rgba(75, 85, 99, 0.06) 2px, rgba(75, 85, 99, 0.06) 3px, transparent 3px, transparent 8px), repeating-linear-gradient(67.5deg, transparent, transparent 2px, rgba(107, 114, 128, 0.05) 2px, rgba(107, 114, 128, 0.05) 3px, transparent 3px, transparent 8px), repeating-linear-gradient(112.5deg, transparent, transparent 2px, rgba(55, 65, 81, 0.04) 2px, rgba(55, 65, 81, 0.04) 3px, transparent 3px, transparent 8px), repeating-linear-gradient(157.5deg, transparent, transparent 2px, rgba(31, 41, 55, 0.03) 2px, rgba(31, 41, 55, 0.03) 3px, transparent 3px, transparent 8px), linear-gradient(315deg, rgba(0,0,0,0.8) 30%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0) 70%);"></div>
						<div class="p-8 sm:p-10 lg:flex-auto flex flex-col relative z-10">
						<!-- Version and Update Badges -->
						<?php gbt_display_version_badge(); ?>
						<h3 class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-white sm:text-5xl"><?php echo esc_html($theme_name_gbt_dash); ?></h3>
						<p class="mt-5 text-base/7 text-gray-400">
							<?php if ($is_license_active): ?>
								<?php if ($is_support_active): ?>
									Your theme is now fully unlocked with premium features, built-in updates, and priority support.
								<?php else: ?>
									Your lifetime license is valid, but <span class="text-[var(--color-wp-red)] font-medium">your subscription has expired</span>. Renew your subscription to restore access to built-in updates, security patches, and priority support. <a href="javascript:void(0)" class="show-license-types-help text-wp-blue hover:underline whitespace-nowrap">📋 Learn more</a>
								<?php endif; ?>
							<?php else: ?>
								Activate your license to unlock premium features, built-in updates, and priority support.
							<?php endif; ?>
						</p>
						<form method="post" action="" class="space-y-6 mt-12">
							<?php wp_nonce_field('getbowtied_license_nonce'); ?>

							<div class="space-y-5 max-w-2xl">
								<div class="mb-1">
									<!-- License key label with key icon and inline help link -->
									<div class="flex items-center justify-between mb-2">
										<div class="flex items-center gap-2">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-400">
												<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
											</svg>
											<label for="license_key" class="text-base font-medium text-white">Purchase code:</label>
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
											<div class="bg-[var(--color-wp-gray-light)] border border-[var(--color-wp-gray-light)] px-4 py-3 rounded-md text-white flex items-center gap-2 w-full">
												<span class="font-medium"><?php echo esc_html(substr($license_key, 0, 4)) . '-****-****-****-' . esc_html(substr($license_key, -4)); ?></span>
												<span class="inline-flex items-center ml-auto font-medium text-[var(--color-wp-green)]">
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
												class="block w-full px-4 py-3 text-white bg-[var(--color-wp-gray-light)] border border-[var(--color-wp-gray-light)] rounded-md focus:outline-none focus:ring-1 focus:ring-wp-blue focus:border-wp-blue"
												value="<?php echo esc_attr($license_key); ?>"
												placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
												style="animation: license-field-shine 2s ease-in-out infinite;" />
										</div>
										
										<style>
										@keyframes license-field-shine {
											0% { 
												border-color: var(--color-wp-gray-light);
												box-shadow: 0 0 0 0 rgba(34, 113, 177, 0);
											}
											50% { 
												border-color: var(--color-wp-blue);
												box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.3);
											}
											100% { 
												border-color: var(--color-wp-gray-light);
												box-shadow: 0 0 0 0 rgba(34, 113, 177, 0);
											}
										}
										</style>
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
										id="license-submit-btn"
										class="flex items-center justify-center w-auto rounded-md <?php echo $license_status === 'active'
																										? 'bg-[var(--color-wp-blue)] text-white ring-1 ring-[var(--color-wp-blue)] hover:bg-[var(--color-wp-blue-darker)] focus:outline-none focus:ring-2 focus:ring-[var(--color-wp-blue)] focus:ring-offset-2'
																										: 'bg-[var(--color-wp-blue)] text-white shadow-sm hover:bg-[var(--color-wp-blue-darker)] focus:outline-none focus:ring-2 focus:ring-[var(--color-wp-blue)] focus:ring-offset-2'; ?> px-4 py-2.5 text-base font-medium transition duration-150 ease-in-out cursor-pointer">
										<?php if ($license_status === 'active'): ?>
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-1.5 license-refresh-icon">
												<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
											</svg>
										<?php else: ?>
											<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
											</svg>
										<?php endif; ?>
										<?php echo $license_status === 'active' ? 'Reload your license data' : 'Activate your license on this website'; ?>
									</button>

									<?php if ($license_status !== 'active'): ?>
										<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="flex items-center justify-center w-auto rounded-md bg-[var(--color-wp-gray-lighter)] text-white/80 ring-1 ring-[var(--color-wp-gray-lighter)] hover:bg-[var(--color-wp-gray-light)] focus:outline-none focus:ring-2 focus:ring-[var(--color-wp-gray-lighter)] focus:ring-offset-2 px-4 py-2.5 text-base font-normal transition duration-150 ease-in-out cursor-pointer">
											Don't have a license yet? Get one here →
										</a>
									<?php endif; ?>

									<?php if ($license_status === 'active'): ?>
										<button type="submit"
											name="save_license"
											class="flex items-center justify-center w-auto rounded-md bg-[var(--color-wp-gray-lighter)] px-4 py-2.5 text-base font-normal text-white/80 ring-1 ring-[var(--color-wp-gray-lighter)] hover:bg-[var(--color-wp-gray-light)] focus:outline-none focus:ring-2 focus:ring-[var(--color-wp-gray-lighter)] focus:ring-offset-2 transition duration-150 ease-in-out cursor-pointer"
											onclick="const licenseKey = document.getElementById('license_key'); licenseKey.value = ''; jQuery(licenseKey).data('has-value', true);">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-1.5">
												<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
											</svg>
											<?php echo 'Deactivate your license on this website'; ?>
										</button>
									<?php endif; ?>
								</div>
								
							</div>

						</form>
						
						<!-- Having trouble with activation? -->
						<div class="mt-auto">
							<div class="border-t border-gray-600 pt-6 mt-6">
								<h4 class="text-base font-medium text-white mb-1">Need help with activation or your license?</h4>
								<p class="text-sm text-gray-400">
									Contact us through <a href="https://1.envato.market/getbowtied-profile-contact" target="_blank" class="text-[var(--color-wp-blue)] hover:underline">our author contact page</a> for help with activation or licensing.
								</p>
							</div>
						</div>
					</div>
					<div class="-mt-2 p-2 lg:mt-0 lg:w-full lg:max-w-md lg:shrink-0 text-sm/6 relative z-10">
						<?php if ($is_license_active): ?>
							<!-- License Information for activated license -->
							<div class="rounded-2xl bg-[var(--color-wp-gray-light)] py-10 px-10 text-center ring-1 ring-[var(--color-wp-gray-light)] h-full flex flex-col">
								<div class="mx-auto w-full">
									<div class="flex justify-center items-center mb-2">
										<svg class="w-8 h-8 text-[var(--color-wp-green)] mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
										</svg>
										<h3 class="text-3xl font-semibold tracking-tight text-white">
											Lifetime regular license
										</h3>
									</div>

									<?php
									// Check if support will expire soon
									$expires_soon = $license_manager->is_support_expiring_soon();
									$days_remaining = $license_manager->get_support_days_remaining();
								?>

									<div class="mt-6 space-y-0 text-left">
										<!-- License details -->
										<div class="border-t border-gray-600 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-400">License status</span>
												<span class="font-medium <?php echo $license_status === 'active' ? 'text-[var(--color-wp-green)]' : 'text-[var(--color-wp-red)]'; ?> flex items-center">
													<?php if ($license_status === 'active'): ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
														</svg>
														Valid
													<?php else: ?>
														<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
															<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
														</svg>
														<?php echo esc_html(ucfirst($license_status)); ?>
													<?php endif; ?>
												</span>
											</div>
										</div>

										<div class="border-t border-gray-600 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-400">License username</span>
												<span class="font-medium text-white">
													<?php echo !empty($license_info['buyer_username']) ? esc_html($license_info['buyer_username']) : '—'; ?>
												</span>
											</div>
										</div>

										<div class="border-t border-gray-600 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-400">Purchase date</span>
												<span class="font-medium text-white">
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

										<div class="border-t border-gray-600 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-400">Subscription until</span>
												<span class="font-medium <?php echo $envato_support_expired ? 'text-[var(--color-wp-red)]' : 'text-white'; ?>">
													<?php
													// Format the Envato support expiration date
													if (!empty($envato_support_expiration)) {
														$timestamp = strtotime($envato_support_expiration);
														if ($timestamp) {
															echo esc_html(date_i18n(get_option('date_format'), $timestamp));
														} else {
															echo esc_html($envato_support_expiration);
														}
													}
													?>
												</span>
											</div>
										</div>

										<div class="border-t border-gray-600 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-400">Subscription status</span>
												<span class="font-medium <?php echo $envato_support_expired ? 'text-[var(--color-wp-red)]' : 'text-[var(--color-wp-green)]'; ?> flex items-center">
													<?php if (!$envato_support_expired): ?>
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

										<div class="border-t border-gray-600 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-400 flex items-center">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 text-gray-400">
														<path stroke-linecap="round" stroke-linejoin="round" d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
													</svg>
													Built-in updates status
												</span>
												<span class="font-medium <?php echo $envato_support_expired ? 'text-[var(--color-wp-red)]' : 'text-[var(--color-wp-green)]'; ?> flex items-center">
													<?php if (!$envato_support_expired): ?>
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

										<div class="border-t border-gray-600 py-3">
											<div class="flex items-center justify-between">
												<span class="text-gray-400 flex items-center">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 text-gray-400">
														<path stroke-linecap="round" stroke-linejoin="round" d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
													</svg>
													Support status
												</span>
												<span class="font-medium <?php echo $envato_support_expired ? 'text-[var(--color-wp-red)]' : 'text-[var(--color-wp-green)]'; ?> flex items-center">
													<?php if (!$envato_support_expired): ?>
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

										<!-- Bonuses Section -->
										<?php if ($has_bonus_updates || $has_bonus_support): ?>
										<div class="mt-0 -mx-10 px-10 py-6 relative overflow-hidden <?php echo $should_disable_special_benefits ? 'opacity-50 grayscale pointer-events-none' : ''; ?>">
											<!-- Background overlay -->
											<div class="absolute inset-0 bg-[var(--color-wp-gray-dark)]"></div>
											<!-- Left accent border with glow effect -->
											<div class="absolute left-0 top-0 bottom-0 w-1 <?php echo $should_disable_special_benefits ? 'bg-gray-600' : 'bg-gradient-to-b from-[var(--color-wp-yellow)]/80 via-[var(--color-wp-yellow)] to-[var(--color-wp-yellow)]/80 shadow-[0_0_15px_rgba(219,166,23,0.4)]'; ?>"></div>
											
											<div class="relative">
												<div class="mb-5">
													<div class="flex items-center mb-2">
														<div class="flex items-center justify-center w-6 h-6 rounded-md <?php echo $should_disable_special_benefits ? 'bg-gray-600/20 ring-1 ring-gray-600/30' : 'bg-[var(--color-wp-yellow)]/20 ring-1 ring-[var(--color-wp-yellow)]/30'; ?> mr-2.5 flex-shrink-0">
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-[var(--color-wp-yellow)]'; ?>">
																<circle cx="12" cy="12" r="10"/>
																<circle cx="9" cy="9" r="1" fill="currentColor"/>
																<circle cx="15" cy="9" r="1" fill="currentColor"/>
																<path d="M8 15 Q12 18 16 15" stroke-linecap="round"/>
															</svg>
														</div>
														<h4 class="<?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-[var(--color-wp-yellow)]'; ?> font-bold text-base uppercase tracking-wide">Special benefits from author</h4>
													</div>
													<p class="text-xs pl-8.5 <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-white/50'; ?>">
														<?php
														$benefits = [];
														if ($has_bonus_updates) {
															$benefits[] = $bonus_updates_expired ? 'built-in updates (expired)' : 'built-in updates';
														}
														if ($has_bonus_support) {
															$benefits[] = $technical_support_expired ? 'technical support (expired)' : 'technical support';
														}
														
														$benefit_count = count($benefits);
														$benefit_singular_plural = $benefit_count > 1 ? 'special benefits' : 'a special benefit';
														echo 'You have access to extended ' . esc_html(implode(' & ', $benefits)) . ' beyond your original subscription period as ' . esc_html($benefit_singular_plural) . ' from the author.';
														?>
													</p>
												</div>
											
												<?php if ($has_bonus_updates): ?>
										<div class="border-t border-gray-600 py-3">
											<div class="flex items-center justify-between">
														<span class="flex items-center <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-gray-400'; ?>">
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-gray-400'; ?>">
															<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5" />
														</svg>
														Built-in updates until
													</span>
													<span class="font-medium <?php echo $should_disable_special_benefits ? 'text-gray-400' : ($bonus_updates_expired ? 'text-[var(--color-wp-red)]' : 'text-[var(--color-wp-green)]'); ?>">
														<?php echo esc_html($bonus_updates_expiration); ?>
													</span>
													</div>
												</div>

												<div class="border-t border-gray-600 py-3">
													<div class="flex items-center justify-between">
														<span class="flex items-center <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-gray-400'; ?>">
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-gray-400'; ?>">
																<path stroke-linecap="round" stroke-linejoin="round" d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
															</svg>
															Built-in updates status
														</span>
														<span class="font-medium <?php echo $should_disable_special_benefits ? 'text-gray-400' : ($bonus_updates_expired ? 'text-[var(--color-wp-red)]' : 'text-[var(--color-wp-green)]'); ?> flex items-center">
															<?php if (!$bonus_updates_expired): ?>
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
												<?php endif; ?>

												<?php if ($has_bonus_support): ?>
												<div class="border-t border-gray-600 py-3">
													<div class="flex items-center justify-between">
														<span class="flex items-center <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-gray-400'; ?>">
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-gray-400'; ?>">
																<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5" />
															</svg>
															Support until
														</span>
														<span class="font-medium <?php echo $should_disable_special_benefits ? 'text-gray-400' : ($technical_support_expired ? 'text-[var(--color-wp-red)]' : 'text-[var(--color-wp-green)]'); ?>">
															<?php echo esc_html($support_until_date); ?>
														</span>
													</div>
												</div>

												<div class="border-t border-gray-600 py-3">
													<div class="flex items-center justify-between">
														<span class="flex items-center <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-gray-400'; ?>">
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 <?php echo $should_disable_special_benefits ? 'text-gray-400' : 'text-gray-400'; ?>">
																<path stroke-linecap="round" stroke-linejoin="round" d="m16.49 12 3.75 3.75m0 0-3.75 3.75m3.75-3.75H3.74V4.499" />
															</svg>
															Support status
														</span>
														<span class="font-medium <?php echo $should_disable_special_benefits ? 'text-gray-400' : ($technical_support_expired ? 'text-[var(--color-wp-red)]' : 'text-[var(--color-wp-green)]'); ?> flex items-center">
															<?php if (!$technical_support_expired): ?>
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
												<?php endif; ?>
											</div>
										</div>
										<?php if ($should_disable_special_benefits && ($has_bonus_updates || $has_bonus_support)): ?>
										<div class="mt-0 -mx-10 px-10 py-6 relative overflow-hidden">
											<!-- Background overlay -->
											<div class="absolute inset-0 bg-[var(--color-wp-gray-dark)]"></div>
											<!-- Left accent border with glow effect (red) -->
											<div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-[var(--color-wp-red)]/80 via-[var(--color-wp-red)] to-[var(--color-wp-red)]/80 shadow-[0_0_15px_rgba(220,38,38,0.4)]"></div>
											
											<div class="relative">
												<div class="mb-5">
													<div class="flex items-center mb-2">
														<div class="flex items-center justify-center w-6 h-6 rounded-md bg-[var(--color-wp-red)]/20 ring-1 ring-[var(--color-wp-red)]/30 mr-2.5 flex-shrink-0">
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-[var(--color-wp-red)]">
																<circle cx="12" cy="12" r="10"/>
																<circle cx="9" cy="9" r="1" fill="currentColor"/>
																<circle cx="15" cy="9" r="1" fill="currentColor"/>
																<path d="M8 16 Q12 13 16 16" stroke-linecap="round"/>
															</svg>
														</div>
														<h4 class="text-[var(--color-wp-red)] font-bold text-base uppercase tracking-wide">Benefits temporarily locked</h4>
													</div>
													<p class="text-white/50 text-xs pl-8.5">
														<?php
														$disabled_benefits = [];
														if ($has_bonus_updates) {
															$disabled_benefits[] = 'built-in updates';
														}
														if ($has_bonus_support) {
															$disabled_benefits[] = 'technical support';
														}
														
														$benefits_text = esc_html(implode(' & ', $disabled_benefits));
														$benefit_count = count($disabled_benefits);
														$benefit_singular_plural = $benefit_count > 1 ? 'special benefits' : 'a special benefit';
														$benefits_reference = $benefit_count > 1 ? 'these benefits' : 'this benefit';
														if (!empty($low_star_reviews)) {
                                                            echo 'We\'d love to unlock these special benefits for you, but we noticed <a href="#" id="show-low-star-reviews-modal" class="text-[var(--color-wp-blue)] hover:text-[var(--color-wp-blue-darker)] transition duration-150 ease-in-out">your previous rating</a> didn\'t reflect a positive experience. We believe in building great relationships with our customers! We\'d really appreciate you sharing some kind words or simply <a href="' . esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')) . '" target="_blank" class="text-[var(--color-wp-blue)] hover:text-[var(--color-wp-blue-darker)] transition duration-150 ease-in-out">updating your rating</a> (even without a written review) to unlock these benefits. After updating, click <br><button type="button" id="reload-license-after-rating-link" class="mt-2 inline-flex items-center justify-center rounded bg-[var(--color-wp-blue)] text-white px-2.5 py-1 text-xs font-semibold shadow-sm hover:bg-[var(--color-wp-blue-darker)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-blue)] cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 mr-1 refresh-now-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg><span class="btn-text">Recheck and Refresh</span></button>';
														} elseif ($has_reviews && $has_outdated_rating) {
                                                            echo 'We\'d love to unlock these special benefits for you, but <a href="#" id="show-outdated-rating-modal" class="text-[var(--color-wp-blue)] hover:text-[var(--color-wp-blue-darker)] transition duration-150 ease-in-out">your rating</a> is from over a year ago and we\'ve grown a lot since then. We\'d really appreciate hearing about your recent experience! You can share some kind words about how things are going or simply <a href="' . esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')) . '" target="_blank" class="text-[var(--color-wp-blue)] hover:text-[var(--color-wp-blue-darker)] transition duration-150 ease-in-out">update your rating</a> (even without writing a review) to reflect our current relationship. After updating, click <br><button type="button" id="reload-license-after-rating-link" class="mt-2 inline-flex items-center justify-center rounded bg-[var(--color-wp-blue)] text-white px-2.5 py-1 text-xs font-semibold shadow-sm hover:bg-[var(--color-wp-blue-darker)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-blue)] cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 mr-1 refresh-now-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg><span class="btn-text">Recheck and Refresh</span></button>';
														} else {
                                                            echo 'We\'d love to unlock these special benefits for you, but we haven\'t heard from you yet and would really appreciate your feedback. We\'d be thrilled if you could share some kind words about how it\'s going or simply <a href="' . esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')) . '" target="_blank" class="text-[var(--color-wp-blue)] hover:text-[var(--color-wp-blue-darker)] transition duration-150 ease-in-out">leave a rating</a> (even without writing a review) to unlock these benefits. After leaving your rating, click <br><button type="button" id="reload-license-after-rating-link" class="mt-2 inline-flex items-center justify-center rounded bg-[var(--color-wp-blue)] text-white px-2.5 py-1 text-xs font-semibold shadow-sm hover:bg-[var(--color-wp-blue-darker)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-blue)] cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 mr-1 refresh-now-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg><span class="btn-text">Recheck and Refresh</span></button>';
														}
														?>
													</p>
												</div>
											
												<div class="border-t border-gray-600 py-3">
													<div class="flex items-center justify-between">
														<span class="text-gray-400 flex items-center">
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 text-gray-400">
																<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
															</svg>
															Benefits status
														</span>
														<span class="font-medium text-[var(--color-wp-red)] flex items-center">
															<svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
																<path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
															</svg>
															Locked
														</span>
													</div>
												</div>

												<div class="border-t border-gray-600 py-3">
													<div class="flex items-center justify-between">
														<span class="text-gray-400 flex items-center">
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 text-gray-400">
																<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
															</svg>
															Unlock benefits
														</span>
                                                    <a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')); ?>" target="_blank" class="inline-flex items-center justify-center rounded-md bg-[var(--color-wp-blue)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[var(--color-wp-blue-darker)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-blue)] transition duration-150 ease-in-out">
                                                        <?php echo (!empty($low_star_reviews) || $has_outdated_rating) ? 'Update rating' : 'Leave a rating'; ?>
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ml-1.5">
																<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
															</svg>
														</a>
													</div>
												</div>
											</div>
										</div>
										<?php endif; ?>
										<?php endif; ?>

										<div class="<?php echo ($has_bonus_updates || $has_bonus_support) ? 'py-3' : 'border-t border-gray-600 py-3'; ?>">
											<div class="flex items-center justify-between">
												<span class="text-gray-400">Subscription auto-renewal</span>
												<span class="font-medium text-white">No</span>
											</div>
										</div>

										<?php if (!empty($last_verified_date)): ?>
											<div class="border-t border-gray-600 py-3">
												<div class="flex items-start justify-between">
													<div class="flex flex-col">
														<span class="text-gray-400">Last verified</span>
														<a href="#" id="reload-license-data-link" class="mt-1 text-[var(--color-wp-blue)] hover:text-[var(--color-wp-blue-darker)] transition duration-150 ease-in-out inline-flex items-center gap-1">
															Refresh now
															<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 refresh-now-icon self-center mt-1">
																<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
															</svg>
														</a>
													</div>
													<div class="flex flex-col items-end text-right">
														<?php
														// Safely format the date to avoid parsing errors with complex formats
														try {
															// First try to get timestamp if it's not already one
															if (!is_numeric($last_verified)) {
																// Just display the original string if we can't parse
																echo '<span class="font-medium text-white">' . esc_html($last_verified_date) . '</span>';
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
																	echo '<span class="font-medium text-white">Today</span>';
																	echo '<span class="font-medium text-white mt-1">' . esc_html($datetime->format('g:i A')) . '</span>';
																} elseif ($verification_day == $yesterday) {
																	echo '<span class="font-medium text-white">Yesterday</span>';
																	echo '<span class="font-medium text-white mt-1">' . esc_html($datetime->format('g:i A')) . '</span>';
																} else {
																	echo '<span class="font-medium text-white">' . esc_html($datetime->format('F j, Y')) . '</span>';
																	echo '<span class="font-medium text-white mt-1">' . esc_html($datetime->format('g:i A')) . '</span>';
																}
															}
														} catch (Exception $e) {
															// Fallback to original string if any error occurs
															echo '<span class="font-medium text-white">' . esc_html($last_verified_date) . '</span>';
														}
														?>
													</div>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>

								<?php if ($expires_soon || !$is_support_active): ?>
									<div class="mt-auto pt-6">
										<a href="#license-options" id="renew-support-button" class="block w-full rounded-md bg-wp-blue px-4 py-2.5 text-center text-base font-semibold text-white shadow-sm hover:bg-wp-blue/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wp-blue transition duration-150 ease-in-out cursor-pointer">
											<?php echo $expires_soon ? 'Extend subscription' : 'Activate subscription'; ?>
										</a>
									</div>
								<?php endif; ?>
							</div>
						<?php else: ?>
							<!-- Default "Get a license" content -->
							<div class="rounded-2xl bg-[var(--color-wp-gray-light)] py-10 text-center ring-1 ring-[var(--color-wp-gray-light)] lg:flex lg:flex-col lg:justify-center lg:py-16">
								<div class="mx-auto max-w-xs px-8">
									<?php if ($regular_license_is_sale): ?>
										<span class="inline-block mb-2 bg-[var(--color-wp-red)] text-white text-xs font-bold px-3 py-1 rounded-full">SALE</span>
									<?php endif; ?>
									<h3 class="text-3xl font-semibold tracking-tight text-white pb-2">
										Get a license today
									</h3>
									<p class="text-base text-gray-400">Pay once, own it forever!</p>
									<div class="mt-6">
										<div class="relative mb-4">
											<?php if ($regular_license_is_sale): ?>
												<div class="flex items-baseline justify-center gap-x-2">
													<span class="text-5xl line-through text-gray-400"><?php echo esc_html($original_price_regular_license_display); ?></span>
													<span class="text-5xl font-bold tracking-tight text-white"><?php echo esc_html($theme_default_price_display); ?></span>
													<span class="text-sm/6 font-semibold tracking-wide text-gray-400">USD</span>
												</div>
											<?php else: ?>
												<div class="flex items-baseline justify-center gap-x-2">
													<span class="text-5xl font-bold tracking-tight text-white"><?php echo esc_html($theme_default_price_display); ?></span>
													<span class="text-sm/6 font-semibold tracking-wide text-gray-400">USD</span>
												</div>
											<?php endif; ?>
										</div>
										<div class="relative mt-12 mb-6">
											<div class="absolute -top-3 inset-x-0">
												<div class="flex justify-center">
													<span class="inline-flex rounded-full bg-[var(--color-wp-yellow)] px-4 py-1 text-xs font-semibold text-gray-900">Most popular</span>
												</div>
											</div>
											<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="block w-full rounded-md bg-wp-blue px-4 pt-5 pb-5 text-center text-base font-semibold text-white shadow-sm hover:bg-wp-blue/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-wp-blue transition duration-150 ease-in-out cursor-pointer">Get regular license</a>
											<p class="mt-2 text-xs text-center text-gray-400 px-4">Ideal for most websites and client projects</p>
										</div>
									</div>

									<div class="relative mb-6 mt-6">
										<div class="absolute inset-0 flex items-center">
											<div class="w-full border-t border-gray-500"></div>
										</div>
										<div class="relative flex justify-center">
											<span class="bg-[var(--color-wp-gray-light)] px-3 text-sm text-gray-400">OR</span>
										</div>
									</div>

									<div>
										<div class="relative mb-4">
											<?php if ($extended_license_is_sale): ?>
												<div class="relative mb-1">
													<span class="bg-[var(--color-wp-red)] text-white text-xs font-bold px-3 py-1 rounded-full absolute -top-3 right-0">SALE</span>
													<span class="text-lg line-through text-gray-400"><?php echo esc_html($original_price_extended_license_display); ?></span>
												</div>
												<div class="flex items-center justify-center gap-x-2">
													<span class="text-base font-bold tracking-wide text-gray-200"><?php echo esc_html($theme_default_price_extended_display); ?> USD</span>
													<button type="button" class="show-license-types-help text-wp-blue focus:outline-none focus:ring-0 focus:ring-offset-0 focus-visible:outline-none focus-visible:ring-0 cursor-pointer">
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
															<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 5.25h.008v.008H12v-.008Z" />
														</svg>
													</button>
												</div>
											<?php else: ?>
												<div class="flex items-center justify-center gap-x-2">
													<span class="text-base font-bold tracking-wide text-gray-200"><?php echo esc_html($theme_default_price_extended_display); ?> USD</span>
													<button type="button" class="show-license-types-help text-wp-blue focus:outline-none focus:ring-0 focus:ring-offset-0 focus-visible:outline-none focus-visible:ring-0 cursor-pointer">
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
															<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 5.25h.008v.008H12v-.008Z" />
														</svg>
													</button>
												</div>
											<?php endif; ?>
										</div>
										<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="block w-full rounded-md bg-[var(--color-wp-gray-lighter)] px-4 py-2.5 text-center text-base font-normal text-white/80 ring-1 ring-[var(--color-wp-gray-lighter)] hover:bg-[var(--color-wp-gray-light)] hover:text-white/80 transition duration-150 ease-in-out cursor-pointer">Get extended license</a>
									</div>

									<p class="mt-6 text-xs/5 text-gray-400 flex items-center justify-start">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5 text-gray-400">
											<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
										</svg>
										Quality checked by Envato
									</p>
									<p class="mt-2 text-xs/5 text-gray-400 flex items-center justify-start">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5 text-gray-400">
											<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
										</svg>
										Power elite author
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
					<h2 id="license-options" class="text-base/7 font-semibold text-wp-blue">Unlock your theme's full potential</h2>
					<p class="mt-2 text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl">Licensing and subscription options available to you</p>
				</div>
				<p class="mx-auto mt-6 max-w-2xl text-center text-lg font-medium text-pretty text-gray-600 sm:text-xl/8">Pick the license and subscription combination that suits you best! Each option comes with unique features, built-in updates, and support levels, so you can get exactly what you need to make your project a success.</p>

				<div class="mx-auto mt-16 grid max-w-lg grid-cols-1 items-center gap-y-6 sm:mt-20 sm:gap-y-0 lg:max-w-7xl lg:grid-cols-3">
					<!-- Regular license -->
					<div class="rounded-3xl rounded-t-3xl bg-white/60 p-8 ring-1 ring-gray-900/10 sm:mx-8 sm:rounded-b-none sm:p-10 lg:mx-0 lg:rounded-tr-none lg:rounded-bl-3xl">
						<h3 id="tier-regular" class="text-base/7 font-semibold text-wp-blue flex items-center">
							Lifetime regular license
							<?php if ($regular_license_is_sale): ?>
								<span class="ml-2 bg-[var(--color-wp-red)] text-white text-xs font-bold px-2 py-0.5 rounded-full">SALE</span>
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
							<span class="text-base text-gray-400 mt-1">one-time</span>
						</div>
						</p>
						<div class="mt-6 flex gap-2 items-center">
							<svg class="h-6 w-6 text-[var(--color-wp-green)]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
								<path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
							</svg>
							<span class="text-sm font-medium text-gray-900">You currently own this license.</span>
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
								Support subscription for 6 months
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-[var(--color-wp-red)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
								</svg>
								<span class="text-gray-600">Built-in critical security updates</span>
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-[var(--color-wp-red)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
								</svg>
								<span class="text-gray-600">Built-in priority bug fixes</span>
							</li>
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-[var(--color-wp-red)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
								</svg>
								<span class="text-gray-600">Dedicated developer assigned for assistance</span>
							</li>
						</ul>
					</div>

					<!-- Support subscription -->
					<div class="relative">
						<div class="absolute -top-4 inset-x-0 z-30">
							<div class="flex justify-center">
								<span class="inline-flex rounded-full bg-[var(--color-wp-yellow)] px-5 py-1.5 text-sm font-semibold text-gray-900 shadow-md ring-2 ring-[var(--color-wp-gray-dark)]">Most popular</span>
							</div>
						</div>
						<div id="professional-upgrade-section" class="relative rounded-3xl bg-[var(--color-wp-gray-dark)] p-8 ring-1 shadow-2xl ring-[var(--color-wp-gray-dark)]/10 sm:p-10 overflow-hidden">
							<!-- Blue Nebula Background with Top Glow -->
							<div class="absolute inset-0" style="background: radial-gradient(ellipse 80% 60% at 0% 0%, rgba(34, 113, 177, 0.5), transparent 70%), var(--color-wp-gray-dark);"></div>
							<!-- Crosshatch Texture Pattern with Diagonal Fade -->
							<div class="absolute inset-0 pointer-events-none" style="background-image: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0) 100%), repeating-linear-gradient(22.5deg, transparent, transparent 2px, rgba(75, 85, 99, 0.06) 2px, rgba(75, 85, 99, 0.06) 3px, transparent 3px, transparent 8px), repeating-linear-gradient(67.5deg, transparent, transparent 2px, rgba(107, 114, 128, 0.05) 2px, rgba(107, 114, 128, 0.05) 3px, transparent 3px, transparent 8px), repeating-linear-gradient(112.5deg, transparent, transparent 2px, rgba(55, 65, 81, 0.04) 2px, rgba(55, 65, 81, 0.04) 3px, transparent 3px, transparent 8px), repeating-linear-gradient(157.5deg, transparent, transparent 2px, rgba(31, 41, 55, 0.03) 2px, rgba(31, 41, 55, 0.03) 3px, transparent 3px, transparent 8px), linear-gradient(315deg, rgba(0,0,0,0.8) 30%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0) 70%);"></div>
						<div class="relative z-10">
							<h3 id="tier-professional" class="text-base/7 font-semibold text-wp-blue-lighter flex items-center">
							Support subscription
							<?php if ($professional_license_is_sale): ?>
								<span class="ml-2 bg-[var(--color-wp-red)] text-white text-xs font-bold px-2 py-0.5 rounded-full">SALE</span>
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
							<span class="text-base text-gray-400 mt-1"><?php echo esc_html($professional_price_text); ?></span>
						</div>
						</p>

						<?php if ($is_support_active): ?>
							<div class="mt-6 flex gap-2 items-center">
								<svg class="h-6 w-6 text-[var(--color-wp-green)]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
									<path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
							</svg>
							<span class="text-sm font-medium text-gray-100">Active until <?php echo esc_html($support_expiration); ?></span>
							</div>

							<?php if (isset($expires_soon) && $expires_soon): ?>
								<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="mt-4 inline-flex items-center justify-center rounded-md bg-[var(--color-wp-yellow)] px-3.5 py-2 text-sm font-semibold text-gray-900 shadow hover:bg-[var(--color-wp-yellow)]/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-yellow)]">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-1.5">
										<path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" />
									</svg>
									Expires soon - Extend now!
								</a>
							<?php endif; ?>
						<?php endif; ?>

						<p class="mt-6 text-base/7 text-white">Support subscription includes built-in updates, priority bug fixes, and expert assistance whenever you need it.<?php if (!$is_support_active): ?> Subscribe today for a stress-free experience.<?php endif; ?></p>

						<?php if (!$is_support_active): ?>
							<div class="mt-6 inline-flex items-center px-3 py-1.5 rounded-full bg-[var(--color-wp-yellow)]/20 text-[var(--color-wp-yellow)] text-sm font-medium">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 mr-1.5">
									<path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.75.75 0 00.674 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
								</svg>
								<span>Unlock built-in updates & priority support!</span>
							</div>
						<?php endif; ?>

						<ul role="list" class="mt-8 space-y-3 text-sm/6 text-white sm:mt-10">
							<li class="flex gap-x-3">
								<svg class="h-6 w-5 flex-none text-wp-blue-lighter" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
									<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
								</svg>
								Built-in automatic or manual updates
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
								Built-in bug fixes and compatibility updates
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
						</ul>
						<?php if (!$is_support_active): ?>
							<!-- Upgrade eligibility confirmation -->
							<div id="subscription-eligibility-message" class="mt-8 text-center">
								<div class="flex flex-col items-center justify-center mb-2">
									<div id="success-icon" class="opacity-0 transition-all duration-300 ease-in-out mb-1">
										<svg class="h-5 w-5 text-[var(--color-wp-green)]" viewBox="0 0 20 20" fill="currentColor">
											<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
										</svg>
									</div>
									<div id="main-text" class="opacity-0 transition-opacity duration-300 ease-in-out">
										<span class="text-sm font-medium text-white">Great! Your lifetime license qualifies for support subscription.</span>
									</div>
								</div>
								<div id="sub-text" class="opacity-0 transition-opacity duration-300 ease-in-out">
									<p class="text-xs text-[var(--color-wp-green)] px-8">Activate your subscription now to access ongoing built-in updates and priority support.</p>
								</div>
							</div>
							<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" aria-describedby="tier-professional" class="mt-6 block rounded-md bg-[var(--color-wp-blue)] px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-[var(--color-wp-blue-darker)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-blue)] sm:mt-8">Subscribe now</a>
						<?php endif; ?>
						
						<div class="mt-6 space-y-2 text-center">
							<a href="javascript:void(0)" class="show-video-modal inline-flex items-center justify-center gap-1.5 text-sm text-gray-400 hover:text-white transition-colors duration-200 underline decoration-dotted">
								<svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
								</svg>
								Watch how subscription works (1 minute video)
							</a>
							<a href="https://1.envato.market/extend-or-renew-items" target="_blank" class="inline-flex items-center justify-center gap-1.5 text-sm text-gray-400 hover:text-white transition-colors duration-200 underline decoration-dotted">
								<svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
								</svg>
								Learn more about subscription and support
							</a>
						</div>
						</div>
						</div>
					</div>

					<!-- Extended license -->
					<div class="rounded-3xl rounded-b-3xl bg-white/60 p-8 ring-1 ring-gray-900/10 sm:mx-8 sm:rounded-t-none sm:p-10 lg:mx-0 lg:rounded-tr-3xl lg:rounded-bl-none">
						<h3 id="tier-extended" class="text-base/7 font-semibold text-wp-blue flex items-center">
							Extended license
							<?php if ($extended_license_is_sale): ?>
								<span class="ml-2 bg-[var(--color-wp-red)] text-white text-xs font-bold px-2 py-0.5 rounded-full">SALE</span>
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
							<span class="text-base text-gray-400 mt-1">one-time</span>
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
						<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" aria-describedby="tier-extended" class="mt-8 block rounded-md px-3.5 py-2.5 text-center text-sm font-normal text-gray-400 ring-1 ring-gray-500/20 ring-inset hover:ring-gray-500/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500 sm:mt-10">Get extended license</a>
						<p class="mt-2 text-xs text-center text-gray-400">Manually select "Extended license" from the dropdown in the marketplace.</p>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- License Help Modal -->
		<div id="license-help-modal" class="fixed inset-0 z-[9999] hidden" aria-labelledby="license-modal-title" role="dialog" aria-modal="true">
			<div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
			<div class="fixed inset-0 z-[9999] w-screen overflow-y-auto">
				<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
					<div class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
						<div class="sm:flex sm:items-start">
							<div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full bg-wp-blue/10 sm:mx-0 sm:size-10">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-wp-blue">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
								</svg>
							</div>
							<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
								<h3 class="text-base font-semibold text-gray-900" id="license-modal-title">Find your purchase code</h3>
								<div class="mt-2">
									<p class="text-sm text-gray-400 mb-2">Follow these steps to locate your purchase code:</p>
									<ol class="text-sm text-gray-600 list-decimal ml-5 mt-2 space-y-2">
										<li>Go to <a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')); ?>" target="_blank" class="text-wp-blue hover:underline">downloads</a> on your Envato account</li>
										<li>Click the "Download" button next to <?php echo esc_html($theme_name_gbt_dash); ?> theme</li>
										<li>Select "License certificate & purchase code" from the dropdown</li>
										<li>Open the downloaded file and copy the <strong>Item purchase code</strong></li>
									</ol>
								</div>
							</div>
						</div>
						<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
							<button type="button" id="close-license-help" class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:ring-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 sm:mt-0 sm:w-auto transition duration-150 ease-in-out cursor-pointer">Close</button>
							<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')); ?>" target="_blank" class="mt-3 inline-flex w-full justify-center rounded-md bg-wp-blue px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-wp-blue/90 sm:mt-0 sm:w-auto sm:mr-3 transition duration-150 ease-in-out cursor-pointer">Get your purchase code</a>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Low Star Reviews Modal -->
		<?php if ($has_low_star_reviews && !empty($low_star_reviews)): ?>
		<div id="low-star-reviews-modal" class="fixed inset-0 z-[9999] hidden" aria-labelledby="reviews-modal-title" role="dialog" aria-modal="true">
			<div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
			<div class="fixed inset-0 z-[9999] w-screen overflow-y-auto">
				<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
					<div class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
						<div class="sm:flex sm:items-start">
							<div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
								<h3 class="text-base font-semibold text-gray-900" id="reviews-modal-title">Your review</h3>
								<div class="mt-2">
									<div class="space-y-3 max-h-96 overflow-y-auto pr-2">
										<?php foreach ($low_star_reviews as $review): ?>
										<div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
											<div class="flex items-center flex-wrap gap-2 <?php echo !empty($review['review_text']) ? 'mb-2' : ''; ?>">
												<?php
												$theme_names = [
													'merchandiser' => 'Merchandiser',
													'mrtailor' => 'Mr. Tailor',
													'shopkeeper' => 'Shopkeeper',
													'theretailer' => 'The Retailer',
													'the-hanger' => 'The Hanger'
												];
												$theme_name = $theme_names[$review['theme_slug'] ?? ''] ?? ucfirst($review['theme_slug'] ?? '');
												?>
												<span class="text-sm font-semibold text-gray-900">
													<?php echo esc_html($theme_name); ?>
												</span>
												<span class="inline-flex items-center">
													<?php
													$rating = (int)($review['rating'] ?? 0);
													for ($i = 0; $i < $rating; $i++) {
														echo '<svg class="w-3.5 h-3.5 text-[var(--color-wp-red)]" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
													}
													for ($i = $rating; $i < 5; $i++) {
														echo '<svg class="w-3.5 h-3.5 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
													}
													?>
													<span class="ml-1.5 text-xs font-medium text-gray-600"><?php echo esc_html($rating); ?>/5</span>
												</span>
											</div>
											<?php if (!empty($review['review_text'])): ?>
											<div class="mt-3">
												<p class="text-sm text-gray-600 leading-relaxed italic">
													&ldquo;<?php echo esc_html($review['review_text']); ?>&rdquo;
												</p>
											</div>
											<?php endif; ?>
										</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>
						<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
							<button type="button" id="close-low-star-reviews-modal" class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:ring-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 sm:mt-0 sm:w-auto transition duration-150 ease-in-out cursor-pointer">Close</button>
							<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_backend_download_url')); ?>" target="_blank" class="mt-3 inline-flex w-full justify-center rounded-md bg-wp-blue px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-wp-blue/90 sm:mt-0 sm:w-auto sm:mr-3 transition duration-150 ease-in-out cursor-pointer">Update Rating</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>

<?php
		// Content End

		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-end.php';

		// Enqueue the license validator script
		wp_enqueue_script('getbowtied-license-validator', $base_paths['url'] . '/dashboard/js/license-validator.js', array('jquery'), $theme_version_gbt_dash, true);

		// Enqueue the license help modal script
		wp_enqueue_script('getbowtied-license-modal', $base_paths['url'] . '/dashboard/js/license-modal.js', array('jquery'), $theme_version_gbt_dash, true);


		// Enqueue the smooth scroll script
		wp_enqueue_script('getbowtied-smooth-scroll', $base_paths['url'] . '/dashboard/js/smooth-scroll.js', array('jquery'), $theme_version_gbt_dash, true);
		
		// Add inline script to remove hash from URL after form submission
		if (isset($_GET['license_updated'])) {
			wp_add_inline_script('getbowtied-smooth-scroll', '
				(function() {
					if (window.location.hash) {
						history.replaceState(null, null, window.location.pathname + window.location.search);
					}
				})();
			');
		}

		// Add inline CSS for coin flip animation
		wp_add_inline_script('getbowtied-smooth-scroll', '
			// Add CSS for coin flip animation
			const style = document.createElement("style");
			style.textContent = `
				@keyframes coin-flip {
					0% { 
						transform: rotateY(0deg);
					}
					20% { 
						transform: rotateY(360deg);
					}
					100% { 
						transform: rotateY(360deg);
					}
				}
				.coin-flip {
					animation: coin-flip 4s ease-in-out infinite !important;
					transform-style: preserve-3d !important;
				}
			`;
			document.head.appendChild(style);
		');

		// Add inline script for subscription eligibility message visibility
		wp_add_inline_script('getbowtied-smooth-scroll', '
			(function() {
				// Wait for DOM to be ready
				document.addEventListener("DOMContentLoaded", function() {
					const subscriptionMessage = document.getElementById("subscription-eligibility-message");
					
					if (subscriptionMessage) {
						// Create intersection observer
						const observer = new IntersectionObserver(function(entries) {
							entries.forEach(function(entry) {
								if (entry.isIntersecting) {
									// Start the sequential animation
									startSequentialAnimation();
								} else {
									// Hide elements when they go out of view
									hideElements();
								}
							});
						}, {
							// Trigger when 50% of the element is visible
							threshold: 0.5,
							// Add some margin to trigger slightly before the element is fully visible
							rootMargin: "0px 0px -10% 0px"
						});
						
						// Start observing the subscription message
						observer.observe(subscriptionMessage);
					}
					
					function startSequentialAnimation() {
						const icon = document.getElementById("success-icon");
						const mainText = document.getElementById("main-text");
						const subText = document.getElementById("sub-text");
						
						// 1. Show icon with infinite coin flip animation (immediate)
						if (icon) {
							icon.style.opacity = "1";
							// Force a reflow to ensure the opacity change is applied
							icon.offsetHeight;
							// Add the infinite coin flip animation
							icon.classList.add("coin-flip");
						}
						
						// 2. Show main text after 200ms
						setTimeout(() => {
							if (mainText) {
								mainText.style.opacity = "1";
							}
						}, 200);
						
						// 3. Show sub text after 400ms
						setTimeout(() => {
							if (subText) {
								subText.style.opacity = "1";
							}
						}, 400);
					}
					
					function hideElements() {
						const icon = document.getElementById("success-icon");
						const mainText = document.getElementById("main-text");
						const subText = document.getElementById("sub-text");
						
						// Hide all elements when they go out of view
						if (icon) {
							icon.style.opacity = "0";
							icon.classList.remove("coin-flip");
						}
						if (mainText) {
							mainText.style.opacity = "0";
						}
						if (subText) {
							subText.style.opacity = "0";
						}
					}
				});
			})();
		');

		// Add inline script for license button spinning animation
		wp_add_inline_script('getbowtied-smooth-scroll', '
			// Add CSS for license refresh icon spinning animation
			const licenseStyle = document.createElement("style");
			licenseStyle.textContent = `
				@keyframes license-spin {
					0% { 
						transform: rotate(0deg);
					}
					100% { 
						transform: rotate(360deg);
					}
				}
				.license-refresh-icon.spinning,
				.refresh-now-icon.spinning {
					animation: license-spin 1s linear infinite !important;
				}
			`;
			document.head.appendChild(licenseStyle);

			// Add JavaScript for license button click handling (spin icon on submit)
			(function() {
				document.addEventListener("DOMContentLoaded", function() {
					const licenseSubmitBtn = document.getElementById("license-submit-btn");
					const licenseRefreshIcon = document.querySelector(".license-refresh-icon");
					
					if (licenseSubmitBtn && licenseRefreshIcon) {
						licenseSubmitBtn.addEventListener("click", function() {
							// Add spinning class to the refresh icon
							licenseRefreshIcon.classList.add("spinning");
						});
					}

					// Handle the "Refresh" link in the "Last verified" section
					const reloadLicenseLink = document.getElementById("reload-license-data-link");
					const refreshNowIcon = document.querySelector(".refresh-now-icon");
					
					if (reloadLicenseLink && licenseSubmitBtn) {
						reloadLicenseLink.addEventListener("click", function(e) {
							e.preventDefault();
							
							// Add spinning animation to the refresh now icon
							if (refreshNowIcon) {
								refreshNowIcon.classList.add("spinning");
							}
							
							// Trigger the main license submit button click
							licenseSubmitBtn.click();
						});
					}
					
						// Handle the "Recheck and Refresh" buttons in the disabled benefits section (can appear multiple times)
						const reloadLicenseAfterRatingButtons = document.querySelectorAll("#reload-license-after-rating-link");
						
						if (reloadLicenseAfterRatingButtons.length && licenseSubmitBtn) {
							reloadLicenseAfterRatingButtons.forEach(function(btn) {
								btn.addEventListener("click", function(e) {
									e.preventDefault();
									// Change button state to waiting
									btn.disabled = true;
									// Visually and functionally indicate waiting state
									btn.classList.add("opacity-70","cursor-not-allowed","pointer-events-none","cursor-wait");
									btn.style.cursor = "wait";
                                    const textSpan = btn.querySelector(".btn-text");
									if (textSpan) {
                                        textSpan.textContent = "Rechecking, please wait…";
									}
                                    const btnIcon = btn.querySelector(".refresh-now-icon");
									if (btnIcon) {
                                        btnIcon.classList.add("spinning");
									}
									// Trigger the main license submit button click
									licenseSubmitBtn.click();
								});
							});
						}
				});
			})();
			
		');
}
}
