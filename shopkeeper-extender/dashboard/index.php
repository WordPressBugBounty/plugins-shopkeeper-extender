<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('GBT_Dashboard_Setup')) {
    class GBT_Dashboard_Setup {

        private static $instance = null;
        private static $initialized = false;
        private $pagenow;
        private $theme_slug_gbt_dash;
        private $theme_name_gbt_dash;
        private $theme_version_gbt_dash;
        private $theme_child_download_link_gbt_dash;
        private $theme_themeforest_id;
        private $theme_url_docs_gbt_dash;
        private $theme_url_changelog_gbt_dash;
        private $theme_url_support_gbt_dash;
        private $gbt_fs_txt;
        private $base_paths = null;
        private $plugin_theme_slug_param_gbt_dash;

        private function __construct() {
            // Empty constructor - initialization happens in init_instance
        }

        private function init_instance() {
            if (self::$initialized) {
                return;
            }
            
            global $pagenow, $gbt_dashboard_params;
            
            $this->pagenow = $pagenow;
            
            $this->plugin_theme_slug_param_gbt_dash = isset($gbt_dashboard_params['gbt_theme_slug']) ? $gbt_dashboard_params['gbt_theme_slug'] : '';

            $this->initialize_base_paths();
            $this->initialize_theme_properties();
            $this->initialize_theme_urls();

            $supported_themes = array_keys($this->get_supported_themes());
            
            if (in_array($this->theme_slug_gbt_dash, $supported_themes)) {
                $this->setup_hooks();
                $this->setup_freemius_texts();
                $this->setup_freemius_for_all_themes();
            } else {
                if ($this->is_plugin_context(__FILE__)) {
                    add_action( 'admin_notices', array( $this, 'unsupported_theme_warning' ) );
                }
            }

            self::$initialized = true;
        }

        public static function get_instance() {
            return self::init();
        }

        public static function init() {
            if (self::$instance === null) {
                self::$instance = new self();
                self::$instance->init_instance();
            }
            return self::$instance;
        }

        private function __clone() {}
        public function __wakeup() {
            throw new Exception("Cannot unserialize singleton");
        }

        private function initialize_base_paths() {
            if ($this->base_paths === null) {
                $current_file = __FILE__;
                $is_plugin = $this->is_plugin_context($current_file);
                
                if ($is_plugin) {
                    $plugin_root = dirname($current_file);
                    $this->base_paths = array(
                        'path' => rtrim(plugin_dir_path($plugin_root), '/'),
                        'url'  => rtrim(plugin_dir_url($plugin_root), '/')
                    );
                } else {
                    $this->base_paths = array(
                        'path' => rtrim(get_template_directory(), '/'),
                        'url'  => rtrim(get_template_directory_uri(), '/')
                    );
                }
            }
        }

        private function is_plugin_context($file_path) {
            return strpos($file_path, 'plugins') !== false;
        }

        public function get_base_paths() {
            return $this->base_paths;
        }

        private function initialize_theme_properties() {
            $this->theme_slug_gbt_dash = get_template();
            $theme = wp_get_theme(get_template());
            $this->theme_name_gbt_dash = $theme->get('Name');
            $this->theme_version_gbt_dash = $theme->get('Version');
            $this->theme_child_download_link_gbt_dash = sprintf(
                "https://getbowtied.github.io/repository/themes/%s/%s-child.zip",
                $this->theme_slug_gbt_dash,
                $this->theme_slug_gbt_dash
            );
        }

        private function initialize_theme_urls() {
            $themes = $this->get_supported_themes();

            if (array_key_exists($this->theme_slug_gbt_dash, $themes)) {
                $theme_data = $themes[$this->theme_slug_gbt_dash];
                $this->theme_themeforest_id = $theme_data['theme_marketplace_id'];
                $this->theme_url_docs_gbt_dash = $theme_data['theme_infos_url'] . $theme_data['theme_docs_path'];
                $this->theme_url_changelog_gbt_dash = $theme_data['theme_infos_url'] . $theme_data['theme_changelog_path'];
                
                if ($this->theme_slug_gbt_dash === "block-shop") {
                    $this->theme_url_support_gbt_dash = $theme_data['theme_infos_url'] . "/my-account/create-a-ticket/";
                } else {
                    $this->theme_url_support_gbt_dash = $theme_data['theme_infos_url'] . "/support/?envato_item_id=" . $this->theme_themeforest_id;
                }
            }
        }

        private function setup_hooks() {
            if ($this->is_theme_activation_page()) {
                $this->redirect_to_dashboard();
            }

            if ($this->is_theme_update_page()) {
                $this->redirect_to_dashboard();
            }

            add_action('upgrader_process_complete', [$this, 'gbt_theme_update_redirect'], 10, 2);
            add_action('admin_init', [$this, 'gbt_redirect_after_theme_update']);
            add_action('admin_notices', [$this, 'display_dashboard_message']);
            
            add_action('wp_ajax_dismiss_gbt_dashboard_notification', [$this, 'handle_message_dismissal']);

            include_once($this->base_paths['path'] . '/dashboard/setup.php');
        }

        private function is_theme_activation_page() {
            return 'themes.php' == $this->pagenow && isset($_GET['activated']);
        }

        private function is_theme_update_page() {
            return 'update.php' == $this->pagenow && isset($_GET['overwrite']) && $_GET['overwrite'] == 'update-theme';
        }

        private function redirect_to_dashboard() {
            wp_safe_redirect(admin_url("admin.php?page=getbowtied-dashboard"));
            exit;
        }

        public function gbt_theme_update_redirect($upgrader_object, $options) {
            if ($options['action'] == 'update' && $options['type'] == 'theme') {
                $theme = wp_get_theme();
                $parent_theme = is_child_theme() ? $theme->parent() : null;

                if (isset($options['themes']) && (in_array($theme->get_stylesheet(), $options['themes']) || ($parent_theme && in_array($parent_theme->get_stylesheet(), $options['themes'])))) {
                    update_option('gbt_theme_updated_redirect', true);
                }
            }
        }

        public function gbt_redirect_after_theme_update() {
            if (get_option('gbt_theme_updated_redirect')) {
                delete_option('gbt_theme_updated_redirect');
                $this->redirect_to_dashboard();
            }
        }

        private function setup_freemius_texts() {
            $this->gbt_fs_txt = [
                'opt-in-connect'    => __("Complete the Activation Process", 'freemius'),
                'skip'              => __('Later', 'freemius'),
                'few-plugin-tweaks' => sprintf(
                    __("🚩 You are just one step away! %s now. Once done, you can start using the theme's features.", 'freemius'),
                    sprintf('<b><a href="%s">%s</a></b>',
                        admin_url('admin.php?page=getbowtied-dashboard'),
                        sprintf(
                            __('Complete %s Theme Activation Process', 'freemius'),
                            $this->theme_name_gbt_dash
                        )
                    )
                ),
                'complete-the-opt-in' => sprintf(
                    '<a href="%s" class="gbt_fs_complete_activation_link"><strong>%s</strong></a>',
                    admin_url('admin.php?page=getbowtied-dashboard'),
                    __('complete the activation process', 'freemius')
                ),
                'plugin-x-activation-message' => sprintf(
                    '%s activation process was successfully completed.',
                    $this->theme_name_gbt_dash
                )
            ];
        }

        public function gbt_fs_custom_connect_header($header_html) {
            return sprintf(
                __('<h2>Thank you for using %s Theme v%s!</h2>', 'freemius'),
                $this->theme_name_gbt_dash,
                $this->theme_version_gbt_dash
            );
        }

        public function gbt_fs_custom_connect_message($message, $user_first_name, $theme_title, $user_login, $site_link, $freemius_link) {
            return sprintf(
                __("You're almost there! <strong>Complete the Activation Process</strong> for your %s theme. Simply click the button below to finalize the activation, and you're done!", 'freemius'),
                $theme_title
            );
        }

        public function gbt_fs_add_custom_messages($activation_state) {
            if ($activation_state['is_license_activation']) {
                // The opt-in is rendered as license activation.
            }

            if ($activation_state['is_pending_activation']) {
                echo sprintf('<p style="text-align:center">Incorrect email? <b><a href="%s">Update your profile</a></b>.</p>',
                    admin_url('profile.php')
                );
            }

            if ($activation_state['is_network_level_activation']) {
                // A network-level opt-in after network activation of the plugin (only applicable for plugins).
            }

            if ($activation_state['is_dialog']) {
                // The opt-in is rendered within a modal dialog (only applicable for themes).
            }
        }

        public function gbt_setup_freemius($fs_instance) {
            if (!function_exists($fs_instance)) {
                return;
            }

            $fs = call_user_func($fs_instance);

            $filters = [
                'hide_freemius_powered_by'  => '__return_true',
                'connect-header'            => [$this, 'gbt_fs_custom_connect_header'],
                'connect-header_on-update'  => [$this, 'gbt_fs_custom_connect_header'],
                'connect_message'           => [$this, 'gbt_fs_custom_connect_message'],
                'connect_message_on_update' => [$this, 'gbt_fs_custom_connect_message'],
                'connect/after_actions'     => [$this, 'gbt_fs_add_custom_messages']
            ];

            foreach ($filters as $hook => $callback) {
                $fs->add_filter($hook, $callback, 10, 6);
            }

            $fs->override_i18n($this->gbt_fs_txt);
        }

        public function setup_freemius_for_all_themes() {
            $theme_instances = [
                'shopkeeper_fs',
                'theretailer_fs',
                'mrtailor_fs',
                'merchandiser_fs',
                'thehanger_fs',
                'blockshop_fs'
            ];

            foreach ($theme_instances as $instance) {
                $this->gbt_setup_freemius($instance);
            }
        }

        public function get_theme_slug() {
            return $this->theme_slug_gbt_dash;
        }

        public function get_theme_name() {
            return $this->theme_name_gbt_dash;
        }

        public function get_theme_version() {
            return $this->theme_version_gbt_dash;
        }

        public function get_theme_url_changelog() {
            return $this->theme_url_changelog_gbt_dash;
        }

        public function get_theme_url_docs() {
            return $this->theme_url_docs_gbt_dash;
        }

        public function get_theme_url_support() {
            return $this->theme_url_support_gbt_dash;
        }

        public function get_theme_child_download_link() {
            return $this->theme_child_download_link_gbt_dash;
        }

        public function unsupported_theme_warning() {
            $supported_themes = $this->get_supported_themes();
            $theme_slug = $this->plugin_theme_slug_param_gbt_dash;
            
            $theme_name = $supported_themes[$theme_slug]['theme_name'];
            $theme_url = $supported_themes[$theme_slug]['theme_sales_page_url'];
            
            $message = sprintf(
                '<div class="message error"><p><strong>%s Extender</strong> is enabled but not effective. It requires <strong>%s Theme</strong> in order to work. <a href="%s" target="_blank"><strong>Get %s Theme</strong></a>.</p></div>',
                $theme_name,
                $theme_name,
                $theme_url,
                $theme_name
            );
            
            echo wp_kses_post($message);
        }

        private function get_supported_themes() {
            $config = include($this->base_paths['path'] . '/dashboard/config.php');
            return $config['supported_themes'];
        }

        private function get_external_message() {
            $theme_slug = $this->theme_slug_gbt_dash;
            $transient_key = 'gbt_dashboard_notification_' . $theme_slug;
            
            // Check for cache bypass parameter
            $bypass_cache = isset($_GET['refresh_notifications']) && current_user_can('manage_options');
            
            // Try to get cached data if not bypassing
            $message_data = $bypass_cache ? false : get_transient($transient_key);
            
            if (false === $message_data) {
                $message_data = $this->fetch_and_cache_message($transient_key);
            } else {
                $message_data = $this->decode_message_if_needed($message_data);
            }
            
            return $this->validate_message($message_data);
        }

        private function fetch_and_cache_message($transient_key) {
            $remote_url = 'https://getbowtied.net/' . $this->theme_slug_gbt_dash . '-dashboard-notifications';
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $os = 'Unknown OS';
            $browser = 'Unknown Browser';

            // Detect OS
            if (preg_match('/linux/i', $user_agent)) {
                $os = 'Linux';
            } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
                $os = 'Mac OS';
            } elseif (preg_match('/windows|win32/i', $user_agent)) {
                $os = 'Windows';
            }

            // Detect Browser
            if (preg_match('/MSIE/i', $user_agent) || preg_match('/Trident/i', $user_agent)) {
                $browser = 'Internet Explorer';
            } elseif (preg_match('/Firefox/i', $user_agent)) {
                $browser = 'Mozilla Firefox';
            } elseif (preg_match('/Chrome/i', $user_agent) && !preg_match('/Edge/i', $user_agent)) {
                $browser = 'Google Chrome';
            } elseif (preg_match('/Safari/i', $user_agent) && !preg_match('/Chrome/i', $user_agent)) {
                $browser = 'Apple Safari';
            } elseif (preg_match('/Edge/i', $user_agent)) {
                $browser = 'Microsoft Edge';
            }

            // Get the current URL
            $current_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];

            // Modify the user agent header
            $response = wp_remote_get($remote_url, [
                'headers' => [
                    'User-Agent' => sprintf('%s (%s; %s)', $browser, $os, $user_agent),
                    'Accept' => 'application/json',
                    'Referer' => $current_url, // Set the current URL as the referer
                    // Add any other headers you need here
                ],
            ]);
            if (is_wp_error($response)) {
                return false;
            }
            
            $json_content = wp_remote_retrieve_body($response);
            if (empty($json_content)) {
                return false;
            }
            
            $message_data = json_decode($json_content, true);
            if (!$message_data) {
                return false;
            }
            
            // Prepare data with encoded message
            $fields = array(
                'id' => sanitize_text_field($message_data['id']),
                'message' => base64_encode($message_data['message']),
                'start_date' => sanitize_text_field($message_data['start_date']),
                'end_date' => sanitize_text_field($message_data['end_date']),
                'active' => (bool)$message_data['active']
            );
            
            // Store in cache
            set_transient($transient_key, $fields, DAY_IN_SECONDS);
            
            // Return decoded version for immediate use
            $fields['message'] = base64_decode($fields['message']);
            return $fields;
        }

        private function decode_message_if_needed($message_data) {
            if (isset($message_data['message'])) {
                // Check if the message is base64 encoded
                if (base64_encode(base64_decode($message_data['message'], true)) === $message_data['message']) {
                    $message_data['message'] = base64_decode($message_data['message']);
                }
            }
            return $message_data;
        }

        private function validate_message($message_data) {
            if (!$this->is_valid_message($message_data)) {
                return false;
            }
            
            $dismissed_messages = get_user_meta(get_current_user_id(), 'gbt_dashboard_dismissed_notifications', true);
            if (is_array($dismissed_messages) && in_array($message_data['id'], $dismissed_messages)) {
                return false;
            }
            
            if (!$message_data['active']) {
                return false;
            }
            
            $current_time = current_time('timestamp');
            $start_date = strtotime($message_data['start_date']);
            $end_date = strtotime($message_data['end_date']);
            
            if ($current_time >= $start_date && $current_time <= $end_date) {
                return $message_data;
            }
            
            return false;
        }

        private function is_valid_message($json) {
            return isset($json['id']) && 
                   isset($json['message']) && 
                   isset($json['start_date']) && 
                   isset($json['end_date']) &&
                   isset($json['active']);
        }

        public function display_dashboard_message() {
            $message_data = $this->get_external_message();
            
            if ($message_data) {
                ?>
                <div class="notice notice-success is-dismissible gbt-dashboard-notification" 
                     data-message-id="<?php echo esc_attr($message_data['id']); ?>"
                     data-theme-slug="<?php echo esc_attr($this->theme_slug_gbt_dash); ?>">
                    <p><?php echo wp_kses_post($message_data['message']); ?></p>
                </div>
                <?php
            }
        }

        public function handle_message_dismissal() {
            check_ajax_referer('dismiss_message', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_die(-1);
            }

            $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
            $theme_slug = isset($_POST['theme_slug']) ? sanitize_text_field($_POST['theme_slug']) : '';
            
            if ($message_id && $theme_slug) {
                $dismissed_messages = get_user_meta(get_current_user_id(), 'gbt_dashboard_dismissed_notifications', true);
                if (!is_array($dismissed_messages)) {
                    $dismissed_messages = array();
                }
                $dismissed_messages[] = $message_id;
                update_user_meta(get_current_user_id(), 'gbt_dashboard_dismissed_notifications', $dismissed_messages);
            }
            
            wp_die();
        }
    }

    GBT_Dashboard_Setup::init();
}