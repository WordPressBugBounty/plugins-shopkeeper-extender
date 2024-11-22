<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('getbowtied_documentation_content')) {
    function getbowtied_documentation_content()
    {
        // Create an instance of the GBT_Dashboard_Setup class
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();

        // Get the documentation URL
        $theme_url_docs_gbt_dash = $gbt_dashboard_setup->get_theme_url_docs();
        
    ?>

        <div class="wrap">
            <div style="padding:10px 40px; margin: 20px 0 0 0">
                <div style="padding:10px 10px; border: 1px solid #ddd; background: #fff">⚠ We are working on new documentation. You will find it here when it is ready. In the meantime you have the old one below.</div>
            </div>
            <iframe id="getbowtied_dashboard_iframe" src="<?php echo esc_url($theme_url_docs_gbt_dash); ?>"></iframe>
        </div>

    <?php
    }
}