<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('getbowtied_changelog_content')) {
    function getbowtied_changelog_content()
    {
        // Create an instance of the GBT_Dashboard_Setup class
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();
        
        // Get the changelog URL using the getter method
        $theme_url_changelog_gbt_dash = $gbt_dashboard_setup->get_theme_url_changelog();
        
    ?>
        <div class="wrap">
            <iframe id="getbowtied_dashboard_iframe" src="<?php echo esc_url($theme_url_changelog_gbt_dash); ?>"></iframe>
        </div>

    <?php
    }
}
