<?php

defined( 'ABSPATH' ) || exit;

class Elementor_Gbt_Third_Party_Plugin {

	const GBT_ELEMENTOR_AFF_LINK_PRICES = 'https://be.elementor.com/visit/?bta=208394&nci=5383';
	const GBT_ELEMENTOR_AFF_LINK_HOMEPAGE = 'https://be.elementor.com/visit/?bta=208394&nci=5349';
	const GBT_ELEMENTOR_AFF_LINK_DOCS = 'https://be.elementor.com/visit/?bta=208394&nci=5517';

	// Setup
	public function __construct()
	{
		if ( is_plugin_active( 'elementor/elementor.php' ) ) {
			add_action( 'init', array( $this, 'remove_elementor_onboarding_redirection' ) );
			add_action( 'admin_init', array( $this, 'elementor_go_pro_link' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	// Disable Onboarding
	public function remove_elementor_onboarding_redirection()
	{
		if ( is_admin() )
		{			
			add_action( 'admin_init', function() {
				if ( did_action( 'elementor/loaded' ) ) {
					remove_action( 'admin_init', [ \Elementor\Plugin::$instance->admin, 'maybe_redirect_to_getting_started' ] );
				}
			}, 1 );

			delete_transient( 'elementor_activation_redirect' );
		}
	}

	// Upgrade Link
	public function elementor_go_pro_link()
	{
		if ( is_admin() && isset( $_GET['page'] ) && 'go_elementor_pro' === $_GET['page'] )
		{
			wp_redirect( self::GBT_ELEMENTOR_AFF_LINK_PRICES );
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
	    		'aff_text' => esc_html__( 'Get more with Elementor Pro', 'elementor' ),
	    		'aff_link_prices' => self::GBT_ELEMENTOR_AFF_LINK_PRICES,
	    		'aff_link_homepage' => self::GBT_ELEMENTOR_AFF_LINK_HOMEPAGE,
	    		'aff_link_docs' => self::GBT_ELEMENTOR_AFF_LINK_DOCS,
	    		'is_FREE' => is_plugin_active( 'elementor/elementor.php' ) ? TRUE : FALSE,
	    		'is_PRO' => is_plugin_active( 'elementor-pro/elementor-pro.php' ) ? TRUE : FALSE
	    	)
	    );
	}

}

new Elementor_Gbt_Third_Party_Plugin();
