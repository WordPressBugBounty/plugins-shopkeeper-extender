<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Theme initialization options handling
 */
class Theme_LI {

    // Singleton instance
    private static $instance = null;

    // Option suffix
    private static $option_suffix = '7dhe8jde45';
    
    // Time period in days before changing option to true
    private static $days_period = 1;

    // Current theme slug
    private static $theme_slug = '';
    
    // Base paths
    private $base_paths;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        global $gbt_dashboard_setup;
        
        // Make sure dashboard setup is initialized
        if (!isset($gbt_dashboard_setup) || !$gbt_dashboard_setup) {
            if (class_exists('GBT_Dashboard_Setup')) {
                $gbt_dashboard_setup = GBT_Dashboard_Setup::init();
            }
        }
        
        if (isset($gbt_dashboard_setup) && is_object($gbt_dashboard_setup)) {
            $this->base_paths = $gbt_dashboard_setup->get_base_paths();
            self::set_theme_slug_from_config($this->base_paths);
            $this->setup_hooks();
        }
    }

    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the option name
     * 
     * @return string The complete option name
     */
    public static function get_option_name() {
        self::ensure_theme_slug_is_set();
        return 'getbowtied_init_' . self::$theme_slug . '_' . self::$option_suffix;
    }

    /**
     * Ensure the theme slug is set from config if it hasn't been already
     */
    private static function ensure_theme_slug_is_set() {
        if (empty(self::$theme_slug)) {
            global $gbt_dashboard_setup;
            
            if (isset($gbt_dashboard_setup) && is_object($gbt_dashboard_setup)) {
                $base_paths = $gbt_dashboard_setup->get_base_paths();
                self::set_theme_slug_from_config($base_paths);
            }
            
            // If still empty, use fallback
            if (empty(self::$theme_slug)) {
                self::$theme_slug = get_template();
            }
        }
    }

    /**
     * Set the theme slug from config
     */
    private static function set_theme_slug_from_config($base_paths) {
        if (!empty($base_paths) && !empty($base_paths['path'])) {
            $config_path = $base_paths['path'] . '/config.php';
            if (file_exists($config_path)) {
                $config = include $config_path;
                if (isset($config['supported_themes'])) {
                    // Use the first key from supported_themes as the current theme
                    self::$theme_slug = key($config['supported_themes']);
                }
            }
        }
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action('admin_init', array($this, 'setup_init_option'));
        add_action('admin_init', array($this, 'check_option_status'));
    }

    /**
     * Set up the init option with timestamp
     */
    public function setup_init_option() {
        $option_name = self::get_option_name();
        
        // Only create the option if it doesn't already exist
        if (get_option($option_name, null) === null) {
            $option_data = array(
                'value' => false,
                'timestamp' => time()
            );
            update_option($option_name, $option_data, false);
        }
    }

    /**
     * Check if enough time has passed to update the option value
     */
    public function check_option_status() {
        $option_name = self::get_option_name();
        $option_data = get_option($option_name);
        
        if (is_array($option_data) && isset($option_data['value']) && isset($option_data['timestamp'])) {
            // Check if option is still false and if enough time has passed
            if ($option_data['value'] === false) {
                $time_passed = time() - $option_data['timestamp'];
                $required_time = self::$days_period * DAY_IN_SECONDS;
                
                if ($time_passed >= $required_time) {
                    // Update the option to true
                    $option_data['value'] = true;
                    update_option($option_name, $option_data, false);
                }
            }
        }
    }

    /**
     * Check if the initialization period has completed
     * 
     * This method checks if the theme initialization period has elapsed.
     * Returns TRUE if the configured time period has passed since initialization (mature state).
     * Returns FALSE during the initial period (immature state).
     * Returns NULL if the option hasn't been initialized at all.
     * 
     * @return bool|null TRUE if initialization period is complete (mature), FALSE if still in initial period (immature), NULL if not initialized
     */
    public static function is_init_period_completed() {
        $option_name = self::get_option_name();
        $option_data = get_option($option_name);
        
        if (is_array($option_data) && isset($option_data['value'])) {
            return $option_data['value'];
        }
        
        return null;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}

// Initialize the class
Theme_LI::get_instance(); 