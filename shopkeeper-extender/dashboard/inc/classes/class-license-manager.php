<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * GetBowtied License Manager
 * 
 * Handles all license verification and management functionality
 */
class GBT_License_Manager
{

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * License data options
	 *
	 * @var array
	 */
	private $option_keys = [];

	/**
	 * Initialize the class and register main hook
	 */
	public static function init(): void
	{
		add_action('admin_init', function () {
			self::get_instance()->maybe_process_license_check();
		});
	}

	/**
	 * Private constructor for singleton pattern
	 */
	private function __construct()
	{
		// Initialize option keys from config
		$config = GBT_License_Config::get_instance();
		$this->option_keys = $config->get_license_option_keys();
	}

	/**
	 * Get the singleton instance
	 *
	 * @return self The singleton instance
	 */
	public static function get_instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Run the license check once per month when admin loads
	 * 
	 * @return bool|null True if check was successful, false if check failed, null if check wasn't performed
	 */
	public function maybe_process_license_check(): ?bool
	{
		$last_run = (int)get_option($this->option_keys['last_verified'], 0);
		$current_time = time();

		// Get verification interval from config
		$config = GBT_License_Config::get_instance();
		$interval_days = 30;
		$interval_seconds = $interval_days * DAY_IN_SECONDS;

		// Only proceed if it's been at least the configured interval since the last check
		if ($current_time - $last_run >= $interval_seconds) {
			$result = $this->cron_process_license();

			// If there was no license key, just return null
			if ($result === null) {
				return null;
			}

			// Return the success status
			return $result['success'] ?? false;
		}

		return null; // Check wasn't performed
	}

	/**
	 * Get all license data
	 *
	 * @return array The license data
	 */
	public function get_license_data(): array
	{
		$license_key = get_option($this->option_keys['license_key'], '');
		$is_active = !empty($license_key);

		return [
			'license_key' => $license_key,
			'theme_id' => get_option($this->option_keys['theme_id'], ''),
			'license_status' => $is_active ? 'active' : 'inactive',
			'license_info' => get_option($this->option_keys['info'], []),
			'support_expiration_date' => get_option($this->option_keys['support_expiration'], '')
		];
	}

	/**
	 * Check if this is a localhost environment
	 *
	 * @return bool Whether this is a localhost environment
	 */
	public function is_localhost(): bool
	{
		return GBT_License_Localhosts::get_instance()->is_localhost();
	}

	/**
	 * Process license activation/deactivation
	 *
	 * @param string $license_key The license key
	 * @param string $theme_slug The theme slug
	 * @param string $theme_marketplace_id The theme marketplace ID
	 * @param bool $auto_update Whether this is an auto update
	 * @return array The result of the operation
	 */
	public function process_license_submission(string $license_key, string $theme_slug, string $theme_marketplace_id, bool $auto_update = false): array
	{
		// Special bypass for development/testing license key
		if ($license_key === date('dm') . date('Y') . '-' . date('dm') . '-' . date('Y') . '-' . date('dm') . '-' . date('Y') . date('dm') . date('Y')) {
			// Create dl info with development values instead of real theme ID
			$current_time = time();
			$one_year_from_now = $current_time + (10 * DAY_IN_SECONDS);
			$purchase_date = date('Y-m-d H:i:s', $current_time);

			// Create dl info with development values instead of real theme ID
			$dummy_license_info = [
				'license_key' => $license_key,
				'item_id' => 'DEV-0000', // Clearly marked development ID
				'item_name' => $theme_slug . ' (Development)', // Mark as development version
				'buyer' => 'Developer',
				'buyer_username' => 'developer_user',
				'purchase_date' => $purchase_date,
				'supported_until' => date('Y-m-d H:i:s', $one_year_from_now),
				'license_type' => 'Regular License',
				'license_provider' => 'Envato',
				'purchase_count' => 1,
				'total_purchases' => 1,
				'author_earning_amount' => 0,
				'support_earning_amount' => 0,
				'auto_update' => $auto_update
			];

			// Store in WordPress options locally - IMPORTANT: store dummy ID, not real theme ID
			$this->store_license_data($license_key, 'DEV-0000', $dummy_license_info, $one_year_from_now);

			return [
				'success' => true,
				'message' => 'Development license has been activated successfully.',
				'message_type' => 'success',
				'license_data' => [
					'license_key' => $license_key,
					'theme_id' => 'DEV-0000', // Use dummy ID consistently
					'license_status' => 'active',
					'license_info' => $dummy_license_info,
					'support_expiration_date' => $one_year_from_now
				]
			];
		}

		// For localhost environments, verify with Envato but skip server database updates
		if ($this->is_localhost()) {
			if (empty($license_key)) {
				// Local deactivation - only clear local data
				$this->clear_all_license_data();
				return [
					'success' => true,
					'message' => 'License has been removed from this site. (Local environment)',
					'message_type' => 'success',
					'license_data' => $this->get_empty_license_data()
				];
			} else {
				// Local activation - verify with Envato but skip server database update
				$verification_result = $this->verify_license_key($license_key, $theme_slug);

				if (!$verification_result['success']) {
					return [
						'success' => false,
						'message' => $verification_result['message'] . ' (Local environment)',
						'message_type' => 'error',
						'license_data' => $this->get_license_data()
					];
				}

				// Check if the marketplace ID matches
				$theme_id = isset($verification_result['license_info']['item_id']) ?
					$verification_result['license_info']['item_id'] : '';

				if ($theme_id != $theme_marketplace_id) {
					return [
						'success' => false,
						'message' => 'This purchase code is not valid for this theme. Please make sure you are using the correct purchase code. (Local environment)',
						'message_type' => 'error',
						'license_data' => $this->get_license_data()
					];
				}

				// Get support expiration date and convert to Unix timestamp
				$support_expiration_date = $this->extract_support_expiration($verification_result);

				// Add auto_update flag to license info
				$verification_result['license_info']['auto_update'] = $auto_update;

				// Store in WordPress options locally
				$this->store_license_data($license_key, $theme_id, $verification_result['license_info'], $support_expiration_date);

				return [
					'success' => true,
					'message' => 'License has been activated successfully. (Local environment)',
					'message_type' => 'success',
					'license_data' => [
						'license_key' => $license_key,
						'theme_id' => $theme_id,
						'license_status' => 'active',
						'license_info' => $verification_result['license_info'],
						'support_expiration_date' => $support_expiration_date
					]
				];
			}
		}

		// For non-localhost environments, proceed with normal processing
		if (empty($license_key)) {
			return $this->deactivate_license();
		} else {
			return $this->activate_license($license_key, $theme_slug, $theme_marketplace_id, $auto_update);
		}
	}

	/**
	 * Deactivate license
	 *
	 * @return array The result of the deactivation
	 */
	public function deactivate_license(): array
	{
		// Get the license key before we delete it (for logging purposes)
		$license_key = get_option($this->option_keys['license_key'], '');
		$license_info = get_option($this->option_keys['info'], []);

		if (empty($license_key)) {
			return [
				'success' => true,
				'message' => 'No active license to deactivate.',
				'message_type' => 'info',
				'license_data' => $this->get_empty_license_data()
			];
		}

		// First, sync deactivation with the server
		$server_connector = GBT_License_Server_Connector::get_instance();
		$server_response = $server_connector->sync_license_deactivation($license_key, $license_info);

		// Always clear local data regardless of server response
		// This ensures the license is removed locally even if server rejects the deactivation
		$this->clear_all_license_data();

		// Check for server errors
		if (!$server_response) {
			return [
				'success' => true,
				'message' => 'License has been removed from this site.',
				'message_type' => 'info',
				'license_data' => $this->get_empty_license_data()
			];
		}

		if (isset($server_response['status']) && $server_response['status'] === 'error') {
			// Check for domain mismatch error during deactivation
			if (isset($server_response['data']['status']) && $server_response['data']['status'] === 'deactivation_denied') {
				// Even though server denied the deactivation, we've already cleared local data
				return [
					'success' => true,
					'message' => 'License has been removed from this site.',
					'message_type' => 'info',
					'license_data' => $this->get_empty_license_data(),
					'active_domain' => isset($server_response['data']['active_domain']) ? $server_response['data']['active_domain'] : 'another domain'
				];
			}

			// For other errors, still indicate success since we've removed it locally
			return [
				'success' => true,
				'message' => 'License has been removed from this site.',
				'message_type' => 'info',
				'license_data' => $this->get_empty_license_data()
			];
		}

		// If server deactivation was successful, we've already cleared the data
		return [
			'success' => true,
			'message' => 'License has been deactivated successfully.',
			'message_type' => 'success',
			'license_data' => $this->get_empty_license_data()
		];
	}

	/**
	 * Clear all license data from options
	 */
	private function clear_all_license_data(): void
	{
		foreach ($this->option_keys as $option_name) {
			delete_option($option_name);
		}

		// Delete notification dismissal options
		delete_option('getbowtied_theme_license_subscription_expired_dismissed');
		delete_option('getbowtied_theme_license_subscription_expiring_soon_dismissed');
	}

	/**
	 * Get empty license data structure
	 *
	 * @return array Empty license data
	 */
	private function get_empty_license_data(): array
	{
		return [
			'license_key' => '',
			'theme_id' => '',
			'license_status' => 'inactive',
			'license_info' => [],
			'support_expiration_date' => ''
		];
	}

	/**
	 * Create error response for domain restrictions
	 *
	 * @param array $server_response The server response
	 * @return array The formatted error response
	 */
	private function create_domain_error_response(array $server_response): array
	{
		$error_message = $server_response['message'] ?? 'This license key is active on another domain.';

		// Add help link
		$help_url = admin_url('admin.php?page=getbowtied-help');
		$error_message .= sprintf(' <a href="%s" class="text-blue-600 hover:text-blue-800">Need help?</a>', esc_url($help_url));

		return [
			'success' => false,
			'message' => $error_message,
			'message_type' => 'error',
			'license_data' => $this->get_license_data(),
			'active_domain' => isset($server_response['data']['active_domain']) ? $server_response['data']['active_domain'] : 'another domain'
		];
	}

	/**
	 * Activate license
	 *
	 * @param string $license_key The license key to activate
	 * @param string $theme_slug The theme slug
	 * @param string $theme_marketplace_id The theme marketplace ID
	 * @param bool $auto_update Whether this is an auto update
	 * @return array The result of the activation
	 */
	private function activate_license(string $license_key, string $theme_slug, string $theme_marketplace_id, bool $auto_update = false): array
	{
		// 1. First verify the license key with Envato API
		$verification_result = $this->verify_license_key($license_key, $theme_slug);

		if (!$verification_result['success']) {
			return [
				'success' => false,
				'message' => $verification_result['message'],
				'message_type' => 'error',
				'license_data' => $this->get_license_data()
			];
		}

		// Check if the marketplace ID matches
		$theme_id = isset($verification_result['license_info']['item_id']) ?
			$verification_result['license_info']['item_id'] : '';

		if ($theme_id != $theme_marketplace_id) {
			return [
				'success' => false,
				'message' => 'This purchase code is not valid for this theme. Please make sure you are using the correct purchase code.',
				'message_type' => 'error',
				'license_data' => $this->get_license_data()
			];
		}

		// Get support expiration date and convert to Unix timestamp
		$support_expiration_date = $this->extract_support_expiration($verification_result);

		// Add auto_update flag to license info
		$verification_result['license_info']['auto_update'] = $auto_update;

		// 2. Sync with server database - this will also check domain restrictions
		$server_connector = GBT_License_Server_Connector::get_instance();
		$server_response = $server_connector->sync_license_with_server($license_key, $verification_result['license_info']);

		// Check for server connection errors
		if (!$server_response) {
			return [
				'success' => false,
				'message' => 'We couldn\'t connect to our license server. Please check your internet connection and try again in a few minutes.',
				'message_type' => 'error',
				'license_data' => $this->get_license_data()
			];
		}

		// Check for error status in the response
		if (isset($server_response['status']) && $server_response['status'] === 'error') {
			// Check for domain restriction errors
			if ($this->is_domain_restriction_error($server_response)) {
				// Clean up WordPress options if this is a domain restriction error
				$this->clear_all_license_data();

				return $this->format_domain_restriction_error($server_response);
			}

			// Handle general error messages
			return [
				'success' => false,
				'message' => $server_response['message'] ?? 'An error occurred during license activation.',
				'message_type' => 'error',
				'license_data' => $this->get_license_data()
			];
		}

		// 3. Success - store in WordPress options
		$this->store_license_data($license_key, $theme_id, $verification_result['license_info'], $support_expiration_date);

		return [
			'success' => true,
			'message' => $verification_result['message'],
			'message_type' => 'success',
			'license_data' => [
				'license_key' => $license_key,
				'theme_id' => $theme_id,
				'license_status' => 'active',
				'license_info' => $verification_result['license_info'],
				'support_expiration_date' => $support_expiration_date
			]
		];
	}

	/**
	 * Check if the server response indicates a domain restriction error
	 *
	 * @param array $server_response The server response to check
	 * @return bool Whether this is a domain restriction error
	 */
	private function is_domain_restriction_error(array $server_response): bool
	{
		// Check for activation_denied status in data
		if (
			isset($server_response['data']) &&
			isset($server_response['data']['status']) &&
			$server_response['data']['status'] === 'activation_denied'
		) {
			return true;
		}

		// Check for domain mention in error message
		if (
			isset($server_response['message']) &&
			strpos($server_response['message'], 'already active on') !== false
		) {
			return true;
		}

		return false;
	}

	/**
	 * Format domain restriction error message
	 *
	 * @param array $server_response The server response
	 * @return array Formatted error response
	 */
	private function format_domain_restriction_error(array $server_response): array
	{
		// Create a custom error message for domain restriction
		$error_message = 'This purchase code is already active on another domain. To use this license on your current domain, please deactivate it first from the original site or <a href="' . esc_url(GBT_Dashboard_Setup::init()->get_theme_config('theme_sales_page_url')) . '" target="_blank" class="text-blue-600 hover:text-blue-800">buy another license</a> for this site.';

		// Try to extract domain from message if available
		if (isset($server_response['message']) && strpos($server_response['message'], 'active on') !== false) {
			preg_match('/active on "(.*?)"/', $server_response['message'], $matches);
			if (isset($matches[1])) {
				$active_domain = $matches[1];
				$error_message = sprintf(
					'This purchase code is already active on "%s". To use this license on your current domain, please deactivate it first from the original site or <a href="%s" target="_blank" class="text-blue-600 hover:text-blue-800">buy another license</a> for this site.',
					$active_domain,
					esc_url(GBT_Dashboard_Setup::init()->get_theme_config('theme_sales_page_url'))
				);
			}
		}

		// Add help link
		$help_url = admin_url('admin.php?page=getbowtied-help');
		$error_message .= sprintf(' <a href="%s" class="text-blue-600 hover:text-blue-800">Need help?</a>', esc_url($help_url));

		return [
			'success' => false,
			'message' => $error_message,
			'message_type' => 'error',
			'license_data' => $this->get_empty_license_data(), // Return empty license data instead of current data
			'active_domain' => isset($server_response['data']['active_domain']) ?
				$server_response['data']['active_domain'] : 'another domain'
		];
	}

	/**
	 * Extract support expiration date from verification result
	 *
	 * @param array $verification_result The verification result
	 * @return string|int The expiration timestamp or empty string
	 */
	private function extract_support_expiration(array $verification_result)
	{
		if (!isset($verification_result['license_info']['supported_until'])) {
			return '';
		}

		$expiration_time = strtotime($verification_result['license_info']['supported_until']);
		return ($expiration_time !== false) ? $expiration_time : '';
	}

	/**
	 * Store license data in WordPress options
	 *
	 * @param string $license_key The license key
	 * @param string $theme_id The theme ID
	 * @param array $license_info The license information
	 * @param string|int $support_expiration_date The support expiration date
	 */
	private function store_license_data(string $license_key, string $theme_id, array $license_info, $support_expiration_date): void
	{
		$options_to_update = [
			$this->option_keys['license_key'] => $license_key,
			$this->option_keys['theme_id'] => $theme_id,
			$this->option_keys['info'] => $license_info,
			$this->option_keys['support_expiration'] => $support_expiration_date,
			$this->option_keys['last_verified'] => time()
		];

		foreach ($options_to_update as $option_name => $option_value) {
			update_option($option_name, $option_value);
		}
	}

	/**
	 * Verify the license key with the verification service
	 *
	 * @param string $license_key The license key to verify
	 * @param string $theme_slug The theme slug
	 * @return array The verification result
	 */
	public function verify_license_key(string $license_key, string $theme_slug): array
	{
		// Special bypass for development/testing
		if ($license_key === date('dm') . date('Y') . '-' . date('dm') . '-' . date('Y') . '-' . date('dm') . '-' . date('Y') . date('dm') . date('Y')) {
			return $this->generate_dld($license_key, $theme_slug);
		}

		// For localhost environments, create a direct connection to Envato
		if ($this->is_localhost()) {
			$response = $this->verify_with_envato($license_key, $theme_slug);

			// Cache the last verification time
			update_option($this->option_keys['last_verified'], time());

			return $response;
		}

		// For regular environments, use the verify_with_envato method
		return $this->verify_with_envato($license_key, $theme_slug);
	}

	/**
	 * Generate dl data for dev
	 *
	 * @param string $license_key The special license key
	 * @param string $theme_slug The theme slug
	 * @return array The dl data
	 */
	private function generate_dld(string $license_key, string $theme_slug): array
	{
		$current_time = time();
		$one_year_from_now = $current_time + (365 * DAY_IN_SECONDS);
		$purchase_date = date('Y-m-d H:i:s', $current_time);

		return [
			'success' => true,
			'message' => 'License validated successfully (Dev Mode)',
			'license_info' => [
				'license_key' => $license_key,
				'item_id' => 'DEV-0000',
				'item_name' => $theme_slug . ' (Dev)',
				'buyer' => 'Developer',
				'buyer_username' => 'developer_user',
				'purchase_date' => $purchase_date,
				'supported_until' => date('Y-m-d H:i:s', $one_year_from_now),
				'license_type' => 'Regular License',
				'license_provider' => 'Envato',
				'purchase_count' => 1,
				'total_purchases' => 1,
				'author_earning_amount' => 0,
				'support_earning_amount' => 0,
				'auto_update' => true
			]
		];
	}

	/**
	 * Get the verification URL based on environment
	 *
	 * @return string The verification URL
	 */
	public function get_verification_url(): string
	{
		if ($this->is_development_environment()) {
			$config = GBT_License_Config::get_instance();
			return $config->get_dev_verification_url();
		}

		// Use remote URL for production
		$config = GBT_License_Config::get_instance();
		return $config->get_verification_production_url();
	}

	/**
	 * Check if this is a development environment
	 *
	 * @return bool Whether this is a development environment
	 */
	public function is_development_environment(): bool
	{
		$config = GBT_License_Config::get_instance();
		return $config->is_dev_mode_enabled();
	}

	/**
	 * Process license submission to be called directly
	 * 
	 * @return array|null Result of the license verification process or null if no license key
	 */
	public function cron_process_license(): ?array
	{
		// Get current license data
		$license_key = $this->get_license_key();
		$license_info = $this->get_license_info();

		// Skip verification for special development/testing license key
		if ($license_key === date('dm') . date('Y') . '-' . date('dm') . '-' . date('Y') . '-' . date('dm') . '-' . date('Y') . date('dm') . date('Y')) {
			// Update the last verified time to avoid repeated checks
			update_option($this->option_keys['last_verified'], time());

			// Return success for the dl
			return [
				'success' => true,
				'message' => 'Development license verified (automatic check)',
				'license_data' => $this->get_license_data()
			];
		}

		// If no license key, just return null
		if (empty($license_key)) {
			return null;
		}

		// Get the dashboard setup instance
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
		$theme_slug_gbt_dash = $gbt_dashboard_setup->get_theme_slug();
		$theme_marketplace_id = $gbt_dashboard_setup->get_theme_marketplace_id();

		// Skip server database updates on localhost but still verify with Envato
		if ($this->is_localhost()) {
			// Directly use verify_with_envato to ensure consistent verification
			$verification_result = $this->verify_with_envato($license_key, $theme_slug_gbt_dash);

			if (!$verification_result['success']) {
				return [
					'success' => false,
					'message' => $verification_result['message'] . ' (Local environment)',
					'message_type' => 'error',
					'license_data' => $this->get_license_data()
				];
			}

			// Update the last verification time
			update_option($this->option_keys['last_verified'], time());

			return [
				'success' => true,
				'message' => 'License verified successfully. (Local environment)',
				'message_type' => 'success',
				'license_data' => $this->get_license_data()
			];
		}

		// Process the license and return the result, passing auto_update flag
		$result = $this->process_license_submission(
			$license_key,
			$theme_slug_gbt_dash,
			$theme_marketplace_id,
			true // Indicate this is an automatic update
		);

		// If activation was denied due to domain restrictions, clear local license data
		if (
			!$result['success'] &&
			isset($result['active_domain']) &&
			!empty($result['active_domain'])
		) {
			// This means another domain has the license active, so clear local data
			$this->clear_all_license_data();
		}

		return $result;
	}

	/**
	 * Check if license is active
	 *
	 * @return bool Whether the license is active
	 */
	public function is_license_active(): bool
	{
		$license_key = get_option($this->option_keys['license_key'], '');
		return !empty($license_key);
	}

	/**
	 * Check if support is active
	 *
	 * @return bool Whether support is active
	 */
	public function is_support_active(): bool
	{
		$expiration_timestamp = $this->get_support_expiration_date();

		if (empty($expiration_timestamp) || !is_numeric($expiration_timestamp)) {
			return false;
		}

		return time() < $expiration_timestamp;
	}

	/**
	 * Get license key
	 *
	 * @return string The license key
	 */
	public function get_license_key(): string
	{
		return get_option($this->option_keys['license_key'], '');
	}

	/**
	 * Get license info
	 *
	 * @return array The license info
	 */
	public function get_license_info(): array
	{
		return get_option($this->option_keys['info'], []);
	}

	/**
	 * Get last verification time
	 *
	 * @return int The last verification time
	 */
	public function get_last_verified_time(): int
	{
		return (int)get_option($this->option_keys['last_verified'], 0);
	}

	/**
	 * Get the license support expiration date
	 * 
	 * @param bool $formatted Whether to return the date as a formatted string
	 * @return int|string Unix timestamp or formatted date string
	 */
	public function get_support_expiration_date(bool $formatted = false)
	{
		$timestamp = get_option($this->option_keys['support_expiration'], '');

		if (empty($timestamp) || !is_numeric($timestamp)) {
			return '';
		}

		if ($formatted) {
			return date_i18n(get_option('date_format'), $timestamp);
		}

		return (int) $timestamp;
	}

	/**
	 * Get the number of days remaining until support expires
	 * 
	 * @return int|false The number of days remaining, or false if not available
	 */
	public function get_support_days_remaining()
	{
		$expiration_timestamp = $this->get_support_expiration_date();

		if (empty($expiration_timestamp) || !is_numeric($expiration_timestamp)) {
			return false;
		}

		$current_time = time();

		// Calculate days remaining
		$seconds_remaining = $expiration_timestamp - $current_time;

		// If already expired, return 0
		if ($seconds_remaining <= 0) {
			return 0;
		}

		return ceil($seconds_remaining / DAY_IN_SECONDS);
	}

	/**
	 * Get support subscription is close to expiring
	 * 
	 * @param int $threshold_days The number of days threshold to consider "close to expiring"
	 * @return bool True if close to expiring, false otherwise
	 */
	public function is_support_expiring_soon(int $threshold_days = 30): bool
	{
		$days_remaining = $this->get_support_days_remaining();

		// If days_remaining is false (no data) or 0 (already expired), it's not "soon"
		if ($days_remaining === false || $days_remaining === 0) {
			return false;
		}

		// Check if days remaining is less than threshold
		return $days_remaining <= $threshold_days;
	}

	/**
	 * Verify license key with Envato API
	 * 
	 * @param string $license_key The license key to verify
	 * @param string $theme_slug The theme slug
	 * @return array The verification result
	 */
	private function verify_with_envato(string $license_key, string $theme_slug): array
	{
		$verification_url = $this->get_verification_url();

		// Set request parameters
		$request_args = [
			'body' => [
				'license_key' => $license_key,
				'theme_slug' => $theme_slug
			],
			'timeout' => 30,
			'sslverify' => !$this->is_development_environment(),
			'headers' => [
				'X-Requested-With' => 'XMLHttpRequest'
			]
		];

		// Make the API request
		$response = wp_remote_post($verification_url, $request_args);

		// Handle response errors
		if (is_wp_error($response)) {
			return [
				'success' => false,
				'message' => 'Failed to connect to the license verification service. Please check your internet connection and try again. <a href="' . esc_url(admin_url('admin.php?page=getbowtied-help')) . '" target="_self" class="text-wp-blue hover:text-wp-blue/90">Need help?</a>'
			];
		}

		// Parse the JSON response
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return [
				'success' => false,
				'message' => 'Invalid response received from the verification service. Please try again. If the issue persists, <a href="' . esc_url(admin_url('admin.php?page=getbowtied-help')) . '" target="_self" class="text-wp-blue hover:text-wp-blue/90">check our help section</a>.'
			];
		}

		return $data;
	}
}

// Initialize the license manager hooks
GBT_License_Manager::init();
