<?php
/**
 * Options Repeater control class
 *
 * @package SK_Customize_Repeater_Control
 */

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	include ABSPATH . WPINC . '/class-wp-customize-control.php';
}

/**
 * Class SK_Customize_Repeater_Control
 */
class SK_Ext_Customize_Repeater_Control extends WP_Customize_Control {

	/**
	 * Social Field ID.
	 *
     * @var string
	 */
	public $id;

	/**
	 * Social Media Profiles.
	 *
	 * Used for icons dropdown list.
	 *
     * @var array
	 */
	public $profiles = array();

	/**
	 * Constructor.
	 *
	 * Supplied `$args` override class property defaults.
	 *
	 * If `$args['settings']` is not defined, use the $id as the setting ID.
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( $manager, $id, $args = array() ) {

		parent::__construct( $manager, $id, $args );

		if ( !empty( $args['profiles'] ) ) {
			$this->profiles = $args['profiles'];
		}

		if ( !empty( $id ) ) {
			$this->id = $id;
		}
	}

	/**
	 * Render the control's content.
	 */
	public function render_content() {

		// Get default options.
		$this_default = json_decode( $this->setting->default );

		// Get values.
		$values = $this->value();
		$json = json_decode( $values );

		if ( ! is_array( $json ) ) {
			$json = array( $values );
		}
		?>

		<div class="customizer-repeater-general-control-repeater customizer-repeater-general-control-droppable">
			<?php
			if ( ( count( $json ) == 1 && '' === $json[0] ) || empty( $json ) ) {
				if ( ! empty( $this_default ) ) {
					$this->iterate_array( $this_default ); ?>
					<input type="hidden"
					id="customizer-repeater-<?php echo esc_attr( $this->id ); ?>-colector" <?php esc_attr( $this->link() ); ?>
					class="customizer-repeater-colector"
					value="<?php echo esc_textarea( json_encode( $this_default ) ); ?>"/>
					<?php
				} else {
					$this->iterate_array(); ?>
					<input type="hidden"
					id="customizer-repeater-<?php echo esc_attr( $this->id ); ?>-colector" <?php esc_attr( $this->link() ); ?>
					class="customizer-repeater-colector"/>
					<?php
				}
			} else {
				$this->iterate_array( $json ); ?>
				<input type="hidden" id="customizer-repeater-<?php echo esc_attr( $this->id ); ?>-colector" <?php esc_attr( $this->link() ); ?>
				class="customizer-repeater-colector" value="<?php echo esc_textarea( $this->value() ); ?>"/>
				<?php
			} ?>
		</div>
		<button type="button" class="button add_field customizer-repeater-new-field">
			<?php esc_html_e( 'Add a Profile', 'shopkeeper-extender' ); ?>
		</button>

		<?php
	}

	/**
	 * Render the social media's content.
	 *
	 * @param array $array Social media profiles list.
	 */
	private function iterate_array( $array = array() ) {

		if( !empty( $array ) ) {
			foreach( $array as $icon ) {
				?>
				<div class="customizer-repeater-general-control-repeater-container customizer-repeater-draggable">
					<div class="customizer-repeater-customize-control-title">
						<?php echo !empty( $icon->title ) ? esc_html( $icon->title ) : esc_html( 'Social Media Profile', 'shopkeeper-extender' ); ?>
					</div>
					<div class="customizer-repeater-box-content-hidden">
						<?php

						$id 		= !empty( $icon->id ) ? $icon->id : '';
						$link 		= !empty( $icon->link ) ? $icon->link : '';
						$link_title = !empty( $icon->link_title ) ? $icon->link_title : '';
						$title 		= !empty( $icon->title ) ? $icon->title : '';
						$icon_slug 	= !empty( $icon->icon_slug ) ? $icon->icon_slug : '';
						$image_url 	= !empty( $icon->image_url ) ? $icon->image_url : '';

						$show_title = ( 'custom' === $icon_slug ) ? true : false;

						$this->icons_control( $icon_slug );
						$this->image_control( $image_url, $icon_slug );

						$this->input_control( array(
							'label' => apply_filters('repeater_input_labels_filter', esc_html__( 'Title:','shopkeeper-extender' ), $this->id, 'customizer_repeater_title_control' ),
							'class' => 'customizer-repeater-title-control',
							'type'  => apply_filters('customizer_repeater_input_types_filter', '', $this->id, 'customizer_repeater_title_control' ),
							'sanitize_callback' => 'esc_html',
						), $title, $show_title );

						$this->input_control( array(
							'label' => apply_filters('repeater_input_labels_filter', esc_html__( 'Link:','shopkeeper-extender' ), $this->id, 'customizer_repeater_link_control' ),
							'class' => 'customizer-repeater-link-control',
							'type'  => apply_filters('customizer_repeater_input_types_filter', '', $this->id, 'customizer_repeater_link_control' ),
							'sanitize_callback' => 'esc_url_raw',
						), $link );

						$this->input_control( array(
							'label' => apply_filters('repeater_input_labels_filter', esc_html__( 'Link Title:','shopkeeper-extender' ), $this->id, 'customizer_repeater_linktitle_control' ),
							'class' => 'customizer-repeater-linktitle-control',
							'type'  => apply_filters('customizer_repeater_input_types_filter', '', $this->id, 'customizer_repeater_linktitle_control' ),
							'sanitize_callback' => 'esc_html',
						), $link_title );

						?>

						<input type="hidden" class="social-repeater-box-id" value="<?php echo esc_attr( $id ); ?>">
						<button type="button" class="social-repeater-general-control-remove-field">
							<?php esc_html_e( 'Remove', 'shopkeeper-extender' ); ?>
						</button>
						<span>|</span>
						<button type="button" class="social-repeater-general-control-done-field">
							<?php esc_html_e( 'Done', 'shopkeeper-extender' ); ?>
						</button>
					</div>
				</div>
				<?php
			}
		} else { ?>
			<div class="customizer-repeater-general-control-repeater-container">
				<div class="customizer-repeater-customize-control-title">
					<?php esc_html_e( 'Social Media Profile', 'shopkeeper-extender' ); ?>
				</div>
				<div class="customizer-repeater-box-content-hidden">
					<?php

					$this->icons_control();
					$this->image_control();

					$this->input_control( array(
						'label' => apply_filters('repeater_input_labels_filter', esc_html__( 'Title:','shopkeeper-extender' ), $this->id, 'customizer_repeater_title_control' ),
						'class' => 'customizer-repeater-title-control',
						'type'  => apply_filters('customizer_repeater_input_types_filter', '', $this->id, 'customizer_repeater_title_control' ),
						'sanitize_callback' => 'esc_html',
					), '', false );

					$this->input_control( array(
						'label' => apply_filters('repeater_input_labels_filter', esc_html__( 'Link:','shopkeeper-extender' ), $this->id, 'customizer_repeater_link_control' ),
						'class' => 'customizer-repeater-link-control',
						'type'  => apply_filters('customizer_repeater_input_types_filter', '', $this->id, 'customizer_repeater_link_control' ),
						'sanitize_callback' => 'esc_url_raw',
					) );

					$this->input_control( array(
						'label' => apply_filters('repeater_input_labels_filter', esc_html__( 'Link Title:','shopkeeper-extender' ), $this->id, 'customizer_repeater_linktitle_control' ),
						'class' => 'customizer-repeater-linktitle-control',
						'type'  => apply_filters('customizer_repeater_input_types_filter', '', $this->id, 'customizer_repeater_linktitle_control' ),
						'sanitize_callback' => 'esc_html',
					) );

					?>
					<input type="hidden" class="social-repeater-box-id">
					<button type="button" class="social-repeater-general-control-remove-field">
						<?php esc_html_e( 'Remove', 'shopkeeper-extender' ); ?>
					</button>
					<span>|</span>
					<button type="button" class="social-repeater-general-control-done-field">
						<?php esc_html_e( 'Done', 'shopkeeper-extender' ); ?>
					</button>
				</div>
			</div>
			<?php
		}

		return;
	}

	/**
	 * Render the input field.
	 *
	 * @param array  $options Input settings.
	 * @param string $value Input value.
	 */
	 private function input_control( $options, $value = '', $show = true ) {
		?>
		<div class="<?php esc_attr_e( $options['class'] ); ?>-wrapper" <?php echo !$show ? 'style="display:none;"' : ''; ?>>
			<span class="customize-control-title"><?php esc_html_e( $options['label'] ); ?></span>
			<input type="text" value="<?php esc_attr_e( $value ); ?>" class="<?php esc_attr_e( $options['class'] ); ?>"/>
		</div>
		<?php

		return;
	}

	/**
	 * Render the theme default icon dropdown.
	 *
	 * @param string $value Select value.
	 */
	private function icons_control( $value = 'facebook' ) {
		?>
		<div class="customizer-repeater-icon-control">
			<span class="customize-control-title">
				<?php esc_html_e('Icon:','shopkeeper-extender'); ?>
			</span>

			<form class="customizer-repeater-icons">
				<?php foreach( $this->profiles as $profile ) { ?>
					<div>
						<input type="radio" name="customizer_repeater_icon" class="customizer-repeater-icon" value="<?php esc_attr_e( $profile['slug'] ); ?>" <?php checked( $value, $profile['slug'] ); ?>
						style="background-image:url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D\'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg\'%20viewBox%3D\'0%200%2050%2050\'%3E%3Cpath%20d%3D\'<?php esc_html_e( $profile['svg_path'] ); ?>\'%20fill%3D\'%23000000\'%2F%3E%3C%2Fsvg%3E');" />
						<span class="tooltip"><?php esc_attr_e( $profile['name'] ); ?></span>
					</div>
				<?php } ?>
				<div>
					<input type="radio" name="customizer_repeater_icon" class="customizer-repeater-icon" value="custom" <?php checked( $value, 'custom' ); ?>
					style="background-image:url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D\'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg\'%20viewBox%3D\'0%200%2024%2024\'%3E%3Cpath%20d%3D\'M 11 2 L 11 11 L 2 11 L 2 13 L 11 13 L 11 22 L 13 22 L 13 13 L 22 13 L 22 11 L 13 11 L 13 2 Z \'%20fill%3D\'%23000000\'%2F%3E%3C%2Fsvg%3E');" />
					<span class="tooltip"><?php esc_html_e( 'Custom Icon', 'shopkeeper-extender' ); ?></span>
				</div>
			</form>
		</div>
		<?php

		return;
	}

	/**
	 * Render the image control.
	 *
	 * @param string $value Select value.
	 * @param string $show Determines if the control should be displayed based on icon choice.
	 */
	private function image_control( $value = '', $icon_slug = '' ) {
		?>
		<div class="customizer-repeater-image-control" <?php echo ( $icon_slug !== 'custom' ) ? 'style="display:none;"' : ''; ?>>
			<span class="customize-control-title">
				<?php esc_html_e('Image:','shopkeeper-extender')?>
			</span>

			<input type="hidden" class="widefat custom-media-url" value="<?php echo esc_url( $value ); ?>">
			<img class="custom-media-url-preview" src="<?php echo esc_url( $value ); ?>" />
			<input type="button" class="button button-secondary customizer-repeater-custom-media-button" value="<?php echo !empty($value) ? esc_attr( 'Change Image','shopkeeper-extender' ) : esc_attr( 'Add Image','shopkeeper-extender' ); ?>" />
		</div>
		<?php

		return;
	}

	/**
	 * Enqueue control related scripts/styles.
	 */
	 public function enqueue() {

		wp_enqueue_style(
			'sk-ext-customizer-repeater-styles',
			plugins_url( 'assets/css/customizer_repeater.css', __FILE__ ),
			array(),
			SK_EXT_VERSION
		);

		wp_enqueue_script(
			'sk-ext-customizer-repeater-script',
			plugins_url( 'assets/js/customizer_repeater.js', __FILE__ ),
			array( 'jquery', 'jquery-ui-draggable' )
		);

		return;
	}
}
