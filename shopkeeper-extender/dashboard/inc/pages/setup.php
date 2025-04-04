<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

// Get the GBT_Dashboard_Setup instance to access base paths
$gbt_dashboard_setup = GBT_Dashboard_Setup::init();
$base_paths = $gbt_dashboard_setup->get_base_paths();

// Include License Config class first
require_once($base_paths['path'] . '/dashboard/inc/classes/class-license-config.php');

// Include License Localhosts class
require_once($base_paths['path'] . '/dashboard/inc/classes/class-license-localhosts.php');

// Include License Manager class
require_once($base_paths['path'] . '/dashboard/inc/classes/class-license-manager.php');

// Include License Subscription Checker class
require_once($base_paths['path'] . '/dashboard/inc/classes/class-license-subscription-checker.php');

// Include License Menu Badge class
require_once($base_paths['path'] . '/dashboard/inc/classes/class-license-menu-badge.php');

// Include License Server Connector class
require_once($base_paths['path'] . '/dashboard/inc/classes/class-license-server-connector.php');

// Include Theme Price Updater class
require_once($base_paths['path'] . '/dashboard/inc/classes/class-theme-price-updater.php');

// Update includes to use base_paths
include_once($base_paths['path'] . '/dashboard/inc/pages/includes.php');
include_once($base_paths['path'] . '/dashboard/inc/pages/pages.php');
include_once($base_paths['path'] . '/dashboard/inc/pages/pages-order.php');

include_once($base_paths['path'] . '/dashboard/inc/pages/content/home.php');
include_once($base_paths['path'] . '/dashboard/inc/pages/content/templates.php');
include_once($base_paths['path'] . '/dashboard/inc/pages/content/documentation.php');
include_once($base_paths['path'] . '/dashboard/inc/pages/content/changelog.php');
include_once($base_paths['path'] . '/dashboard/inc/pages/content/help.php');

// Include license page only if theme is not block-shop
if ($gbt_dashboard_setup->get_theme_slug() !== 'block-shop') {
	include_once($base_paths['path'] . '/dashboard/inc/pages/content/license.php');
}

// Include diagnostics.php
include_once($base_paths['path'] . '/dashboard/inc/pages/content/diagnostics.php');
