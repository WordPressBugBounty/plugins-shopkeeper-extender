<?php

/**
 * Plugin Name:       		Shopkeeper Extender
 * Plugin URI:        		https://shopkeeper.wp-theme.design/
 * Description:       		Extends the functionality of Shopkeeper with theme specific features.
 * Version:           		4.1.1
 * Author:            		Get Bowtied
 * Author URI:				https://getbowtied.com
 * Text Domain:				shopkeeper-extender
 * Domain Path:				/languages/
 * Requires at least: 		6.0
 * Tested up to: 			6.7.1
 *
 * @package  Shopkeeper Extender
 * @author   GetBowtied
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( ! class_exists( 'ShopkeeperExtender' ) ) :

	class ShopkeeperExtender {

		private static $instance = null;
		private static $initialized = false;

		private function __construct() {
			// Empty constructor - initialization happens in init_instance
		}

		private function init_instance() {
			if (self::$initialized) {
				return;
			}

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			
			define( 'SK_EXT_ENQUEUE_SUFFIX', SCRIPT_DEBUG ? '' : '.min' );
			
			$version = ( isset(get_plugin_data( __FILE__ )['Version']) && !empty(get_plugin_data( __FILE__ )['Version']) ) ? get_plugin_data( __FILE__ )['Version'] : '1.0';
			define ( 'SK_EXT_VERSION', $version );

			$this->theme_slug = 'shopkeeper';

			// Move existing constructor code here
			if( function_exists('shopkeeper_theme_slug') ) {
				// Helpers
				include_once( dirname( __FILE__ ) . '/includes/helpers/helpers.php' );

				// Vendor
				include_once( dirname( __FILE__ ) . '/includes/vendor/enqueue.php' );

	            // Customizer
				include_once( dirname( __FILE__ ) . '/includes/customizer/repeater/class-sk-ext-repeater-control.php' );

				// Shortcodes
				include_once( dirname( __FILE__ ) . '/includes/shortcodes/index.php' );

				// Social Media
				include_once( dirname( __FILE__ ) . '/includes/social-media/class-social-media.php' );

				// Widgets
				include_once( 'includes/widgets/social-media.php' );           

				//Custom Menu
				include_once( dirname( __FILE__ ) . '/includes/custom-menu/index.php' );

				// Social Sharing Buttons
				if ( is_plugin_active( 'woocommerce/woocommerce.php') ) {
					include_once( dirname( __FILE__ ) . '/includes/social-sharing/class-social-sharing.php' );
				}

                // Addons
    			if ( is_plugin_active( 'woocommerce/woocommerce.php') ) {
    				include_once( dirname( __FILE__ ) . '/includes/addons/class-wc-category-header-image.php' );
    			}

			}

			if ( is_admin() ) {
				global $gbt_dashboard_params;
				$gbt_dashboard_params = array(
					'gbt_theme_slug' => $this->theme_slug,
				);
				include_once( dirname( __FILE__ ) . '/dashboard/index.php' );
			}

			// GBT Third Party
			include_once( dirname( __FILE__ ) . '/includes/gbt-third-party/setup.php' );

			self::$initialized = true;
		}

		public static function get_instance() {
			return self::init();
		}

		public static function init() {
			if (self::$instance === null) {
				self::$instance = new self();
				self::$instance->init_instance();
			}
			return self::$instance;
		}

		private function __clone() {}
		
		public function __wakeup() {
			throw new Exception("Cannot unserialize singleton");
		}
	}

endif;

add_action( 'after_setup_theme', function() {
    ShopkeeperExtender::init();
} );
