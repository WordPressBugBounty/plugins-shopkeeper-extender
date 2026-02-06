<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Special License Manager
 * 
 * Handles special license data retrieval and management functionality
 */
class GBT_Special_License_Manager
{
    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * WordPress option key for storing special license data
     *
     * @var string
     */
    private $option_key = 'gbt_special_license_data';

    /**
     * Initialize the class and register main hook
     */
    public static function init(): void
    {
        add_action('admin_init', function () {
            self::get_instance();
        });
    }

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        // Initialize option key from config
        $config = GBT_License_Config::get_instance();
        $option_keys = $config->get_license_option_keys();
        $this->option_key = $option_keys['special_license_data'] ?? 'gbt_special_license_data';
    }

    /**
     * Get the singleton instance
     *
     * @return self The singleton instance
     */
    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get special license data for a license key (from WordPress option only)
     *
     * @param string $license_key The license key to check
     * @return array|false Special license data or false on failure
     */
    public function get_special_license_data(string $license_key)
    {
        if (empty($license_key)) {
            return false;
        }

        // Always get data from WordPress option (no caching, no API calls)
        return $this->get_cached_special_license_data();
    }

    /**
     * Refresh special license data from API and update WordPress option
     *
     * @param string $license_key The license key to refresh
     * @return array|false Special license data or false on failure
     */
    public function refresh_special_license_data(string $license_key)
    {
        if (empty($license_key)) {
            return false;
        }

        // Fetch fresh data from API and store in WordPress option
        return $this->fetch_special_license_data_from_api($license_key);
    }

    /**
     * Get cached special license data from WordPress options
     *
     * @return array|false Cached special license data or false if not found
     */
    public function get_cached_special_license_data()
    {
        $cached_data = get_option($this->option_key, false);
        return $cached_data;
    }

    /**
     * Store special license data in WordPress options
     *
     * @param array $data Special license data to store
     * @return bool True on success, false on failure
     */
    public function store_special_license_data(array $data): bool
    {
        return update_option($this->option_key, $data);
    }

    /**
     * Clear cached special license data
     *
     * @return bool True on success, false on failure
     */
    public function clear_special_license_data(): bool
    {
        return delete_option($this->option_key);
    }

    /**
     * Fetch special license data from API
     *
     * @param string $license_key The license key to check
     * @return array|false Special license data or false on failure
     */
    private function fetch_special_license_data_from_api(string $license_key)
    {
		// Get the appropriate API URL
		$config = GBT_License_Config::get_instance();
		$urls = $config->get_special_license_urls();

        // Prepare request data
		$request_args = [
			'body' => [
				'license_key' => $license_key
			],
			'timeout' => 30,
			'sslverify' => true,
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => home_url(),
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            ]
        ];

        // Try URLs in order until one returns valid JSON
        $response = $this->try_special_license_urls_with_fallback($urls, $request_args);

        // Check for errors
        if (is_wp_error($response)) {
            $this->log_error('All special license server URLs failed: ' . $response->get_error_message());
            return false;
        }

        // Parse the JSON response
        $data = $this->parse_special_license_response($response);

        // Store in cache if valid data was returned
        if ($data !== false) {
            $this->store_special_license_data($data);
        }

        return $data;
    }

    /**
     * Try multiple special license server URLs with fallback until one returns valid JSON
     * 
     * @param array $urls Array of URLs to try
     * @param array $request_args WordPress HTTP request arguments
     * @return mixed WordPress HTTP response or WP_Error
     */
    private function try_special_license_urls_with_fallback(array $urls, array $request_args)
    {
        foreach ($urls as $url) {
            $this->log_debug('Trying special license URL: ' . $url);
            $response = wp_remote_post($url, $request_args);
            
            // If request succeeded and returned valid JSON, use this response
            if (!is_wp_error($response)) {
                $response_body = wp_remote_retrieve_body($response);
                $data = json_decode($response_body, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $this->log_debug('Special license URL succeeded: ' . $url);
                    return $response;
                }
                $this->log_debug('Special license URL returned invalid JSON: ' . $url);
            } else {
                $this->log_debug('Special license URL failed: ' . $url . ' - ' . $response->get_error_message());
            }
        }

        // If all URLs failed, return the last response
        return $response;
    }

    /**
     * Parse special license API response
     *
     * @param array $response WordPress HTTP response
     * @return array|false The parsed response or false on error
     */
    private function parse_special_license_response($response)
    {
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('Invalid JSON response from special license server: ' . json_last_error_msg());
            $this->log_error('Raw response: ' . $response_body);
            return false;
        }

        return $data;
    }

    /**
     * Check if a license key has special benefits
     *
     * @param string $license_key The license key to check
     * @return bool Whether the license has special benefits
     */
    public function has_special_license(string $license_key): bool
    {
        $data = $this->get_special_license_data($license_key);
        
        if (!$data || !isset($data['data'])) {
            return false;
        }

        return isset($data['data']['overall_status']['has_special_license']) && 
               $data['data']['overall_status']['has_special_license'] === true;
    }

    /**
     * Check if built-in updates are active for a special license
     *
     * @param string $license_key The license key to check
     * @return bool Whether built-in updates are active
     */
    public function has_active_built_in_updates(string $license_key): bool
    {
        $data = $this->get_special_license_data($license_key);
        
        if (!$data || !isset($data['data'])) {
            return false;
        }

        return isset($data['data']['overall_status']['built_in_updates_active']) && 
               $data['data']['overall_status']['built_in_updates_active'] === true;
    }

    /**
     * Check if support is active for a special license
     *
     * @param string $license_key The license key to check
     * @return bool Whether support is active
     */
    public function has_active_support(string $license_key): bool
    {
        $data = $this->get_special_license_data($license_key);
        
        if (!$data || !isset($data['data'])) {
            return false;
        }

        return isset($data['data']['overall_status']['support_active']) && 
               $data['data']['overall_status']['support_active'] === true;
    }

    /**
     * Get days remaining for built-in updates
     *
     * @param string $license_key The license key to check
     * @return int|false Days remaining or false if not available
     */
    public function get_built_in_updates_days_remaining(string $license_key)
    {
        $data = $this->get_special_license_data($license_key);
        
        if (!$data || !isset($data['data']['built_in_updates']['days_remaining'])) {
            return false;
        }

        return (int) $data['data']['built_in_updates']['days_remaining'];
    }

    /**
     * Get days remaining for support
     *
     * @param string $license_key The license key to check
     * @return int|false Days remaining or false if not available
     */
    public function get_support_days_remaining(string $license_key)
    {
        $data = $this->get_special_license_data($license_key);
        
        if (!$data || !isset($data['data']['support']['days_remaining'])) {
            return false;
        }

        return (int) $data['data']['support']['days_remaining'];
    }

    /**
     * Get formatted special license status message
     *
     * @param string $license_key The license key to check
     * @return string Formatted status message
     */
    public function get_special_license_status_message(string $license_key): string
    {
        $data = $this->get_special_license_data($license_key);
        
        if (!$data || !isset($data['data'])) {
            return 'No special license data available.';
        }

        $license_data = $data['data'];
        $status = $license_data['overall_status'];

        if (!$status['has_special_license']) {
            return 'No special license benefits found.';
        }

        $messages = [];

        // Built-in updates status
        if ($status['built_in_updates_active']) {
            $days = $license_data['built_in_updates']['days_remaining'];
            $messages[] = sprintf(
                'Built-in updates active (%d days remaining)',
                $days
            );
        } elseif (isset($license_data['built_in_updates']['expired']) && $license_data['built_in_updates']['expired']) {
            $messages[] = 'Built-in updates expired';
        }

        // Support status
        if ($status['support_active']) {
            $days = $license_data['support']['days_remaining'];
            $messages[] = sprintf(
                'Support active (%d days remaining)',
                $days
            );
        } elseif (isset($license_data['support']['expired']) && $license_data['support']['expired']) {
            $messages[] = 'Support expired';
        }

        return empty($messages) ? 'Special license found but no active benefits.' : implode(', ', $messages);
    }

    /**
     * Log debug information
     * 
     * @param string $message The debug message to log
     */
    private function log_debug(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore QITStandard.PHP.DebugCode.DebugFunctionFound -- Legitimate debug logging when WP_DEBUG is enabled.
            error_log('[GBT Special License] ' . $message);
        }
    }

    /**
     * Log an error message
     * 
     * @param string $message The error message to log
     */
    private function log_error(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore QITStandard.PHP.DebugCode.DebugFunctionFound -- Legitimate debug logging when WP_DEBUG is enabled.
            error_log('[GBT Special License Error] ' . $message);
        }
    }
}

// Initialize the special license manager
GBT_Special_License_Manager::init();
