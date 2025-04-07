<?php
/**
 * GetBowtied Global Notification Handler
 */
if (!defined('ABSPATH')) exit;

if (!class_exists('GBT_Notification_Handler')) {
    class GBT_Notification_Handler {
        private static $instance = null;
        private $script_handle = 'gbt-notification-handler';
        private $nonce_action = 'gbt_dismiss_notification';
        private $dismiss_days = 7; // Default dismissal period in days
        private $transient_prefix = 'gbt_notif_'; // Prefix for all notification transients
        
        public static function instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {
            add_action('wp_ajax_gbt_dismiss_notification', [$this, 'ajax_dismiss']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
            
            // Register hook for theme updates to clear notifications
            add_action('upgrader_process_complete', [$this, 'clear_notifications_on_update'], 10, 2);
        }
        
        public function enqueue_assets() {
            $gbt_dashboard = GBT_Dashboard_Setup::init();
            
            wp_enqueue_script(
                $this->script_handle,
                $gbt_dashboard->get_base_paths()['url'] . '/dashboard/js/gbt-notification-handler.js',
                ['jquery'],
                $gbt_dashboard->get_theme_version(),
                true
            );
            
            wp_localize_script(
                $this->script_handle,
                'gbtNotificationHandler',
                ['ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce($this->nonce_action)]
            );
        }
        
        public function ajax_dismiss() {
            check_ajax_referer($this->nonce_action, 'nonce');
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error('Insufficient permissions');
            }
            
            $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
            
            if (empty($message_id)) {
                wp_send_json_error('Missing message ID');
            }
            
            $this->save_dismissal($message_id);
            wp_send_json_success(true);
        }
        
        /**
         * Save notification dismissal using transients
         *
         * @param string $message_id Notification message ID
         * @return bool Success
         */
        public function save_dismissal($message_id) {
            $user_id = get_current_user_id();
            $transient_key = $this->get_transient_key($user_id, $message_id);
            
            // Set transient with expiration time
            $result = set_transient($transient_key, time(), $this->dismiss_days * DAY_IN_SECONDS);
            
            // Trigger action for other integrations
            do_action('gbt_notification_dismissed', $message_id, $user_id);
            
            return $result;
        }
        
        /**
         * Check if a notification is dismissed
         *
         * @param string $message_id Notification message ID
         * @return bool Whether notification is dismissed
         */
        public function is_dismissed($message_id) {
            $user_id = get_current_user_id();
            $transient_key = $this->get_transient_key($user_id, $message_id);
            
            // Check transient (will return false if expired)
            return false !== get_transient($transient_key);
        }
        
        /**
         * Get the transient key for a notification
         *
         * @param int    $user_id    User ID
         * @param string $message_id Message ID
         * @return string The transient key
         */
        private function get_transient_key($user_id, $message_id) {
            return $this->transient_prefix . $user_id . '_' . $message_id;
        }
        
        /**
         * Clear notifications when theme is updated
         * 
         * @param WP_Upgrader $upgrader   WP_Upgrader instance
         * @param array       $hook_extra Array of bulk item update data
         */
        public function clear_notifications_on_update($upgrader, $hook_extra) {
            // Check if this is a theme update
            if ($hook_extra['type'] !== 'theme') {
                return;
            }
            
            // Get current theme
            $theme = wp_get_theme();
            $theme_name = $theme->get_stylesheet();
            $parent_theme = is_child_theme() ? $theme->parent()->get_stylesheet() : null;
            
            // Check if our theme is being updated
            if (isset($hook_extra['themes']) && (in_array($theme_name, $hook_extra['themes']) || ($parent_theme && in_array($parent_theme, $hook_extra['themes'])))) {
                $this->clear_all_notification_transients();
            }
        }
        
        /**
         * Delete all notification transients for all users
         */
        public function clear_all_notification_transients() {
            global $wpdb;
            
            // Get all transients that match our prefix
            $transient_like = $wpdb->esc_like('_transient_' . $this->transient_prefix) . '%';
            $timeout_like = $wpdb->esc_like('_transient_timeout_' . $this->transient_prefix) . '%';
            
            // Delete the transients
            $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $transient_like));
            $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name LIKE %s", $timeout_like));
            
            // Also clear the notification data transient
            $theme_slug = GBT_Dashboard_Setup::init()->get_theme_slug();
            delete_transient('gbt_dashboard_notification_' . $theme_slug);
            
            return true;
        }
        
        /**
         * Set dismissal period in days
         * 
         * @param int $days Number of days to dismiss notifications
         * @return self For method chaining
         */
        public function set_dismiss_days($days) {
            $this->dismiss_days = max(1, intval($days));
            return $this;
        }
    }
    
    function gbt_notification_handler() {
        return GBT_Notification_Handler::instance();
    }
    
    gbt_notification_handler();
} 