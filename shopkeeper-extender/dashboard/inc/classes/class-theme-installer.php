<?php

/**
 * Theme Installer Class
 * 
 * Handles theme installation and activation functionality with support for different installation modes:
 * - Parent only: Installs and activates only the parent theme
 * - Parent + Child: Installs both parent and child themes, activates child theme
 * 
 * @package Shopkeeper Extender
 * @author GetBowtied
 * 
 * @example
 * // Install parent theme only
 * $installer = new GBT_Theme_Installer('shopkeeper', GBT_Theme_Installer::MODE_PARENT_ONLY);
 * $result = $installer->install_theme($theme_url, $theme_name);
 * 
 * @example
 * // Install parent and child themes
 * $installer = new GBT_Theme_Installer('shopkeeper', GBT_Theme_Installer::MODE_PARENT_AND_CHILD);
 * $result = $installer->install_theme($theme_url, $theme_name);
 * 
 * @example
 * // Auto-fallback to current theme if plugin theme slug is empty
 * $installer = new GBT_Theme_Installer('', GBT_Theme_Installer::MODE_PARENT_AND_CHILD);
 * $result = $installer->install_theme($theme_url, $theme_name);
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GBT_Theme_Installer {
    
    private $plugin_theme_slug_param_gbt_dash;
    private $install_mode;
    
    // Installation modes
    const MODE_PARENT_ONLY = 'parent_only';
    const MODE_PARENT_AND_CHILD = 'parent_and_child';
    
    public function __construct($plugin_theme_slug, $install_mode = self::MODE_PARENT_AND_CHILD) {
        // Fallback to current theme if plugin theme slug is not provided
        $this->plugin_theme_slug_param_gbt_dash = !empty($plugin_theme_slug) ? $plugin_theme_slug : get_template();
        $this->install_mode = $install_mode;
    }
    
    /**
     * Set installation mode
     */
    public function set_install_mode($mode) {
        if (in_array($mode, [self::MODE_PARENT_ONLY, self::MODE_PARENT_AND_CHILD])) {
            $this->install_mode = $mode;
        }
    }
    
    /**
     * Get current installation mode
     */
    public function get_install_mode() {
        return $this->install_mode;
    }
    
    /**
     * Check if current mode is parent only
     */
    public function is_parent_only_mode() {
        return $this->install_mode === self::MODE_PARENT_ONLY;
    }
    
    /**
     * Check if current mode is parent and child
     */
    public function is_parent_and_child_mode() {
        return $this->install_mode === self::MODE_PARENT_AND_CHILD;
    }
    
    /**
     * Get the effective theme slug (plugin theme or fallback to current theme)
     */
    public function get_effective_theme_slug() {
        return !empty($this->plugin_theme_slug_param_gbt_dash) ? 
            $this->plugin_theme_slug_param_gbt_dash : 
            get_template();
    }
    
    /**
     * Get the child theme slug based on the parent theme
     */
    private function get_child_theme_slug() {
        $parent_theme_slug = $this->get_effective_theme_slug();
        return $parent_theme_slug . '-child';
    }
    
    /**
     * Install theme programmatically (non-AJAX)
     */
    public function install_theme($theme_url, $theme_name) {
        // Validate URL to prevent SSRF attacks
        if (!GBT_URL_Validator::is_trusted_theme_url($theme_url)) {
            return array('success' => false, 'error' => 'Invalid theme URL. Only trusted sources are allowed.');
        }
        
        // Download the theme
        $response = wp_safe_remote_get($theme_url, array(
            'timeout' => 300,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => 'Failed to download theme: ' . $response->get_error_message());
        }
        
        $theme_zip = wp_remote_retrieve_body($response);
        
        if (empty($theme_zip)) {
            return array('success' => false, 'error' => 'Downloaded theme file is empty');
        }
        
        // Create temporary file
        $temp_file = wp_tempnam('theme_');
        $write_result = file_put_contents($temp_file, $theme_zip);
        
        if ($write_result === false) {
            return array('success' => false, 'error' => 'Failed to save theme file');
        }
        
        // Install the theme using native PHP ZIP functions
        $themes_dir = WP_CONTENT_DIR . '/themes/';
        
        // Check if themes directory is writable
        if (!is_writable($themes_dir)) {
            unlink($temp_file);
            return array('success' => false, 'error' => 'Themes directory is not writable. Please check file permissions.');
        }
        
        // Extract theme using native PHP ZIP
        $zip = new ZipArchive();
        $result = $zip->open($temp_file);
        
        if ($result !== TRUE) {
            unlink($temp_file);
            return array('success' => false, 'error' => 'Failed to open theme ZIP file. Error code: ' . $result);
        }
        
        // Extract to themes directory
        $extract_result = $zip->extractTo($themes_dir);
        $zip->close();
        
        // Clean up temp file
        unlink($temp_file);
        
        if (!$extract_result) {
            return array('success' => false, 'error' => 'Failed to extract theme files. Please check file permissions.');
        }
        
        // Handle installation based on mode
        if ($this->install_mode === self::MODE_PARENT_ONLY) {
            // Parent only mode - just activate the parent theme
            $supported_themes = $this->get_supported_themes();
            $theme_slug = '';
            foreach ($supported_themes as $slug => $theme_data) {
                if ($theme_data['theme_name'] === $theme_name) {
                    $theme_slug = $slug;
                    break;
                }
            }
            
            if ($theme_slug) {
                switch_theme($theme_slug);
                return array('success' => true, 'message' => 'Theme installed and activated successfully.');
            } else {
                return array('success' => false, 'error' => 'Theme installed but could not be activated.');
            }
        } else {
            // Parent + Child mode - install child theme and activate it
            $child_theme_url = $this->get_theme_child_download_link();
            $child_theme_result = $this->install_child_theme($child_theme_url, $theme_name);
            
            if ($child_theme_result['success']) {
                $child_theme_slug = $this->get_child_theme_slug();
                
                if (!$this->check_theme_exists($child_theme_slug)) {
                    // Fallback to parent theme
                    $supported_themes = $this->get_supported_themes();
                    $theme_slug = '';
                    foreach ($supported_themes as $slug => $theme_data) {
                        if ($theme_data['theme_name'] === $theme_name) {
                            $theme_slug = $slug;
                            break;
                        }
                    }
                    if ($theme_slug) {
                        switch_theme($theme_slug);
                    }
                    return array('success' => true, 'message' => 'Theme installed successfully. Child theme not found, parent theme activated.');
                } else {
                    $switch_result = switch_theme($child_theme_slug);
                    
                    if (is_wp_error($switch_result)) {
                        // Fallback to parent theme
                        $supported_themes = $this->get_supported_themes();
                        $theme_slug = '';
                        foreach ($supported_themes as $slug => $theme_data) {
                            if ($theme_data['theme_name'] === $theme_name) {
                                $theme_slug = $slug;
                                break;
                            }
                        }
                        if ($theme_slug) {
                            switch_theme($theme_slug);
                        }
                        return array('success' => true, 'message' => 'Theme and child theme installed successfully. Parent theme activated (child theme activation failed).');
                    } else {
                        return array('success' => true, 'message' => 'Theme and child theme installed successfully. Child theme activated.');
                    }
                }
            } else {
                // If child theme installation fails, activate the main theme as fallback
                $supported_themes = $this->get_supported_themes();
                $theme_slug = '';
                foreach ($supported_themes as $slug => $theme_data) {
                    if ($theme_data['theme_name'] === $theme_name) {
                        $theme_slug = $slug;
                        break;
                    }
                }
                
                if ($theme_slug) {
                    switch_theme($theme_slug);
                }
                
                return array('success' => true, 'message' => 'Theme installed and activated successfully. Child theme installation failed: ' . $child_theme_result['error']);
            }
        }
    }
    
    /**
     * Handle theme installation AJAX request
     */
    public function handle_theme_install_ajax() {
        // Check nonce for security
        if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'install_theme_ajax')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('install_themes')) {
            wp_die('Insufficient permissions');
        }
        
        $theme_url = sanitize_url($_POST['theme_url']);
        $theme_name = sanitize_text_field($_POST['theme_name']);
        
        // Validate URL to prevent SSRF attacks
        if (!GBT_URL_Validator::is_trusted_theme_url($theme_url)) {
            wp_send_json_error('Invalid theme URL. Only trusted sources are allowed.');
        }
        
        // Download the theme
        $response = wp_safe_remote_get($theme_url, array(
            'timeout' => 300,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to download theme: ' . $response->get_error_message());
        }
        
        $theme_zip = wp_remote_retrieve_body($response);
        
        if (empty($theme_zip)) {
            wp_send_json_error('Downloaded theme file is empty');
        }
        
        // Create temporary file
        $temp_file = wp_tempnam('theme_');
        $write_result = file_put_contents($temp_file, $theme_zip);
        
        if ($write_result === false) {
            wp_send_json_error('Failed to save theme file');
        }
        
        // Install the theme using native PHP ZIP functions
        $themes_dir = WP_CONTENT_DIR . '/themes/';
        
        // Check if themes directory is writable
        if (!is_writable($themes_dir)) {
            unlink($temp_file);
            wp_send_json_error('Themes directory is not writable. Please check file permissions.');
        }
        
        // Extract theme using native PHP ZIP
        $zip = new ZipArchive();
        $result = $zip->open($temp_file);
        
        if ($result !== TRUE) {
            unlink($temp_file);
            wp_send_json_error('Failed to open theme ZIP file. Error code: ' . $result);
        }
        
        // Extract to themes directory
        $extract_result = $zip->extractTo($themes_dir);
        $zip->close();
        
        // Clean up temp file
        unlink($temp_file);
        
        if (!$extract_result) {
            wp_send_json_error('Failed to extract theme files. Please check file permissions.');
        }
        
        // Step 1: Parent theme is now installed
        if ($this->install_mode === self::MODE_PARENT_ONLY) {
            // Parent only mode - just activate the parent theme
            $supported_themes = $this->get_supported_themes();
            $theme_slug = '';
            foreach ($supported_themes as $slug => $theme_data) {
                if ($theme_data['theme_name'] === $theme_name) {
                    $theme_slug = $slug;
                    break;
                }
            }
            
            if ($theme_slug) {
                switch_theme($theme_slug);
                wp_send_json_success('Theme installed and activated successfully.');
            } else {
                wp_send_json_error('Theme installed but could not be activated.');
            }
        } else {
            // Parent + Child mode - install child theme and activate it
            $child_theme_url = $this->get_theme_child_download_link();
            $child_theme_result = $this->install_child_theme($child_theme_url, $theme_name);
            
            if ($child_theme_result['success']) {
                // Step 3: Both themes installed, now activate child theme
                $child_theme_slug = $this->get_child_theme_slug();
                
                // Check if child theme exists before trying to activate
                if (!$this->check_theme_exists($child_theme_slug)) {
                    // Fallback to parent theme
                    $supported_themes = $this->get_supported_themes();
                    $theme_slug = '';
                    foreach ($supported_themes as $slug => $theme_data) {
                        if ($theme_data['theme_name'] === $theme_name) {
                            $theme_slug = $slug;
                            break;
                        }
                    }
                    if ($theme_slug) {
                        switch_theme($theme_slug);
                    }
                    wp_send_json_success('Theme installed successfully. Child theme not found, parent theme activated.');
                } else {
                    $switch_result = switch_theme($child_theme_slug);
                    
                    if (is_wp_error($switch_result)) {
                        // Fallback to parent theme
                        $supported_themes = $this->get_supported_themes();
                        $theme_slug = '';
                        foreach ($supported_themes as $slug => $theme_data) {
                            if ($theme_data['theme_name'] === $theme_name) {
                                $theme_slug = $slug;
                                break;
                            }
                        }
                        if ($theme_slug) {
                            switch_theme($theme_slug);
                        }
                        wp_send_json_success('Theme and child theme installed successfully. Parent theme activated (child theme activation failed).');
                    } else {
                        wp_send_json_success('Theme and child theme installed successfully. Child theme activated.');
                    }
                }
            } else {
                // If child theme installation fails, activate the main theme as fallback
                $supported_themes = $this->get_supported_themes();
                $theme_slug = '';
                foreach ($supported_themes as $slug => $theme_data) {
                    if ($theme_data['theme_name'] === $theme_name) {
                        $theme_slug = $slug;
                        break;
                    }
                }
                
                if ($theme_slug) {
                    switch_theme($theme_slug);
                }
                
                wp_send_json_success('Theme installed and activated successfully. Child theme installation failed: ' . $child_theme_result['error']);
            }
        }
    }
    
    /**
     * Handle theme activation AJAX request
     */
    public function handle_theme_activate_ajax() {
        // Check nonce for security
        if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'activate_theme_ajax')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('switch_themes')) {
            wp_die('Insufficient permissions');
        }
        
        $theme_slug = sanitize_text_field($_POST['theme_slug']);
        
        // Check if the main theme exists before attempting activation
        if (!$this->check_theme_exists($theme_slug)) {
            $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : admin_url('admin.php?page=getbowtied-dashboard');
            wp_send_json_error('Something went wrong, please <a href="' . $referrer . '">refresh this page</a>.');
        }
        
        // Try to activate child theme first, fallback to main theme
        $child_theme_slug = $theme_slug . '-child';
        
        if ($this->check_theme_exists($child_theme_slug)) {
            $switch_result = switch_theme($child_theme_slug);
            if (is_wp_error($switch_result)) {
                // Fallback to main theme
                $main_switch_result = switch_theme($theme_slug);
                if (is_wp_error($main_switch_result)) {
                    wp_send_json_error('Failed to activate theme: ' . $main_switch_result->get_error_message());
                }
                wp_send_json_success('Theme activated successfully');
            } else {
                wp_send_json_success('Child theme activated successfully');
            }
        } else {
            $switch_result = switch_theme($theme_slug);
            if (is_wp_error($switch_result)) {
                wp_send_json_error('Failed to activate theme: ' . $switch_result->get_error_message());
            }
            wp_send_json_success('Theme activated successfully');
        }
    }
    
    /**
     * Install child theme
     */
    private function install_child_theme($child_theme_url, $theme_name) {
        // Download the child theme
        $response = wp_safe_remote_get($child_theme_url, array(
            'timeout' => 300,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => 'Failed to download child theme: ' . $response->get_error_message());
        }
        
        $child_theme_zip = wp_remote_retrieve_body($response);
        
        if (empty($child_theme_zip)) {
            return array('success' => false, 'error' => 'Downloaded child theme file is empty');
        }
        
        // Create temporary file
        $temp_file = wp_tempnam('child_theme_');
        $write_result = file_put_contents($temp_file, $child_theme_zip);
        
        if ($write_result === false) {
            return array('success' => false, 'error' => 'Failed to save child theme file');
        }
        
        // Install the child theme using native PHP ZIP functions
        $themes_dir = WP_CONTENT_DIR . '/themes/';
        
        // Check if themes directory is writable
        if (!is_writable($themes_dir)) {
            unlink($temp_file);
            return array('success' => false, 'error' => 'Themes directory is not writable for child theme');
        }
        
        // Extract child theme using native PHP ZIP
        $zip = new ZipArchive();
        $result = $zip->open($temp_file);
        
        if ($result !== TRUE) {
            unlink($temp_file);
            return array('success' => false, 'error' => 'Failed to open child theme ZIP file. Error code: ' . $result);
        }
        
        // Extract to themes directory
        $extract_result = $zip->extractTo($themes_dir);
        $zip->close();
        
        // Clean up temp file
        unlink($temp_file);
        
        if (!$extract_result) {
            return array('success' => false, 'error' => 'Failed to extract child theme files. Please check file permissions.');
        }
        
        // Don't activate child theme here - let the parent method handle activation
        // Just verify the child theme was installed correctly
        $child_theme_slug = $this->get_child_theme_slug();
        $child_theme_path = $themes_dir . $child_theme_slug;
        
        if (!is_dir($child_theme_path)) {
            return array('success' => false, 'error' => 'Child theme directory not found after installation');
        }
        
        // Also check if WordPress can see the theme
        $themes = wp_get_themes();
        $theme_found = false;
        foreach ($themes as $theme) {
            if ($theme->get_stylesheet() === $child_theme_slug) {
                $theme_found = true;
                break;
            }
        }
        
        if (!$theme_found) {
            return array('success' => false, 'error' => 'Child theme not recognized by WordPress');
        }
        
        return array('success' => true);
    }
    
    /**
     * Get child theme download link
     */
    private function get_theme_child_download_link() {
        $theme_slug = $this->get_effective_theme_slug();
            
        return sprintf(
            "https://getbowtied.github.io/repository/themes/%s/%s-child.zip",
            $theme_slug,
            $theme_slug
        );
    }
    
    /**
     * Check if theme exists
     */
    private function check_theme_exists($theme_slug) {
        $themes = wp_get_themes();
        foreach ($themes as $theme) {
            if ($theme->get_stylesheet() === $theme_slug) {
                return true;
            }
        }
        return false;
    }
    

    /**
     * Get supported themes configuration
     */
    private function get_supported_themes() {
        // Use the main dashboard's supported themes configuration
        if (class_exists('GBT_Dashboard_Setup')) {
            $dashboard = GBT_Dashboard_Setup::get_instance();
            if (method_exists($dashboard, 'get_supported_themes')) {
                return $dashboard->get_supported_themes();
            }
        }
        
        // Fallback to basic structure if dashboard is not available
        return array(
            'shopkeeper' => array(
                'theme_name' => 'Shopkeeper',
                'theme_sales_page_url' => 'https://themeforest.net/item/shopkeeper-ecommerce-wp-theme-for-woocommerce/9553045'
            )
        );
    }
}
