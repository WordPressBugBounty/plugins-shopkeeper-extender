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
    }

    GBT_Dashboard_Setup::init();
}