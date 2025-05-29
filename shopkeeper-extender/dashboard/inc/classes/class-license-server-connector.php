<?php

/**
 * License Server Connector
 * 
 * Connects to the remote license server API to record license activations
 * This file should be included in the WordPress theme/plugin
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class for managing license server connections
 */
class GBT_License_Server_Connector
{
	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Server API URL
	 *
	 * @var string
	 */
	private $server_api_url;

	/**
	 * Initialize the class and register main hook
	 */
	public static function init(): void
	{
		add_action('init', function () {
			self::get_instance();
		});
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct()
	{
		// Set the server API URL based on environment
		$this->server_api_url = $this->get_api_url();

		// Add hooks to trigger license server sync after license verification
		add_action('getbowtied_license_verified', array($this, 'sync_license_with_server'), 10, 2);

		// Add hook for license deactivation
		add_action('getbowtied_license_deactivated', array($this, 'sync_license_deactivation'), 10, 2);
	}

	/**
	 * Determine the server API URL based on environment
	 *
	 * @return string The server API URL
	 */
	private function get_api_url(): string
	{
		if ($this->is_development_environment()) {
			$config = GBT_License_Config::get_instance();
			return $config->get_dev_license_server_url();
		}

		// Use remote URL for production
		$config = GBT_License_Config::get_instance();
		return $config->get_license_server_api_url();
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
	 * Sync the license with the remote server after verification
	 * 
	 * @param string $license_key The verified license key
	 * @param array $license_data The license data
	 * @return array|false The response array or false on failure
	 */
	public function sync_license_with_server(string $license_key, array $license_data)
	{
		// Skip server sync for special development/testing license key
		if ($license_key === date('dm') . date('Y') . '-' . date('dm') . '-' . date('Y') . '-' . date('dm') . '-' . date('Y') . date('dm') . date('Y')) {
			return [
				'success' => true,
				'message' => 'Development license activated locally (no server sync)',
				'status' => 'ok',
				'data' => [
					'domain' => $this->get_site_domain(),
					'license_key' => $license_key
				]
			];
		}

		// Ensure we have the required data
		if (empty($license_key) || empty($license_data)) {
			$this->log_error('Missing license key or data for server sync');
			return false;
		}

		// Prepare the license data for the API request
		$post_data = $this->prepare_activation_data($license_key, $license_data);

		// Send the data to the server API
		$response = $this->send_api_request($post_data);

		// Log the response for debugging
		$this->log_response($response);

		return $response;
	}

	/**
	 * Prepare license activation data for API request
	 *
	 * @param string $license_key The license key
	 * @param array $license_data The license data
	 * @return array The prepared data
	 */
	private function prepare_activation_data(string $license_key, array $license_data): array
	{
		// Get the site domain
		$domain = $this->get_site_domain();

		// Get the admin email with fallback
		$admin_email = $this->get_admin_email();

		// Get theme information from the dashboard setup
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
		$theme_slug = $gbt_dashboard_setup->get_theme_slug();

		// Check if this is an auto update - ensure boolean conversion
		$is_auto_update = !empty($license_data['auto_update']);

		return [
			'license_key' => $license_key,
			'domain' => $domain,
			'admin_email' => $admin_email,
			'theme_slug' => $theme_slug,
			'item_id' => $license_data['item_id'] ?? 0,
			'buyer_username' => $license_data['buyer_username'] ?? '',
			'purchase_date' => $license_data['purchase_date'] ?? null,
			'support_expiration' => $license_data['supported_until'] ?? null,
			'license_provider' => $license_data['license_provider'] ?? '',
			'license_type' => $license_data['license_type'] ?? '',
			'total_purchases' => $license_data['total_purchases'] ?? 0,
			'author_earning_amount' => $license_data['author_earning_amount'] ?? 0,
			'support_earning_amount' => $license_data['support_earning_amount'] ?? 0,
			'auto_update' => $is_auto_update ? 'true' : 'false'
		];
	}

	/**
	 * Sync the license deactivation with the remote server
	 * 
	 * @param string $license_key The deactivated license key
	 * @param array $license_data The license data before deactivation
	 * @return array|false The response array or false on failure
	 */
	public function sync_license_deactivation(string $license_key, array $license_data)
	{
		// Skip server sync for special development/testing license key
		if ($license_key === date('dm') . date('Y') . '-' . date('dm') . '-' . date('Y') . '-' . date('dm') . '-' . date('Y') . date('dm') . date('Y')) {
			return [
				'success' => true,
				'message' => 'Development license deactivated locally (no server sync)',
				'status' => 'ok',
				'data' => [
					'domain' => $this->get_site_domain(),
					'license_key' => $license_key
				]
			];
		}

		// Ensure we have a license key
		if (empty($license_key)) {
			$this->log_error('Missing license key for deactivation sync');
			return false;
		}

		// Prepare the license data for the API request
		$post_data = $this->prepare_deactivation_data($license_key, $license_data);

		// Send the data to the server API
		$response = $this->send_api_request($post_data);

		// Log the response for debugging
		$this->log_response($response);

		return $response;
	}

	/**
	 * Prepare license deactivation data for API request
	 *
	 * @param string $license_key The license key
	 * @param array $license_data The license data
	 * @return array The prepared data
	 */
	private function prepare_deactivation_data(string $license_key, array $license_data): array
	{
		// Get the site domain
		$domain = $this->get_site_domain();

		// Get the admin email with fallback
		$admin_email = $this->get_admin_email();

		// Get theme information from the dashboard setup
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
		$theme_slug = $gbt_dashboard_setup->get_theme_slug();

		return [
			'license_key' => $license_key,
			'domain' => $domain,
			'admin_email' => $admin_email,
			'action' => 'deactivation',
			'theme_slug' => $theme_slug,
			'item_id' => $license_data['item_id'] ?? 0,
			'buyer_username' => $license_data['buyer_username'] ?? ''
		];
	}

	/**
	 * Send an API request to the license server
	 * 
	 * @param array $post_data The data to send
	 * @return array|false The response array or false on failure
	 */
	private function send_api_request(array $post_data)
	{
		// Get URLs from config
		if ($this->is_development_environment()) {
			$config = GBT_License_Config::get_instance();
			$urls = [$config->get_dev_license_server_url()];
		} else {
			$config = GBT_License_Config::get_instance();
			$urls = $config->get_license_server_urls();
		}

		// Log the request data
		$this->log_debug('Trying license server URLs: ' . implode(', ', $urls));
		$this->log_debug('Request data: ' . json_encode($post_data));

		$request_args = [
			'body' => $post_data,
			'timeout' => 30,
			'sslverify' => !$this->is_development_environment(),
			'headers' => [
				'X-Requested-With' => 'XMLHttpRequest',
				'Referer' => home_url(),
				'User-Agent' => 'WordPress/' . get_bloginfo('version')
			]
		];

		// Try URLs in order until one returns valid JSON
		$response = $this->try_license_urls_with_fallback($urls, $request_args);

		// Check for errors
		if (is_wp_error($response)) {
			$this->log_error('All license server URLs failed: ' . $response->get_error_message());
			return false;
		}

		// Log the raw response for debugging
		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);
		$this->log_debug('Response code: ' . $response_code);
		$this->log_debug('Raw response body: ' . $response_body);

		// Parse the JSON response
		return $this->parse_api_response($response_body);
	}

	/**
	 * Try multiple license server URLs with fallback until one returns valid JSON
	 * 
	 * @param array $urls Array of URLs to try
	 * @param array $request_args WordPress HTTP request arguments
	 * @return mixed WordPress HTTP response or WP_Error
	 */
	private function try_license_urls_with_fallback(array $urls, array $request_args)
	{
		foreach ($urls as $url) {
			$this->log_debug('Trying URL: ' . $url);
			$response = wp_remote_post($url, $request_args);
			
			// If request succeeded and returned valid JSON, use this response
			if (!is_wp_error($response)) {
				$response_body = wp_remote_retrieve_body($response);
				$data = json_decode($response_body, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
					$this->log_debug('URL succeeded: ' . $url);
					return $response;
				}
				$this->log_debug('URL returned invalid JSON: ' . $url);
			} else {
				$this->log_debug('URL failed: ' . $url . ' - ' . $response->get_error_message());
			}
		}

		// If all URLs failed, return the last response
		return $response;
	}

	/**
	 * Parse API response
	 *
	 * @param string $response_body The response body to parse
	 * @return array|false The parsed response or false on error
	 */
	private function parse_api_response(string $response_body)
	{
		$data = json_decode($response_body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->log_error('Invalid JSON response from license server: ' . json_last_error_msg());
			$this->log_error('Raw response: ' . $response_body);
			return false;
		}

		return $data;
	}

	/**
	 * Get the admin email with fallback if empty
	 * 
	 * @return string The admin email
	 */
	private function get_admin_email(): string
	{
		$admin_email = get_option('admin_email');
		
		// If admin email is empty, try fallback methods
		if (empty($admin_email)) {
			// Try to get current user email if available
			$current_user = wp_get_current_user();
			if ($current_user && !empty($current_user->user_email)) {
				$admin_email = $current_user->user_email;
			} else {
				// Final fallback - create a placeholder email based on domain
				$domain = $this->get_site_domain();
				$admin_email = 'admin@' . $domain;
			}
		}
		
		return $admin_email;
	}

	/**
	 * Get the site domain
	 * 
	 * @return string The site domain
	 */
	private function get_site_domain(): string
	{
		$domain = parse_url(home_url(), PHP_URL_HOST);
		return $domain ?: $_SERVER['HTTP_HOST'] ?? 'unknown';
	}

	/**
	 * Log debug information
	 * 
	 * @param string $message The debug message to log
	 */
	private function log_debug(string $message): void
	{
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[GBT License Server] ' . $message);
		}
	}

	/**
	 * Log an error message
	 * 
	 * @param string $message The error message to log
	 */
	private function log_error(string $message): void
	{
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[GBT License Server Error] ' . $message);
		}
	}

	/**
	 * Log API response for debugging
	 * 
	 * @param array|false $response The API response
	 */
	private function log_response($response): void
	{
		if (defined('WP_DEBUG') && WP_DEBUG && $response) {
			error_log('[GBT License Server] API Response: ' . json_encode($response));
		}
	}
}

// Initialize the license server connector
GBT_License_Server_Connector::init();
