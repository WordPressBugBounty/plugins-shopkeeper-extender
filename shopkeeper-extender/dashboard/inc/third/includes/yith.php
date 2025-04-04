<?php

defined('ABSPATH') || exit;

class YITH_Gbt_Third_Party_Plugin
{

	const GBT_YITH_REFER_ID = '1189755';

	// Setup
	public function __construct()
	{
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	// Enqueue Scripts
	public function enqueue_scripts()
	{
		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();

		// Get base paths and theme version
		$base_paths = $gbt_dashboard_setup->get_base_paths();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();

		wp_enqueue_script(
			'gbt-yith-integration',
			$base_paths['url'] . '/dashboard/inc/third/assets/js/yith.js',
			array('jquery'),
			$theme_version_gbt_dash,
			true
		);

		wp_localize_script(
			'gbt-yith-integration',
			'gbt_yith',
			array(
				'refer_id' => self::GBT_YITH_REFER_ID
			)
		);
	}
}

new YITH_Gbt_Third_Party_Plugin();
