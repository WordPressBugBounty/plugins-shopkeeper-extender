<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('getbowtied_home_content')) {
    function getbowtied_home_content()
    {
        // Create an instance of the GBT_Dashboard_Setup class
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();

        // Get the base paths
        $base_paths = $gbt_dashboard_setup->get_base_paths();
        
        // Get the values using the getter methods
        $theme_slug_gbt_dash = $gbt_dashboard_setup->get_theme_slug();
        $theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
        $theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
        $theme_url_docs_gbt_dash = $gbt_dashboard_setup->get_theme_url_docs();
        $theme_url_changelog_gbt_dash = $gbt_dashboard_setup->get_theme_url_changelog();
        $theme_url_support_gbt_dash = $gbt_dashboard_setup->get_theme_url_support();
        $theme_child_download_link_gbt_dash = $gbt_dashboard_setup->get_theme_child_download_link();

        // Content Start
        include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';
        ?>

        <div class="overflow-hidden py-24 sm:py-32">
          <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">
              <div class="lg:pr-8">
                <div class="lg:max-w-lg">
                  <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white">
                      <svg class="size-2 fill-green-400" viewBox="0 0 6 6" aria-hidden="true">
                        <circle cx="3" cy="3" r="3" />
                      </svg>
                      VERSION <?php echo esc_html($theme_version_gbt_dash); ?>
                    </span>
                    <a href="<?php echo esc_url($theme_url_changelog_gbt_dash); ?>" target="_blank" class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors">
                      CHANGELOG
                    </a>
                  </div>
                  <h2 class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl"><?php echo esc_html($theme_name_gbt_dash); ?></h2>
                  <dl class="mt-10 max-w-xl space-y-8 text-base/7 text-gray-600 lg:max-w-none">
                    <div class="relative pl-9">
                      <dt class="inline font-semibold text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute top-1 left-1 size-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                        New to <?php echo esc_html($theme_name_gbt_dash); ?>?
                      </dt>
                      <dd class="inline">Download the <a href="<?php echo esc_html($theme_child_download_link_gbt_dash); ?>">Child Theme</a> to customize without risking core theme updates.</dd>
                    </div>
                    <div class="relative pl-9">
                      <dt class="inline font-semibold text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute top-1 left-1 size-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                        Accelerate Your Project.
                      </dt>
                      <?php if (is_plugin_active('kits-templates-and-patterns/kits-templates-and-patterns.php')): ?>
                      <dd class="inline">Use our <a href="<?php echo esc_url(admin_url('themes.php?page=kits-templates-and-patterns&browse=' . $theme_slug_gbt_dash)); ?>">"Kits, Templates and Patterns"</a> plugin to launch your website faster with pre-designed templates.</dd>
                      <?php else: ?>
                      <dd class="inline">Activate our <a href="<?php echo esc_url(admin_url('admin.php?page=getbowtied-plugins')); ?>">"Kits, Templates and Patterns"</a> plugin to launch your project faster.</dd>
                      <?php endif; ?>
                    </div>
                    <div class="relative pl-9">
                      <dt class="inline font-semibold text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute top-1 left-1 size-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                        </svg>
                        Personalize Your Design.
                      </dt>
                      <dd class="inline">Use the <a href="<?php echo esc_url(admin_url('customize.php')); ?>">Customizer</a> to effortlessly tailor your website's look and feel to match your unique brand.</dd>
                    </div>
                    <div class="relative pl-9">
                      <dt class="inline font-semibold text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="absolute top-1 left-1 size-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                        </svg>
                        Need Support?
                      </dt>
                      <dd class="inline">Our team is ready to <a href="<?php echo esc_url(admin_url('admin.php?page=getbowtied-help')); ?>">help you</a> overcome challenges and unlock your website's full potential.</dd>
                    </div>
                  </dl>
                </div>
              </div>
              <img src="<?php echo esc_url($base_paths['url'] . '/dashboard/assets/img/theme/' . $theme_slug_gbt_dash . '/screenshot.png'); ?>" alt="<?php echo esc_attr($theme_name_gbt_dash); ?> Screenshot" class="w-full h-auto object-cover rounded-xl shadow-xl ring-1 ring-gray-400/10 lg:w-full">
            </div>
          </div>
        </div>

        <?php
        // Content End
        include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-end.php';
    }
}
