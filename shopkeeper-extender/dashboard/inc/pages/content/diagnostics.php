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
		$server_name = $_SERVER['SERVER_NAME'] ?? 'Not available';
		$server_addr = $_SERVER['SERVER_ADDR'] ?? 'Not available';
		$remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'Not available';

		// Environment Configuration
		$is_localhost = $license_manager->is_localhost();
		$is_development = $license_manager->is_development_environment();
		$is_dev = $config->is_dev_mode_enabled();
		$is_dev_env = $license_manager->is_development_environment();
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
			'Development Environment' => $is_dev_env ? 'true' : 'false',
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

		// Main content
?>

		<div class="overflow-hidden py-24 sm:py-32">
			<div class="mx-auto max-w-7xl px-6 lg:px-8">
				<div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-1">
					<div>
						<div class="lg:max-w-lg">
							<div class="flex items-center gap-3">
								<span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white">
									<svg class="size-2 fill-green-400" viewBox="0 0 6 6" aria-hidden="true">
										<circle cx="3" cy="3" r="3" />
									</svg>
									VERSION <?php echo esc_html($theme_version_gbt_dash); ?>
								</span>
							</div>
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

								<div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
									<div class="flex">
										<div class="flex-shrink-0">
											<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
											</svg>
										</div>
										<div class="ml-3">
											<h3 class="text-sm font-medium text-yellow-800">Environment Detection Status</h3>
											<div class="mt-2 text-sm text-yellow-700">
												<p>The system uses the above parameters to determine if your site is running in a localhost environment. Localhost environments skip server database updates for license verification.</p>
												<p class="mt-2">
													<strong>Current environment detection:</strong><br>
													<?php if ($is_localhost): ?>
														<span class="text-green-600 font-semibold">• Detected as localhost</span> (server database updates are skipped)<br>
													<?php else: ?>
														<span class="text-blue-600 font-semibold">• Detected as production</span> (full license verification with server database updates)<br>
													<?php endif; ?>
													<?php if ($is_dev_env): ?>
														<span class="text-green-600 font-semibold">• Development mode is enabled (WP_GBT_DEV_ENV = true)</span> (using local development settings)
													<?php else: ?>
														<span class="text-blue-600 font-semibold">• Production mode is enabled (WP_GBT_DEV_ENV = false)</span> (using live API endpoints)
													<?php endif; ?>
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
								<div class="mb-6 p-4 rounded-lg border <?php echo $is_license_active ? 'border-green-200 bg-green-50' : 'border-yellow-200 bg-yellow-50'; ?>">
									<div class="flex">
										<div class="flex-shrink-0">
											<?php if ($is_license_active): ?>
												<svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
													<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
												</svg>
											<?php else: ?>
												<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
													<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
												</svg>
											<?php endif; ?>
										</div>
										<div class="ml-3">
											<h3 class="text-sm font-medium <?php echo $is_license_active ? 'text-green-800' : 'text-yellow-800'; ?>">
												License Status: <?php echo ucfirst(esc_html($license_status)); ?>
											</h3>
											<div class="mt-2 text-sm <?php echo $is_license_active ? 'text-green-700' : 'text-yellow-700'; ?>">
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

								<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
									<div class="flex">
										<div class="flex-shrink-0">
											<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 01-1-1v-4a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd"></path>
											</svg>
										</div>
										<div class="ml-3">
											<h3 class="text-sm font-medium text-yellow-800">Localhost Detection Method</h3>
											<div class="mt-2 text-sm text-yellow-700">
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
												<td class="py-3 px-4 text-gray-600 font-mono text-sm">
													Primary: <?php echo esc_html($config->get_primary_api_url()); ?><br>
													Backup: <?php echo esc_html($config->get_backup_api_url()); ?>
												</td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm <?php echo $is_dev ? 'text-yellow-600' : ''; ?>">
													<?php if ($is_dev): ?>
														<?php echo esc_html($config->get_dev_api_base_url()); ?>
													<?php else: ?>
														<?php echo esc_html($config->get_api_base_url()); ?>
													<?php endif; ?>
												</td>
											</tr>
											<tr class="border-b border-gray-100">
												<td class="py-3 px-4 font-medium text-gray-700">License Verification</td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($config->get_verification_production_url()); ?></td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm <?php echo $is_dev ? 'text-yellow-600' : ''; ?>">
													<?php echo $is_dev ? esc_html($config->get_dev_verification_url()) : esc_html($config->get_verification_production_url()); ?>
												</td>
											</tr>
											<tr class="border-b border-gray-100">
												<td class="py-3 px-4 font-medium text-gray-700">License Server API</td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm"><?php echo esc_html($config->get_license_server_api_url()); ?></td>
												<td class="py-3 px-4 text-gray-600 font-mono text-sm <?php echo $is_dev ? 'text-yellow-600' : ''; ?>">
													<?php echo $is_dev ? esc_html($config->get_dev_license_server_url()) : esc_html($config->get_license_server_api_url()); ?>
												</td>
											</tr>
										</tbody>
									</table>
								</div>

								<div class="mt-4 p-4 <?php echo $is_dev ? 'bg-yellow-50 border-yellow-200' : 'bg-blue-50 border-blue-200'; ?> rounded-lg border">
									<div class="flex">
										<div class="flex-shrink-0">
											<svg class="h-5 w-5 <?php echo $is_dev ? 'text-yellow-400' : 'text-blue-400'; ?>" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 01-1-1v-4a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd" />
											</svg>
										</div>
										<div class="ml-3">
											<h3 class="text-sm font-medium <?php echo $is_dev ? 'text-yellow-800' : 'text-blue-800'; ?>">
												<?php echo $is_dev ? 'Development Mode Active' : 'Production Mode Active'; ?>
											</h3>
											<div class="mt-2 text-sm <?php echo $is_dev ? 'text-yellow-700' : 'text-blue-700'; ?>">
												<?php if ($is_dev): ?>
													<p>Development mode is enabled (WP_GBT_DEV_ENV = true). Using local development endpoints for all API calls.</p>
												<?php else: ?>
													<p>Production mode is enabled (WP_GBT_DEV_ENV = false). Using live API endpoints for all requests.</p>
													<p class="mt-1">API requests will first try the primary URL (<?php echo esc_html($config->get_primary_api_url()); ?>). If unreachable, requests will automatically use the backup URL (<?php echo esc_html($config->get_backup_api_url()); ?>).</p>
												<?php endif; ?>
											</div>
										</div>
									</div>
								</div>
								
								<?php if (!$is_dev): ?>
								<div class="mt-4 flex items-center justify-between">
									<div class="text-sm text-gray-600">
										<p>The system performs a live connection check on each API request without storing test results.</p>
									</div>
								</div>
								<?php endif; ?>
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
								<h4 class="text-base font-medium text-gray-800 mb-4">API Request Results</h4>
								
								<?php
								// Execute request using wp_remote_post instead of curl
								$license_key = '6646352a-4e78-4669-b7c0-736b41198171';
								
								// Get the proper verification URL based on environment
								$verification_url = $is_dev 
									? $config->get_dev_verification_url() 
									: $config->get_verification_production_url();
								
								// Parse the URL to extract host and path
								$parsed_url = parse_url($verification_url);
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
										'license_key' => $license_key
									)),
									'sslverify'   => true,
								);
								
								// Store start time for calculating total request time
								$start_time = microtime(true);
								
								// Make the request to the actual verification URL
								$response = wp_remote_post($verification_url, $args);
								
								// Calculate total time taken
								$total_time = microtime(true) - $start_time;
								
								// Process response
								$is_error = is_wp_error($response);
								$error_message = $is_error ? $response->get_error_message() : '';
								
								if (!$is_error) {
									$response_code = wp_remote_retrieve_response_code($response);
									$headers = wp_remote_retrieve_headers($response);
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
									
									$body = wp_remote_retrieve_body($response);
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

license_key=<?php echo esc_html($license_key); ?></pre>
										</div>
									</div>
									
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h5 class="text-sm font-medium text-gray-800 mb-2">Verification URL</h5>
										<div class="bg-gray-100 p-3 rounded font-mono text-xs">
											<span class="<?php echo $is_dev ? 'text-yellow-600' : 'text-blue-600'; ?>">
												<?php echo esc_html($verification_url); ?>
												<?php echo $is_dev ? ' (Development)' : ' (Production)'; ?>
											</span>
										</div>
									</div>
									
									<?php if ($is_error): ?>
									<div class="bg-red-50 p-4 rounded-lg border border-red-200">
										<h5 class="text-sm font-medium text-red-800 mb-2">WordPress Error</h5>
										<div class="bg-red-100 p-3 rounded font-mono text-xs text-red-800">
											<pre><?php echo esc_html($error_message); ?></pre>
										</div>
									</div>
									<?php else: ?>
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h5 class="text-sm font-medium text-gray-800 mb-2">Response Status</h5>
										<div class="bg-gray-100 p-3 rounded font-mono text-xs">
											<span class="<?php echo ($response_code >= 200 && $response_code < 300) ? 'text-green-600' : 'text-red-600'; ?>">
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
											$content_type = wp_remote_retrieve_header($response, 'content-type');
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
												echo '<iframe id="html-response-frame" class="w-full" style="height: 400px;" srcdoc="' . esc_attr($body) . '"></iframe>';
												echo '</div>';
												echo '<div class="mt-4">';
												echo '<button id="toggle-html-source" class="px-3 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300">Show HTML Source</button>';
												echo '<div id="html-source" class="mt-2 hidden">';
												echo '<pre class="font-mono text-xs">' . esc_html($body) . '</pre>';
												echo '</div>';
												echo '</div>';
												// Add JavaScript to toggle HTML source
												echo '<script>
													document.getElementById("toggle-html-source").addEventListener("click", function() {
														var sourceEl = document.getElementById("html-source");
														var buttonEl = document.getElementById("toggle-html-source");
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

						<!-- Theme Price Update Test Panel -->
						<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-8">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
									</svg>
									Theme Price Update API Test
								</h3>
							</div>
							<div class="p-8">
								<h4 class="text-base font-medium text-gray-800 mb-4">API Request Results</h4>
								
								<?php
								// Execute request using wp_remote_post for update_theme_price
								$license_key = '4a50fd1a-05b0-4b58-acc0-e9d6b6e1d77f';
								
								// Get the base API URL
								$api_base_url = $is_dev 
									? $config->get_dev_api_base_url() 
									: $config->get_api_base_url();
								
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
											<span class="<?php echo $is_dev ? 'text-yellow-600' : 'text-blue-600'; ?>">
												<?php echo esc_html($price_update_url); ?>
												<?php echo $is_dev ? ' (Development)' : ' (Production)'; ?>
											</span>
										</div>
									</div>
									
									<?php if ($is_error): ?>
									<div class="bg-red-50 p-4 rounded-lg border border-red-200">
										<h5 class="text-sm font-medium text-red-800 mb-2">WordPress Error</h5>
										<div class="bg-red-100 p-3 rounded font-mono text-xs text-red-800">
											<pre><?php echo esc_html($error_message); ?></pre>
										</div>
									</div>
									<?php else: ?>
									<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
										<h5 class="text-sm font-medium text-gray-800 mb-2">Response Status</h5>
										<div class="bg-gray-100 p-3 rounded font-mono text-xs">
											<span class="<?php echo ($response_code >= 200 && $response_code < 300) ? 'text-green-600' : 'text-red-600'; ?>">
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
