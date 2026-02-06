<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * GetBowtied License Menu Badge
 * 
 * Adds a notification badge to the Shopkeeper menu item
 * when there are license issues (no license, expired, or expiring soon).
 */
class GBT_License_Menu_Badge
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Use admin_menu filter instead (runs later than the hook)
        add_filter('parent_file', array($this, 'add_menu_badge'));
    }

    /**
     * Check if there are license issues
     * 
     * @return bool True if there are license issues, false otherwise
     */
    public function has_license_issues()
    {
        // Get dashboard setup
        global $gbt_dashboard_setup;
        
        if (isset($gbt_dashboard_setup) && is_object($gbt_dashboard_setup)) {
            // Check if theme is block-shop
            $theme_slug = $gbt_dashboard_setup->get_theme_slug();
            if ($theme_slug === 'block-shop') {
                return false; // No license issues for block-shop theme
            }
        }
        
        // First check if initialization period is complete
        if (class_exists('Theme_LI') && Theme_LI::is_init_period_completed() !== true) {
            return false;
        }
        
        // Get license manager instance if available
        if (!class_exists('GBT_License_Manager')) {
            return false;
        }
        
        $license_manager = GBT_License_Manager::get_instance();
        
        // Check for license issues
        if (!$license_manager->is_license_active()) {
            return true;
        }
        
        if (!$license_manager->is_support_active()) {
            return true;
        }
        
        // Check if expiring soon
        if ($license_manager->is_support_expiring_soon()) {
            return true;
        }
        
        if ($this->has_locked_special_benefits($license_manager)) {
            return true;
        }

        return false;
    }

    /**
     * Add badge to menu using WordPress global variables
     *
     * @param string $parent_file The parent file
     * @return string The parent file (unchanged)
     */
    public function add_menu_badge($parent_file)
    {
        global $menu, $submenu;
        
        // Only proceed if there are license issues
        if (!$this->has_license_issues()) {
            return $parent_file;
        }
        
        // Get the badge HTML for consistent appearance
        $badge = $this->get_badge_html();
        
        // Add badge to main GetBowtied menu item
        if (!empty($menu)) {
            foreach ($menu as $key => $item) {
                if (isset($item[2]) && $item[2] === 'getbowtied-dashboard') {
                    // Add badge to menu title (index 0)
                    $menu[$key][0] = $menu[$key][0] . ' ' . $badge;
                    break;
                }
            }
        }
        
        // Add badge to License submenu items
        if (isset($submenu['getbowtied-dashboard'])) {
            foreach ($submenu['getbowtied-dashboard'] as $key => $item) {
                if (isset($item[2]) && $item[2] === 'getbowtied-license') {
                    // Add badge to submenu title (index 0)
                    $submenu['getbowtied-dashboard'][$key][0] = $submenu['getbowtied-dashboard'][$key][0] . ' ' . $badge;
                }
            }
        }
        
        // Return the parent file (unchanged)
        return $parent_file;
    }
    
    /**
     * Get the HTML for the badge
     *
     * @return string The badge HTML
     */
    private function get_badge_html()
    {
        return '<span class="update-plugins count-1"><span class="plugin-count">!</span></span>';
    }

    /**
     * Check if special benefits exist but are currently locked
     *
     * @param GBT_License_Manager $license_manager The license manager instance
     * @return bool True when benefits are locked
     */
    private function has_locked_special_benefits(GBT_License_Manager $license_manager): bool
    {
        if (!class_exists('GBT_Buyer_Review_Checker') || !class_exists('GBT_Special_License_Manager')) {
            return false;
        }

        $license_key = $license_manager->get_license_key();

        if (empty($license_key)) {
            return false;
        }

        $special_license_manager = GBT_Special_License_Manager::get_instance();

        if (!$special_license_manager->has_special_license($license_key)) {
            return false;
        }

        $review_checker = GBT_Buyer_Review_Checker::get_instance();

        return $review_checker->should_disable_special_benefits($license_key);
    }
}

// Initialize the badge
add_action('admin_init', function() {
    new GBT_License_Menu_Badge();
}); 