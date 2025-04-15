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
	 * Notification settings
	 */
	private $notification_settings = [
		'expired_id'          => 'license_subscription_expired',
		'expiring_soon_id'    => 'license_subscription_expiring_soon',
		'dismiss_option'      => 'getbowtied_theme_license_subscription_expired_dismissed',
		'dismiss_soon_option' => 'getbowtied_theme_license_subscription_expiring_soon_dismissed',
		'dismiss_days'        => 1,
		'expiring_threshold'  => 14, // Days threshold for "expiring soon" notification
		'script_handle'       => 'gbt-license-subscription-notification',
		'script_filename'     => 'license-subscription-notification.js',
		'nonce_action'        => 'gbt_license_notification_nonce'
	];

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

		// Update notification settings with values from config
		$this->notification_settings['dismiss_days'] = 1;
		$this->notification_settings['expiring_threshold'] = 14;

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
		// Load styles on all admin pages
		add_action('admin_enqueue_scripts', [$this, 'enqueue_notification_assets']);

		// Display notifications
		add_action('admin_notices', [$this, 'check_license_and_display_notification']);

		// Handle dismissals
		add_action('wp_ajax_dismiss_license_subscription_notification', [$this, 'handle_ajax_notification_dismissal']);
	}

	/**
	 * Check license status and display appropriate notification if needed
	 */
	public function check_license_and_display_notification()
	{
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
		
		// First check if license information exists
		if (!$this->has_valid_license_info()) {
			$this->display_missing_license_notification();
			return;
		}

		// Get license manager instance to access support days calculation
		$license_manager = GBT_License_Manager::get_instance();

		// Priority check: First check if license is active but support has expired 
		if ($license_manager->is_license_active() && !$license_manager->is_support_active() && !$this->is_notification_dismissed()) {
			$this->display_expired_subscription_notification();
			return;
		}

		// Next check: If license is active and support is active, check if it's about to expire
		if ($license_manager->is_license_active() && $license_manager->is_support_active()) {
			// Check if it's expiring soon and notification hasn't been dismissed
			if (
				$license_manager->is_support_expiring_soon($this->notification_settings['expiring_threshold'])
				&& !$this->is_expiring_soon_notification_dismissed()
			) {
				$this->display_expiring_soon_notification($license_manager->get_support_days_remaining());
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
		$license_page_url = admin_url('admin.php?page=getbowtied-license');
		$purchase_url = $gbt_dashboard->get_theme_sales_page_url();

?>
		<div class="notice-error settings-error notice getbowtied_ext_notice gbt-dashboard-notification no-license-notification"
			data-message-id="license_no_license_detected"
			data-theme-slug="<?php echo esc_attr($theme_slug); ?>">
			<div class="getbowtied_ext_notice__aside">
				<div class="getbowtied_icon" aria-hidden="true"><br></div>
			</div>

			<div class="getbowtied_ext_notice__content">
				<div class="title-container">
					<h3 class="title"><strong>CRITICAL ALERT:</strong> <?php echo esc_html($theme_name); ?> theme license not detected!</h3>
					<a href="#" class="getbowtied_ext_notice__toggle_link">Click for details</a>
				</div>
				<div class="getbowtied_ext_notice__collapsible_content">
					<p>Your <?php echo esc_html($theme_name); ?> theme is currently operating without a valid license key.</p>

					<p>
						<strong>To avoid any disruptions to your website</strong>, your theme requires a valid license key.
					</p>

					<p>
						<strong class="getbowtied_ext_notice_red_text">Security Risk: Without a valid license, your website may be exposed to vulnerabilities.</strong>
					</p>

					<p>
						<a href="<?php echo esc_url($license_page_url); ?>" class="button button-primary button-large">Activate Your License Now</a>
						<a href="<?php echo esc_url($purchase_url); ?>" target="_blank" class="button button-large">Get a License</a>
						<span class="reminder-options">
							<a href="#" class="dismiss-notification reminder-btn" data-message-id="license_no_license_detected" data-theme-slug="<?php echo esc_attr($theme_slug); ?>" data-days="1">Remind me tomorrow</a>
							<a href="#" class="dismiss-notification reminder-btn" data-message-id="license_no_license_detected" data-theme-slug="<?php echo esc_attr($theme_slug); ?>" data-days="7">Remind me in 1 week</a>
							<a href="#" class="dismiss-notification reminder-btn" data-message-id="license_no_license_detected" data-theme-slug="<?php echo esc_attr($theme_slug); ?>" data-days="30">Remind me in 1 month</a>
						</span>
					</p>
				</div>
			</div>
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

		$license_page_url = admin_url('admin.php?page=getbowtied-license');
		$license_details_url = $license_page_url;
		$renew_url = $gbt_dashboard->get_theme_sales_page_url();

	?>
		<div class="notice-error settings-error notice getbowtied_ext_notice expired-notification gbt-dashboard-notification"
			data-message-id="<?php echo esc_attr($this->notification_settings['expired_id']); ?>"
			data-theme-slug="<?php echo esc_attr($theme_slug); ?>">
			<div class="getbowtied_ext_notice__aside">
				<div class="getbowtied_icon" aria-hidden="true"><br></div>
			</div>

			<div class="getbowtied_ext_notice__content">
				<div class="title-container">
					<h3 class="title">Your "<?php echo esc_html($theme_name); ?>" Professional Plan has expired</h3>
					<a href="#" class="getbowtied_ext_notice__toggle_link">Click for details</a>
				</div>
				<div class="getbowtied_ext_notice__collapsible_content">
					<h4>Your <?php echo esc_html($theme_name); ?> theme Professional Plan has ended, putting your website at risk.</h4>

					<p>
						<strong>IMPORTANT:</strong> Your site is currently <span class="getbowtied_ext_notice_red_text">no longer auto-receiving</span>:
					</p>

					<ul>
						<li class="dashicons-before dashicons-shield-alt">Built-in critical security updates</li>
						<li class="dashicons-before dashicons-update-alt">Built-in priority bug fixes, security & compatibility updates</li>
						<li class="dashicons-before dashicons-admin-users">Expert assistance from a dedicated developer</li>
					</ul>

					<p>
						<strong class="getbowtied_ext_notice_red_text">Without an active support plan, your website is exposed to security vulnerabilities and compatibility issues with future WordPress and WooCommerce updates.</strong>
					</p>

					<p>
						<strong>Renew now to regain exclusive access to automatic updates, priority assistance, and security patches that ensure your store runs smoothly and securely.</strong>
					</p>

					<p>
						<a href="<?php echo esc_url($license_details_url); ?>" class="button button-primary button-large">View Your License Details</a>
						&nbsp;
						<span class="reminder-options">
							<a href="#" class="dismiss-notification reminder-btn" data-message-id="<?php echo esc_attr($this->notification_settings['expired_id']); ?>" data-theme-slug="<?php echo esc_attr($theme_slug); ?>" data-days="1">Remind me tomorrow</a>
							<a href="#" class="dismiss-notification reminder-btn" data-message-id="<?php echo esc_attr($this->notification_settings['expired_id']); ?>" data-theme-slug="<?php echo esc_attr($theme_slug); ?>" data-days="7">Remind me in 1 week</a>
							<a href="#" class="dismiss-notification reminder-btn" data-message-id="<?php echo esc_attr($this->notification_settings['expired_id']); ?>" data-theme-slug="<?php echo esc_attr($theme_slug); ?>" data-days="30">Remind me in 1 month</a>
						</span>
					</p>
				</div>
			</div>
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

		// Get license manager for formatted date
		$license_manager = GBT_License_Manager::get_instance();
		$expiration_date = $license_manager->get_support_expiration_date(true); // Get formatted date

		$license_page_url = admin_url('admin.php?page=getbowtied-license');
		$license_details_url = $license_page_url;
		$renew_url = $gbt_dashboard->get_theme_sales_page_url();

		// Format days remaining text
		$days_text = $days_remaining == 1 ? 'day' : 'days';

	?>
		<div class="notice-warning settings-error notice getbowtied_ext_notice gbt-dashboard-notification"
			data-message-id="<?php echo esc_attr($this->notification_settings['expiring_soon_id']); ?>" 
			data-theme-slug="<?php echo esc_attr($theme_slug); ?>">
			<div class="getbowtied_ext_notice__aside">
				<div class="getbowtied_icon" aria-hidden="true"><br></div>
			</div>

			<div class="getbowtied_ext_notice__content">
				<div class="title-container">
					<h3 class="title">IMPORTANT: Your <?php echo esc_html($theme_name); ?> Professional Plan <?php
																													if ($days_remaining == 0) {
																														echo 'expires today!';
																													} elseif ($days_remaining == 1) {
																														echo 'expires tomorrow!';
																													} else {
																														echo 'expires in ' . esc_html($days_remaining) . ' days!';
																													}
																													?></h3>
					<a href="#" class="getbowtied_ext_notice__toggle_link">Click for details</a>
				</div>
				<div class="getbowtied_ext_notice__collapsible_content">
					<h4>Your <?php echo esc_html($theme_name); ?> Professional Plan will expire on <?php echo esc_html($expiration_date); ?>, putting your site at risk.</h4>

					<p>
						<strong>Act now:</strong> <?php
													if ($days_remaining == 0) {
														echo '<span class="getbowtied_ext_notice_red_text">Today is your last day of coverage</span>. After today, you will';
													} elseif ($days_remaining == 1) {
														echo '<span class="getbowtied_ext_notice_red_text">Starting tomorrow</span>, you will';
													} else {
														echo '<span class="getbowtied_ext_notice_red_text">In ' . esc_html($days_remaining) . ' days</span>, you will';
													}
													?> lose access to:
					</p>

					<ul>
						<li class="dashicons-before dashicons-shield-alt">Built-in critical security updates</li>
						<li class="dashicons-before dashicons-update-alt">Built-in priority bug fixes, security & compatibility updates</li>
						<li class="dashicons-before dashicons-admin-users">Expert assistance from a dedicated developer</li>
					</ul>

					<p>
						<strong>Renew today to maintain uninterrupted access to automatic updates, premium features, and critical security patches.</strong>
					</p>

					<p>
						<a href="<?php echo esc_url($license_details_url); ?>" class="button button-primary button-large">View Your License Details</a>
						&nbsp;
						<span class="reminder-options">
							<a href="#" class="dismiss-notification reminder-btn" data-message-id="<?php echo esc_attr($this->notification_settings['expiring_soon_id']); ?>" data-theme-slug="<?php echo esc_attr($theme_slug); ?>" data-days="1">Remind me tomorrow</a>
						</span>
					</p>
				</div>
			</div>
		</div>
	<?php
	}

	/**
	 * Check if expired notification has been dismissed by the user
	 *
	 * @return bool True if notification is dismissed, false otherwise
	 */
	private function is_notification_dismissed()
	{
		return $this->is_notification_type_dismissed(
			$this->notification_settings['dismiss_option']
		);
	}

	/**
	 * Check if expiring soon notification has been dismissed by the user
	 *
	 * @return bool True if notification is dismissed, false otherwise
	 */
	private function is_expiring_soon_notification_dismissed()
	{
		return $this->is_notification_type_dismissed(
			$this->notification_settings['dismiss_soon_option']
		);
	}

	/**
	 * Generic method to check if a notification of specified type is dismissed
	 * 
	 * @param string $option_name The option name to check for dismissal
	 * @return bool True if dismissed, false otherwise
	 */
	private function is_notification_type_dismissed($option_name)
	{
		$dismissed = get_option($option_name, []);
		$theme_slug = GBT_Dashboard_Setup::init()->get_theme_slug();

		// Not dismissed if no record exists
		if (empty($dismissed[$theme_slug])) {
			return false;
		}

		// Check if stored value is an array (new format) or timestamp (old format)
		if (is_array($dismissed[$theme_slug])) {
			$dismissed_time = $dismissed[$theme_slug]['time'];
			$dismiss_days = $dismissed[$theme_slug]['days'];
		} else {
			// Legacy format - just a timestamp with default days
			$dismissed_time = $dismissed[$theme_slug];
			$dismiss_days = $this->notification_settings['dismiss_days'];
		}

		// Calculate dismiss period
		$dismiss_seconds = $dismiss_days * DAY_IN_SECONDS;

		// Still dismissed if within the dismissal period
		if (time() < ($dismissed_time + $dismiss_seconds)) {
			return true;
		}

		// Dismissal period expired, clear the record
		$this->clear_dismissal_record($theme_slug, $dismissed, $option_name);

		return false;
	}

	/**
	 * Clear dismissal record after dismiss period has expired
	 *
	 * @param string $theme_slug The theme slug to clear
	 * @param array $dismissed The current dismissed notifications array
	 * @param string $option_name The option name to update
	 */
	private function clear_dismissal_record($theme_slug, $dismissed, $option_name)
	{
		unset($dismissed[$theme_slug]);
		update_option($option_name, $dismissed);
	}

	/**
	 * Handle AJAX request to dismiss notification
	 */
	public function handle_ajax_notification_dismissal()
	{
		// Verify nonce for security
		check_ajax_referer($this->notification_settings['nonce_action'], 'nonce');

		// Validate and sanitize input
		$message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
		$theme_slug = isset($_POST['theme_slug']) ? sanitize_text_field($_POST['theme_slug']) : '';
		$days = isset($_POST['days']) ? absint($_POST['days']) : $this->notification_settings['dismiss_days'];

		// Get the appropriate dismiss option based on message ID
		$dismiss_option = $this->get_dismiss_option_for_message($message_id);

		if ($dismiss_option === false) {
			wp_send_json_error('Invalid message ID');
		}

		// Save dismissal timestamp with days parameter
		$this->save_notification_dismissal($theme_slug, $dismiss_option, $days);

		wp_send_json_success();
	}

	/**
	 * Get the dismiss option name for a given message ID
	 * 
	 * @param string $message_id The message ID
	 * @return string|false The option name or false if invalid message ID
	 */
	private function get_dismiss_option_for_message($message_id)
	{
		switch ($message_id) {
			case $this->notification_settings['expired_id']:
				return $this->notification_settings['dismiss_option'];
			case $this->notification_settings['expiring_soon_id']:
				return $this->notification_settings['dismiss_soon_option'];
			case 'license_no_license_detected':
				return $this->notification_settings['dismiss_option'];
			default:
				return false;
		}
	}

	/**
	 * Save notification dismissal to database
	 *
	 * @param string $theme_slug The theme slug to associate with dismissal
	 * @param string $option_name The option name to update
	 * @param int $days Number of days to dismiss for
	 */
	private function save_notification_dismissal($theme_slug, $option_name, $days = 1)
	{
		$dismissed = get_option($option_name, []);
		// Store both the time and the number of days to dismiss for
		$dismissed[$theme_slug] = [
			'time' => time(),
			'days' => $days
		];
		update_option($option_name, $dismissed);
	}

	/**
	 * Enqueue JavaScript for handling notification dismissal
	 */
	public function enqueue_notification_assets()
	{
		$gbt_dashboard = GBT_Dashboard_Setup::init();
		$base_paths = $gbt_dashboard->get_base_paths();
		$theme_version = $gbt_dashboard->get_theme_version();

		// Get the script URL using base_paths
		$script_url = $base_paths['url'] . '/dashboard/js/' . $this->notification_settings['script_filename'];

		// Register and enqueue the script
		wp_enqueue_script(
			$this->notification_settings['script_handle'],
			$script_url,
			['jquery'],
			$theme_version ?? '1.0',
			true
		);

		// Localize the script with required data
		wp_localize_script(
			$this->notification_settings['script_handle'],
			'gbtLicenseSubscriptionData',
			[
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce($this->notification_settings['nonce_action'])
			]
		);

		// Enqueue notification styles across all admin pages
		wp_enqueue_style(
			'getbowtied-license-notifications',
			$base_paths['url'] . '/dashboard/css/license-notifications.css',
			['dashicons'],
			$theme_version ?? '1.0'
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
