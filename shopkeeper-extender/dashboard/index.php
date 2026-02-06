<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Only execute in admin area or WP-CLI
if (function_exists('is_admin') && !is_admin() && !(defined('WP_CLI') && WP_CLI)) {
    return;
}

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
            
            // Include the Theme Installer class
            require_once dirname(__FILE__) . '/inc/classes/class-theme-installer.php';
            
            // Include the URL Validator utility
            require_once dirname(__FILE__) . '/inc/classes/class-url-validator.php';
            
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
                    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_theme_installer_scripts' ) );
                    add_action( 'wp_ajax_install_theme_ajax', array( $this, 'handle_theme_install_ajax' ) );
                    add_action( 'wp_ajax_activate_theme_ajax', array( $this, 'handle_theme_activate_ajax' ) );
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

        /**
         * Get dashboard page slugs
         * Centralized method to avoid duplication (DRY principle)
         *
         * @return array Array of dashboard page slugs
         */
        public function get_dashboard_page_slugs() {
            $pages = array(
                'getbowtied-dashboard',
                'getbowtied-help',
                'getbowtied-templates'
            );

            // Add diagnostics and license pages only if theme is not block-shop
            if ($this->theme_slug_gbt_dash !== 'block-shop') {
                $pages[] = 'getbowtied-diagnostics';
                $pages[] = 'getbowtied-license';
            }

            return $pages;
        }

        private function initialize_theme_properties() {
            $this->theme_slug_gbt_dash = get_template();
            $theme = wp_get_theme(get_template());
            $this->theme_name_gbt_dash = $theme->get('Name');
            $this->theme_version_gbt_dash = $theme->get('Version');
            
            // For child theme download, use the parent theme slug
            // This is the theme this plugin is designed for
            $parent_theme_slug = !empty($this->plugin_theme_slug_param_gbt_dash) ? 
                $this->plugin_theme_slug_param_gbt_dash : 
                $this->theme_slug_gbt_dash;
            $this->theme_child_download_link_gbt_dash = sprintf(
                "https://getbowtied.github.io/repository/themes/%s/%s-child.zip",
                $parent_theme_slug,
                $parent_theme_slug
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

            add_action('upgrader_process_complete', [$this, 'gbt_theme_update_redirect'], 10, 2);
            add_action('admin_init', [$this, 'gbt_redirect_after_theme_update']);
            add_action('admin_notices', [$this, 'display_dashboard_message']);
            
            add_action('wp_ajax_dismiss_gbt_dashboard_notification', [$this, 'handle_message_dismissal']);
            add_action('wp_ajax_gbt_enable_auto_updates', [$this, 'ajax_enable_auto_updates']);
            add_action('wp_ajax_install_theme_ajax', [$this, 'handle_theme_install_ajax']);
            add_action('wp_ajax_activate_theme_ajax', [$this, 'handle_theme_activate_ajax']);

            include_once($this->base_paths['path'] . '/dashboard/setup.php');
        }

        private function is_theme_activation_page() {
            return 'themes.php' == $this->pagenow && isset($_GET['activated']) && $this->is_supported_theme();
        }

        private function redirect_to_dashboard() {
            wp_safe_redirect(admin_url("admin.php?page=getbowtied-dashboard"));
            exit;
        }

        public function gbt_theme_update_redirect($upgrader_object, $options) {
            if (($options['type'] ?? '') !== 'theme') {
                return;
            }

            $current_theme = wp_get_theme();
            $relevant_slugs = array_filter([
                $current_theme->get_stylesheet(),
                $current_theme->parent() ? $current_theme->parent()->get_stylesheet() : null,
            ]);

            $updated_slugs = [];

            if (!empty($options['themes'])) {
                $updated_slugs = (array) $options['themes'];
            } else {
                $updated_slugs = array_filter([
                    $options['theme'] ?? null,
                    $options['destination_name'] ?? null,
                    is_array($upgrader_object->result ?? null) ? ($upgrader_object->result['destination_name'] ?? null) : null,
                ]);
            }

            if (empty($updated_slugs)) {
                return;
            }

            if (!empty(array_intersect($relevant_slugs, $updated_slugs))) {
                update_option('gbt_theme_updated_redirect', true);
            }
        }

        public function gbt_redirect_after_theme_update() {
            if (get_option('gbt_theme_updated_redirect')) {
                delete_option('gbt_theme_updated_redirect');
                
                // Clear notification transients after theme update
                if (class_exists('GBT_Notification_Handler')) {
                    gbt_notification_handler()->clear_all_notification_transients();
                }
			
			// Refresh license information after theme update
			if (class_exists('GBT_License_Manager')) {
				$license_manager = GBT_License_Manager::get_instance();
				$license_manager->cron_process_license();
			}
                
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
                    esc_url( admin_url('profile.php') )
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
        

        public function get_theme_marketplace_id() {
            return $this->theme_themeforest_id;
        }

        /**
         * Get the theme sales page URL from config
         * 
         * @return string The URL to the theme's sales page
         */
        public function get_theme_sales_page_url() {
            $themes = $this->get_supported_themes();
            if (array_key_exists($this->theme_slug_gbt_dash, $themes)) {
                return $themes[$this->theme_slug_gbt_dash]['theme_sales_page_url'];
            }
            return '';
        }

        /**
         * Get a specific config value for the current theme
         * 
         * @param string $key The configuration key to retrieve
         * @return string|null The config value or null if not found
         */
        public function get_theme_config($key) {
            $themes = $this->get_supported_themes();
            if (array_key_exists($this->theme_slug_gbt_dash, $themes) && isset($themes[$this->theme_slug_gbt_dash][$key])) {
                return $themes[$this->theme_slug_gbt_dash][$key];
            }
            return null;
        }

        /**
         * Get a config value from a global section (not theme-specific)
         * 
         * @param string $section The config section name
         * @param string $key The configuration key to retrieve
         * @return mixed|null The config value or null if not found
         */
        public function get_global_config($section, $key) {
            $config = include($this->base_paths['path'] . '/dashboard/config.php');
            if (isset($config[$section]) && isset($config[$section][$key])) {
                return $config[$section][$key];
            }
            return null;
        }

        /**
         * Check if license is active
         * 
         * @return boolean True if license is active, false otherwise
         */
        public function is_license_active() {
            // Skip license check for Block Shop theme
            if ($this->theme_slug_gbt_dash === "block-shop") {
                return true;
            }
            
            // Check if License_Manager class exists
            if (!class_exists('GBT_License_Manager')) {
                require_once $this->base_paths['path'] . '/dashboard/inc/classes/class-license-manager.php';
            }
            
            // Get license manager instance and check status
            $license_manager = GBT_License_Manager::get_instance();
            return $license_manager->is_license_active();
        }
        
        /**
         * Check if support is active
         * 
         * @return boolean True if support is active, false otherwise
         */
        public function is_support_active() {
            // Skip license check for Block Shop theme
            if ($this->theme_slug_gbt_dash === "block-shop") {
                return true;
            }
            
            // Check if License_Manager class exists
            if (!class_exists('GBT_License_Manager')) {
                require_once $this->base_paths['path'] . '/dashboard/inc/classes/class-license-manager.php';
            }
            
            // Get license manager instance and check status
            $license_manager = GBT_License_Manager::get_instance();
            return $license_manager->is_support_active();
        }

        public function unsupported_theme_warning() {
            $supported_themes = $this->get_supported_themes();
            $theme_slug = $this->plugin_theme_slug_param_gbt_dash;
            
            $theme_name = $supported_themes[$theme_slug]['theme_name'];
            $theme_sales_page_url = $supported_themes[$theme_slug]['theme_sales_page_url'];
            $theme_update_url = $supported_themes[$theme_slug]['theme_update_url'];
            
            // Check if theme or child theme exists but is not activated
            $theme_exists = $this->check_theme_exists($theme_slug);
            $child_theme_exists = $this->check_theme_exists($theme_slug . '-child');
            
            if ($theme_exists || $child_theme_exists) {
                // Theme or child theme exists but not activated - show activate message
                $message = sprintf(
                    '<div class="message error"><p><strong>%s Extender</strong> is enabled but not effective. It requires <strong>%s Theme</strong> in order to work. Please <a href="#" class="gbt-activate-theme-ajax" data-theme-slug="%s" data-theme-name="%s"><strong>activate the %s Theme</strong></a>.</p></div>',
                    $theme_name,
                    $theme_name,
                    esc_attr($theme_slug),
                    esc_attr($theme_name),
                    $theme_name
                );
            } else {
                // Theme doesn't exist - check if we can install it
                $download_url = $this->get_theme_download_url($theme_update_url);
                
                if ($download_url) {
                    // Can install theme - show install and activate message
                    $message = sprintf(
                        '<div class="message error"><p><strong>%s Extender</strong> is enabled but not effective. It requires <strong>%s Theme</strong> in order to work. Please <a href="#" class="gbt-install-theme-ajax" data-theme-url="%s" data-theme-name="%s"><strong>install and activate the %s Theme</strong></a>.</p></div>',
                        $theme_name,
                        $theme_name,
                        esc_url($download_url),
                        esc_attr($theme_name),
                        $theme_name
                    );
                } else {
                    // Fallback message for sales page
                    $message = sprintf(
                        '<div class="message error"><p><strong>%s Extender</strong> is enabled but not effective. It requires <strong>%s Theme</strong> in order to work. <a href="%s" target="_blank"><strong>Get %s Theme</strong></a>.</p></div>',
                        $theme_name,
                        $theme_name,
                        $theme_sales_page_url,
                        $theme_name
                    );
                }
            }
            
            echo wp_kses_post($message);
        }

        private function get_supported_themes() {
            $config = include($this->base_paths['path'] . '/dashboard/config.php');
            return $config['supported_themes'];
        }

        private function is_supported_theme() {
            $current_theme = wp_get_theme();
            $current_theme_slug = $current_theme->get_stylesheet();
            $supported_theme_slugs = array_keys($this->get_supported_themes());
            
            // Check if current theme is supported
            if (in_array($current_theme_slug, $supported_theme_slugs)) {
                return true;
            }
            
            // Check if it's a child theme of a supported theme
            if ($current_theme->parent() && in_array($current_theme->parent()->get_stylesheet(), $supported_theme_slugs)) {
                return true;
            }
            
            return false;
        }

        private function check_theme_exists($theme_slug) {
            // Get all installed themes
            $themes = wp_get_themes();
            
            // Check if theme exists in installed themes
            foreach ($themes as $theme) {
                if ($theme->get_stylesheet() === $theme_slug) {
                    return true;
                }
            }
            
            return false;
        }

        private function get_theme_download_url($theme_update_url) {
            // Validate URL to prevent SSRF attacks
            if (!GBT_URL_Validator::is_trusted_theme_url($theme_update_url)) {
                return false;
            }
            
            // Fetch the JSON data from the theme update URL
            $response = wp_safe_remote_get($theme_update_url, array(
                'timeout' => 10,
                'sslverify' => true
            ));
            
            if (is_wp_error($response)) {
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            // Return the download_url if it exists
            if (isset($data['download_url'])) {
                return $data['download_url'];
            }
            
            return false;
        }

        public function handle_theme_install_ajax() {
            // Check if installation mode is specified in the request
            $install_mode = isset($_POST['install_mode']) ? sanitize_text_field($_POST['install_mode']) : GBT_Theme_Installer::MODE_PARENT_AND_CHILD;
            
            // Use plugin theme slug, fallback to current theme if not available
            $theme_slug = !empty($this->plugin_theme_slug_param_gbt_dash) ? 
                $this->plugin_theme_slug_param_gbt_dash : 
                $this->theme_slug_gbt_dash;
            
            $theme_installer = new GBT_Theme_Installer($theme_slug, $install_mode);
            $theme_installer->handle_theme_install_ajax();
        }

        public function handle_theme_activate_ajax() {
            // Use plugin theme slug, fallback to current theme if not available
            $theme_slug = !empty($this->plugin_theme_slug_param_gbt_dash) ? 
                $this->plugin_theme_slug_param_gbt_dash : 
                $this->theme_slug_gbt_dash;
                
            $theme_installer = new GBT_Theme_Installer($theme_slug);
            $theme_installer->handle_theme_activate_ajax();
        }
        
        /**
         * Create a theme installer with specific mode
         */
        public function create_theme_installer($mode = GBT_Theme_Installer::MODE_PARENT_AND_CHILD) {
            // Use plugin theme slug, fallback to current theme if not available
            $theme_slug = !empty($this->plugin_theme_slug_param_gbt_dash) ? 
                $this->plugin_theme_slug_param_gbt_dash : 
                $this->theme_slug_gbt_dash;
                
            return new GBT_Theme_Installer($theme_slug, $mode);
        }
        
        /**
         * Install theme in parent-only mode
         */
        public function install_parent_theme_only($theme_url, $theme_name) {
            $installer = $this->create_theme_installer(GBT_Theme_Installer::MODE_PARENT_ONLY);
            return $installer->install_theme($theme_url, $theme_name);
        }
        
        /**
         * Install theme with child theme
         */
        public function install_theme_with_child($theme_url, $theme_name) {
            $installer = $this->create_theme_installer(GBT_Theme_Installer::MODE_PARENT_AND_CHILD);
            return $installer->install_theme($theme_url, $theme_name);
        }


        public function enqueue_theme_installer_scripts() {
            // Get base paths
            $base_paths = $this->get_base_paths();
            $theme_version_gbt_dash = $this->get_theme_version();
            
            // Enqueue theme installation script
            wp_enqueue_script(
                'getbowtied-theme-installer',
                $base_paths['url'] . '/dashboard/js/theme-installer.js',
                array('jquery'),
                $theme_version_gbt_dash,
                true
            );

            // Localize script for theme installation AJAX
            wp_localize_script(
                'getbowtied-theme-installer',
                'gbtThemeInstallerData',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('install_theme_ajax'),
                    'activate_nonce' => wp_create_nonce('activate_theme_ajax')
                )
            );
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
            $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
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
            $current_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' );

            $response = wp_safe_remote_get($remote_url, [
                'headers' => [
                    'User-Agent' => sprintf('%s (%s; %s)', $browser, $os, $user_agent),
                    'Accept' => 'application/json',
                    'Referer' => $current_url,
                ],
                'sslverify' => true,
                'timeout' => 30
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
            if (!$this->is_valid_message($message_data) || 
                !$message_data['active'] || 
                gbt_notification_handler()->is_dismissed($message_data['id'])) {
                return false;
            }
            
            $current_time = current_time('timestamp');
            $start_date = strtotime($message_data['start_date']);
            $end_date = strtotime($message_data['end_date']);
            
            return ($current_time >= $start_date && $current_time <= $end_date) ? $message_data : false;
        }

        private function is_valid_message($json) {
            return isset($json['id']) && 
                   isset($json['message']) && 
                   isset($json['start_date']) && 
                   isset($json['end_date']) &&
                   isset($json['active']);
        }

        public function display_dashboard_message() {
            if ($message_data = $this->get_external_message()) {
                printf(
                    '<div class="notice notice-success is-dismissible gbt-dashboard-notification" data-message-id="%s" data-theme-slug="%s"><p>%s</p></div>',
                    esc_attr($message_data['id']),
                    esc_attr($this->theme_slug_gbt_dash),
                    wp_kses_post($message_data['message'])
                );
            }
        }

        /**
         * Handle message dismissal via AJAX (Legacy method)
         */
        public function handle_message_dismissal() {
            check_ajax_referer('dismiss_message', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_die(-1);
            }

            $message_id = isset($_POST['message_id']) ? sanitize_text_field($_POST['message_id']) : '';
            
            if ($message_id) {
                gbt_notification_handler()->save_dismissal($message_id);
            }
            
            wp_die();
        }

        /**
         * Get theme auto-update status information
         * 
         * @return array Array containing auto-update status information
         */
        public function get_theme_auto_update_status() {
            // Check if auto-updates are enabled for themes
            $auto_updates_enabled = get_option('auto_update_themes', array());
            $current_theme = get_template();
            $is_auto_update_enabled = in_array($current_theme, $auto_updates_enabled);
            
            // Check if auto-updates are globally enabled for themes
            $auto_update_themes_enabled = wp_is_auto_update_enabled_for_type('theme');
            
            return array(
                'is_enabled' => $is_auto_update_enabled,
                'is_globally_enabled' => $auto_update_themes_enabled,
                'theme_slug' => $current_theme,
                'theme_name' => $this->get_theme_name(),
                'themes_page_url' => admin_url('themes.php')
            );
        }

        /**
         * Get auto-update enable button
         * 
         * @return string HTML button to enable auto-updates or empty string if already enabled
         */
        public function get_auto_update_enable_button() {
            $status = $this->get_theme_auto_update_status();
            
            if ($status['is_enabled']) {
                return '';
            } else {
                $theme_name = $this->get_theme_name();
                $enabling_text = __('Enabling...', 'getbowtied');
                return sprintf(
                    '<button type="button" class="gbt-enable-auto-updates gbt-auto-update-button flex items-center justify-center w-auto rounded-md bg-[var(--color-wp-blue)] text-white shadow-sm hover:bg-[var(--color-wp-blue-darker)] focus:outline-none focus:ring-2 focus:ring-[var(--color-wp-blue)] focus:ring-offset-2 px-4 py-2.5 text-base font-medium transition duration-150 ease-in-out cursor-pointer" data-theme="%s">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 mr-1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span class="gbt-button-text" data-enabling-text="%s">Enable Auto Updates for %s Theme</span>
                    </button>',
                    esc_attr($status['theme_slug']),
                    esc_attr($enabling_text),
                    esc_html($theme_name)
                );
            }
        }

        /**
         * Get auto-update content data
         * 
         * @param string $state The state: 'disabled' or 'success'
         * @return array Array containing title, description, and icon
         */
        public function get_auto_update_content($state = 'disabled') {
            $content = [
                'disabled' => [
                    'title' => 'Auto Updates Available.',
                    'description' => 'Keep your theme secure and up-to-date with auto updates. If enabled, all theme updates are handled automatically in the background for your peace of mind.',
                    'icon' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99'
                ],
                'success' => [
                    'title' => 'Auto Updates Active.',
                    'description' => 'Great news! Auto updates are keeping your theme secure and up-to-date. Everything is handled automatically.',
                    'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                ]
            ];

            return isset($content[$state]) ? $content[$state] : $content['disabled'];
        }

        /**
         * AJAX handler to enable auto-updates for the current theme
         */
        public function ajax_enable_auto_updates() {
            // Verify nonce for security
            if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'gbt_enable_auto_updates')) {
                wp_send_json_error('Invalid nonce');
            }

            // Check user capabilities
            if (!current_user_can('update_themes')) {
                wp_send_json_error('Insufficient permissions');
            }

            $theme_slug = sanitize_text_field($_POST['theme_slug']);
            $current_theme = get_template();

            // Verify the theme slug matches the current theme
            if ($theme_slug !== $current_theme) {
                wp_send_json_error('Invalid theme');
            }

            // Get current auto-update settings
            $auto_updates = get_option('auto_update_themes', array());

            // Add theme to auto-updates if not already there
            if (!in_array($theme_slug, $auto_updates)) {
                $auto_updates[] = $theme_slug;
                $result = update_option('auto_update_themes', $auto_updates);

                if ($result) {
                    wp_send_json_success(array(
                        'message' => sprintf(
                            'Auto-updates have been enabled for %s. Your theme will automatically receive security and feature updates.',
                            $this->get_theme_name()
                        ),
                        'theme_name' => $this->get_theme_name()
                    ));
                } else {
                    wp_send_json_error('Failed to enable auto-updates');
                }
            } else {
                wp_send_json_success(array(
                    'message' => sprintf(
                        'Auto-updates are already enabled for %s.',
                        $this->get_theme_name()
                    ),
                    'theme_name' => $this->get_theme_name()
                ));
            }
        }
    }

    GBT_Dashboard_Setup::init();
}