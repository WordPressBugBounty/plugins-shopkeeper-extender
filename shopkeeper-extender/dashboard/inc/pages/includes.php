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

		// Get dashboard page slugs from centralized method
		$our_pages = $gbt_dashboard_setup->get_dashboard_page_slugs();

		// Only load our assets on our pages
		if (!in_array($_GET['page'], $our_pages)) {
			return;
		}

		// Apply screen options filter only on our custom pages
		add_filter('screen_options_show_screen', '__return_false');

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

		// Enqueue license types modal script globally for all dashboard pages
		// This is needed because the banner appears on all pages and has "learn more" links
		if ($gbt_dashboard_setup->get_theme_slug() !== 'block-shop') {
			wp_enqueue_script(
				'getbowtied-license-types-modal',
				$base_paths['url'] . '/dashboard/js/license-types-modal.js',
				array('jquery'),
				$theme_version_gbt_dash,
				true
			);
		}

		// Enqueue auto-update handler script
		wp_enqueue_script(
			'getbowtied-auto-update-handler',
			$base_paths['url'] . '/dashboard/js/auto-update-handler.js',
			array('jquery'),
			$theme_version_gbt_dash,
			true
		);

		// Localize script for AJAX
		wp_localize_script(
			'getbowtied-auto-update-handler',
			'gbtAutoUpdateData',
			array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('gbt_enable_auto_updates')
			)
		);

		// Enqueue theme installation script
		wp_enqueue_script(
			'getbowtied-theme-installer',
			$base_paths['url'] . '/dashboard/js/theme-installer.js',
			array('jquery'),
			$theme_version_gbt_dash,
			true
		);

		// Localize script for theme installation AJAX
		wp_localize_script(
			'getbowtied-theme-installer',
			'gbtThemeInstallerData',
			array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('install_theme_ajax'),
				'activate_nonce' => wp_create_nonce('activate_theme_ajax')
			)
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

		if (isset($_GET['page']) && $_GET['page'] === 'getbowtied-theme-documentation') {
			wp_enqueue_script(
				'gbt-documentation-iframe',
				$base_paths['url'] . '/dashboard/js/iframe.js',
				array('jquery'),
				$theme_version_gbt_dash,
				true
			);
		}

		// Removed old Dashboard Message script - now using global notification handler
	}

	add_action('admin_enqueue_scripts', 'getbowtied_dashboard_pages_styles_and_scripts');
}
