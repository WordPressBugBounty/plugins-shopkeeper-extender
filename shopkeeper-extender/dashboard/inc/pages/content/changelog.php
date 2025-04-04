<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!function_exists('getbowtied_changelog_content')) {
	function getbowtied_changelog_content()
	{
		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();

		// Get the base paths
		$base_paths = $gbt_dashboard_setup->get_base_paths();

		// Get the changelog URL using the getter method
		$theme_url_changelog_gbt_dash = $gbt_dashboard_setup->get_theme_url_changelog();

		// Content Start
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-start.php';

?>

		<iframe id="getbowtied_dashboard_iframe" src="<?php echo esc_url($theme_url_changelog_gbt_dash); ?>"></iframe>

<?php
		// Content End
		include_once $base_paths['path'] . '/dashboard/inc/pages/content/template-parts/content-end.php';
	}
}
