<?php

function sk_bool_to_string( $bool ) {
	$bool = is_bool( $bool ) ? $bool : ( 'yes' === $bool || 1 === $bool || 'true' === $bool || '1' === $bool );

	return true === $bool ? 'yes' : 'no';
}

function sk_string_to_bool( $string ) {
	return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string );
}

function sk_sanitize_checkbox( $input ) {
	return sk_bool_to_string($input);
}

function sk_sanitize_repeater( $input ) {
	$input_decoded = json_decode($input,true);

	if(!empty($input_decoded)) {
		foreach ($input_decoded as $boxk => $box ){
			foreach ($box as $key => $value){
				$input_decoded[$boxk][$key] = wp_kses_post( force_balance_tags( $value ) );
			}
		}

		return json_encode($input_decoded);
	}

	return $input;
}

/**
 * Checks if topbar is enabled.
 */
function sk_ext_is_topbar_enabled(){

    return get_theme_mod( 'top_bar_switch', false );
}

// Other Plugins
function gbt_other_plugins_enqueue_scripts() {
   wp_enqueue_script( 'gbt-other-plugins', plugins_url( 'js/other_plugins.js', __FILE__ ), array('jquery') );
}
add_action( 'admin_enqueue_scripts', 'gbt_other_plugins_enqueue_scripts' );

// Admin Notices
function shopkeeper_extender_admin_notices_enqueue_scripts() {
   wp_enqueue_script( 'shopkeeper-extender-admin-notices-js', plugins_url( 'js/admin_notices.js', __FILE__ ), array('jquery') );
   wp_enqueue_style( 'shopkeeper-extender-admin-notices-css', plugins_url( 'css/admin_notices.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'shopkeeper_extender_admin_notices_enqueue_scripts' );
