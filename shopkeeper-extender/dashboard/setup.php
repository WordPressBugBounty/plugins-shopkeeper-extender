<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Use the init method to ensure proper initialization
$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
$base_paths = $gbt_dashboard_setup->get_base_paths();

include_once( $base_paths['path'] . '/dashboard/inc/pages/setup.php' );
include_once( $base_paths['path'] . '/dashboard/inc/classes/class-theme-li.php' );
include_once( $base_paths['path'] . '/dashboard/inc/classes/class-gbt-notification-handler.php' );

// Only include pointers if theme is not Block Shop
if ($gbt_dashboard_setup->get_theme_slug() !== 'block-shop') {
    include_once( $base_paths['path'] . '/dashboard/inc/pointers/pointers.php' );
}

include_once( $base_paths['path'] . '/dashboard/inc/third/setup.php' );