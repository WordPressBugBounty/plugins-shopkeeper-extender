<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Buyer Review Checker
 * 
 * Retrieves and caches buyer reviews from the API for the current license
 */
class GBT_Buyer_Review_Checker
{
    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * WordPress option key for storing review data
     *
     * @var string
     */
    private $option_key = 'gbt_buyer_review_data';

    /**
     * Development buyer username override
     * Set this to test with a specific buyer without changing the license
     * Example: public static $dev_buyer_username = 'test_buyer';
     *
     * @var string|null
     */
    public static $dev_buyer_username = NULL; // Set to a buyer username for testing, or leave null for production

    /**
     * Enable/disable low-rating penalty when deciding special benefits.
     * Set to false to allow special benefits even if low-star reviews exist.
     *
     * @var bool
     */
    public static $enable_low_rating_check = true;

    /**
     * Enable/disable no-review penalty when deciding special benefits.
     * Set to false to allow special benefits even if buyer has not left a review yet.
     *
     * @var bool
     */
    public static $enable_no_review_check = false;

    /**
     * Enable/disable outdated rating check (>= 1 year) when deciding special benefits.
     * Set to true to also require a recent review in addition to rating quality.
     *
     * @var bool
     */
    public static $enable_outdated_rating_check = false;

    /**
     * Initialize the class
     */
    public static function init(): void
    {
        // Class is loaded, instance will be created on first get_instance() call
    }

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
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
     * Get buyer review data for a license key (from WordPress option cache)
     *
     * @param string $license_key The license key to check
     * @return array|false Review data or false on failure
     */
    public function get_buyer_review_data(string $license_key)
    {
        if (empty($license_key)) {
            return false;
        }

        return $this->get_cached_review_data();
    }

    /**
     * Refresh buyer review data from API and update WordPress option
     *
     * @param string $license_key The license key to refresh
     * @return array|false Review data or false on failure
     */
    public function refresh_buyer_review_data(string $license_key)
    {
        if (empty($license_key)) {
            return false;
        }

        $buyer_username = $this->get_local_buyer_username();
        if (empty($buyer_username)) {
            $this->clear_review_data();
            return false;
        }

        $theme_slug = get_template();
        if (empty($theme_slug)) {
            $this->log_error('Could not determine current theme slug');
            return false;
        }

        return $this->fetch_review_data_from_api($buyer_username, $theme_slug);
    }

    /**
     * Get buyer_username from local WordPress license info
     *
     * @return string Buyer username or empty string
     */
    private function get_local_buyer_username(): string
    {
        // Check for development override (class property)
        if (!empty(self::$dev_buyer_username)) {
            $this->log_debug('Using development buyer username override: ' . self::$dev_buyer_username);
            return self::$dev_buyer_username;
        }

        if (!class_exists('GBT_License_Manager')) {
            return '';
        }

        $license_manager = GBT_License_Manager::get_instance();
        $license_info = $license_manager->get_license_info();

        return $license_info['buyer_username'] ?? '';
    }

    /**
     * Get cached review data from WordPress options
     *
     * @return array|false Cached review data or false if not found
     */
    private function get_cached_review_data()
    {
        return get_option($this->option_key, false);
    }

    /**
     * Store review data in WordPress options
     *
     * @param array $data Review data to store
     * @return bool True on success, false on failure
     */
    private function store_review_data(array $data): bool
    {
        return update_option($this->option_key, $data);
    }

    /**
     * Clear cached review data
     *
     * @return bool True on success, false on failure
     */
    public function clear_review_data(): bool
    {
        return delete_option($this->option_key);
    }

    /**
     * Fetch review data from API
     *
     * @param string $buyer_username The buyer username to check
     * @param string $theme_slug The theme slug to check reviews for
     * @return array|false Review data or false on failure
     */
    private function fetch_review_data_from_api(string $buyer_username, string $theme_slug)
    {
		// Get the appropriate API URL
		$config = GBT_License_Config::get_instance();
		$urls = $config->get_buyer_review_urls();

        // Prepare request data - send buyer_username and theme_slug
		$request_args = [
			'body' => [
				'buyer_username' => $buyer_username,
				'theme_slug' => $theme_slug
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
        $response = $this->try_review_urls_with_fallback($urls, $request_args);

        // Check for errors
        if (is_wp_error($response)) {
            $this->log_error('All buyer review server URLs failed: ' . $response->get_error_message());
            return false;
        }

        // Parse the JSON response
        $data = $this->parse_review_response($response);

        if ($data === false) {
            return false;
        }

        if (!isset($data['data']) || !is_array($data['data'])) {
            $data['data'] = [];
        }

        $review_ids = $data['data']['review_ids'] ?? [];

        $detailed_reviews = [];
        if (!empty($review_ids)) {
            $detailed_reviews = $this->get_detailed_reviews_from_ids($review_ids, $buyer_username, $theme_slug);

            // Require successful detailed data for every review ID
            if (empty($detailed_reviews) || count($detailed_reviews) !== count($review_ids)) {
                $this->log_error('Failed to retrieve detailed rating information for all review IDs. Clearing cached review data.');
                $this->clear_review_data();
                return false;
            }
        }

        $data['data']['review_ids'] = $review_ids;
        $data['data']['reviews'] = $detailed_reviews;
        $data['data']['review_count'] = count($detailed_reviews);
        $data['data']['has_reviews'] = !empty($detailed_reviews);

        // Store in cache
        $this->store_review_data($data);

        return $data;
    }

    /**
     * Try multiple review server URLs with fallback until one returns valid JSON
     * 
     * @param array $urls Array of URLs to try
     * @param array $request_args WordPress HTTP request arguments
     * @return mixed WordPress HTTP response or WP_Error
     */
    private function try_review_urls_with_fallback(array $urls, array $request_args)
    {
        $last_error = null;
        
        foreach ($urls as $url) {
            $this->log_debug('Trying buyer review URL: ' . $url);
            $response = wp_remote_post($url, $request_args);
            
            if (is_wp_error($response)) {
                $last_error = $response;
                $this->log_debug('Buyer review URL failed: ' . $url . ' - ' . $response->get_error_message());
                continue;
            }
            
            // Check if response contains valid JSON
            $response_body = wp_remote_retrieve_body($response);
            $data = json_decode($response_body, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $this->log_debug('Buyer review URL succeeded: ' . $url);
                return $response;
            }
            
            $this->log_debug('Buyer review URL returned invalid JSON: ' . $url);
        }

        return $last_error ?? new WP_Error('all_urls_failed', 'All review server URLs failed');
    }

    /**
     * Parse review API response
     *
     * @param array $response WordPress HTTP response
     * @return array|false The parsed response or false on error
     */
    private function parse_review_response($response)
    {
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error('Invalid JSON response: ' . json_last_error_msg());
            return false;
        }

        return $data;
    }

    /**
     * Build detailed review data by fetching each review ID individually
     *
     * @param array $review_ids Array of review IDs returned by the summary API
     * @param string $buyer_username Buyer username
     * @param string $theme_slug Theme slug
     * @return array Array of detailed review data
     */
    private function get_detailed_reviews_from_ids(array $review_ids, string $buyer_username, string $theme_slug): array
    {
        if (empty($review_ids)) {
            return [];
        }

        $reviews = [];

        foreach ($review_ids as $review_id) {
            $review_id = trim((string)$review_id);
            if ($review_id === '') {
                continue;
            }

            $details = $this->fetch_specific_rating_details($review_id, $buyer_username);

            if (!is_array($details) || isset($details['error'])) {
                $this->log_debug('Failed to fetch detailed rating for review ID: ' . $review_id);
                continue;
            }

            $reviews[] = [
                'theme_slug' => $theme_slug,
                'rating' => (int)($details['rating'] ?? 0),
                'review_id' => (string)($details['review_id'] ?? $review_id),
                'review_text' => $details['comment'] ?? '',
                'review_date' => $details['date'] ?? '',
                'buyer_username' => $details['author'] ?? $buyer_username,
                'review_url' => $details['review_url'] ?? '',
                'rating_category' => $details['rating_category'] ?? '',
                'author_response' => $details['response'] ?? '',
            ];
        }

        return $reviews;
    }

    /**
     * Fetch detailed rating information for a specific review ID
     *
     * @param string $review_id Review identifier
     * @param string $buyer_username Buyer username
     * @return array|false Detailed review data or false on failure
     */
    private function fetch_specific_rating_details(string $review_id, string $buyer_username)
    {
        if ($review_id === '' || $buyer_username === '') {
            return false;
        }

		$config = GBT_License_Config::get_instance();
		$urls = $config->get_specific_rating_urls();

		$request_args = [
			'timeout' => 30,
			'sslverify' => true,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
                'Referer' => home_url(),
            ],
        ];

        $query_args = [
            'rating_id' => $review_id,
            'username' => $buyer_username,
        ];

        foreach ($urls as $base_url) {
            $url = add_query_arg($query_args, $base_url);
            $this->log_debug('Trying specific rating URL: ' . $url);

            $response = wp_remote_get($url, $request_args);

            if (is_wp_error($response)) {
                $this->log_debug('Specific rating URL failed: ' . $url . ' - ' . $response->get_error_message());
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log_debug('Specific rating URL returned invalid JSON: ' . $url);
                continue;
            }

            if (isset($data['error'])) {
                $this->log_debug('Specific rating URL returned error: ' . $url . ' - ' . $data['error']);
                continue;
            }

            return $data;
        }

        return false;
    }

    /**
     * Get validated review data
     *
     * @param string $license_key The license key to check
     * @return array|null Validated data array or null if invalid
     */
    private function get_validated_data(string $license_key): ?array
    {
        $data = $this->get_buyer_review_data($license_key);
        
        if (!$data || !isset($data['status']) || $data['status'] !== 'success') {
            return null;
        }

        return $data;
    }

    /**
     * Check if buyer has any reviews
     *
     * @param string $license_key The license key to check
     * @return bool Whether the buyer has reviews
     */
    public function has_reviews(string $license_key): bool
    {
        $data = $this->get_validated_data($license_key);
        return $data && !empty($data['data']['reviews'] ?? []);
    }

    /**
     * Get all reviews for the buyer
     *
     * @param string $license_key The license key to check
     * @return array Array of all reviews or empty array
     */
    public function get_reviews(string $license_key): array
    {
        $data = $this->get_validated_data($license_key);
        return $data['data']['reviews'] ?? [];
    }

    /**
     * Get total count of reviews
     *
     * @param string $license_key The license key to check
     * @return int Count of reviews
     */
    public function get_review_count(string $license_key): int
    {
        $data = $this->get_validated_data($license_key);
        return (int) ($data['data']['review_count'] ?? 0);
    }

    /**
     * Check if buyer has low star reviews (which should disable special benefits)
     *
     * @param string $license_key The license key to check
     * @return bool True if buyer has low star reviews
     */
    public function has_low_star_reviews(string $license_key): bool
    {
        $reviews = $this->get_reviews($license_key);
        
        foreach ($reviews as $review) {
            $rating = (int) ($review['rating'] ?? 0);
            if ($rating >= 1 && $rating <= 3) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determine if special benefits should be disabled.
     * Disables when buyer has low-star reviews (1-3), no reviews at all,
     * or the latest review is 1 year old or older (outdated rating).
     *
     * @param string $license_key The license key to check
     * @return bool True if special benefits should be disabled
     */
    public function should_disable_special_benefits(string $license_key): bool
    {
        // If we couldn't verify reviews successfully, don't penalise the customer
        if (!$this->is_check_successful($license_key)) {
            return false;
        }

        if (self::$enable_low_rating_check && $this->has_low_star_reviews($license_key)) {
            return true;
        }
        
        if (self::$enable_no_review_check && !$this->has_reviews($license_key)) {
            return true;
        }
        
        // Disable if the buyer's rating is outdated (>= 1 year old)
        if (self::$enable_outdated_rating_check && $this->has_outdated_rating($license_key)) {
            return true;
        }
        
        return false;
    }

    /**
     * Heuristically determine if the buyer's rating is outdated (>= 1 year old).
     * Assumes review_date strings like "2 days ago", "11 months ago", "1 year ago", "2 years ago".
     * If all available review_date strings indicate years, consider it outdated.
     * If any review_date suggests recent activity (days/weeks/months), it's not outdated.
     *
     * @param string $license_key The license key to check
     * @return bool True if rating is considered outdated
     */
    public function has_outdated_rating(string $license_key): bool
    {
        $reviews = $this->get_reviews($license_key);
        if (empty($reviews)) {
            return false;
        }
        
        $hasAnyRecent = false;
        $hasAnyYear = false;
        
        foreach ($reviews as $review) {
            $dateText = strtolower(trim((string)($review['review_date'] ?? '')));
            if ($dateText === '') {
                // If we can't tell, skip this entry
                continue;
            }
            if (strpos($dateText, 'day') !== false || strpos($dateText, 'week') !== false || strpos($dateText, 'month') !== false) {
                $hasAnyRecent = true;
            }
            if (strpos($dateText, 'year') !== false) {
                $hasAnyYear = true;
            }
        }
        
        // Outdated if we saw a year marker and no explicit recent markers
        return $hasAnyYear && !$hasAnyRecent;
    }

    /**
     * Get buyer username from review data
     *
     * @param string $license_key The license key to check
     * @return string Buyer username or empty string
     */
    public function get_buyer_username(string $license_key): string
    {
        $data = $this->get_validated_data($license_key);
        return $data['data']['buyer_username'] ?? '';
    }

    /**
     * Get the full API response message
     *
     * @param string $license_key The license key to check
     * @return string The API response message
     */
    public function get_api_message(string $license_key): string
    {
        $data = $this->get_buyer_review_data($license_key);
        return $data['message'] ?? '';
    }

    /**
     * Check if the API check was successful
     *
     * @param string $license_key The license key to check
     * @return bool Whether the API check succeeded
     */
    public function is_check_successful(string $license_key): bool
    {
        return $this->get_validated_data($license_key) !== null;
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
            error_log('[GBT Buyer Review] ' . $message);
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
            error_log('[GBT Buyer Review Error] ' . $message);
        }
    }
}

// Initialize the buyer review checker
GBT_Buyer_Review_Checker::init();

