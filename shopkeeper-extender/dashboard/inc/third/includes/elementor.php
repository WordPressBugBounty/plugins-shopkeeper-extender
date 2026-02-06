<?php

defined('ABSPATH') || exit;

// Include WordPress plugin API
require_once ABSPATH . 'wp-admin/includes/plugin.php';

class Elementor_Gbt_Third_Party_Plugin
{

	const GBT_ELEMENTOR_PREFIX_AFF_LINK = 'https://be.elementor.com/visit/?bta=208394&brand=elementor&landingPage=';

	const GBT_ELEMENTOR_PRO_LINK = 'https://elementor.com/pro/';
	const GBT_ELEMENTOR_HELP_LINK = 'https://elementor.com/help/';

	// Setup
	public function __construct()
	{
		if (is_plugin_active('elementor/elementor.php')) {
			add_action('init', array($this, 'remove_elementor_onboarding_redirection'));
			add_action('admin_init', array($this, 'elementor_go_pro_link'));
			add_action('admin_init', array($this, 'elementor_go_knowledge_base_link'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
			add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_scripts'));
		}

		if (is_plugin_active('pro-elements/pro-elements.php')) {
			add_action('admin_head', array($this, 'hide_elementor_notice'));
		}
	}

	// Disable Onboarding
	public function remove_elementor_onboarding_redirection()
	{
		if (is_admin()) {
			add_action('admin_init', function () {
				if (did_action('elementor/loaded')) {
					remove_action('admin_init', [\Elementor\Plugin::$instance->admin, 'maybe_redirect_to_getting_started']);
				}
			}, 1);

			delete_transient('elementor_activation_redirect');
		}
	}

	// Upgrade Link
	public function elementor_go_pro_link()
	{
		if (is_admin() && isset($_GET['page']) && 'go_elementor_pro' === $_GET['page']) {
			wp_safe_redirect(self::GBT_ELEMENTOR_PREFIX_AFF_LINK . self::GBT_ELEMENTOR_PRO_LINK);
			exit;
		}
	}

	// Knowledge Base Link
	public function elementor_go_knowledge_base_link()
	{
		if (is_admin() && isset($_GET['page']) && 'go_knowledge_base_site' === $_GET['page']) {
			wp_safe_redirect(self::GBT_ELEMENTOR_PREFIX_AFF_LINK . self::GBT_ELEMENTOR_HELP_LINK);
			exit;
		}
	}

	// Scripts
	public function enqueue_scripts()
	{
		// Create an instance of the GBT_Dashboard_Setup class
		$gbt_dashboard_setup = GBT_Dashboard_Setup::init();

		// Get base paths and theme version
		$base_paths = $gbt_dashboard_setup->get_base_paths();
		$theme_version_gbt_dash = $gbt_dashboard_setup->get_theme_version();

		wp_enqueue_script(
			'gbt-third-party-plugins',
			$base_paths['url'] . '/dashboard/inc/third/assets/js/elementor.js',
			array('jquery'),
			$theme_version_gbt_dash,
			true
		);

		wp_localize_script(
			'gbt-third-party-plugins',
			'gbt_elementor',
			array(
				'is_FREE' => is_plugin_active('elementor/elementor.php') ? TRUE : FALSE,
				'is_PRO' => is_plugin_active('elementor-pro/elementor-pro.php') ? TRUE : FALSE,
				'gbt_elementor_prefix_aff_link' => self::GBT_ELEMENTOR_PREFIX_AFF_LINK,
				'gbt_elementor_pro_link' => self::GBT_ELEMENTOR_PRO_LINK
			)
		);
	}

	// Hide Elementor Notice
	public function hide_elementor_notice()
	{
		echo '<style>.e-notice { display: none !important; }</style>';
	}
}

new Elementor_Gbt_Third_Party_Plugin();
