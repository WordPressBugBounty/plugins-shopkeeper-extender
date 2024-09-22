<?php

/**
 * Plugin Name:       		Shopkeeper Extender
 * Plugin URI:        		https://shopkeeper.wp-theme.design/
 * Description:       		Extends the functionality of Shopkeeper with theme specific features.
 * Version:           		3.8
 * Author:            		Get Bowtied
 * Author URI:				https://getbowtied.com
 * Text Domain:				shopkeeper-extender
 * Domain Path:				/languages/
 * Requires at least: 		6.0
 * Tested up to: 			6.6
 *
 * @package  Shopkeeper Extender
 * @author   GetBowtied
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

define( 'SK_EXT_ENQUEUE_SUFFIX', SCRIPT_DEBUG ? '' : '.min' );

$version = ( isset(get_plugin_data( __FILE__ )['Version']) && !empty(get_plugin_data( __FILE__ )['Version']) ) ? get_plugin_data( __FILE__ )['Version'] : '1.0';
define ( 'SK_EXT_VERSION', $version );

if ( ! class_exists( 'ShopkeeperExtender' ) ) :

	/**
	 * ShopkeeperExtender class.
	*/
	class ShopkeeperExtender {

		/**
		 * The single instance of the class.
		 *
		 * @var ShopkeeperExtender
		*/
		protected static $_instance = null;

		/**
		 * ShopkeeperExtender constructor.
		 *
		*/
		public function __construct() {

			// Shopkeeper Dependent Components
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

				// Gutenberg Blocks
				//include_once( dirname( __FILE__ ) . '/includes/gbt-blocks/index.php' );            

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

				// Admin Notices
    			//add_action( 'admin_notices', array( $this, 'shopkeeper_extender_admin_notices' ) );
    			//add_action( 'wp_ajax_shopkeeper_extender_dismiss_dashboard_notice', array( $this, 'shopkeeper_extender_dismiss_dashboard_notice' ) );

			} else {

				add_action( 'admin_notices', array( $this, 'shopkeeper_theme_not_activated_warning' ) );

			}

			// GBT Third Party
			include_once( dirname( __FILE__ ) . '/includes/gbt-third-party/setup.php' );
		}

		/**
		 * Ensures only one instance of ShopkeeperExtender is loaded or can be loaded.
		 *
		 * @return ShopkeeperExtender
		*/
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public function shopkeeper_theme_not_activated_warning() {
		?>
			<div class="message error shopkeeper-theme-inactive">
				<p><?php echo wp_kses_post( '<strong>Shopkeeper Extender</strong> is enabled but not effective. It requires <strong>Shopkeeper Theme</strong> in order to work. <a href="https://1.envato.market/getbowtied-to-shopkeeper" target="_blank"><strong>Get Shopkeeper Theme</strong></a>.' ); ?></p>
			</div>
		<?php
		}

		public function shopkeeper_extender_admin_notices() {
		?>

			<?php if ( !get_option('dismissed-black-friday-2023-notice', FALSE ) ) { ?>

			<div class="notice-error settings-error notice is-dismissible spk_ext_notice black_friday_2023_notice">

				<div class="spk_ext_notice__aside">
					<div class="spk_icon" aria-hidden="true"><br></div>
				</div>
				
				<div class="spk_ext_notice__content">

					<h3 class="title">Shopkeeper's Black Friday Sale 2023!</h3>

					<h4>Renew your theme subscription for a full year at an incredible 37% discount: Only $37/year. This is a Limited-Time Offer, so act now!</h4>

					<p>
						This subscription not only guarantees <em class="u_dotted">uninterrupted access to our dedicated team</em> throughout the year but also grants you complimentary <em class="u_dotted">automatic updates, including maintenance and security patches</em>, for your premium theme.
						<br />
						One license empowers you to use the theme in a single end product. (One license per website; additional websites require additional licenses).
					</p>

					<p>
						<a href="https://getbowtied.net/spk-ext-wp-notice-check-license-status" target="_blank" class="button button-primary button-large">Check your Subscription Status</a>
						&nbsp;
						<a href="https://getbowtied.net/spk-ext-wp-notice-get-another-license" target="_blank" class="button button-large">Buy a license for 37% OFF!</a>
					</p>

				</div>

			</div>

			<?php } ?>

		<?php
		}

		public function shopkeeper_extender_dismiss_dashboard_notice() {
			if( $_POST['notice'] == 'black_friday_2023' ) {
				update_option('dismissed-black-friday-2023-notice', TRUE );
			}
		}

	}
endif;

add_action( 'after_setup_theme', function() {
    $shopkeeper_extender = new ShopkeeperExtender;
} );
