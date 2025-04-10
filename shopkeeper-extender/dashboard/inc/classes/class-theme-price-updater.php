<?php

/**
 * Theme Price Updater
 * 
 * Updates theme price information from the Envato API
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class for managing theme price updates
 */
class GBT_Theme_Price_Updater
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
	 * Option name for storing price data
	 *
	 * @var string
	 */
	private $option_name = 'getbowtied_theme_live_price';
	
	/**
	 * Option name for storing last verification time
	 *
	 * @var string
	 */
	private $last_verified_option = 'getbowtied_theme_live_price_last_verified';

	/**
	 * Initialize the class
	 */
	public static function init(): void
	{
		// Initialization is now handled directly in license.php
		// No hooks are registered here
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct()
	{
		// Set the server API URL based on environment
		$this->server_api_url = $this->get_api_url();
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
			return $config->get_dev_theme_price_api_url();
		}

		// Use remote URL for production
		$config = GBT_License_Config::get_instance();
		return $config->get_theme_price_api_url();
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
	 * Updates the theme price information from the Envato API.
	 * 
	 * @param string $theme_marketplace_id The theme's marketplace ID
	 * @param float $theme_default_price_regular_license The default theme price if API call fails
	 * @param float $theme_default_price_extended_license The default extended license price if API call fails
	 * @return array The current price data
	 */
	public function update_theme_price($theme_marketplace_id, $theme_default_price_regular_license, $theme_default_price_extended_license): array
	{
		// Get existing price data
		$existing_price_data = get_option($this->option_name, false);
		
		// Get last verification time
		$last_verified = get_option($this->last_verified_option, 0);
		
		// Check if we need to update the price data (if it doesn't exist or if it's older than 24 hours)
		$should_update = false;
		
		if (!$existing_price_data) {
			$should_update = true; // No existing data, need to fetch
		} else {
			// Check if the data is older than 24 hours using the separate last verified option
			$twenty_four_hours = 86400; // 24 hours in seconds
			$should_update = (time() - $last_verified) > $twenty_four_hours;
		}
		
		// If update is needed, fetch from API
		if ($should_update && !empty($theme_marketplace_id)) {
			// Prepare the request data
			$post_data = [
				'theme_marketplace_id' => $theme_marketplace_id,
				'theme_default_price_regular_license' => $theme_default_price_regular_license,
				'theme_default_price_extended_license' => $theme_default_price_extended_license
			];
			
			// Send the data to the server API
			$response = $this->fetch_price_data($post_data);
			
			// Process API response
			if ($response && isset($response['regular_license_price']) && isset($response['extended_license_price'])) {
				// Update the last verification time
				update_option($this->last_verified_option, time(), false);
				
				// Store the updated price data
				update_option($this->option_name, $response, false);
				return $response;
			} else if (!$existing_price_data) {
				// If API call failed and no existing data, create a default response
				$default_data = [
					'regular_license_price' => floatval($theme_default_price_regular_license),
					'extended_license_price' => floatval($theme_default_price_extended_license),
					'source' => 'default'
				];
				// Update the last verification time even for default data
				update_option($this->last_verified_option, time(), false);
				
				// Store the default price data
				update_option($this->option_name, $default_data, false);
				return $default_data;
			}
		}
		
		// Return existing data if we didn't update
		return $existing_price_data ?: [
			'regular_license_price' => floatval($theme_default_price_regular_license),
			'extended_license_price' => floatval($theme_default_price_extended_license),
			'source' => 'default'
		];
	}
	
	/**
	 * Fetch price data from the API
	 *
	 * @param array $post_data The data to send to the API
	 * @return array|false The response data or false on failure
	 */
	private function fetch_price_data(array $post_data)
	{
		// Send the request
		$response = wp_remote_post($this->server_api_url, [
			'timeout' => 10,
			'body' => $post_data
		]);
		
		// Check for errors
		if (is_wp_error($response)) {
			$this->log_error('Error fetching price data: ' . $response->get_error_message());
			return false;
		}
		
		// Check response code
		$response_code = wp_remote_retrieve_response_code($response);
		if ($response_code !== 200) {
			$this->log_error('Invalid response code: ' . $response_code);
			return false;
		}
		
		// Get the response body
		$body = wp_remote_retrieve_body($response);
		if (empty($body)) {
			$this->log_error('Empty response body');
			return false;
		}
		
		// Decode the JSON response
		$data = json_decode($body, true);
		if (!is_array($data)) {
			$this->log_error('Invalid JSON response');
			return false;
		}
		
		return $data;
	}
	
	/**
	 * Log an error message
	 *
	 * @param string $message The error message to log
	 */
	private function log_error(string $message): void
	{
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('[Theme Price Updater] ' . $message);
		}
	}
	
	/**
	 * Get the last time the price was verified
	 *
	 * @return int Timestamp of the last verification
	 */
	public function get_last_verification_time(): int
	{
		return (int) get_option($this->last_verified_option, 0);
	}

	/**
	 * Check if we need to update the theme price and do it if necessary
	 * This runs once per day when accessing any admin page
	 */
	private function maybe_update_theme_price(): void
	{
		// Get last verification time
		$last_verified = get_option($this->last_verified_option, 0);
		
		// Check if the data is older than 24 hours
		$twenty_four_hours = 86400; // 24 hours in seconds
		$should_update = (time() - $last_verified) > $twenty_four_hours;
		
		if ($should_update) {
			// Get theme information
			$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
			$theme_marketplace_id = $gbt_dashboard_setup->get_theme_marketplace_id();
			$theme_default_price_regular_license = $gbt_dashboard_setup->get_theme_config('theme_default_price_regular_license');
			$theme_default_price_extended_license = $gbt_dashboard_setup->get_theme_config('theme_default_price_extended_license');
			
			// Update the theme price
			$this->update_theme_price(
				$theme_marketplace_id,
				$theme_default_price_regular_license,
				$theme_default_price_extended_license
			);
		}
	}

	/**
	 * Get the current price data from options
	 * 
	 * @param float $default_regular_price The default price to use if no data exists
	 * @param float $default_extended_price The default extended price to use if no data exists
	 * @return array The current price data
	 */
	public function get_current_price_data(float $default_regular_price, float $default_extended_price): array
	{
		$price_data = get_option($this->option_name, false);
		
		if (!$price_data) {
			return [
				'regular_license_price' => $default_regular_price,
				'extended_license_price' => $default_extended_price,
				'source' => 'default'
			];
		}
		
		return $price_data;
	}
}

// Initialize the class
GBT_Theme_Price_Updater::init(); 