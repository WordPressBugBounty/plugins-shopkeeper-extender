<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('getbowtied_license_content')) {
    function getbowtied_license_content() {
        // Get the dashboard setup instance
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();

        // Get the base paths
        $base_paths = $gbt_dashboard_setup->get_base_paths();
        
        // Get the values using the getter methods
        $theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
        $theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
        
        // Get saved license key
        $license_key = get_option('getbowtied_theme_license_key', '');
        $license_status = get_option('getbowtied_theme_license_status', 'inactive');
        
        // Handle form submission
        if (isset($_POST['save_license']) && check_admin_referer('getbowtied_license_nonce')) {
            $new_license_key = sanitize_text_field($_POST['license_key']);
            
            if (empty($new_license_key)) {
                delete_option('getbowtied_theme_license_key');
                delete_option('getbowtied_theme_license_status');
                $license_key = '';
                $license_status = 'inactive';
            } else {
                update_option('getbowtied_theme_license_key', $new_license_key);
                $license_key = $new_license_key;
                // Here you would typically validate the license key with your licensing system
                // For now, we'll just mark it as active if a key is provided
                update_option('getbowtied_theme_license_status', 'active');
                $license_status = 'active';
            }
        }

        // Content Start
        include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';
        ?>
        
        <div class="overflow-hidden py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-1">
                    <div>
                        <div class="lg:max-w-lg">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white">
                                    <svg class="size-2 fill-green-400" viewBox="0 0 6 6" aria-hidden="true">
                                        <circle cx="3" cy="3" r="3" />
                                    </svg>
                                    VERSION <?php echo esc_html($theme_version_gbt_dash); ?>
                                </span>
                            </div>
                            <h2 class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl"><?php echo esc_html($theme_name_gbt_dash); ?> License</h2>
                            <p class="mt-6 text-lg leading-8 text-gray-600">
                                Activate your license to unlock premium features, automatic updates, and dedicated support.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- License Form -->
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                    License Management
                                </h3>
                            </div>
                            <div class="p-8">
                                <form method="post" action="" class="space-y-6">
                                    <?php wp_nonce_field('getbowtied_license_nonce'); ?>
                                    
                                    <div class="space-y-2">
                                        <label for="license_key" class="block font-medium text-gray-700 flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v-1l1-1 1-1-.257-.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd" />
                                            </svg>
                                            License Key:
                                        </label>
                                        <input type="text" 
                                                id="license_key" 
                                                name="license_key" 
                                                class="w-full px-4 py-3 text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-wp-blue focus:border-wp-blue placeholder:text-gray-400 hover:border-gray-400" 
                                                value="<?php echo esc_attr($license_key); ?>" 
                                                placeholder="Enter your license key"
                                        />
                                    </div>
                                    
                                    <div class="py-3 px-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <p class="font-medium text-gray-700 flex items-center gap-2">
                                            Status: 
                                            <span class="inline-flex items-center gap-1.5 <?php echo $license_status === 'active' ? 'text-green-500' : 'text-red-600'; ?> font-semibold">
                                                <?php if ($license_status === 'active'): ?>
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                <?php else: ?>
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                    </svg>
                                                <?php endif; ?>
                                                <?php echo ucfirst(esc_html($license_status)); ?>
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <div class="pt-2">
                                        <button type="submit" 
                                                name="save_license" 
                                                class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white bg-wp-blue hover:bg-wp-blue/90 transition-colors no-underline shadow-sm">
                                            <?php if ($license_status === 'active'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                                </svg>
                                                Update License
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                                </svg>
                                                Activate License
                                            <?php endif; ?>
                                        </button>
                                        <?php if ($license_status === 'active'): ?>
                                            <button type="submit" 
                                                    name="save_license" 
                                                    class="mt-3 sm:mt-0 sm:ml-3 w-full sm:w-auto inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition-colors no-underline shadow-sm"
                                                    onclick="document.getElementById('license_key').value = '';">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                Deactivate License
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Advantages List -->
                        <div class="p-8">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                Benefits of an Active License
                            </h3>
                            <ul class="!space-y-6">
                                <li class="flex">
                                    <div class="flex-shrink-0 text-green-500 font-bold mt-0.5 mr-3">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-medium text-gray-900">Automatic Updates</h4>
                                        <p class="text-sm text-gray-600 mt-1">Get the latest features and improvements as soon as they're released.</p>
                                    </div>
                                </li>

                                <li class="flex">
                                    <div class="flex-shrink-0 text-green-500 font-bold mt-0.5 mr-3">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-medium text-gray-900">Security Updates</h4>
                                        <p class="text-sm text-gray-600 mt-1">Stay protected with the latest security patches and bug fixes.</p>
                                    </div>
                                </li>

                                <li class="flex">
                                    <div class="flex-shrink-0 text-green-500 font-bold mt-0.5 mr-3">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-medium text-gray-900">Premium Features</h4>
                                        <p class="text-sm text-gray-600 mt-1">Access all premium features and functionality to enhance your website.</p>
                                    </div>
                                </li>
                                
                                <li class="flex">
                                    <div class="flex-shrink-0 text-green-500 font-bold mt-0.5 mr-3">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-medium text-gray-900">Premium Support</h4>
                                        <p class="text-sm text-gray-600 mt-1">Get priority assistance from our dedicated support team.</p>
                                    </div>
                                </li>
                            </ul>
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