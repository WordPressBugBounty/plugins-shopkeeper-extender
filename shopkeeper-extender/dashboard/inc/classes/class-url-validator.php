<?php

/**
 * URL Validator Utility Class
 * 
 * Provides secure URL validation to prevent SSRF attacks
 * 
 * @package Shopkeeper Extender
 * @author GetBowtied
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GBT_URL_Validator {
    
    /**
     * List of trusted domains for theme downloads
     */
    private static $trusted_domains = array(
        'getbowtied.github.io',
        'github.com',
        'githubusercontent.com',
        'getbowtied.com',
        'getbowtied.net'
    );
    
    /**
     * Check if URL is from a trusted source to prevent SSRF attacks
     * 
     * @param string $url The URL to validate
     * @return bool True if URL is from trusted domain, false otherwise
     */
    public static function is_trusted_theme_url($url) {
        // Parse the URL
        $parsed_url = wp_parse_url($url);
        
        if (!$parsed_url || !isset($parsed_url['host'])) {
            return false;
        }
        
        $host = strtolower($parsed_url['host']);
        
        // Check if host is in trusted domains
        foreach (self::$trusted_domains as $trusted_domain) {
            if ($host === $trusted_domain || substr($host, -strlen('.' . $trusted_domain)) === '.' . $trusted_domain) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get list of trusted domains
     * 
     * @return array List of trusted domains
     */
    public static function get_trusted_domains() {
        return self::$trusted_domains;
    }
    
    /**
     * Add a trusted domain to the list
     * 
     * @param string $domain The domain to add
     */
    public static function add_trusted_domain($domain) {
        if (!in_array($domain, self::$trusted_domains)) {
            self::$trusted_domains[] = $domain;
        }
    }
}
