<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * GetBowtied License Configuration
 * 
 * Centralizes configuration settings for the license system including:
 * - Environment detection (localhost, development mode)
 * - URL configurations for both production and development
 * - License system settings
 */
class GBT_License_Config
{

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Private constructor for singleton pattern
	 */
	private function __construct()
	{
		// Private constructor to enforce singleton pattern
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

	// -------------------------------------------------------------------------
	// Environment Detection Methods
	// -------------------------------------------------------------------------

	/**
	 * Check if development mode is enabled
	 * 
	 * @return bool Whether development mode is enabled
	 */
	public function is_dev_mode_enabled(): bool
	{
		return defined('WP_GBT_DEV_ENV') && WP_GBT_DEV_ENV === true;
	}

	/**
	 * Get localhost hostnames
	 * 
	 * @deprecated Use GBT_License_Localhosts::get_instance()->get_localhost_hostnames() instead
	 * @return array List of localhost hostnames
	 */
	public function get_localhost_hostnames(): array
	{
		return GBT_License_Localhosts::get_instance()->get_localhost_hostnames();
	}

	/**
	 * Get localhost domain extensions
	 * 
	 * @deprecated Use GBT_License_Localhosts::get_instance()->get_localhost_domain_extensions() instead
	 * @return array List of localhost domain extensions
	 */
	public function get_localhost_domain_extensions(): array
	{
		return GBT_License_Localhosts::get_instance()->get_localhost_domain_extensions();
	}

	// -------------------------------------------------------------------------
	// Production URL Configuration Methods
	// -------------------------------------------------------------------------

	/**
	 * Get the main company website URL
	 * 
	 * @return string The main GetBowtied website URL
	 */
	public function get_company_website_url(): string
	{
		return 'https://getbowtied.com';
	}

	/**
	 * Get the API base URL for production
	 * 
	 * @return string The base URL for API endpoints
	 */
	public function get_api_base_url(): string
	{
		return 'https://my.getbowtied.com/v1';
	}

	/**
	 * Get verification production URL
	 * 
	 * @return string The production verification URL
	 */
	public function get_verification_production_url(): string
	{
		return $this->get_api_base_url() . '/verify_license.php';
	}

	/**
	 * Get license server API URL for production
	 * 
	 * @return string The license server API URL
	 */
	public function get_license_server_api_url(): string
	{
		return $this->get_api_base_url() . '/license_receiver_api.php';
	}

	/**
	 * Get theme price API URL for production
	 * 
	 * @return string The theme price API URL
	 */
	public function get_theme_price_api_url(): string
	{
		return $this->get_api_base_url() . '/update_theme_price.php';
	}

	// -------------------------------------------------------------------------
	// Development URL Configuration Methods
	// -------------------------------------------------------------------------

	/**
	 * Get development server path
	 * 
	 * @return string The development server path
	 */
	public function get_dev_server_path(): string
	{
		return '/dashboard/_server';
	}

	/**
	 * Get development API base URL
	 * 
	 * @return string The development API base URL
	 */
	public function get_dev_api_base_url(): string
	{
		$gbt_dashboard = GBT_Dashboard_Setup::init();
		$base_paths = $gbt_dashboard->get_base_paths();
		return $base_paths['url'] . $this->get_dev_server_path();
	}

	/**
	 * Get development verification URL
	 * 
	 * @return string The development verification URL
	 */
	public function get_dev_verification_url(): string
	{
		return $this->get_dev_api_base_url() . '/verify_license.php';
	}

	/**
	 * Get development license server URL
	 * 
	 * @return string The development license server URL
	 */
	public function get_dev_license_server_url(): string
	{
		return $this->get_dev_api_base_url() . '/license_receiver_api.php';
	}

	/**
	 * Get development theme price API URL
	 * 
	 * @return string The development theme price API URL
	 */
	public function get_dev_theme_price_api_url(): string
	{
		return $this->get_dev_api_base_url() . '/update_theme_price.php';
	}

	// -------------------------------------------------------------------------
	// License Configuration Methods
	// -------------------------------------------------------------------------

	/**
	 * Get license option keys
	 * 
	 * @return array The license option keys used for storing license data in WordPress options
	 */
	public function get_license_option_keys(): array
	{
		return [
			'license_key' => 'getbowtied_theme_license_key',
			'theme_id' => 'getbowtied_theme_license_theme_id',
			'info' => 'getbowtied_theme_license_info',
			'last_verified' => 'getbowtied_theme_license_last_verified',
			'support_expiration' => 'getbowtied_theme_license_support_expiration_date'
		];
	}
}
