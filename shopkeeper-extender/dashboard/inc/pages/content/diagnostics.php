<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('getbowtied_diagnostics_content')) {
	/**
	 * Display system diagnostics content
	 */
	function getbowtied_diagnostics_content()
	{
		// Theme and Dashboard Setup
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
		$base_paths = $gbt_dashboard_setup->get_base_paths();
		$theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
		// Load the License Manager class if not already loaded
		if (!class_exists('GBT_License_Manager')) {
			require_once $base_paths['path'] . '/dashboard/inc/classes/class-license-manager.php';
		}

		// Initialize Core Services
		$license_manager = GBT_License_Manager::get_instance();
		$config = GBT_License_Config::get_instance();

		// Server Information
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : 'Not available';
		$server_addr = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : 'Not available';
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'Not available';

		// Environment Configuration
		$is_localhost = $license_manager->is_localhost();
		$localhost_hostnames = $config->get_localhost_hostnames();
		$localhost_extensions = $config->get_localhost_domain_extensions();

		// WordPress and Theme Information
		$active_plugins = count(get_option('active_plugins'));
		$theme_data = wp_get_theme();
		$parent_theme = $theme_data->parent() ? $theme_data->parent()->get('Name') . ' (v' . $theme_data->parent()->get('Version') . ')' : 'None';

		// License Data
		$stored_options = $license_manager->get_license_data();
		$license_key = $stored_options['license_key'] ?? '';
		$license_status = $stored_options['license_status'] ?? 'inactive';
		$license_info = $stored_options['license_info'] ?? [];
		$support_expiration_date = $stored_options['support_expiration_date'] ?? '';
		$is_license_active = $license_manager->is_license_active();
		$is_support_active = $license_manager->is_support_active();

		// Format support expiration date if available
		$formatted_expiration_date = !empty($support_expiration_date) ?
			date_i18n(get_option('date_format'), $support_expiration_date) :
			'Not available';

		// Days remaining
		$days_remaining = $license_manager->get_support_days_remaining();
		$days_remaining_text = ($days_remaining !== false) ?
			$days_remaining . ' days' :
			'Not available';

		// Verification and Timestamps
		$verification_url = $license_manager->get_verification_url();
		$last_verified = $license_manager->get_last_verified_time();
		$wp_timezone = wp_timezone();
		$datetime = new DateTime();
		$last_verified_display = 'Never';
		if ($last_verified) {
			$datetime->setTimestamp($last_verified);
			$datetime->setTimezone($wp_timezone);
			$last_verified_display = $datetime->format('F j, Y g:i a (T)');
		}

		// Diagnostic Data Arrays
		$diagnostic_data = [
			'Server Name ($_SERVER[\'SERVER_NAME\'])' => $server_name,
			'Server Address ($_SERVER[\'SERVER_ADDR\'])' => $server_addr,
			'Remote Address ($_SERVER[\'REMOTE_ADDR\'])' => $remote_addr,
			'Localhost Detection' => $is_localhost ? 'true' : 'false',
			'PHP Version' => phpversion(),
			'WordPress Version' => get_bloginfo('version'),
			'OS/Platform' => php_uname()
		];

		$additional_data = [
			'Theme Name' => $theme_data->get('Name'),
			'Theme Version' => $theme_data->get('Version'),
			'Parent Theme' => $parent_theme,
			'Active Plugins' => $active_plugins,
			'WordPress Memory Limit' => WP_MEMORY_LIMIT,
			'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
			'WP Cron' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'Disabled' : 'Enabled',
			'Site URL' => get_site_url(),
			'Home URL' => get_home_url(),
			'Multisite' => is_multisite() ? 'Yes' : 'No',
			'Database Version' => get_option('db_version')
		];

		$license_info_data = [
			'Purchase Code' => !empty($license_key) ? substr($license_key, 0, 4) . '****' : 'Not set',
			'License Status' => ucfirst($license_status),
			'Support Status' => $is_support_active ? 'Active' : 'Expired',
			'Support Expiration' => $formatted_expiration_date,
			'Last Verified' => $last_verified_display
		];

		// Content Start
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';
		
		// Include badges component
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/badges.php';

		// Main content
?>

		<div class="overflow-hidden py-24 sm:py-32">
			<div class="mx-auto max-w-7xl px-6 lg:px-8">
				<div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-1">
					<div>
						<div class="lg:max-w-lg">
							<?php gbt_display_version_badge(); ?>
							<h2 class="mt-4 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl leading-14"><?php echo esc_html($theme_name_gbt_dash); ?> System Diagnostics</h2>
							<p class="mt-6 text-lg leading-8 text-gray-600">
								View detailed information about your server environment, WordPress configuration, and license detection settings.
							</p>
						</div>
					</div>

					<div>
						<!-- Diagnostic Information Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 01-1-1v-4a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd" />
									</svg>
									Server Environment & Configuration
								</h3>
							</div>
							<div class="p-8">
								<div class="overflow-x-auto">
									<table class="w-full text-left border-collapse">
										<thead>
											<tr>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Parameter</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Value</th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($diagnostic_data as $param => $value):
												$value_display = is_bool($value) ? ($value ? 'true' : 'false') : $value;
											?>
												<tr class="border-b border-gray-100">
													<td class="py-3 px-4 font-medium text-gray-700"><?php echo esc_html($param); ?></td>
													<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($value_display); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>

								<div class="mt-6 bg-[var(--color-wp-yellow)]/10 border border-[var(--color-wp-yellow)]/20 rounded-md p-4">
									<div class="flex">
										<div class="flex-shrink-0">
											<svg class="h-5 w-5 text-[var(--color-wp-yellow)]" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
											</svg>
										</div>
										<div class="ml-3">
											<h3 class="text-sm font-medium text-[var(--color-wp-yellow)]">Environment Detection Status</h3>
											<div class="mt-2 text-sm text-[var(--color-wp-yellow)]">
												<p>The system uses the above parameters to determine if your site is running in a localhost environment. Localhost environments skip server database updates for license verification.</p>
												<p class="mt-2">
													<strong>Current environment detection:</strong><br>
													<?php if ($is_localhost): ?>
														<span class="text-[var(--color-wp-green)] font-semibold">• Detected as localhost</span> (server database updates are skipped)<br>
													<?php else: ?>
														<span class="text-[var(--color-wp-blue)] font-semibold">• Detected as production</span> (full license verification with server database updates)<br>
													<?php endif; ?>
								<span class="text-[var(--color-wp-blue)] font-semibold">• Production endpoints are being used for all license requests</span>
												</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- WordPress and Theme Information Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-8">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
									</svg>
									Theme & WordPress Configuration
								</h3>
							</div>
							<div class="p-8">
								<div class="overflow-x-auto">
									<table class="w-full text-left border-collapse">
										<thead>
											<tr>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Parameter</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Value</th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($additional_data as $param => $value):
											?>
												<tr class="border-b border-gray-100">
													<td class="py-3 px-4 font-medium text-gray-700"><?php echo esc_html($param); ?></td>
													<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($value); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						<!-- License Configuration Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-8">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
									</svg>
									License Configuration
								</h3>
							</div>
							<div class="p-8">
								<div class="mb-6 p-4 rounded-lg border <?php echo $is_license_active ? 'border-[var(--color-wp-green)]/20 bg-[var(--color-wp-green)]/10' : 'border-[var(--color-wp-yellow)]/20 bg-[var(--color-wp-yellow)]/10'; ?>">
									<div class="flex">
										<div class="flex-shrink-0">
											<?php if ($is_license_active): ?>
												<svg class="h-5 w-5 text-[var(--color-wp-green)]" viewBox="0 0 20 20" fill="currentColor">
													<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
												</svg>
											<?php else: ?>
												<svg class="h-5 w-5 text-[var(--color-wp-yellow)]" viewBox="0 0 20 20" fill="currentColor">
													<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
												</svg>
											<?php endif; ?>
										</div>
										<div class="ml-3">
											<h3 class="text-sm font-medium <?php echo $is_license_active ? 'text-[var(--color-wp-green)]' : 'text-[var(--color-wp-yellow)]'; ?>">
												License Status: <?php echo esc_html(ucfirst($license_status)); ?>
											</h3>
											<div class="mt-2 text-sm <?php echo $is_license_active ? 'text-[var(--color-wp-green)]' : 'text-[var(--color-wp-yellow)]'; ?>">
												<p>
													<?php if ($is_license_active): ?>
														Your license is active. Support is <?php echo $is_support_active ? 'active' : 'expired'; ?>.
													<?php else: ?>
														No active license found. Please activate your license on the License page.
													<?php endif; ?>
												</p>
											</div>
										</div>
									</div>
								</div>

								<div class="overflow-x-auto">
									<table class="w-full text-left border-collapse">
										<thead>
											<tr>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">License Parameter</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Value</th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($license_info_data as $param => $value):
											?>
												<tr class="border-b border-gray-100">
													<td class="py-3 px-4 font-medium text-gray-700"><?php echo esc_html($param); ?></td>
													<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($value); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>

								<?php if ($is_license_active): ?>
									<div class="mt-6">
										<a href="<?php echo esc_url(admin_url('admin.php?page=getbowtied-license')); ?>" class="inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white bg-wp-blue hover:bg-wp-blue/90 transition-colors no-underline shadow-sm">
											<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
											</svg>
											Manage License
										</a>
									</div>
								<?php else: ?>
									<div class="mt-6">
										<a href="<?php echo esc_url(admin_url('admin.php?page=getbowtied-license')); ?>" class="inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white bg-wp-blue hover:bg-wp-blue/90 transition-colors no-underline shadow-sm">
											<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
											</svg>
											Activate License
										</a>
									</div>
								<?php endif; ?>
							</div>
						</div>

						<!-- Localhost Configuration Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-8">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
									</svg>
									Localhost Configuration
								</h3>
							</div>
							<div class="p-8">
								<div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h4 class="text-base font-medium text-gray-800 mb-2">Localhost Hostnames</h4>
										<div class="max-h-64 overflow-y-auto pr-2">
											<ul class="list-disc list-inside space-y-1 text-gray-600">
												<?php foreach ($localhost_hostnames as $hostname): ?>
													<li class="font-mono text-sm"><?php echo esc_html($hostname); ?></li>
												<?php endforeach; ?>
											</ul>
										</div>
										<p class="mt-3 text-sm text-gray-500">These hostnames are directly matched to identify localhost environments.</p>
									</div>

									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h4 class="text-base font-medium text-gray-800 mb-2">Localhost Domain Extensions</h4>
										<div class="max-h-64 overflow-y-auto pr-2">
											<ul class="list-disc list-inside space-y-1 text-gray-600">
												<?php foreach ($localhost_extensions as $extension): ?>
													<li class="font-mono text-sm"><?php echo esc_html($extension); ?></li>
												<?php endforeach; ?>
											</ul>
										</div>
										<p class="mt-3 text-sm text-gray-500">Domains ending with these extensions are identified as localhost environments.</p>
									</div>
								</div>

								<div class="bg-[var(--color-wp-yellow)]/10 border border-[var(--color-wp-yellow)]/20 rounded-md p-4">
									<div class="flex">
										<div class="flex-shrink-0">
											<svg class="h-5 w-5 text-[var(--color-wp-yellow)]" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 01-1-1v-4a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd"></path>
											</svg>
										</div>
										<div class="ml-3">
											<h3 class="text-sm font-medium text-[var(--color-wp-yellow)]">Localhost Detection Method</h3>
											<div class="mt-2 text-sm text-[var(--color-wp-yellow)]">
												<p>The system considers a site to be running on localhost if any of these conditions are met:</p>
												<ol class="list-decimal list-inside mt-1 ml-2">
													<li>The server name matches one of the localhost hostnames</li>
													<li>The server name ends with one of the localhost domain extensions</li>
													<li>The server address is 'localhost' or '::1'</li>
													<li>The remote address is 'localhost' or '::1'</li>
												</ol>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- API Configuration Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-8">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path d="M13 7H7v6h6V7z" />
										<path fill-rule="evenodd" d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2z" clip-rule="evenodd" />
									</svg>
									API Configuration
								</h3>
							</div>
							<div class="p-8">
								<div class="overflow-x-auto">
									<table class="w-full text-left border-collapse">
										<thead>
											<tr>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">API Endpoint</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Configured URL</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Active URL</th>
											</tr>
										</thead>
										<tbody>
											<tr class="border-b border-gray-100">
												<td class="py-3 px-4 font-medium text-gray-700">Company Website</td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($config->get_company_website_url()); ?></td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($config->get_company_website_url()); ?></td>
											</tr>
						<tr class="border-b border-gray-100">
							<td class="py-3 px-4 font-medium text-gray-700">API Base URL</td>
							<?php $base_urls = $config->get_api_base_urls(); ?>
							<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($base_urls[0] ?? ''); ?></td>
							<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($base_urls[0] ?? ''); ?></td>
						</tr>
						<tr class="border-b border-gray-100">
							<td class="py-3 px-4 font-medium text-gray-700">License Verification</td>
							<?php $verification_urls = $config->get_verification_urls(); ?>
							<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($verification_urls[0] ?? ''); ?></td>
							<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($verification_urls[0] ?? ''); ?></td>
						</tr>
						<tr class="border-b border-gray-100">
							<td class="py-3 px-4 font-medium text-gray-700">License Server API</td>
							<?php $license_server_urls = $config->get_license_server_urls(); ?>
							<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($license_server_urls[0] ?? ''); ?></td>
							<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($license_server_urls[0] ?? ''); ?></td>
						</tr>
										</tbody>
									</table>
								</div>

					<div class="mt-4 p-4 bg-[var(--color-wp-blue)]/10 border-[var(--color-wp-blue)]/20 rounded-lg border">
									<div class="flex">
										<div class="flex-shrink-0">
								<svg class="h-5 w-5 text-[var(--color-wp-blue)]" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 01-1-1v-4a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd" />
											</svg>
										</div>
										<div class="ml-3">
								<h3 class="text-sm font-medium text-[var(--color-wp-blue)]">
									Production Endpoints Active
								</h3>
								<div class="mt-2 text-sm text-[var(--color-wp-blue)]">
									<p>All API requests use live GetBowtied endpoints. No development overrides are active.</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Curl Test Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-8">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414z" clip-rule="evenodd" />
									</svg>
									License API Test
								</h3>
							</div>
							<div class="p-8">
								<h4 class="text-base font-medium text-gray-800 mb-4">API URL Fallback Test</h4>
								
								<?php
								// Execute request using the same logic as the license manager
								// nosemgrep: generic-api-key -- Placeholder for diagnostics API test, not a real secret.
								$license_key = '6646352a-4e78-4669-b7c0-736b41198171';
								
					// Get all URLs to test (primary + fallback)
					$urls_to_test = $config->get_verification_urls();
					$environment = 'Production';
								
								$request_args = [
									'body' => [
										'license_key' => $license_key
									],
									'timeout' => 30,
									'headers' => [
										'X-Requested-With' => 'XMLHttpRequest',
										'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
										'Accept' => 'application/json, text/javascript, */*; q=0.01',
										'Origin' => home_url(),
										'Referer' => admin_url()
									],
									'sslverify' => true,
								];
								
								$total_start_time = microtime(true);
								$url_results = [];
								$successful_url = null;
								$first_valid_response = null;
								
								// Test each URL and show the fallback logic
								foreach ($urls_to_test as $index => $url) {
									$url_start_time = microtime(true);
									$response = wp_remote_post($url, $request_args);
									$url_time = microtime(true) - $url_start_time;
									
									$result = [
										'url' => $url,
										'index' => $index + 1,
										'time' => $url_time,
										'is_error' => is_wp_error($response),
										'error_message' => is_wp_error($response) ? $response->get_error_message() : null,
										'response_code' => is_wp_error($response) ? null : wp_remote_retrieve_response_code($response),
										'body' => is_wp_error($response) ? null : wp_remote_retrieve_body($response),
										'is_valid_json' => false,
										'json_data' => null,
										'used_by_system' => false
									];
									
									// Check if response is valid JSON
									if (!$result['is_error'] && !empty($result['body'])) {
										$json_data = json_decode($result['body'], true);
										if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
											$result['is_valid_json'] = true;
											$result['json_data'] = $json_data;
											
											// This would be the URL the system uses (first successful one)
											if ($successful_url === null) {
												$successful_url = $url;
												$first_valid_response = $result;
												$result['used_by_system'] = true;
											}
										}
									}
									
									$url_results[] = $result;
									
									// In real system, we would stop here if we got valid JSON
									// But for diagnostics, we test all URLs to show the full picture
								}
								
								$total_time = microtime(true) - $total_start_time;
								?>
								
								<div class="mb-6">
									<div class="bg-[var(--color-wp-blue)]/10 p-4 rounded-lg border border-[var(--color-wp-blue)]/20">
										<h5 class="text-sm font-medium text-[var(--color-wp-blue)] mb-2">Environment & Fallback Logic</h5>
										<div class="text-sm text-[var(--color-wp-blue)]">
											<p><strong>Environment:</strong> <?php echo esc_html($environment); ?></p>
											<p><strong>URLs to test:</strong> <?php echo count($urls_to_test); ?></p>
											<p><strong>Logic:</strong> Try each URL in order until one returns valid JSON, then stop.</p>
											<?php if ($successful_url): ?>
											<p class="mt-2"><strong>✅ System would use:</strong> <code><?php echo esc_html($successful_url); ?></code></p>
											<?php else: ?>
											<p class="mt-2"><strong>❌ All URLs failed</strong> - System would show error message</p>
											<?php endif; ?>
										</div>
									</div>
								</div>

								<div class="mb-6 space-y-4">
									<?php foreach ($url_results as $result): ?>
									<div class="bg-gray-50 p-4 rounded-lg border <?php echo $result['used_by_system'] ? 'border-[var(--color-wp-green)] bg-[var(--color-wp-green)]/10' : 'border-gray-200'; ?>">
										<div class="flex items-center justify-between mb-2">
											<h5 class="text-sm font-medium text-gray-800">
												URL #<?php echo esc_html($result['index']); ?>
												<?php if ($result['used_by_system']): ?>
													<span class="ml-2 px-2 py-1 text-xs bg-[var(--color-wp-green)]/20 text-[var(--color-wp-green)] rounded">✅ Used by System</span>
												<?php elseif ($result['is_valid_json']): ?>
													<span class="ml-2 px-2 py-1 text-xs bg-[var(--color-wp-yellow)]/20 text-[var(--color-wp-yellow)] rounded">⚠️ Valid but not used</span>
												<?php else: ?>
													<span class="ml-2 px-2 py-1 text-xs bg-[var(--color-wp-red)]/20 text-[var(--color-wp-red)] rounded">❌ Failed/Invalid</span>
												<?php endif; ?>
											</h5>
											<span class="text-xs text-gray-500"><?php echo number_format($result['time'], 4); ?>s</span>
										</div>
										
										<div class="bg-gray-100 p-3 rounded font-mono text-xs mb-3">
											<?php echo esc_html($result['url']); ?>
										</div>
										
										<?php if ($result['is_error']): ?>
											<div class="bg-[var(--color-wp-red)]/20 p-3 rounded">
												<div class="text-[var(--color-wp-red)] text-sm font-medium">WordPress Error:</div>
												<div class="text-[var(--color-wp-red)] text-xs font-mono mt-1"><?php echo esc_html($result['error_message']); ?></div>
											</div>
										<?php else: ?>
											<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
												<div class="bg-gray-100 p-3 rounded">
													<div class="text-gray-700 text-xs font-medium mb-1">Response Code:</div>
													<div class="text-xs font-mono <?php echo ($result['response_code'] >= 200 && $result['response_code'] < 300) ? 'text-[var(--color-wp-green)]' : 'text-[var(--color-wp-red)]'; ?>">
														HTTP <?php echo esc_html($result['response_code']); ?>
													</div>
												</div>
												<div class="bg-gray-100 p-3 rounded">
													<div class="text-gray-700 text-xs font-medium mb-1">JSON Valid:</div>
													<div class="text-xs font-mono <?php echo $result['is_valid_json'] ? 'text-[var(--color-wp-green)]' : 'text-[var(--color-wp-red)]'; ?>">
														<?php echo $result['is_valid_json'] ? '✅ Yes' : '❌ No'; ?>
													</div>
												</div>
											</div>
											
											<?php if (!empty($result['body'])): ?>
											<div class="mt-3 bg-gray-100 p-3 rounded">
												<div class="text-gray-700 text-xs font-medium mb-2">Response Body:</div>
												<div class="max-h-40 overflow-y-auto">
													<?php if ($result['is_valid_json']): ?>
														<pre class="text-xs"><?php echo esc_html(json_encode($result['json_data'], JSON_PRETTY_PRINT)); ?></pre>
													<?php else: ?>
														<pre class="text-xs"><?php echo esc_html(substr($result['body'], 0, 500)) . (strlen($result['body']) > 500 ? '...' : ''); ?></pre>
													<?php endif; ?>
												</div>
											</div>
											<?php endif; ?>
										<?php endif; ?>
									</div>
									<?php endforeach; ?>
								</div>
								
								<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
									<h5 class="text-sm font-medium text-gray-800 mb-2">Summary</h5>
									<div class="text-sm text-gray-700 space-y-1">
										<p><strong>Total test time:</strong> <?php echo number_format($total_time, 4); ?> seconds</p>
										<p><strong>URLs tested:</strong> <?php echo count($url_results); ?></p>
										<p><strong>Valid responses:</strong> <?php echo count(array_filter($url_results, function($r) { return $r['is_valid_json']; })); ?></p>
										<?php if ($successful_url): ?>
										<p><strong>System behavior:</strong> Would use first successful URL and stop testing others</p>
										<p><strong>Actual system time:</strong> ~<?php echo number_format($first_valid_response['time'], 4); ?> seconds (only first successful URL)</p>
										<?php else: ?>
										<p><strong>System behavior:</strong> Would show error message to user</p>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Price & Sale Detection Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-8">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
									</svg>
									Price & Sale Detection Analysis
								</h3>
							</div>
							<div class="p-8">
								<?php
								// Load Theme Price Updater if needed
								if (!class_exists('GBT_Theme_Price_Updater')) {
									require_once $base_paths['path'] . '/dashboard/inc/classes/class-theme-price-updater.php';
								}
								$price_updater = GBT_Theme_Price_Updater::get_instance();
								
								// Get default prices from config
								$theme_default_price_regular_license = $gbt_dashboard_setup->get_theme_config('theme_default_price_regular_license');
								$theme_default_price_extended_license = $gbt_dashboard_setup->get_theme_config('theme_default_price_extended_license');
								
								// Get current live price data
								$price_data = $price_updater->get_current_price_data(
									$theme_default_price_regular_license,
									$theme_default_price_extended_license
								);
								
								// Sale detection logic (same as license.php)
								$regular_license_is_sale = false;
								$extended_license_is_sale = false;
								$professional_license_is_sale = false;
								
								if (isset($price_data['regular_license_price']) && isset($price_data['extended_license_price'])) {
									// Compare live prices with default prices to check for sales
									$regular_license_is_sale = $price_data['regular_license_price'] < $theme_default_price_regular_license;
									$extended_license_is_sale = $price_data['extended_license_price'] < $theme_default_price_extended_license;
									
									// If regular license is on sale, professional license is also on sale
									$professional_license_is_sale = $regular_license_is_sale;
								}
								
								// Get support price formula and calculate professional price
								$support_price_formula = $gbt_dashboard_setup->get_global_config('support_prices', 'support_price_formula');
								$live_professional_price = is_callable($support_price_formula) ? $support_price_formula($price_data['regular_license_price']) : 0;
								$default_professional_price = is_callable($support_price_formula) ? $support_price_formula($theme_default_price_regular_license) : 0;
								
								// Get last price verification time
								$price_last_verified = $price_updater->get_last_verification_time();
								$price_last_verified_display = 'Never';
								if ($price_last_verified) {
									$wp_timezone = wp_timezone();
									$datetime = new DateTime();
									$datetime->setTimestamp($price_last_verified);
									$datetime->setTimezone($wp_timezone);
									$price_last_verified_display = $datetime->format('F j, Y g:i a (T)');
								}
								?>
								
								<h4 class="text-base font-medium text-gray-800 mb-4">Price Comparison & Sale Detection</h4>
								
								<div class="mb-6 bg-[var(--color-wp-blue)]/10 p-4 rounded-lg border border-[var(--color-wp-blue)]/20">
									<h5 class="text-sm font-medium text-[var(--color-wp-blue)] mb-2">How Sale Detection Works</h5>
									<div class="text-sm text-[var(--color-wp-blue)]">
										<p><strong>Logic:</strong> A sale is detected when the live API price is lower than the default config price.</p>
										<p><strong>Formula:</strong> <code>is_sale = live_price &lt; default_price</code></p>
										<p><strong>Last Updated:</strong> <?php echo esc_html($price_last_verified_display); ?></p>
									</div>
								</div>

								<div class="overflow-x-auto">
									<table class="w-full text-left border-collapse">
										<thead>
											<tr>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">License Type</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Default Price</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Live Price</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Sale Status</th>
												<th class="py-3 px-4 bg-gray-50 font-medium text-gray-700 border-b">Difference</th>
											</tr>
										</thead>
										<tbody>
											<tr class="border-b border-gray-100">
												<td class="py-3 px-4 font-medium text-gray-700">Regular License</td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm">$<?php echo number_format($theme_default_price_regular_license, 0); ?></td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm">$<?php echo number_format($price_data['regular_license_price'], 0); ?></td>
												<td class="py-3 px-4">
													<?php if ($regular_license_is_sale): ?>
														<span class="px-2 py-1 text-xs bg-[var(--color-wp-red)]/20 text-[var(--color-wp-red)] rounded font-medium">🔥 ON SALE</span>
													<?php else: ?>
														<span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">No sale</span>
													<?php endif; ?>
												</td>
												<td class="py-3 px-4 text-sm">
													<?php 
													$regular_diff = $price_data['regular_license_price'] - $theme_default_price_regular_license;
													$regular_diff_percent = $theme_default_price_regular_license > 0 ? ($regular_diff / $theme_default_price_regular_license) * 100 : 0;
													?>
													<span class="<?php echo $regular_diff < 0 ? 'text-[var(--color-wp-red)]' : ($regular_diff > 0 ? 'text-[var(--color-wp-green)]' : 'text-gray-600'); ?>">
														<?php echo $regular_diff > 0 ? '+' : ''; ?>$<?php echo number_format($regular_diff, 0); ?>
														<?php if ($regular_diff != 0): ?>
															(<?php echo $regular_diff > 0 ? '+' : ''; ?><?php echo number_format($regular_diff_percent, 1); ?>%)
														<?php endif; ?>
													</span>
												</td>
											</tr>
											<tr class="border-b border-gray-100">
												<td class="py-3 px-4 font-medium text-gray-700">Extended License</td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm">$<?php echo number_format($theme_default_price_extended_license, 0); ?></td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm">$<?php echo number_format($price_data['extended_license_price'], 0); ?></td>
												<td class="py-3 px-4">
													<?php if ($extended_license_is_sale): ?>
														<span class="px-2 py-1 text-xs bg-[var(--color-wp-red)]/20 text-[var(--color-wp-red)] rounded font-medium">🔥 ON SALE</span>
													<?php else: ?>
														<span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">No sale</span>
													<?php endif; ?>
												</td>
												<td class="py-3 px-4 text-sm">
													<?php 
													$extended_diff = $price_data['extended_license_price'] - $theme_default_price_extended_license;
													$extended_diff_percent = $theme_default_price_extended_license > 0 ? ($extended_diff / $theme_default_price_extended_license) * 100 : 0;
													?>
													<span class="<?php echo $extended_diff < 0 ? 'text-[var(--color-wp-red)]' : ($extended_diff > 0 ? 'text-[var(--color-wp-green)]' : 'text-gray-600'); ?>">
														<?php echo $extended_diff > 0 ? '+' : ''; ?>$<?php echo number_format($extended_diff, 0); ?>
														<?php if ($extended_diff != 0): ?>
															(<?php echo $extended_diff > 0 ? '+' : ''; ?><?php echo number_format($extended_diff_percent, 1); ?>%)
														<?php endif; ?>
													</span>
												</td>
											</tr>
											<tr class="border-b border-gray-100">
												<td class="py-3 px-4 font-medium text-gray-700">Professional Upgrade</td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm">$<?php echo number_format($default_professional_price, 0); ?></td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm">$<?php echo number_format($live_professional_price, 0); ?></td>
												<td class="py-3 px-4">
													<?php if ($professional_license_is_sale): ?>
														<span class="px-2 py-1 text-xs bg-[var(--color-wp-red)]/20 text-[var(--color-wp-red)] rounded font-medium">🔥 ON SALE</span>
													<?php else: ?>
														<span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">No sale</span>
													<?php endif; ?>
												</td>
												<td class="py-3 px-4 text-sm">
													<?php 
													$professional_diff = $live_professional_price - $default_professional_price;
													$professional_diff_percent = $default_professional_price > 0 ? ($professional_diff / $default_professional_price) * 100 : 0;
													?>
													<span class="<?php echo $professional_diff < 0 ? 'text-[var(--color-wp-red)]' : ($professional_diff > 0 ? 'text-[var(--color-wp-green)]' : 'text-gray-600'); ?>">
														<?php echo $professional_diff > 0 ? '+' : ''; ?>$<?php echo number_format($professional_diff, 2); ?>
														<?php if ($professional_diff != 0): ?>
															(<?php echo $professional_diff > 0 ? '+' : ''; ?><?php echo number_format($professional_diff_percent, 1); ?>%)
														<?php endif; ?>
													</span>
												</td>
											</tr>
										</tbody>
									</table>
								</div>

								<div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
									<h5 class="text-sm font-medium text-gray-800 mb-2">Additional Information</h5>
									<div class="text-sm text-gray-700 space-y-1">
										<p><strong>Price Data Source:</strong> <?php echo esc_html($price_data['source'] ?? 'unknown'); ?></p>
										<p><strong>Professional Price Formula:</strong> <code>ceil((($regular_price - 12) * (1 - 0.125)) * 100) / 100</code></p>
										<p><strong>Professional Sale Logic:</strong> Professional upgrade follows regular license sale status</p>
										<?php if ($regular_license_is_sale || $extended_license_is_sale): ?>
										<p class="text-[var(--color-wp-red)] font-medium">✨ Sales are currently active and will be displayed on the License page!</p>
										<?php else: ?>
										<p class="text-gray-600">💡 No sales detected - prices match default configuration values</p>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Theme Price Update Test Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-8">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
									</svg>
									Theme Price Update API Test
								</h3>
							</div>
							<div class="p-8">
								<h4 class="text-base font-medium text-gray-800 mb-4">API Request Results</h4>
								
								<?php
								// Execute request using wp_remote_post for update_theme_price
								// nosemgrep: generic-api-key -- Placeholder for diagnostics API test, not a real secret.
								$license_key = '6646352a-4e78-4669-b7c0-736b41198171';
								
					// Get the base API URL
					$api_base_url = $config->get_api_base_urls()[0];
								
								// Make sure the base URL ends with a slash
								$api_base_url = rtrim($api_base_url, '/') . '/';
								
								// Construct the full URL for update_theme_price
								$price_update_url = $api_base_url . 'update_theme_price.php';
								
								// Parse the URL to extract host and path
								$parsed_url = parse_url($price_update_url);
								$host = $parsed_url['host'];
								$path = $parsed_url['path'];
								
								$args = array(
									'method'      => 'POST',
									'timeout'     => 45,
									'redirection' => 5,
									'httpversion' => '1.1',
									'headers'     => array(
										'X-Requested-With' => 'XMLHttpRequest',
										'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
										'Accept'           => 'application/json, text/javascript, */*; q=0.01',
										'Origin'           => home_url(),
										'Referer'          => admin_url()
									),
									'body'        => http_build_query(array(
										'license_key' => $license_key,
										'theme_name'  => $theme_name_gbt_dash,
									)),
									'sslverify'   => true,
								);
								
								// Store start time for calculating total request time
								$start_time = microtime(true);
								
								// Make the request to the price update URL
								$price_response = wp_remote_post($price_update_url, $args);
								
								// Calculate total time taken
								$total_time = microtime(true) - $start_time;
								
								// Process response
								$is_error = is_wp_error($price_response);
								$error_message = $is_error ? $price_response->get_error_message() : '';
								
								if (!$is_error) {
									$response_code = wp_remote_retrieve_response_code($price_response);
									$headers = wp_remote_retrieve_headers($price_response);
									$header_array = $headers->getAll();
									$header_string = '';
									
									foreach ($header_array as $key => $value) {
										if (is_array($value)) {
											foreach ($value as $single_value) {
												$header_string .= "$key: $single_value\n";
											}
										} else {
											$header_string .= "$key: $value\n";
										}
									}
									
									$body = wp_remote_retrieve_body($price_response);
								}
								?>
								
								<div class="mb-6 grid grid-cols-1 gap-6">
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h5 class="text-sm font-medium text-gray-800 mb-2">Request Details (wp_remote_post)</h5>
										<div class="bg-gray-100 p-3 rounded font-mono text-xs overflow-x-auto">
											<pre>POST <?php echo esc_html($path); ?> HTTP/1.1
Host: <?php echo esc_html($host); ?>

X-Requested-With: XMLHttpRequest
Content-Type: application/x-www-form-urlencoded; charset=UTF-8
Accept: application/json, text/javascript, */*; q=0.01
Origin: <?php echo esc_url(home_url()); ?>
Referer: <?php echo esc_url(admin_url()); ?>

license_key=<?php echo esc_html($license_key); ?>
theme_name=<?php echo esc_html($theme_name_gbt_dash); ?></pre>
										</div>
									</div>
									
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h5 class="text-sm font-medium text-gray-800 mb-2">Price Update URL</h5>
										<div class="bg-gray-100 p-3 rounded font-mono text-xs">
						<span class="text-[var(--color-wp-blue)]">
												<?php echo esc_html($price_update_url); ?>
							 (Production)
											</span>
										</div>
									</div>
									
									<?php if ($is_error): ?>
									<div class="bg-[var(--color-wp-red)]/10 p-4 rounded-lg border border-[var(--color-wp-red)]/20">
										<h5 class="text-sm font-medium text-[var(--color-wp-red)] mb-2">WordPress Error</h5>
										<div class="bg-[var(--color-wp-red)]/20 p-3 rounded font-mono text-xs text-[var(--color-wp-red)]">
											<pre><?php echo esc_html($error_message); ?></pre>
										</div>
									</div>
									<?php else: ?>
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h5 class="text-sm font-medium text-gray-800 mb-2">Response Status</h5>
										<div class="bg-gray-100 p-3 rounded font-mono text-xs">
											<span class="<?php echo ($response_code >= 200 && $response_code < 300) ? 'text-[var(--color-wp-green)]' : 'text-[var(--color-wp-red)]'; ?>">
												HTTP/1.1 <?php echo esc_html($response_code); ?>
											</span>
											<p class="mt-1">Total time: <?php echo number_format($total_time, 4); ?> seconds</p>
										</div>
									</div>
									
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h5 class="text-sm font-medium text-gray-800 mb-2">Response Headers</h5>
										<div class="bg-gray-100 p-3 rounded font-mono text-xs overflow-x-auto">
											<pre><?php echo esc_html($header_string); ?></pre>
										</div>
									</div>
									
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h5 class="text-sm font-medium text-gray-800 mb-2">Response Body</h5>
										<div class="bg-gray-100 p-3 rounded overflow-x-auto">
											<?php 
											// First check if the content is HTML
											$content_type = wp_remote_retrieve_header($price_response, 'content-type');
											$is_html = strpos($content_type, 'text/html') !== false;
											$is_json = strpos($content_type, 'application/json') !== false;
											
											// If it's JSON, pretty print it
											if ($is_json || (!$is_html && json_decode($body) !== null && json_last_error() === JSON_ERROR_NONE)) {
												$json_body = json_decode($body);
												echo '<pre class="font-mono text-xs">' . esc_html(json_encode($json_body, JSON_PRETTY_PRINT)) . '</pre>';
											}
											// If it's HTML, render it in an iframe
											else if ($is_html) {
												echo '<div class="mb-2"><span class="text-xs text-gray-500">Showing rendered HTML response:</span></div>';
												echo '<div class="border border-gray-200">';
												echo '<iframe id="html-price-response-frame" class="w-full" style="height: 400px;" srcdoc="' . esc_attr($body) . '"></iframe>';
												echo '</div>';
												echo '<div class="mt-4">';
												echo '<button id="toggle-price-html-source" class="px-3 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300">Show HTML Source</button>';
												echo '<div id="price-html-source" class="mt-2 hidden">';
												echo '<pre class="font-mono text-xs">' . esc_html($body) . '</pre>';
												echo '</div>';
												echo '</div>';
												// Add JavaScript to toggle HTML source
												echo '<script>
													document.getElementById("toggle-price-html-source").addEventListener("click", function() {
														var sourceEl = document.getElementById("price-html-source");
														var buttonEl = document.getElementById("toggle-price-html-source");
														if (sourceEl.classList.contains("hidden")) {
															sourceEl.classList.remove("hidden");
															buttonEl.textContent = "Hide HTML Source";
														} else {
															sourceEl.classList.add("hidden");
															buttonEl.textContent = "Show HTML Source";
														}
													});
												</script>';
											}
											// Otherwise, just show as text
											else {
												echo '<pre class="font-mono text-xs">' . esc_html($body) . '</pre>';
											}
											?>
										</div>
									</div>
									<?php endif; ?>
								</div>
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
