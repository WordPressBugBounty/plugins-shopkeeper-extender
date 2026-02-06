<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * GetBowtied License Localhost Detection
 * 
 * Handles detection of local development environments
 * through hostname and domain extension matching
 */
class GBT_License_Localhosts
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

	/**
	 * Get localhost hostnames
	 * 
	 * List of hostnames that are considered localhost environments
	 * 
	 * @return array List of localhost hostnames
	 */
	public function get_localhost_hostnames(): array
	{
		return [
			'localhost',
			'::1',
			'127.0.0.1',
			'local',
			'local.test',
			'*.localwp.com',
			'localwp.com',
			'*.localwp.site',
			'dev',
			'development',
			'staging',
			'test',
			'docker.local',
			'testing',
			'demo',
			'sandbox',
			'preview',
			'localdev',
			'wpdev'
		];
	}

	/**
	 * Get localhost domain extensions
	 * 
	 * List of domain extensions that indicate a localhost environment
	 * 
	 * @return array List of localhost domain extensions
	 */
	public function get_localhost_domain_extensions(): array
	{
		return [
			'.local',
			'.test',
			'.localhost',
			'.dev',
			'.development',
			'.localwp',
			'.example',
			'.invalid',
			'.staging',
			'.internal',
			'.loc',
			'.docker',
			'.vm',
			'.sandbox',
			'.testing',
			'.demo',
			'.docksal',
			'.lndo.site',
			'.wp',
			'.wip'
		];
	}

	/**
	 * Check if this is a localhost environment
	 *
	 * @return bool Whether this is a localhost environment
	 */
	public function is_localhost(): bool
	{
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
		$server_addr = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		// More specific domain check - ensure we're checking for exact extensions
		$is_local_domain = false;
		if (!empty($server_name)) {
			// Check exact matches first
			if (in_array($server_name, $this->get_localhost_hostnames())) {
				$is_local_domain = true;
			}
			// Check domain extensions with more precision
			else {
				$extensions = $this->get_localhost_domain_extensions();
				foreach ($extensions as $ext) {
					// Check if domain ends with this extension (not just contains it)
					if (substr($server_name, -strlen($ext)) === $ext) {
						$is_local_domain = true;
						break;
					}
				}
			}
		}

		return $is_local_domain ||
			in_array($server_addr, ['localhost', '::1']) ||
			in_array($remote_addr, ['localhost', '::1']);
	}
}
