<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('getbowtied_dashboard_pages')) {
	function getbowtied_dashboard_pages() {

		global $menu;
		
		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
		
		// Use getter methods to access the properties
		$theme_slug_gbt_dash = $gbt_dashboard_setup->get_theme_slug();
		$theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
		
		$menu[53] = array('', 'read', 'separator-getbowtied', '', 'wp-menu-separator getbowtied-menu-separator');
		
		add_menu_page(
			$theme_name_gbt_dash,
			$theme_name_gbt_dash,
			'manage_options',
			'getbowtied-dashboard',
			'getbowtied_home_content',
			'dashicons-cart',
			54
		);
	    
	    add_submenu_page(
	    	'getbowtied-dashboard',
	    	'Home',
	    	'Home',
	    	'manage_options',
	    	'getbowtied-dashboard'
	    );

		add_submenu_page(
	    	'getbowtied-dashboard',
	    	'Customize',
	    	'Customize',
	    	'manage_options',
	    	admin_url('customize.php')
	    );

	    if (is_plugin_active('kits-templates-and-patterns/kits-templates-and-patterns.php')) {
		    
		    add_submenu_page(
		    	'getbowtied-dashboard',
		    	'Templates',
		    	'Templates',
		    	'manage_options',
		    	admin_url('themes.php?page=kits-templates-and-patterns&browse=' . $theme_slug_gbt_dash)
		    );
		
		} else {

		    add_submenu_page(
		    	'getbowtied-dashboard',
		    	'Templates',
		    	'Templates',
		    	'manage_options',
		    	'getbowtied-templates',
		    	'getbowtied_templates_content'
		    );
		
		}
	    
	    //add_submenu_page('getbowtied-dashboard', 'Documentation', 'Documentation', 'manage_options', 'getbowtied-documentation', 'getbowtied_documentation_content' );
	    //add_submenu_page('getbowtied-dashboard', 'Changelog', 'Changelog', 'manage_options', 'getbowtied-changelog', 'getbowtied_changelog_content' );
	    
	    /*add_submenu_page(
	    	'getbowtied-dashboard',
	    	'License',
	    	'License',
	    	'manage_options',
	    	'getbowtied-license',
	    	'getbowtied_license_content'
	    );*/
	    
	    add_submenu_page(
	    	'getbowtied-dashboard',
	    	'Help',
	    	'Help',
	    	'manage_options',
	    	'getbowtied-help',
	    	'getbowtied_help_content'
	    );
	}

	add_action('admin_menu', 'getbowtied_dashboard_pages');
}
