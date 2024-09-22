<?php

if ( ! class_exists( 'SKSocialSharing' ) ) :

	/**
	 * SKSocialSharing class.
	 *
	 * @since 1.4.2
	*/
	class SKSocialSharing {

		/**
		 * The single instance of the class.
		 *
		 * @since 1.4.2
		 * @var SKSocialSharing
		*/
		protected static $_instance = null;

		/**
		 * SKSocialSharing constructor.
		 *
		 * @since 1.4.2
		*/
		public function __construct() {

			if( !get_option( 'sk_social_sharing_options_import', false ) ) {
				$done_import = $this->import_options();
				update_option( 'sk_social_sharing_options_import', true );
			}

			$this->enqueue_styles();
			$this->customizer_options();

			add_filter( 'getbowtied_woocommerce_before_single_product_summary_data_tabs', function() {
				if ( sk_string_to_bool( get_option( 'sk_sharing_options', 'yes' ) ) ) {
					$this->getbowtied_single_share_product();
				}
			}, 50 );

			if( sk_string_to_bool( get_option( 'sk_sharing_options', 'yes' ) ) && sk_string_to_bool( get_option( 'sk_sharing_options_facebook', 'yes' ) ) && sk_string_to_bool( get_option( 'sk_sharing_options_facebook_meta', 'yes' ) ) ) {
				add_action( 'wp_head', array( $this, 'sk_add_facebook_meta' ), 10 );
			}
		}

		public function sk_add_facebook_meta() {
			if ( is_single() && is_product() ) {
				$product = wc_get_product(get_the_ID());
				if( $product ) {
					$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'large' );
					$image = isset($image[0]) ? $image[0] : '';
					$description = wp_strip_all_tags(wpautop(str_replace('&nbsp;', '', $product->get_short_description())));
					?>

					<meta property="og:url" content="<?php the_permalink(); ?>">
					<meta property="og:type" content="product">
					<meta property="og:title" content="<?php the_title(); ?>">
					<meta property="og:description" content="<?php esc_html_e($description); ?>">
					<meta property="og:image" content="<?php esc_attr_e($image); ?>">

					<?php
				}
			}

			return;
		}

		/**
		 * Ensures only one instance of SKSocialSharing is loaded or can be loaded.
		 *
		 * @since 1.4.2
		 *
		 * @return SKSocialSharing
		*/
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Enqueue styles.
		 *
		 * @since 1.4.2
		 * @return void
		*/
		protected function enqueue_styles() {
			add_action( 'wp_enqueue_scripts', function() {
				wp_enqueue_style('sk-social-sharing-styles', plugins_url( 'assets/css/social-sharing'.SK_EXT_ENQUEUE_SUFFIX.'.css', __FILE__ ), NULL );
			});
		}

		/**
		 * Imports social sharing options stored as theme mods into the options WP table.
		 *
		 * @since 1.4.2
		 * @return void
		 */
		private function import_options() {

			update_option( 'sk_sharing_options', get_theme_mod( 'sharing_options', true ) );
		}

		/**
		 * Registers customizer options.
		 *
		 * @since 1.4.2
		 * @return void
		 */
		protected function customizer_options() {
			add_action( 'customize_register', array( $this, 'sk_social_sharing_customizer' ) );
		}

		/**
		 * Creates customizer options.
		 *
		 * @since 1.4.2
		 * @return void
		 */
		public function sk_social_sharing_customizer( $wp_customize ) {

			// Social Sharing Options.
			$wp_customize->add_setting( 'sk_sharing_options', array(
				'type'		 			=> 'option',
				'capability' 			=> 'manage_options',
				'sanitize_callback'    	=> 'sk_sanitize_checkbox',
				'sanitize_js_callback'  => 'sk_string_to_bool',
				'transport'				=> 'refresh',
				'default'	 			=> 'yes',
			) );

			$wp_customize->add_control(
				new WP_Customize_Control(
					$wp_customize,
					'sk_sharing_options',
					array(
						'type'		  => 'checkbox',
						'label'       => esc_attr__( 'Social Sharing Options', 'shopkeeper-extender' ),
						'section'     => 'product',
						'priority'    => 20,
					)
				)
			);

			// Include Facebook
			$wp_customize->add_setting( 'sk_sharing_options_facebook', array(
				'type'		 			=> 'option',
				'capability' 			=> 'manage_options',
				'sanitize_callback'    	=> 'sk_sanitize_checkbox',
				'sanitize_js_callback'  => 'sk_string_to_bool',
				'transport'				=> 'refresh',
				'default'	 			=> 'yes',
			) );

			$wp_customize->add_control(
				new WP_Customize_Control(
					$wp_customize,
					'sk_sharing_options_facebook',
					array(
						'type'		  => 'checkbox',
						'label'       => esc_attr__( 'Include Facebook', 'shopkeeper-extender' ),
						'section'     => 'product',
						'priority'    => 20,
						'active_callback' => function() {
							return sk_string_to_bool( get_option( 'sk_sharing_options', 'yes' ) );
						}
					)
				)
			);

			// Include Facebook
			$wp_customize->add_setting( 'sk_sharing_options_facebook_meta', array(
				'type'		 			=> 'option',
				'capability' 			=> 'manage_options',
				'sanitize_callback'    	=> 'sk_sanitize_checkbox',
				'sanitize_js_callback'  => 'sk_string_to_bool',
				'transport'				=> 'refresh',
				'default'	 			=> 'yes',
			) );

			$wp_customize->add_control(
				new WP_Customize_Control(
					$wp_customize,
					'sk_sharing_options_facebook_meta',
					array(
						'type'		  => 'checkbox',
						'label'       => esc_attr__( 'Facebook Open Graph', 'shopkeeper-extender' ),
						'section'     => 'product',
						'priority'    => 20,
						'active_callback' => function() {
							return sk_string_to_bool( get_option( 'sk_sharing_options', 'yes' ) ) && sk_string_to_bool( get_option( 'sk_sharing_options_facebook', 'yes' ) );
						}
					)
				)
			);

			// Include Twitter
			$wp_customize->add_setting( 'sk_sharing_options_twitter', array(
				'type'		 			=> 'option',
				'capability' 			=> 'manage_options',
				'sanitize_callback'    	=> 'sk_sanitize_checkbox',
				'sanitize_js_callback'  => 'sk_string_to_bool',
				'transport'				=> 'refresh',
				'default'	 			=> 'yes',
			) );

			$wp_customize->add_control(
				new WP_Customize_Control(
					$wp_customize,
					'sk_sharing_options_twitter',
					array(
						'type'		  => 'checkbox',
						'label'       => esc_attr__( 'Include Twitter', 'shopkeeper-extender' ),
						'section'     => 'product',
						'priority'    => 20,
						'active_callback' => function() {
							return sk_string_to_bool( get_option( 'sk_sharing_options', 'yes' ) );
						}
					)
				)
			);

			// Include Pinterest
			$wp_customize->add_setting( 'sk_sharing_options_pinterest', array(
				'type'		 			=> 'option',
				'capability' 			=> 'manage_options',
				'sanitize_callback'    	=> 'sk_sanitize_checkbox',
				'sanitize_js_callback'  => 'sk_string_to_bool',
				'transport'				=> 'refresh',
				'default'	 			=> 'yes',
			) );

			$wp_customize->add_control(
				new WP_Customize_Control(
					$wp_customize,
					'sk_sharing_options_pinterest',
					array(
						'type'		  => 'checkbox',
						'label'       => esc_attr__( 'Include Pinterest', 'shopkeeper-extender' ),
						'section'     => 'product',
						'priority'    => 20,
						'active_callback' => function() {
							return sk_string_to_bool( get_option( 'sk_sharing_options', 'yes' ) );
						}
					)
				)
			);
		}

		public function getbowtied_single_share_product() {

		    global $post, $product;

			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'large' );
			$image = isset($image[0]) ? $image[0] : '';
			?>

		    <div class="product_socials_wrapper show-share-text-on-mobiles">

				<div class="share-product-text">
					<?php esc_html_e( 'Share this product', 'shopkeeper-extender'); ?>
				</div>

				<div class="product_socials_wrapper_inner">

					<?php if( sk_string_to_bool( get_option( 'sk_sharing_options_facebook', 'yes' ) ) ) { ?>
						<a target="_blank"
							class="social_media social_media_facebook"
							href="https://www.facebook.com/sharer.php?u=<?php the_permalink(); ?>"
							title="<?php esc_html_e( 'Facebook', 'shopkeeper-extender' ); ?>">
							<svg
	                    		xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
								width="20" height="20"
								viewBox="0 0 50 50">
								<path d="M32,11h5c0.552,0,1-0.448,1-1V3.263c0-0.524-0.403-0.96-0.925-0.997C35.484,2.153,32.376,2,30.141,2C24,2,20,5.68,20,12.368 V19h-7c-0.552,0-1,0.448-1,1v7c0,0.552,0.448,1,1,1h7v19c0,0.552,0.448,1,1,1h7c0.552,0,1-0.448,1-1V28h7.222 c0.51,0,0.938-0.383,0.994-0.89l0.778-7C38.06,19.518,37.596,19,37,19h-8v-5C29,12.343,30.343,11,32,11z"></path>
							</svg>
						</a>
					<?php } ?>

					<?php if( sk_string_to_bool( get_option( 'sk_sharing_options_twitter', 'yes' ) ) ) { ?>
						<a target="_blank"
							class="social_media social_media_twitter"
							href="https://twitter.com/share?url=<?php the_permalink(); ?>&amp;text=<?php the_title(); ?>"
							title="<?php esc_html_e( 'Twitter', 'shopkeeper-extender' ); ?>">
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="16" height="16">
								<path d="m9.51665,6.77629l5.9529,-6.77629l-1.41015,0l-5.17108,5.88255l-4.12706,-5.88255l-4.76125,0l6.24228,8.89631l-6.24228,7.10511l1.41015,0l5.45727,-6.21351l4.35942,6.21351l4.76125,0m-14.06899,-14.96006l2.16639,0l9.97284,13.96986l-2.16692,0"/>
							</svg>
						</a>
					<?php } ?>

					<?php if( sk_string_to_bool( get_option( 'sk_sharing_options_pinterest', 'yes' ) ) ) { ?>
						<a target="_blank"
							class="social_media social_media_pinterest"
							href="http://pinterest.com/pin/create/button/?url=<?php urlencode(the_permalink()); ?>&amp;description=<?php the_title(); ?>&amp;media=<?php esc_attr_e($image); ?>"
							title="<?php esc_html_e( 'Pinterest', 'shopkeeper-extender' ); ?>"
							count-layout=”vertical”>
							<svg
	                    		xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
								width="20" height="20"
								viewBox="0 0 50 50">
								<path d="M25,2C12.318,2,2,12.317,2,25s10.318,23,23,23s23-10.317,23-23S37.682,2,25,2z M27.542,32.719 c-3.297,0-4.516-2.138-4.516-2.138s-0.588,2.309-1.021,3.95s-0.507,1.665-0.927,2.591c-0.471,1.039-1.626,2.674-1.966,3.177 c-0.271,0.401-0.607,0.735-0.804,0.696c-0.197-0.038-0.197-0.245-0.245-0.678c-0.066-0.595-0.258-2.594-0.166-3.946 c0.06-0.88,0.367-2.371,0.367-2.371l2.225-9.108c-1.368-2.807-0.246-7.192,2.871-7.192c2.211,0,2.79,2.001,2.113,4.406 c-0.301,1.073-1.246,4.082-1.275,4.224c-0.029,0.142-0.099,0.442-0.083,0.738c0,0.878,0.671,2.672,2.995,2.672 c3.744,0,5.517-5.535,5.517-9.237c0-2.977-1.892-6.573-7.416-6.573c-5.628,0-8.732,4.283-8.732,8.214 c0,2.205,0.87,3.091,1.273,3.577c0.328,0.395,0.162,0.774,0.162,0.774l-0.355,1.425c-0.131,0.471-0.552,0.713-1.143,0.368 C15.824,27.948,13,26.752,13,21.649C13,16.42,17.926,11,25.571,11C31.64,11,37,14.817,37,21.001 C37,28.635,32.232,32.719,27.542,32.719z"></path>
							</svg>
						</a>
					<?php } ?>

				</div>

			</div>

		<?php
		}
	}

endif;

$sk_social_sharing = new SKSocialSharing;
