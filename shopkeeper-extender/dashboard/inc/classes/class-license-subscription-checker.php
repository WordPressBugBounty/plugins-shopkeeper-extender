<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * GetBowtied License Subscription Checker
 * 
 * Checks if license subscription has expired or is about to expire
 * and displays appropriate dashboard notifications.
 */
class GBT_License_Subscription_Checker
{

	/**
	 * Singleton instance
	 */
	private static $instance = null;


	/**
	 * Notification IDs
	 */
	private $notification_ids = [
		'no_license' => 'license_no_license_detected',
		'expired' => 'license_subscription_expired',
		'expiring_soon' => 'license_subscription_expiring_soon'
	];

	/**
	 * User meta key prefix for dismissed notifications
	 */
	private $dismissed_meta_prefix = 'gbt_dismissed_license_notifications_';

	/**
	 * Option keys
	 */
	private $option_keys = [
		'license_key' => 'getbowtied_theme_license_key',
		'support_expiration' => 'getbowtied_theme_license_support_expiration_date'
	];

	/**
	 * Private constructor for singleton pattern
	 */
	private function __construct()
	{
		// Get config values
		$this->load_config_values();
		$this->register_hooks();
	}

	/**
	 * Load configuration values from the config class
	 */
	private function load_config_values()
	{
		// Get license config
		$config = GBT_License_Config::get_instance();

		// Get license option keys from config
		$option_keys = $config->get_license_option_keys();
		$this->option_keys = [
			'license_key' => $option_keys['license_key'],
			'support_expiration' => $option_keys['support_expiration']
		];
	}

	/**
	 * Get the singleton instance
	 *
	 * @return GBT_License_Subscription_Checker The singleton instance
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all hooks for the notification system
	 */
	private function register_hooks()
	{
		// Display notifications
		add_action('admin_notices', [$this, 'check_license_and_display_notification']);
		
		// Handle AJAX dismissal
		add_action('wp_ajax_dismiss_license_notification', [$this, 'ajax_dismiss_notification']);
		
		// Enqueue dismissal script
		add_action('admin_enqueue_scripts', [$this, 'enqueue_dismissal_script']);
	}

	/**
	 * Check license status and display appropriate notification if needed
	 */
	public function check_license_and_display_notification()
	{
		// Skip on our custom dashboard pages - banner is displayed via license-status-banner.php component
		if (isset($_GET['page'])) {
			$gbt_dashboard = GBT_Dashboard_Setup::init();
			$dashboard_pages = $gbt_dashboard->get_dashboard_page_slugs();
			if (in_array($_GET['page'], $dashboard_pages)) {
				return;
			}
		}

		// Get dashboard setup and check theme slug
		global $gbt_dashboard_setup;
		if (isset($gbt_dashboard_setup) && is_object($gbt_dashboard_setup)) {
			$theme_slug = $gbt_dashboard_setup->get_theme_slug();
			if ($theme_slug === 'block-shop') {
				return; // No license notifications for block-shop theme
			}
		}
		
		// First check if the initialization period is complete
		if (class_exists('Theme_LI') && Theme_LI::is_init_period_completed() !== true) {
			return; // Don't show any notifications during the initialization period
		}
		
		// Get theme version for notification IDs
		$gbt_dashboard = GBT_Dashboard_Setup::init();
		$theme_version = $gbt_dashboard->get_theme_version();
		
		// First check if license information exists
		if (!$this->has_valid_license_info()) {
			$base_type = $this->get_base_type($this->notification_ids['no_license']);
			if (!$this->is_notification_dismissed_by_type($base_type)) {
				$this->display_missing_license_notification();
			}
			return;
		}

		// Get license manager instance to access support days calculation
		$license_manager = GBT_License_Manager::get_instance();

		// Priority check: First check if license is active but support has expired 
		if ($license_manager->is_license_active() && !$license_manager->is_support_active()) {
			$base_type = $this->get_base_type($this->notification_ids['expired']);
			if (!$this->is_notification_dismissed_by_type($base_type)) {
				$this->display_expired_subscription_notification();
			}
			return;
		}

		// Next check: If license is active and support is active, check if it's about to expire
		if ($license_manager->is_license_active() && $license_manager->is_support_active()) {
			// Check if it's expiring soon
			if ($license_manager->is_support_expiring_soon()) {
				$base_type = $this->get_base_type($this->notification_ids['expiring_soon']);
				if (!$this->is_notification_dismissed_by_type($base_type)) {
					$this->display_expiring_soon_notification($license_manager->get_support_days_remaining());
				}
			}
		}
	}

	/**
	 * Check if valid license information exists in the database
	 *
	 * @return bool True if license info exists, false otherwise
	 */
	private function has_valid_license_info()
	{
		$license_key = get_option($this->option_keys['license_key'], '');
		return !empty($license_key);
	}

	/**
	 * Display notification for missing license
	 */
	public function display_missing_license_notification()
	{
		$gbt_dashboard = GBT_Dashboard_Setup::init();
		$theme_name = $gbt_dashboard->get_theme_name();
		$theme_slug = $gbt_dashboard->get_theme_slug();
		$theme_version = $gbt_dashboard->get_theme_version();
		$license_page_url = admin_url('admin.php?page=getbowtied-license');
		$purchase_url = $gbt_dashboard->get_theme_sales_page_url();
		
		$notice_id = $this->get_notice_id($this->notification_ids['no_license'], $theme_version);

?>
		<div id="<?php echo esc_attr($notice_id); ?>" class="notice notice-error">
			<p style="display: flex; align-items: center;">
				<span class="dashicons dashicons-warning" style="color: #d63638; margin-right: 8px;"></span>
				<span>A license key is required to use <?php echo esc_html($theme_name); ?> theme. <a href="<?php echo esc_url($license_page_url); ?>" class="button button-primary"><strong>Fix it now →</strong></a></span>
			</p>
		</div>
	<?php
	}

	/**
	 * Display notification for expired subscription
	 */
	public function display_expired_subscription_notification()
	{
		$gbt_dashboard = GBT_Dashboard_Setup::init();
		$theme_name = $gbt_dashboard->get_theme_name();
		$theme_slug = $gbt_dashboard->get_theme_slug();
		$theme_version = $gbt_dashboard->get_theme_version();

		$license_page_url = admin_url('admin.php?page=getbowtied-license');
		$license_details_url = $license_page_url;
		$renew_url = $gbt_dashboard->get_theme_sales_page_url();
		
		$notice_id = $this->get_notice_id($this->notification_ids['expired'], $theme_version);

	?>
		<div id="<?php echo esc_attr($notice_id); ?>" class="notice notice-error is-dismissible">
			<p style="display: flex; align-items: center;">
				<span class="dashicons dashicons-dismiss" style="color: #d63638; margin-right: 8px;"></span>
				<span>Your subscription for <?php echo esc_html($theme_name); ?> theme has expired. <a href="<?php echo esc_url($license_details_url); ?>" class="button button-primary"><strong>Fix it now →</strong></a></span>
			</p>
		</div>
	<?php
	}

	/**
	 * Display notification for subscription expiring soon
	 * 
	 * @param int $days_remaining The number of days remaining until expiration
	 */
	public function display_expiring_soon_notification($days_remaining)
	{
		$gbt_dashboard = GBT_Dashboard_Setup::init();
		$theme_name = $gbt_dashboard->get_theme_name();
		$theme_slug = $gbt_dashboard->get_theme_slug();
		$theme_version = $gbt_dashboard->get_theme_version();

		// Get license manager for formatted date
		$license_manager = GBT_License_Manager::get_instance();
		$expiration_date = $license_manager->get_support_expiration_date(true); // Get formatted date

		$license_page_url = admin_url('admin.php?page=getbowtied-license');
		$license_details_url = $license_page_url;
		$renew_url = $gbt_dashboard->get_theme_sales_page_url();

		// Format days remaining text
		$days_text = $days_remaining == 1 ? 'day' : 'days';
		
		$notice_id = $this->get_notice_id($this->notification_ids['expiring_soon'], $theme_version);

	?>
		<div id="<?php echo esc_attr($notice_id); ?>" class="notice notice-warning is-dismissible">
			<p style="display: flex; align-items: center;">
				<span class="dashicons dashicons-clock" style="color: #dba617; margin-right: 8px;"></span>
				<span>Your <?php echo esc_html($theme_name); ?> theme subscription <?php
				if ($days_remaining == 0) {
					echo 'expires today.';
				} elseif ($days_remaining == 1) {
					echo 'expires tomorrow.';
				} else {
					echo 'expires in ' . esc_html($days_remaining) . ' days.';
				}
				?>&nbsp;<a href="<?php echo esc_url($license_details_url); ?>" class="button button-primary"><strong>Fix it now →</strong></a></span>
			</p>
		</div>
	<?php
	}

	/**
	 * Generate notice ID with hash (version-dependent for HTML ID)
	 *
	 * @param string $base_id Base notification ID
	 * @param string $version Theme version
	 * @return string Hashed notice ID (12 character MD5 hash)
	 */
	private function get_notice_id($base_id, $version)
	{
		// Create a hash from the base ID and version (changes per version)
		return substr(md5($base_id . $version), 0, 12);
	}
	
	/**
	 * Get base notification type from base_id
	 *
	 * @param string $base_id Base notification ID (e.g., 'license_subscription_expired')
	 * @return string The base notification type (e.g., 'subscription_expired')
	 */
	private function get_base_type($base_id)
	{
		// Return the short form without 'license_' prefix
		return str_replace('license_', '', $base_id);
	}

	/**
	 * Check if a notification has been dismissed permanently by base type
	 *
	 * @param string $base_type The base notification type (e.g., 'subscription_expired')
	 * @return bool True if dismissed, false otherwise
	 */
	private function is_notification_dismissed_by_type($base_type)
	{
		$user_id = get_current_user_id();
		
		// Build meta key from base type
		$meta_key = $this->dismissed_meta_prefix . $base_type;
		
		// Check if the specific meta key exists and is truthy
		return (bool) get_user_meta($user_id, $meta_key, true);
	}
	
	/**
	 * Get notification base type from hash (for AJAX)
	 *
	 * @param string $hash The hashed notification ID
	 * @return string|false The base notification type or false if not found
	 */
	private function get_notification_type_from_hash($hash)
	{
		$gbt_dashboard = GBT_Dashboard_Setup::init();
		$theme_version = $gbt_dashboard->get_theme_version();
		
		// Check each notification type to see which one produces this hash
		foreach ($this->notification_ids as $key => $base_id) {
			// Generate hash from base_id and current version
			if ($this->get_notice_id($base_id, $theme_version) === $hash) {
				// Return the short form without 'license_' prefix
				return $this->get_base_type($base_id);
			}
		}
		
		return false;
	}

	/**
	 * Handle AJAX dismissal of license notifications - saves permanently
	 */
	public function ajax_dismiss_notification()
	{
		check_ajax_referer('dismiss_license_notification', 'nonce');

		if (!current_user_can('edit_posts')) {
			wp_send_json_error('Insufficient permissions');
		}

		$message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';

		if (empty($message_id)) {
			wp_send_json_error('Missing message ID');
		}

		// Find which base notification type this hash corresponds to
		$base_type = $this->get_notification_type_from_hash($message_id);
		
		if (!$base_type) {
			wp_send_json_error('Invalid notification ID');
		}

		// Build the distinct meta key for this notification
		$user_id = get_current_user_id();
		$meta_key = $this->dismissed_meta_prefix . $base_type;
		
		// Save dismissal permanently with true value
		update_user_meta($user_id, $meta_key, true);

		wp_send_json_success();
	}

	/**
	 * Enqueue dismissal script for license notifications
	 */
	public function enqueue_dismissal_script()
	{
		$gbt_dashboard = GBT_Dashboard_Setup::init();
		$base_paths = $gbt_dashboard->get_base_paths();
		$theme_version = $gbt_dashboard->get_theme_version();

		wp_enqueue_script(
			'license-notification-dismissal',
			$base_paths['url'] . '/dashboard/js/license-notification-dismissal.js',
			['jquery'],
			$theme_version,
			true
		);

		wp_localize_script(
			'license-notification-dismissal',
			'licenseNotificationData',
			[
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('dismiss_license_notification')
			]
		);
	}

}

/**
 * Initialize the license subscription checker on admin pages only
 */
add_action('admin_init', function () {
	if (is_admin()) {
		GBT_License_Subscription_Checker::get_instance();
	}
});
