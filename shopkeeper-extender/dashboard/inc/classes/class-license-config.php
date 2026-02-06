<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * GetBowtied License Configuration
 * 
 * Centralizes configuration settings for the license system including:
 * - Environment detection (localhost)
 * - URL configurations for production endpoints
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
	 * Get all API base URLs (primary and fallback) for production
	 * 
	 * @return array Array of base URLs to try in order
	 */
	public function get_api_base_urls(): array
	{
		return [
			'https://api1.getbowtied.net/v1',
			'https://api2.getbowtied.net/v1'
		];
	}

	/**
	 * Get all verification URLs (primary and fallback)
	 * 
	 * @return array Array of verification URLs to try in order
	 */
	public function get_verification_urls(): array
	{
		$urls = [];
		foreach ($this->get_api_base_urls() as $base_url) {
			$urls[] = $base_url . '/verify_license.php';
		}
		return $urls;
	}

	/**
	 * Get all license server URLs (primary and fallback)
	 * 
	 * @return array Array of license server URLs to try in order
	 */
	public function get_license_server_urls(): array
	{
		$urls = [];
		foreach ($this->get_api_base_urls() as $base_url) {
			$urls[] = $base_url . '/license_receiver_api.php';
		}
		return $urls;
	}

	/**
	 * Get all theme price URLs (primary and fallback)
	 * 
	 * @return array Array of theme price URLs to try in order
	 */
	public function get_theme_price_urls(): array
	{
		$urls = [];
		foreach ($this->get_api_base_urls() as $base_url) {
			$urls[] = $base_url . '/update_theme_price.php';
		}
		return $urls;
	}

	/**
	 * Get all special license URLs (primary and fallback)
	 * 
	 * @return array Array of special license URLs to try in order
	 */
	public function get_special_license_urls(): array
	{
		$urls = [];
		foreach ($this->get_api_base_urls() as $base_url) {
			$urls[] = $base_url . '/get_special_license.php';
		}
		return $urls;
	}

	/**
	 * Get all buyer review URLs (primary and fallback)
	 * 
	 * @return array Array of buyer review URLs to try in order
	 */
	public function get_buyer_review_urls(): array
	{
		$urls = [];
		foreach ($this->get_api_base_urls() as $base_url) {
			$urls[] = $base_url . '/get_buyer_reviews.php';
		}
		return $urls;
	}

	/**
	 * Get all specific rating URLs (primary and fallback)
	 *
	 * @return array Array of specific rating URLs to try in order
	 */
	public function get_specific_rating_urls(): array
	{
		$urls = [];
		foreach ($this->get_api_base_urls() as $base_url) {
			$urls[] = $base_url . '/get_specific_rating.php';
		}
		return $urls;
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
			'support_expiration' => 'getbowtied_theme_license_support_expiration_date',
			'special_license_data' => 'getbowtied_theme_special_license_data'
		];
	}
}
