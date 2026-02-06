<?php
/**
 * License Status Banner Component
 * 
 * Reusable component that displays license status messages at the top of dashboard pages.
 * Shows one of three states: No License, Expired Support, or Expiring Soon.
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Get dashboard setup
$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
$theme_slug = $gbt_dashboard_setup->get_theme_slug();

// Don't show for block-shop theme
if ($theme_slug === 'block-shop') {
	return;
}

// Check if initialization period is complete
if (class_exists('Theme_LI') && Theme_LI::is_init_period_completed() !== true) {
	return;
}

// Get license manager instance
$license_manager = GBT_License_Manager::get_instance();
$theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
$license_page_url = admin_url('admin.php?page=getbowtied-license');

// Get license status
$is_license_active = $license_manager->is_license_active();
$is_support_active = $license_manager->is_support_active();

// ================================================= 
// Pricing and Modal Variables
// ================================================= 

// Get pricing information for the modal
if (!class_exists('GBT_Theme_Price_Updater')) {
	require_once $gbt_dashboard_setup->get_base_paths()['path'] . '/dashboard/inc/classes/class-theme-price-updater.php';
}
$price_updater = GBT_Theme_Price_Updater::get_instance();

// Get theme pricing information from configuration
$theme_default_price_regular_license = $gbt_dashboard_setup->get_theme_config('theme_default_price_regular_license');
$theme_default_price_extended_license = $gbt_dashboard_setup->get_theme_config('theme_default_price_extended_license');

// Get the price data using the updater class
$price_data = $price_updater->get_current_price_data(
	$theme_default_price_regular_license,
	$theme_default_price_extended_license
);

// Check for sales by comparing live prices with default prices
$regular_license_is_sale = false;
$extended_license_is_sale = false;
$professional_license_is_sale = false;

if (isset($price_data['regular_license_price']) && isset($price_data['extended_license_price'])) {
	$regular_license_is_sale = $price_data['regular_license_price'] < $theme_default_price_regular_license;
	$extended_license_is_sale = $price_data['extended_license_price'] < $theme_default_price_extended_license;
	$professional_license_is_sale = $regular_license_is_sale;
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

// Get support price formula from config and apply it to the theme price
$support_price_formula = $gbt_dashboard_setup->get_global_config('support_prices', 'support_price_formula');
$support_price = is_callable($support_price_formula) ? $support_price_formula($theme_default_price_regular_license) : 0;

// Format prices for display
$theme_default_price_display = '$' . number_format($theme_default_price_regular_license, 0);
$theme_default_price_extended_display = '$' . number_format($theme_default_price_extended_license, 0);

// Original prices (before sale)
$original_price_regular_license_display = $regular_license_is_sale ? '$' . number_format($gbt_dashboard_setup->get_theme_config('theme_default_price_regular_license'), 0) : '';
$original_price_extended_license_display = $extended_license_is_sale ? '$' . number_format($gbt_dashboard_setup->get_theme_config('theme_default_price_extended_license'), 0) : '';

// Professional license pricing
$professional_price = $support_price;
$professional_price_display = '$' . $professional_price;
$professional_price_text = 'for 6 months';

// Original professional price (before sale)
$original_professional_price = $professional_license_is_sale ?
	(is_callable($support_price_formula) ? $support_price_formula($gbt_dashboard_setup->get_theme_config('theme_default_price_regular_license')) : 0) : 0;
$original_professional_price_display = $professional_license_is_sale ? '$' . $original_professional_price : '';

// ================================================= 
// YouTube Video Content (Reusable)
// ================================================= 

/**
 * Filter to add SVG support to wp_kses
 * 
 * @param array $tags Allowed HTML tags
 * @return array Modified allowed HTML tags
 */
function gbt_kses_allowed_html_svg( $tags ) {
	$tags['svg'] = array(
		'class' => true,
		'fill' => true,
		'viewBox' => true,
		'viewbox' => true,
		'stroke-width' => true,
		'stroke' => true,
		'xmlns' => true,
		'xmlns:xlink' => true,
		'width' => true,
		'height' => true,
		'style' => true,
		'aria-hidden' => true,
		'role' => true,
	);
	
	$tags['path'] = array(
		'stroke-linecap' => true,
		'stroke-linejoin' => true,
		'd' => true,
		'fill-rule' => true,
		'clip-rule' => true,
		'fill' => true,
		'stroke' => true,
		'stroke-width' => true,
		'style' => true,
	);
	
	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'gbt_kses_allowed_html_svg', 10, 1 );

/**
 * Get allowed HTML for YouTube video content
 * 
 * @return array Allowed HTML tags and attributes
 */
function gbt_get_youtube_video_allowed_html() {
	$allowed_html = wp_kses_allowed_html( 'post' );
	
	// Allow iframe for YouTube embed
	$allowed_html['iframe'] = array(
		'class' => true,
		'src' => true,
		'title' => true,
		'frameborder' => true,
		'allow' => true,
		'allowfullscreen' => true,
		'style' => true,
		'width' => true,
		'height' => true,
	);
	
	// Allow SVG elements with all common attributes
	$allowed_html['svg'] = array(
		'class' => true,
		'fill' => true,
		'viewBox' => true,
		'viewbox' => true, // lowercase variant
		'stroke-width' => true,
		'stroke' => true,
		'xmlns' => true,
		'xmlns:xlink' => true,
		'width' => true,
		'height' => true,
		'style' => true,
		'aria-hidden' => true,
		'role' => true,
	);
	
	// Allow path elements within SVG with all common attributes
	$allowed_html['path'] = array(
		'stroke-linecap' => true,
		'stroke-linejoin' => true,
		'd' => true,
		'fill-rule' => true,
		'clip-rule' => true,
		'fill' => true,
		'stroke' => true,
		'stroke-width' => true,
		'style' => true,
	);
	
	// Ensure span attributes are allowed
	if ( ! isset( $allowed_html['span'] ) ) {
		$allowed_html['span'] = array();
	}
	$allowed_html['span']['class'] = true;
	$allowed_html['span']['style'] = true;
	
	// Ensure div attributes are allowed
	if ( ! isset( $allowed_html['div'] ) ) {
		$allowed_html['div'] = array();
	}
	$allowed_html['div']['class'] = true;
	$allowed_html['div']['style'] = true;
	
	// Ensure p attributes are allowed
	if ( ! isset( $allowed_html['p'] ) ) {
		$allowed_html['p'] = array();
	}
	$allowed_html['p']['class'] = true;
	
	return $allowed_html;
}

/**
 * Get YouTube video HTML content
 * 
 * @return string HTML content for the video section
 */
function gbt_get_youtube_video_content() {
	ob_start();
	?>
	<div class="relative w-full" style="padding-bottom: 56.25%;">
		<iframe 
			class="absolute top-0 left-0 w-full h-full rounded-lg" 
			src="https://www.youtube.com/embed/NJ5qm2FnSX4?enablejsapi=1" 
			title="How Envato Provides Item Support" 
			frameborder="0" 
			allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
			allowfullscreen>
		</iframe>
	</div>
	<div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-600">
		<span class="flex items-center gap-1">
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
				<path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
			</svg>
			451,729 views
		</span>
		<span class="text-gray-400">•</span>
		<span class="flex items-center gap-1">
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
			</svg>
			Nov 16, 2017
		</span>
	</div>
	<p class="mt-2 text-xs text-gray-600">An explainer video for how support subscription works on Envato.</p>
	<?php
	return ob_get_clean();
}

// ================================================= 
// Banner Display
// ================================================= 

// Display appropriate banner based on license status
if (!$is_license_active) {
	// No license detected
	?>
	<div class="relative overflow-hidden bg-white border border-[var(--color-wp-red)] border-l-4 border-l-[var(--color-wp-red)] shadow-sm px-8 py-6 mb-8">
		<div class="w-full">
			<div class="flex items-start gap-6">
				<div class="flex-shrink-0">
					<div class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-wp-red)]/10">
						<svg class="h-7 w-7 text-[var(--color-wp-red)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
						</svg>
					</div>
				</div>
				<div class="flex-1 min-w-0">
					<div class="flex flex-wrap items-center gap-2 mb-2">
						<span class="inline-flex items-center rounded-md bg-[var(--color-wp-red)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-red)] ring-1 ring-inset ring-[var(--color-wp-red)]/20">
							No License
						</span>
						<span class="text-gray-400">•</span>
						<span class="text-sm text-gray-600"><?php echo esc_html($theme_name_gbt_dash); ?> v<?php echo esc_html($theme_version_gbt_dash); ?></span>
					</div>
					<h3 class="text-xl font-semibold text-gray-900 mb-2">
						A license key is required to use <?php echo esc_html($theme_name_gbt_dash); ?> theme
					</h3>
					<div class="flex items-center gap-4 mb-4">
						<span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
							<svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
							</svg>
							The theme cannot be updated, no support access
						</span>
					</div>
					<p class="text-sm text-gray-600 mb-4 max-w-3xl">
						Your <?php echo esc_html($theme_name_gbt_dash); ?> theme requires a valid license key. Activate your license to access built-in updates and priority support. <a href="javascript:void(0)" class="show-license-types-help text-wp-blue hover:underline whitespace-nowrap">📋 Learn more</a>
					</p>
					<div class="flex flex-wrap items-center gap-4">
						<a href="<?php echo esc_url($license_page_url); ?>#license-area" class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-wp-red)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[var(--color-wp-red)]/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-red)] transition-colors">
							<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
							</svg>
							Activate License
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
} elseif ($is_license_active && !$is_support_active) {
	// License active but support expired
	?>
	<div class="relative overflow-hidden bg-white border border-[var(--color-wp-red)] border-l-4 border-l-[var(--color-wp-red)] shadow-sm px-8 py-6 mb-8">
		<div class="w-full">
			<div class="flex items-start gap-6">
				<div class="flex-shrink-0">
					<div class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-wp-red)]/10">
						<svg class="h-7 w-7 text-[var(--color-wp-red)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
						</svg>
					</div>
				</div>
				<div class="flex-1 min-w-0">
					<div class="flex flex-wrap items-center gap-2 mb-2">
						<span class="inline-flex items-center rounded-md bg-[var(--color-wp-green)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-green)] ring-1 ring-inset ring-[var(--color-wp-green)]/20">
							Lifetime License Active
						</span>
						<span class="inline-flex items-center rounded-md bg-[var(--color-wp-red)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-red)] ring-1 ring-inset ring-[var(--color-wp-red)]/20">
							Subscription Expired
						</span>
						<span class="text-gray-400">•</span>
						<span class="text-sm text-gray-600"><?php echo esc_html($theme_name_gbt_dash); ?> v<?php echo esc_html($theme_version_gbt_dash); ?></span>
					</div>
					<h3 class="text-xl font-semibold text-gray-900 mb-2">
						Your subscription for <?php echo esc_html($theme_name_gbt_dash); ?> theme has expired
					</h3>
					<div class="flex items-center gap-4 mb-4">
						<span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
							<svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
							</svg>
							Built-in updates disabled, no support access
						</span>
					</div>
					<p class="text-sm text-gray-600 mb-4 max-w-3xl">
						Your subscription has ended. Renew now to restore access to built-in updates, security patches, and priority support for your <?php echo esc_html($theme_name_gbt_dash); ?> theme. <a href="javascript:void(0)" class="show-license-types-help text-wp-blue hover:underline whitespace-nowrap">📋 Learn more</a>
					</p>
					<div class="flex flex-wrap items-center gap-4">
						<a href="<?php echo esc_url($license_page_url); ?>#license-options" class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-wp-red)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[var(--color-wp-red)]/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-red)] transition-colors">
							<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
							</svg>
							Renew Subscription
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
} elseif ($is_license_active && $is_support_active && $license_manager->is_support_expiring_soon()) {
	// Support expiring soon
	$days_remaining = $license_manager->get_support_days_remaining();
	$support_expiration_date = $license_manager->get_support_expiration_date();
	$expiration_date = $support_expiration_date ? date('F j, Y', $support_expiration_date) : 'soon';
	?>
	<div class="relative overflow-hidden bg-white border border-[var(--color-wp-yellow)] border-l-4 border-l-[var(--color-wp-yellow)] shadow-sm px-8 py-6 mb-8">
		<div class="w-full">
			<div class="flex items-start gap-6">
				<div class="flex-shrink-0">
					<div class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-wp-yellow)]/10">
						<svg class="h-7 w-7 text-[var(--color-wp-yellow)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
						</svg>
					</div>
				</div>
				<div class="flex-1 min-w-0">
					<div class="flex flex-wrap items-center gap-2 mb-2">
						<span class="inline-flex items-center rounded-md bg-[var(--color-wp-green)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-green)] ring-1 ring-inset ring-[var(--color-wp-green)]/20">
							Lifetime License Active
						</span>
						<span class="inline-flex items-center rounded-md bg-[var(--color-wp-yellow)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-yellow)] ring-1 ring-inset ring-[var(--color-wp-yellow)]/20">
							<?php
							if ($days_remaining == 0) {
								echo 'Expires Today';
							} elseif ($days_remaining == 1) {
								echo 'Expires Tomorrow';
							} else {
								echo 'Expires in ' . esc_html($days_remaining) . ' Days';
							}
							?>
						</span>
						<span class="text-gray-400">•</span>
						<span class="text-sm text-gray-600"><?php echo esc_html($theme_name_gbt_dash); ?> v<?php echo esc_html($theme_version_gbt_dash); ?></span>
					</div>
					<h3 class="text-xl font-semibold text-gray-900 mb-2">
						Your subscription for <?php echo esc_html($theme_name_gbt_dash); ?> theme expiring soon
					</h3>
					<div class="flex items-center gap-4 mb-4">
						<span class="inline-flex items-center gap-1.5 text-xs text-[var(--color-wp-green)]">
							<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
							</svg>
							Built-in updates and support active until <?php echo esc_html($expiration_date); ?>
						</span>
					</div>
					<p class="text-sm text-gray-600 mb-4 max-w-3xl">
						Your subscription expires on <?php echo esc_html($expiration_date); ?>. 
						<?php
						if ($days_remaining == 0) {
							echo 'Renew today to maintain continuous access to built-in updates and support.';
						} elseif ($days_remaining == 1) {
							echo 'Renew before tomorrow to avoid losing access to built-in updates and support.';
						} else {
							echo 'Renew now to maintain uninterrupted access to built-in updates and support.';
						}
						?> <a href="javascript:void(0)" class="show-license-types-help text-wp-blue hover:underline whitespace-nowrap">📋 Learn more</a>
					</p>
					<div class="flex flex-wrap items-center gap-4">
						<a href="<?php echo esc_url($license_page_url); ?>#license-options" class="inline-flex items-center gap-2 rounded-lg bg-[var(--color-wp-yellow)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[var(--color-wp-yellow)]/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-wp-yellow)] transition-colors">
							<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
							</svg>
							Renew Now
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

// ================================================= 
// License Types Help Modal (shown with banner)
// ================================================= 
?>

<div id="license-types-help-modal" class="fixed inset-0 z-[9999] hidden" aria-labelledby="license-types-modal-title" role="dialog" aria-modal="true">
	<div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
	<div class="fixed inset-0 z-[9999] w-screen overflow-y-auto">
		<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
			<div class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
				<div class="sm:flex sm:items-start">
					<div class="mt-3 sm:mt-0 text-left w-full">
						<h3 class="text-base font-semibold text-gray-900" id="license-types-modal-title">Licensing and Subscription options available to you</h3>
						<div class="mt-4">
							<!-- YouTube Video Section -->
							<div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
								<div>
									<?php
									echo wp_kses( gbt_get_youtube_video_content(), gbt_get_youtube_video_allowed_html() );
									?>
								</div>
							</div>

							<div class="mb-4 bg-gray-50 border border-gray-200 rounded-lg p-4">
								<div class="mb-4 bg-[var(--color-wp-blue)]/10 border border-[var(--color-wp-blue)]/20 rounded-lg p-4">
									<h4 class="text-sm font-medium text-gray-900 flex items-center">
										Lifetime Regular License
										<?php if ($regular_license_is_sale): ?>
											<span class="ml-1 bg-[var(--color-wp-red)] text-white text-xs font-bold px-1.5 py-0.5 rounded-full">SALE</span>
											<span class="ml-2">
												<span class="text-lg line-through text-gray-500 mr-1"><?php echo esc_html($original_price_regular_license_display); ?></span>
												<span class="text-lg font-semibold text-gray-900"><?php echo esc_html($theme_default_price_display); ?></span>
											</span>
										<?php else: ?>
											<span class="ml-1">(<?php echo esc_html($theme_default_price_display); ?>)</span>
										<?php endif; ?>
									</h4>
									<p class="text-sm text-gray-600 mt-1">Perfect for most websites, the Lifetime Regular License includes:</p>
									<ul class="text-sm text-gray-600 list-disc ml-5 mt-2 space-y-1">
										<li>Use on a single end product</li>
										<li>Use in a personal project or on behalf of a client</li>
										<li>Support subscription for 6 months - included</li>
										<li>All theme features and updates</li>
									</ul>
									<div class="text-left">
										<a href="https://1.envato.market/theme-license" target="_blank" class="text-sm text-wp-blue hover:underline underline decoration-dotted">Learn more about licensing options</a>
									</div>
									<?php if ($is_license_active): ?>
										<div class="flex items-center gap-2 mt-3">
											<span class="inline-flex items-center rounded-md bg-[var(--color-wp-green)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-green)] ring-1 ring-inset ring-[var(--color-wp-green)]/20">You have this</span>
										</div>
									<?php else: ?>
										<div class="flex items-center gap-2 mt-3">
											<span class="inline-flex items-center rounded-md bg-[var(--color-wp-red)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-red)] ring-1 ring-inset ring-[var(--color-wp-red)]/20">You need this</span>
											<span class="text-gray-600">→</span>
											<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="inline-flex items-center rounded-md bg-wp-blue px-2.5 py-1 text-xs font-semibold text-white border border-wp-blue hover:bg-wp-blue/90 hover:border-wp-blue/90 transition-colors">Act now</a>
										</div>
									<?php endif; ?>
								</div>

								<div class="bg-[var(--color-wp-blue)]/10 border border-[var(--color-wp-blue)]/20 rounded-lg p-4">
									<h4 class="text-sm font-medium text-gray-900 flex items-center">
										Support Subscription
										<?php if ($professional_license_is_sale): ?>
											<span class="ml-1 bg-[var(--color-wp-red)] text-white text-xs font-bold px-1.5 py-0.5 rounded-full">SALE</span>
											<span class="ml-2">
												<span class="text-sm line-through text-gray-500 mr-1"><?php echo esc_html($original_professional_price_display); ?></span>
												<span class="text-sm font-semibold text-[var(--color-wp-blue)]"><?php echo esc_html($professional_price_display); ?></span>
											</span>
										<?php else: ?>
											<span class="ml-1">(<?php echo esc_html($professional_price_display); ?>)</span>
										<?php endif; ?>
									</h4>
									<p class="text-sm text-gray-600"><?php echo esc_html($professional_price_text); ?></p>
									<p class="text-sm text-gray-600 mt-1">Subscription unlocks built-in updates, assistance, and premium add-ons</p>
									<div class="text-left">
										<a href="https://1.envato.market/extend-or-renew-items" target="_blank" class="text-sm text-wp-blue hover:underline underline decoration-dotted">Learn more about subscription and support</a>
									</div>
									<?php if ($is_license_active): ?>
										<div class="flex items-center gap-2 mt-3">
											<?php if ($is_support_active && $license_manager->is_support_expiring_soon()): ?>
												<span class="inline-flex items-center rounded-md bg-[var(--color-wp-yellow)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-yellow)] ring-1 ring-inset ring-[var(--color-wp-yellow)]/20">You need to extend</span>
												<span class="text-gray-600">→</span>
												<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="inline-flex items-center rounded-md bg-wp-blue px-2.5 py-1 text-xs font-semibold text-white border border-wp-blue hover:bg-wp-blue/90 hover:border-wp-blue/90 transition-colors">Act now</a>
											<?php elseif (!$is_support_active): ?>
												<span class="inline-flex items-center rounded-md bg-[var(--color-wp-red)]/10 px-2.5 py-1 text-xs font-semibold text-[var(--color-wp-red)] ring-1 ring-inset ring-[var(--color-wp-red)]/20">You need this</span>
												<span class="text-gray-600">→</span>
												<a href="<?php echo esc_url($gbt_dashboard_setup->get_theme_config('theme_sales_page_url')); ?>" target="_blank" class="inline-flex items-center rounded-md bg-wp-blue px-2.5 py-1 text-xs font-semibold text-white border border-wp-blue hover:bg-wp-blue/90 hover:border-wp-blue/90 transition-colors">Act now</a>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<div>
								<h4 class="text-sm font-medium text-gray-900 flex items-center">
									Lifetime Extended License
									<?php if ($extended_license_is_sale): ?>
										<span class="ml-1 bg-[var(--color-wp-red)] text-white text-xs font-bold px-1.5 py-0.5 rounded-full">SALE</span>
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

							<p class="mt-4 text-sm text-gray-500">For more detailed information, please refer to the <a href="https://1.envato.market/theme-license" target="_blank" class="text-wp-blue hover:underline">License Terms</a>.</p>
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

<!-- Video Only Modal -->
<div id="video-only-modal" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true">
	<div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
	<div class="fixed inset-0 z-[9999] w-screen overflow-y-auto">
		<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
			<div class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
				<div class="sm:flex sm:items-start">
					<div class="mt-3 sm:mt-0 text-left w-full">
						<div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
							<?php
							echo wp_kses( gbt_get_youtube_video_content(), gbt_get_youtube_video_allowed_html() );
							?>
						</div>
					</div>
				</div>
				<div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
					<button type="button" id="close-video-modal" class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:ring-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 sm:ml-3 sm:w-auto transition duration-150 ease-in-out cursor-pointer">Close</button>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
