<?php

/**
 * Theme Updates Handler
 * 
 * Handles blocking of theme updates based on custom conditions
 * Works for all GetBowtied themes using config.php settings
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GBT_Theme_Updates {
    
    // Class constants
    const BLOCKED_URL_PREFIX = 'blocked://';
    const BLOCK_SHOP_THEME = 'block-shop';
    const NOTICE_TYPE_SUCCESS = 'notice-success';
    const NOTICE_TYPE_INFO = 'notice-info';
    const NOTICE_TYPE_ERROR = 'notice-error';
    
    // Private properties
    private $theme_slug;
    private $update_url;
    private $allow_updates;
    private $update_checker;
    private $config;
    private $base_paths;
    private $license_manager;
    
    public function __construct($allow_updates = false) {
        $this->initialize_dependencies();
        $this->allow_updates = $allow_updates;
        
        if ($this->should_initialize_updates()) {
            $this->init();
        }
    }
    
    /**
     * Initialize all dependencies
     */
    private function initialize_dependencies() {
        $this->load_base_paths();
        $this->load_config();
        $this->detect_current_theme();
        $this->initialize_license_manager();
    }
    
    /**
     * Check if updates should be initialized
     */
    private function should_initialize_updates() {
        return $this->theme_slug && 
               $this->update_url && 
               $this->theme_slug !== self::BLOCK_SHOP_THEME;
    }
    
    /**
     * Initialize license manager instance
     */
    private function initialize_license_manager() {
        if (class_exists('GBT_License_Manager')) {
            $this->license_manager = GBT_License_Manager::get_instance();
        }
    }
    
    /**
     * Load base paths from dashboard setup
     */
    private function load_base_paths() {
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();
        $this->base_paths = $gbt_dashboard_setup->get_base_paths();
    }
    
    /**
     * Load configuration from config.php
     */
    private function load_config() {
        $config_path = $this->base_paths['path'] . '/dashboard/config.php';
        if (file_exists($config_path)) {
            $this->config = include $config_path;
        }
    }
    
    /**
     * Detect the current theme and get its update URL from config
     */
    private function detect_current_theme() {
        $current_theme = get_template(); // Always get parent theme
        
        if (isset($this->config['supported_themes'][$current_theme])) {
            $this->theme_slug = $current_theme;
            $this->update_url = $this->config['supported_themes'][$current_theme]['theme_update_url'];
        }
    }
    
    /**
     * Initialize the conditional updates system
     */
    private function init() {
        add_action('init', array($this, 'setup_update_checker'));
        add_action('init', array($this, 'setup_conditional_logic'));
    }
    
    /**
     * Setup the plugin update checker
     */
    public function setup_update_checker() {
        if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
            require_once $this->base_paths['path'] . '/dashboard/inc/puc/plugin-update-checker.php';
        }
        
        $theme_functions_path = get_template_directory() . '/functions.php';
        $this->update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            $this->update_url,
            $theme_functions_path,
            $this->theme_slug
        );
        
        // Block update by removing download URL
        $this->update_checker->addResultFilter(array($this, 'filter_update_result'));
    }
    
    /**
     * Setup conditional logic hooks
     */
    public function setup_conditional_logic() {
        $this->update_allow_updates_from_license();
        $this->register_update_hooks();
    }
    
    /**
     * Update allow_updates based on license status
     */
    private function update_allow_updates_from_license() {
        if (!$this->license_manager) {
            $this->allow_updates = false;
            return;
        }
        
        $is_license_active = $this->is_license_active();
        $is_support_active = $this->is_support_active();
        $this->allow_updates = ($is_license_active && $is_support_active);
    }
    
    /**
     * Check if license is active
     */
    private function is_license_active() {
        return $this->license_manager && 
               method_exists($this->license_manager, 'is_license_active') ? 
               $this->license_manager->is_license_active() : false;
    }
    
    /**
     * Check if support is active
     */
    private function is_support_active() {
        return $this->license_manager && 
               method_exists($this->license_manager, 'is_support_active') ? 
               $this->license_manager->is_support_active() : false;
    }
    
    /**
     * Register WordPress hooks for update management
     */
    private function register_update_hooks() {
        add_filter('site_transient_update_themes', array($this, 'filter_theme_transient'), 999);
        add_filter('upgrader_pre_download', array($this, 'block_theme_download'), 10, 3);
        add_action('admin_notices', array($this, 'show_update_notice'));
        add_action('wp_ajax_dismiss_update_notification', array($this, 'ajax_dismiss_update_notification'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dismissal_script'));
        add_action('upgrader_process_complete', array($this, 'cleanup_old_dismissals'), 10, 2);
    }
    
    /**
     * Enqueue dismissal script for update notifications
     */
    public function enqueue_dismissal_script() {
        $gbt_dashboard = GBT_Dashboard_Setup::init();
        $base_paths = $gbt_dashboard->get_base_paths();
        $theme_version = $gbt_dashboard->get_theme_version();

        wp_enqueue_script(
            'update-notification-dismissal',
            $base_paths['url'] . '/dashboard/js/update-notification-dismissal.js',
            ['jquery'],
            $theme_version,
            true
        );

        wp_localize_script(
            'update-notification-dismissal',
            'updateNotificationData',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gbt_dismiss_notification')
            ]
        );

        // Enqueue auto-update handler script for the enable auto-updates link
        wp_enqueue_script(
            'getbowtied-auto-update-handler',
            $base_paths['url'] . '/dashboard/js/auto-update-handler.js',
            ['jquery'],
            $theme_version,
            true
        );

        wp_localize_script(
            'getbowtied-auto-update-handler',
            'gbtAutoUpdateData',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gbt_enable_auto_updates')
            ]
        );
    }
    
    /**
     * Handle AJAX dismissal of update notifications by hash
     */
    public function ajax_dismiss_update_notification() {
        check_ajax_referer('gbt_dismiss_notification', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
        
        if (empty($message_id)) {
            wp_send_json_error('Missing message ID');
        }
        
        // Reverse map hash to full message_id
        $full_message_id = $this->get_message_id_from_hash($message_id);
        
        if (!$full_message_id) {
            wp_send_json_error('Invalid message ID');
        }
        
        // Save dismissal permanently in user meta
        $this->save_update_dismissal($full_message_id);
        
        wp_send_json_success();
    }
    
    /**
     * Save update notification dismissal permanently
     *
     * @param string $message_id The full message ID
     */
    private function save_update_dismissal($message_id) {
        $user_id = get_current_user_id();
        $meta_key = 'gbt_dismissed_update_notification_' . $message_id;
        
        // Save dismissal permanently with true value
        update_user_meta($user_id, $meta_key, true);
    }
    
    /**
     * Check if update notification is dismissed permanently
     *
     * @param string $message_id The full message ID
     * @return bool True if dismissed, false otherwise
     */
    private function is_update_dismissed($message_id) {
        $user_id = get_current_user_id();
        $meta_key = 'gbt_dismissed_update_notification_' . $message_id;
        
        // Check if the specific meta key exists and is truthy
        return (bool) get_user_meta($user_id, $meta_key, true);
    }
    
    /**
     * Cleanup old update notification dismissals when theme updates
     *
     * @param WP_Upgrader $upgrader   WP_Upgrader instance
     * @param array       $hook_extra Array of bulk item update data
     */
    public function cleanup_old_dismissals($upgrader, $hook_extra) {
        // Check if this is a theme update
        if ($hook_extra['type'] !== 'theme') {
            return;
        }
        
        // Always use parent theme (template)
        $parent_theme_slug = get_template();
        
        // Check if our parent theme is being updated
        if (isset($hook_extra['themes']) && in_array($parent_theme_slug, $hook_extra['themes'])) {
            $this->delete_all_update_dismissals();
        }
    }
    
    /**
     * Delete all update notification dismissals for all users
     */
    private function delete_all_update_dismissals() {
        global $wpdb;
        
        // Build LIKE pattern for update notification meta keys
        $meta_key_pattern = 'gbt_dismissed_update_notification_theme_update_' . $this->theme_slug . '_%';
        
        // Delete all matching user meta entries
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s",
            $meta_key_pattern
        ));
    }
    
    /**
     * Filter update result from update checker
     */
    public function filter_update_result($update, $httpResult = null) {
        if (!$update || $this->allow_updates) {
            return $update;
        }
        
        $blocked_update = clone $update;
        $blocked_update->download_url = self::BLOCKED_URL_PREFIX . $this->theme_slug;
        return $blocked_update;
    }
    
    /**
     * Filter WordPress theme update transient
     */
    public function filter_theme_transient($transient) {
        if (!$this->allow_updates && 
            isset($transient->response[$this->theme_slug]) && 
            $this->is_using_target_theme()) {
            
            $transient->response[$this->theme_slug]['package'] = self::BLOCKED_URL_PREFIX . $this->theme_slug;
        }
        return $transient;
    }
    
    /**
     * Block theme download during upgrade
     */
    public function block_theme_download($reply, $package, $upgrader) {
        if (!$this->should_block_download($upgrader)) {
            return $reply;
        }
        
        if ($this->is_blocked_package($package)) {
            return $this->create_download_error();
        }
        
        return $reply;
    }
    
    /**
     * Check if download should be blocked
     */
    private function should_block_download($upgrader) {
        return is_a($upgrader, 'Theme_Upgrader') && !$this->allow_updates;
    }
    
    /**
     * Check if package is blocked
     */
    private function is_blocked_package($package) {
        return is_string($package) && strpos($package, self::BLOCKED_URL_PREFIX) === 0;
    }
    
    /**
     * Create download error for blocked updates
     */
    private function create_download_error() {
        $theme_name = $this->get_theme_name();
        $license_page_url = admin_url('admin.php?page=getbowtied-license');
        
        return new WP_Error(
            $this->theme_slug . '_update_restricted',
            sprintf(
                'Update blocked for %s. Check the <a href="%s" target="_top"><strong>license page</strong></a>.',
                esc_html($theme_name),
                esc_url($license_page_url)
            ),
            array('theme' => $this->theme_slug)
        );
    }
    
    /**
     * Show admin notice when update is blocked
     */
    public function show_update_notice() {
        if (!$this->should_show_update_notice()) {
            return;
        }
        
        $update_info = $this->get_update_information();
        if (!$update_info) {
            return;
        }
        
        // Check if notification is dismissed
        if ($this->is_update_notification_dismissed($update_info)) {
            return;
        }
        
        $this->render_update_notice($update_info);
    }
    
    /**
     * Check if update notice should be shown
     */
    private function should_show_update_notice() {
        // Don't show during initialization period
        if (class_exists('Theme_LI') && Theme_LI::is_init_period_completed() !== true) {
            return false;
        }
        
        return $this->is_using_target_theme();
    }
    
    /**
     * Check if update notification is dismissed
     */
    private function is_update_notification_dismissed($update_info) {
        $message_id = $this->get_update_notification_message_id($update_info);
        return $this->is_update_dismissed($message_id);
    }
    
    /**
     * Get unique message ID for update notification
     */
    private function get_update_notification_message_id($update_info) {
        $status = $this->allow_updates ? 'allowed' : 'blocked';
        return 'theme_update_' . $this->theme_slug . '_' . $status . '_' . $update_info['new_version'];
    }
    
    /**
     * Generate hashed notice ID for update notification
     *
     * @param string $message_id The message ID
     * @return string Hashed notice ID (12 character MD5 hash)
     */
    private function get_update_notice_id($message_id) {
        $theme_object = wp_get_theme($this->theme_slug);
        $theme_version = $theme_object ? $theme_object->get('Version') : '';
        
        // Create a hash from the message ID and current theme version
        return substr(md5($message_id . $theme_version), 0, 12);
    }
    
    /**
     * Get message ID from hashed notice ID
     *
     * @param string $hash The hashed notice ID
     * @return string|false The message ID or false if not found
     */
    private function get_message_id_from_hash($hash) {
        $theme_object = wp_get_theme($this->theme_slug);
        $theme_version = $theme_object ? $theme_object->get('Version') : '';
        
        // Check current update information
        $update_info = $this->get_update_information();
        if (!$update_info) {
            return false;
        }
        
        // Try both allowed and blocked status
        foreach (['allowed', 'blocked'] as $status) {
            $message_id = 'theme_update_' . $this->theme_slug . '_' . $status . '_' . $update_info['new_version'];
            if ($this->get_update_notice_id($message_id) === $hash) {
                return $message_id;
            }
        }
        
        return false;
    }
    
    /**
     * Get update information from WordPress transient
     */
    private function get_update_information() {
        $updates = get_site_transient('update_themes');
        if (!$updates || !isset($updates->response[$this->theme_slug])) {
            return null;
        }
        
        return $this->prepare_update_data($updates->response[$this->theme_slug]);
    }
    
    /**
     * Prepare update data for display
     */
    private function prepare_update_data($update_entry) {
        $theme_object = wp_get_theme($this->theme_slug);
        $current_version = $theme_object ? $theme_object->get('Version') : '';
        $new_version = $this->extract_new_version($update_entry);
        
        return array(
            'theme_name' => $this->get_theme_name(),
            'current_version' => $current_version,
            'new_version' => $new_version,
            'license_page_url' => admin_url('admin.php?page=getbowtied-license'),
            'updates_page_url' => admin_url('update-core.php#update-themes-table')
        );
    }
    
    /**
     * Extract new version from update entry
     */
    private function extract_new_version($update_entry) {
        if (is_array($update_entry) && isset($update_entry['new_version'])) {
            return $update_entry['new_version'];
        } elseif (is_object($update_entry) && isset($update_entry->new_version)) {
            return $update_entry->new_version;
        }
        return '';
    }
    
    /**
     * Render the update notice
     */
    private function render_update_notice($update_info) {
        if ($this->allow_updates) {
            $this->render_success_notice($update_info);
        } else {
            $this->render_blocked_notice($update_info);
        }
    }
    
    /**
     * Render success notice for allowed updates
     */
    private function render_success_notice($update_info) {
        $message_id = $this->get_update_notification_message_id($update_info);
        $notice_id = $this->get_update_notice_id($message_id);
        echo '<div id="' . esc_attr($notice_id) . '" class="notice ' . esc_attr(self::NOTICE_TYPE_SUCCESS) . ' is-dismissible">';
        echo '<p style="display: flex; align-items: center;">';
        echo wp_kses_post($this->get_success_icon());
        echo '<span><strong>' . esc_html($update_info['theme_name']) . ' Update Available:</strong> ';
        echo esc_html($this->get_version_message($update_info));
        echo '<a href="' . esc_url($update_info['updates_page_url']) . '"><strong>Update to version ' . esc_html($update_info['new_version']) . ' right now</strong></a>.';
        echo wp_kses_post($this->get_auto_update_enable_link());
        echo '</span>';
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * Render blocked notice
     */
    private function render_blocked_notice($update_info) {
        if ($this->is_license_fully_active()) {
            $this->render_success_notice($update_info);
            return;
        }
        
        $message_id = $this->get_update_notification_message_id($update_info);
        $notice_id = $this->get_update_notice_id($message_id);
        echo '<div id="' . esc_attr($notice_id) . '" class="notice ' . esc_attr(self::NOTICE_TYPE_INFO) . ' is-dismissible">';
        echo '<p style="display: flex; align-items: center;">';
        echo wp_kses_post($this->get_warning_icon());
        echo '<span><strong>' . esc_html($update_info['theme_name']) . ' Update Available:</strong> ';
        echo esc_html($this->get_version_info($update_info));
        echo '<strong>Updates are restricted for you → </strong> ';
        echo wp_kses_post($this->get_license_action_message($update_info['license_page_url']));
        echo '</span>';
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * Check if license is fully active (both license and support)
     */
    private function is_license_fully_active() {
        return $this->is_license_active() && $this->is_support_active();
    }
    
    /**
     * Get success icon HTML
     */
    private function get_success_icon() {
        return '<span style="color: #00a32a; margin-right: 8px; line-height: 0; display: inline-flex;">'
            . '<svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">'
            . '<path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v8.19l2.22-2.22a.75.75 0 1 1 1.06 1.06l-3.5 3.5a.75.75 0 0 1-1.06 0l-3.5-3.5a.75.75 0 1 1 1.06-1.06l2.22 2.22V3.75A.75.75 0 0 1 10 3Zm-6.25 13a.75.75 0 0 1 .75-.75h11a.75.75 0 0 1 0 1.5h-11a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"></path>'
            . '</svg>'
            . '</span>';
    }
    
    /**
     * Get warning icon HTML
     */
    private function get_warning_icon() {
        return '<span class="dashicons dashicons-warning" style="color: #d63638; margin-right: 8px;"></span>';
    }

    
    
    /**
     * Get version message for success notice
     */
    private function get_version_message($update_info) {
        if ($update_info['current_version'] && $update_info['new_version']) {
            return 'You have version ' . $update_info['current_version'] . ' installed. ';
        }
        return '';
    }
    
    /**
     * Get version info for blocked notice
     */
    private function get_version_info($update_info) {
        if ($update_info['current_version'] && $update_info['new_version']) {
            return 'You have version ' . $update_info['current_version'] . ' installed. The new version is ' . $update_info['new_version'] . '. ';
        }
        return '';
    }
    
    /**
     * Get license action message based on current status
     */
    private function get_license_action_message($license_page_url) {
        if (!$this->is_license_active()) {
            return '<a href="' . esc_url($license_page_url . '#license-area') . '"><strong>Activate license</strong></a> to restore built-in updates and security fixes.';
        } elseif ($this->is_license_active() && !$this->is_support_active()) {
            return '<a href="' . esc_url($license_page_url . '#license-options') . '"><strong>Renew subscription</strong></a> to restore built-in updates and security fixes.';
        } else {
            return '<a href="' . esc_url($license_page_url) . '"><strong>Resolve license/subscription</strong></a> to restore built-in updates and security fixes.';
        }
    }
    
    /**
     * Get auto-update enable link for update notification
     */
    private function get_auto_update_enable_link() {
        // Check if auto-updates are already enabled
        $auto_updates = get_option('auto_update_themes', array());
        if (in_array($this->theme_slug, $auto_updates)) {
            return '';
        }
        
        $dashboard_setup = GBT_Dashboard_Setup::init();
        $theme_name = $dashboard_setup->get_theme_name();
        
        // Create a clickable link that triggers the auto-update enable functionality
        $enabling_text = __('Enabling...', 'getbowtied');
        $success_text = __('Auto-updates enabled.', 'getbowtied');
        return '<span class="gbt-auto-update-text-wrapper" data-enabling-text="' . esc_attr($enabling_text) . '" data-success-text="' . esc_attr($success_text) . '"> For the future, you may consider <a href="#" class="gbt-enable-auto-updates gbt-auto-update-link" data-theme="' . esc_attr($this->theme_slug) . '" style="cursor: pointer;"><strong>enabling automatic updates</strong></a>.</span>';
    }
    
    /**
     * Get theme name from config
     */
    private function get_theme_name() {
        if (isset($this->config['supported_themes'][$this->theme_slug]['theme_name'])) {
            return $this->config['supported_themes'][$this->theme_slug]['theme_name'];
        }
        return ucfirst($this->theme_slug);
    }
    
    /**
     * Check if we're using the target theme (parent or child)
     */
    private function is_using_target_theme() {
        return get_template() === $this->theme_slug || get_stylesheet() === $this->theme_slug;
    }
    
    // ================================
    // PUBLIC API METHODS
    // ================================
    
    /**
     * Set whether updates are allowed
     */
    public function set_allow_updates($allow) {
        $this->allow_updates = (bool) $allow;
    }
    
    /**
     * Get current allow updates status
     */
    public function get_allow_updates() {
        return $this->allow_updates;
    }
    
    /**
     * Set custom condition callback
     */
    public function set_condition_callback($callback) {
        if (is_callable($callback)) {
            add_action('init', function() use ($callback) {
                $this->allow_updates = call_user_func($callback);
            });
        }
    }
    
    /**
     * Get current theme slug
     */
    public function get_theme_slug() {
        return $this->theme_slug;
    }
    
    /**
     * Get current theme update URL
     */
    public function get_update_url() {
        return $this->update_url;
    }
    
    /**
     * Get license manager instance
     */
    public function get_license_manager() {
        return $this->license_manager;
    }
    
    /**
     * Refresh license status and update allow_updates accordingly
     */
    public function refresh_license_status() {
        $this->update_allow_updates_from_license();
    }
}

// Auto-initialize with default settings (updates blocked by default)
new GBT_Theme_Updates(); 