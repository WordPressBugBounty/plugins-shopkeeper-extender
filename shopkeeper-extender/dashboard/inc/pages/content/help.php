<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('getbowtied_help_content')) {
    function getbowtied_help_content()
    {
        // Create an instance of the GBT_Dashboard_Setup class
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();
        
        // Get the URLs using the getter methods
        $theme_url_docs_gbt_dash = $gbt_dashboard_setup->get_theme_url_docs();
        $theme_url_changelog_gbt_dash = $gbt_dashboard_setup->get_theme_url_changelog();
        $theme_url_support_gbt_dash = $gbt_dashboard_setup->get_theme_url_support();
?>

	<div class="wrap">

		<h1>Need Help?</h1>
		
		<p>
			<strong>Are you feeling overwhelmed or stuck in your current project?</strong><br /><br />
			Whether you're navigating through technical challenges, seeking advice on a strategic decision, or simply looking for someone to guide you through a complex process, help is available.<br />
			Our dedicated team of experts is here to offer support, answer your questions, and provide the insights you need to move forward with confidence.<br />
		</p>

		<ul>
			<li>
				<a href="<?php echo esc_url($theme_url_docs_gbt_dash); ?>" target="_blank" class="button button-primary button-large">Read the documentation</a>
				&nbsp;
				<a href="<?php echo esc_url($theme_url_support_gbt_dash); ?>" target="_blank" class="button button-large">Contact the Customer Support</a>
			</li>
			<li>&nbsp;</li>
			<li><a href="<?php echo esc_url($theme_url_changelog_gbt_dash); ?>" target="_blank" class="">Changelog</a></li>
		</ul>
	
	</div>

<?php
    }
}