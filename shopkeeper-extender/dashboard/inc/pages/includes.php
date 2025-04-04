<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!function_exists('getbowtied_dashboard_pages_styles_and_scripts')) {
	function getbowtied_dashboard_pages_styles_and_scripts()
	{
		// Check if we're on one of our pages
		if (empty($_GET['page'])) {
			return;
		}

		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();

		// Our page slugs based on actual registered pages
		$our_pages = array(
			'getbowtied-dashboard',    // Main dashboard page
			'getbowtied-help',         // Help page
			'getbowtied-templates',    // Templates page
			'getbowtied-diagnostics'   // Diagnostics page
		);

		// Add license page only if theme is not block-shop
		if ($gbt_dashboard_setup->get_theme_slug() !== 'block-shop') {
			$our_pages[] = 'getbowtied-license';      // License page
		}

		add_filter('screen_options_show_screen', '__return_false');

		// Only load our assets on our pages
		if (!in_array($_GET['page'], $our_pages)) {
			return;
		}

		// Get base paths and theme version
		$base_paths = $gbt_dashboard_setup->get_base_paths();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();

		// Tailwind CSS
		wp_enqueue_style(
			'getbowtied-dashboard-tailwind',
			$base_paths['url'] . '/dashboard/css/dashboard.css',
			array(),
			$theme_version_gbt_dash,
			'all'
		);

		// Base Styles
		wp_enqueue_style(
			'getbowtied-dashboard-pages',
			$base_paths['url'] . '/dashboard/css/pages.css',
			array('getbowtied-dashboard-tailwind'), // Make pages.css load after Tailwind
			$theme_version_gbt_dash,
			'all'
		);

		// Scripts 
		wp_enqueue_script(
			'getbowtied-dashboard-pages',
			$base_paths['url'] . '/dashboard/js/pages.js',
			array('jquery'),
			$theme_version_gbt_dash,
			true
		);

		// Documentation and Changelog pages are commented out in pages.php
		// Keeping the conditional for future use if these pages are enabled
		if (
			'getbowtied-documentation' === $_GET['page'] ||
			'getbowtied-changelog' === $_GET['page']
		) {
			wp_enqueue_script(
				'getbowtied-iframe-resizer',
				$base_paths['url'] . '/dashboard/js/vendor/iframe-resizer/iframeResizer.min.js',
				array('jquery'),
				'4.3.2',
				true
			);

			wp_enqueue_script(
				'getbowtied-dashboard-iframe',
				$base_paths['url'] . '/dashboard/js/iframe.js',
				array('jquery'),
				$theme_version_gbt_dash,
				true
			);
		}

		// Dashboard Message
		wp_enqueue_script(
			'gbt-dashboard-notification',
			$base_paths['url'] . '/dashboard/js/notifications.js',
			array('jquery'),
			$theme_version_gbt_dash,
			true
		);

		wp_localize_script(
			'gbt-dashboard-notification',
			'gbtDashboard',
			array(
				'nonce' => wp_create_nonce('dismiss_message')
			)
		);
	}

	add_action('admin_enqueue_scripts', 'getbowtied_dashboard_pages_styles_and_scripts');
}
