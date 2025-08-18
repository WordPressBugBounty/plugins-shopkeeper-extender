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
    
    private $theme_slug;
    private $update_url;
    private $allow_updates;
    private $update_checker;
    private $config;
    private $base_paths;
    
    public function __construct($allow_updates = false) {
        $this->load_base_paths();
        $this->load_config();
        $this->detect_current_theme();
        $this->allow_updates = $allow_updates;
        
        // Only initialize if we have a supported theme and it's not block-shop
        if ($this->theme_slug && $this->update_url && $this->theme_slug !== 'block-shop') {
            $this->init();
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
            require_once $this->base_paths['path'] . '/dashboard/inc/plugin-update-checker/plugin-update-checker.php';
        }
        
        $this->update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            $this->update_url,
            $this->base_paths['path'],
            $this->theme_slug
        );
        
        // Block update by removing download URL
        $this->update_checker->addResultFilter(array($this, 'filter_update_result'));
    }
    
    /**
     * Setup conditional logic hooks
     */
    public function setup_conditional_logic() {
        add_filter('site_transient_update_themes', array($this, 'filter_theme_transient'), 999);
        add_filter('upgrader_pre_download', array($this, 'block_theme_download'), 10, 3);
        add_action('admin_notices', array($this, 'show_update_notice'));
    }
    
    /**
     * Filter update result from update checker
     */
    public function filter_update_result($update, $httpResult = null) {
        if (!$update || $this->allow_updates) {
            return $update;
        }
        
        $blocked_update = clone $update;
        $blocked_update->download_url = '';
        return $blocked_update;
    }
    
    /**
     * Filter WordPress theme update transient
     */
    public function filter_theme_transient($transient) {
        if (!$this->allow_updates && isset($transient->response[$this->theme_slug]) && $this->is_using_target_theme()) {
            // Instead of empty string, use a blocked URL that we can intercept
            $transient->response[$this->theme_slug]['package'] = 'blocked://' . $this->theme_slug;
        }
        return $transient;
    }
    
    /**
     * Block theme download during upgrade
     */
    public function block_theme_download($reply, $package, $upgrader) {
        // Only process if it's a theme upgrader and updates are blocked
        if (!is_a($upgrader, 'Theme_Upgrader') || $this->allow_updates) {
            return $reply;
        }
        
        // Check if this is our blocked package URL
        if (is_string($package) && strpos($package, 'blocked://') === 0) {
            $theme_name = $this->get_theme_name();
            return new WP_Error(
                $this->theme_slug . '_update_restricted',
                sprintf(
                    'An error occurred while updating %s: Theme updates are currently restricted. Please contact support for assistance.',
                    $theme_name
                ),
                array('theme' => $this->theme_slug)
            );
        }
        
        return $reply;
    }
    
    /**
     * Show admin notice when update is blocked
     */
    public function show_update_notice() {
        if (!$this->allow_updates && $this->is_using_target_theme()) {
            $updates = get_site_transient('update_themes');
            if ($updates && isset($updates->response[$this->theme_slug])) {
                $theme_name = $this->get_theme_name();
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>' . $theme_name . ' Update Available:</strong> Installation currently restricted.</p>';
                echo '</div>';
            }
        }
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
    

    
    /**
     * Set whether updates are allowed
     */
    public function set_allow_updates($allow) {
        $this->allow_updates = $allow;
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
}

// Auto-initialize with default settings (updates blocked by default)
new GBT_Theme_Updates(); 