<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Get the GBT_Dashboard_Setup instance to access base paths
$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
$base_paths = $gbt_dashboard_setup->get_base_paths();

// Update includes to use base_paths
include_once( $base_paths['path'] . '/dashboard/inc/pages/includes.php' );
include_once( $base_paths['path'] . '/dashboard/inc/pages/pages.php' );

include_once( $base_paths['path'] . '/dashboard/inc/pages/content/home.php' );
include_once( $base_paths['path'] . '/dashboard/inc/pages/content/templates.php' );
include_once( $base_paths['path'] . '/dashboard/inc/pages/content/documentation.php' );
include_once( $base_paths['path'] . '/dashboard/inc/pages/content/changelog.php' );
include_once( $base_paths['path'] . '/dashboard/inc/pages/content/help.php' );