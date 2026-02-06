<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('getbowtied_license1_content')) {
    function getbowtied_license1_content()
    {
        // Get the dashboard setup instance
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();
        $base_paths = $gbt_dashboard_setup->get_base_paths();
        $theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
        $theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
        $theme_slug_gbt_dash = $gbt_dashboard_setup->get_theme_slug();
        $theme_marketplace_id = $gbt_dashboard_setup->get_theme_marketplace_id();

        // Load the License Manager class
        if (!class_exists('GBT_License_Manager')) {
            require_once $base_paths['path'] . '/dashboard/inc/classes/class-license-manager.php';
        }

        // Get license manager instance
        $license_manager = GBT_License_Manager::get_instance();

        // Get license data
        $license_data = $license_manager->get_license_data();

        // Register ajax script
        wp_enqueue_script(
            'gbt-license-refresh',
            $base_paths['url'] . '/dashboard/js/license-refresh.js',
            array('jquery'),
            $theme_version_gbt_dash,
            true
        );

        // Localize script with necessary data
        wp_localize_script(
            'gbt-license-refresh',
            'gbtLicenseRefresh',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gbt_license_refresh_nonce'),
                'theme_slug' => $theme_slug_gbt_dash,
                'theme_id' => $theme_marketplace_id,
                'refreshing_text' => __('Refreshing license data...', 'getbowtied'),
                'success_text' => __('License refreshed successfully!', 'getbowtied'),
                'error_text' => __('Error refreshing license. Please try again.', 'getbowtied')
            )
        );

        // Content Start
        include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';

        // Main content
?>

        <div class="overflow-hidden py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-1">
                    <div>
                        <div class="lg:max-w-lg">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-x-1.5 rounded-md bg-[var(--color-wp-gray-dark)] px-3 py-1.5 text-xs font-medium text-white">
                                    <svg class="size-2 fill-[var(--color-wp-green)]" viewBox="0 0 6 6" aria-hidden="true">
                                        <circle cx="3" cy="3" r="3" />
                                    </svg>
                                    VERSION <?php echo esc_html($theme_version_gbt_dash); ?>
                                </span>
                            </div>
                            <h2 class="mt-4 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl leading-14"><?php echo esc_html($theme_name_gbt_dash); ?> License Refresh</h2>
                            <p class="mt-6 text-lg leading-8 text-gray-600">
                                This page demonstrates AJAX license refresh functionality without page reload.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden p-8">
                        <div class="space-y-8">
                            <div id="license-status-container">
                                <h3 class="text-xl font-semibold text-gray-900 mb-4">Current License Status</h3>

                                <div class="py-3 px-4 bg-gray-50 rounded-lg border border-gray-200 space-y-4 mb-8">
                                    <p class="font-medium text-gray-700 flex items-center gap-2">
                                        Status:
                                        <span id="license-status" class="inline-flex items-center gap-1.5 <?php echo $license_data['license_status'] === 'active' ? 'text-[var(--color-wp-green)]' : 'text-[var(--color-wp-red)]'; ?> font-semibold">
                                            <?php if ($license_data['license_status'] === 'active'): ?>
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                </svg>
                                            <?php endif; ?>
                                            <span id="license-status-text"><?php echo esc_html(ucfirst($license_data['license_status'])); ?></span>
                                        </span>
                                    </p>

                                    <p class="font-medium text-gray-700 flex items-center gap-2">
                                        Support:
                                        <span id="support-status" class="inline-flex items-center gap-1.5 <?php echo $license_manager->is_support_active() ? 'text-[var(--color-wp-green)]' : 'text-[var(--color-wp-red)]'; ?> font-semibold">
                                            <?php if ($license_manager->is_support_active()): ?>
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            <?php else: ?>
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                </svg>
                                            <?php endif; ?>
                                            <span id="support-status-text"><?php echo $license_manager->is_support_active() ? 'Active' : 'Expired'; ?></span>
                                        </span>
                                    </p>

                                    <?php
                                    $last_verified = $license_manager->get_last_verified_time();
                                    if ($last_verified):
                                        $wp_timezone = wp_timezone();
                                        $datetime = new DateTime();
                                        $datetime->setTimestamp($last_verified);
                                        $datetime->setTimezone($wp_timezone);
                                        $last_verified_date = $datetime->format('F j, Y g:i a (T)');
                                    ?>
                                        <p class="text-sm text-gray-600">
                                            <strong>Last Verified:</strong> <span id="last-verified-date"><?php echo esc_html($last_verified_date); ?></span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div id="license-refresh-container">
                                <div class="flex items-center gap-4">
                                    <button id="refresh-license-btn" class="inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white bg-wp-blue hover:bg-wp-blue/90 transition-colors shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                        </svg>
                                        Refresh License Data
                                    </button>

                                    <div id="refresh-status" class="hidden text-sm font-medium"></div>
                                </div>
                            </div>

                            <div id="license-response" class="hidden mt-6 py-3 px-4 rounded-lg border"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php
        // Content End
        include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-end.php';
    }
}

/**
 * AJAX handler for license refresh
 */
function gbt_ajax_refresh_license()
{
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'gbt_license_refresh_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // Get theme data
    $theme_slug = isset($_POST['theme_slug']) ? sanitize_text_field($_POST['theme_slug']) : '';
    $theme_id = isset($_POST['theme_id']) ? sanitize_text_field($_POST['theme_id']) : '';

    if (empty($theme_slug) || empty($theme_id)) {
        wp_send_json_error(['message' => 'Missing required parameters']);
    }

    // Get License Manager instance
    $license_manager = GBT_License_Manager::get_instance();

    // Get license key
    $license_key = $license_manager->get_license_key();

    if (empty($license_key)) {
        wp_send_json_error([
            'message' => 'No license key found. Please activate a license first.',
            'license_status' => 'inactive',
            'is_support_active' => false
        ]);
    }

    // Refresh license data
    $result = $license_manager->process_license_submission($license_key, $theme_slug, $theme_id);

    if ($result['success']) {
        // Format the last verified date
        $last_verified = $license_manager->get_last_verified_time();
        $wp_timezone = wp_timezone();
        $datetime = new DateTime();
        $datetime->setTimestamp($last_verified);
        $datetime->setTimezone($wp_timezone);
        $last_verified_date = $datetime->format('F j, Y g:i a (T)');

        wp_send_json_success([
            'message' => $result['message'],
            'license_status' => $result['license_data']['license_status'],
            'is_support_active' => $license_manager->is_support_active(),
            'last_verified' => $last_verified_date
        ]);
    } else {
        wp_send_json_error([
            'message' => $result['message'],
            'license_status' => $result['license_data']['license_status'],
            'is_support_active' => $license_manager->is_support_active()
        ]);
    }
}
add_action('wp_ajax_gbt_refresh_license', 'gbt_ajax_refresh_license');
