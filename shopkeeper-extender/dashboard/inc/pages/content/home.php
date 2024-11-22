<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('getbowtied_home_content')) {
    function getbowtied_home_content()
    {
        // Create an instance of the GBT_Dashboard_Setup class
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();
        
        // Get the values using the getter methods
        $theme_slug_gbt_dash = $gbt_dashboard_setup->get_theme_slug();
        $theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
        $theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();
        $theme_url_docs_gbt_dash = $gbt_dashboard_setup->get_theme_url_docs();
        $theme_url_changelog_gbt_dash = $gbt_dashboard_setup->get_theme_url_changelog();
        $theme_url_support_gbt_dash = $gbt_dashboard_setup->get_theme_url_support();
        $theme_child_download_link_gbt_dash = $gbt_dashboard_setup->get_theme_child_download_link();
    ?>

        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html($theme_name_gbt_dash); ?> <?php echo esc_html($theme_version_gbt_dash); ?></h1>
            <a href="<?php echo esc_url($theme_url_changelog_gbt_dash); ?>" target="_blank" class="page-title-action">Changelog</a>
            <br /><br />

            <div class="">
                <h2>Are you just beginning your journey?</h2>
                
                <p><a href="<?php echo esc_html($theme_child_download_link_gbt_dash); ?>">Start by downloading the <?php echo esc_html($theme_name_gbt_dash); ?> child theme.</a></p>

                <p>After downloading, go ahead and install and activate it just like you would do with a typical theme. (<a href="https://developer.wordpress.org/themes/advanced-topics/child-themes/" target="_blank">Wondering why to opt for a child theme?</a>)</p>
                
                <hr />

                <h2>Accelerate your project launch with a starter template!</h2>

                <?php
                if (is_plugin_active('kits-templates-and-patterns/kits-templates-and-patterns.php')) {
                ?>

                    <p>
                        Our starter templates are incredibly user-friendly, making them suitable for both beginners and seasoned professionals.<br />
                        With easy customization options, you can tweak the templates to perfectly match your project's identity and requirements.
                    </p>

                    <a href="<?php echo esc_url(admin_url('themes.php?page=kits-templates-and-patterns&browse=' . $theme_slug_gbt_dash)); ?>" class="button button-primary button-large"><?php echo esc_html($theme_name_gbt_dash); ?> Templates</a>
                
                <?php
                
                } else {

                ?>

                    <p>
                        Install the "<strong>Kits, Templates and Patterns</strong>" plugin and give our starter templates a try! They'll help you launch your project faster.<br />
                        These templates are built with Elementor, and you will have access to all the widgets in Elementor PRO without a subscription.
                    </p>

                    <a href="<?php echo esc_url(admin_url('plugins.php?page='.$theme_slug_gbt_dash.'-plugins')); ?>" class="button button-primary button-large">Get "Kits, Templates and Patterns" plugin for <?php echo esc_html($theme_name_gbt_dash); ?>. It's free.</a>
                
                <?php
                }
                ?>

                <br /><br />

                <h2>Looking for a unique touch for your website?</h2>

                <p>
                    The Customizer is your gateway to creating a truly unique digital presence.<br />
                    This tool offers an intuitive interface that allows you to personalize your site with ease, making sure it reflects your style and meets your specific needs.
                </p>

                <a href="<?php echo esc_url(admin_url('customize.php')); ?>" class="button button-primary button-large">Customize <?php echo esc_html($theme_name_gbt_dash); ?></a>
                
                <br /><br />

                <h2>Need Help?</h2>

                <p>
                    Don't let obstacles deter you from reaching your full potential. Reach out today, and let's work together to tackle your challenges and achieve your goals.<br />
                    With the right support, there's no limit to what you can accomplish.
                </p>

                <ul>
                    <li>
                        <a href="<?php echo esc_url($theme_url_docs_gbt_dash); ?>" target="_blank" class="button button-primary button-large">Read the documentation</a>
                        &nbsp;
                        <a href="<?php echo esc_url($theme_url_support_gbt_dash); ?>" target="_blank" class="button button-large">Contact the Customer Support</a>
                    </li>
                    <li>&nbsp;</li>
                </ul>
                
                <br /><br />

            </div>

        </div>

    <?php
    }
}
