<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('getbowtied_dashboard_pages_styles_and_scripts')) {
	function getbowtied_dashboard_pages_styles_and_scripts() {

		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
		
		// Get base paths and theme version
		$base_paths = $gbt_dashboard_setup->get_base_paths();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();

		// Styles
		wp_enqueue_style('getbowtied-dashboard-pages', $base_paths['url'] . '/dashboard/css/pages.css', false, $theme_version_gbt_dash, 'all');

		// Scripts 
		wp_enqueue_script('getbowtied-dashboard-pages', $base_paths['url'] . '/dashboard/js/pages.js', array('jquery'), $theme_version_gbt_dash, TRUE);

		if ( (!empty( $_GET['page'] ) && ('getbowtied-documentation' == $_GET['page'])) || (!empty( $_GET['page'] ) && ('getbowtied-changelog' == $_GET['page'])) ) {
			wp_enqueue_script('getbowtied-iframe-resizer', $base_paths['url'] . '/dashboard/js/vendor/iframe-resizer/iframeResizer.min.js', array('jquery'), '4.3.2', TRUE);
			wp_enqueue_script('getbowtied-dashboard-iframe', $base_paths['url'] . '/dashboard/js/iframe.js', array('jquery'), $theme_version_gbt_dash, TRUE);
		}
	}

	add_action( 'admin_enqueue_scripts', 'getbowtied_dashboard_pages_styles_and_scripts' );
}
