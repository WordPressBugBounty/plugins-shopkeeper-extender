<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Check and declare the first function
if (!function_exists('gbt_display_welcome_pointer')) {
    function gbt_display_welcome_pointer() {
        // Create an instance of the GBT_Dashboard_Setup class
        $gbt_dashboard_setup = GBT_Dashboard_Setup::init();
        
        // Get the theme name using the getter method
        $theme_name_gbt_dash = $gbt_dashboard_setup->get_theme_name();
        
        wp_enqueue_script( 'jquery' );
        wp_enqueue_style( 'wp-pointer' );
        wp_enqueue_script( 'wp-pointer' );
        
        if (
            ! get_user_meta(
                get_current_user_id(),
                'getbowtied-welcome-pointer-dismissed',
                true
            )
        ):
        ?>
            <style>
                /*.getbowtied-welcome-pointer {

                }
                .getbowtied-welcome-pointer h3 {
                    background: #FF5A44;
                    border-color: #FF5A44;
                }
                .getbowtied-welcome-pointer h3:before {
                    color: #FF5A44;
                    content: "\f174";
                }*/
                .custom-pointer-buttons {
                    padding: 5px 15px;
                    text-align: right;
                }
                .custom-pointer-dismiss {
                    padding: 3px 10px;
                    position: relative;
                    text-decoration: none;
                }
            </style>

            <script>
            jQuery(
                function() {
                    jQuery('#toplevel_page_getbowtied-dashboard').pointer( 
                        {
                            content:
                                "<h3><?php echo esc_html($theme_name_gbt_dash); ?> Dashboard</h3>" +
                                "<h4>Welcome to <?php echo esc_html($theme_name_gbt_dash); ?> Dashboard!</h4>" +
                                "<p>Here you will find templates, kits, plugins, useful links, documentation and help.</p>" +
                                "<p>If you get lost, come back here.</p>" +
                                "<div class='custom-pointer-buttons'>" +
                                "<button class='button-secondary custom-pointer-dismiss'>Dismiss</button>" +
                                "</div>",

                            position:
                                {
                                    edge:  'left',
                                    align: 'center'
                                },

                            pointerClass:
                                'getbowtied-welcome-pointer',

                            pointerWidth: 500,
                            
                            buttons: function() {}, // Disable default buttons
                            
                        }
                    ).pointer('open');

                    // Add custom dismiss button handler
                    jQuery('.custom-pointer-dismiss').on('click', function() {
                        jQuery('#toplevel_page_getbowtied-dashboard').pointer('close');
                        jQuery.post(
                            ajaxurl,
                            {
                                pointer: 'getbowtied-welcome-pointer',
                                action: 'dismiss-getbowtied-welcome-pointer',
                            }
                        );
                    });
                }
            );
            </script>

        <?php
        endif;
    }
}
add_action( 'in_admin_footer', 'gbt_display_welcome_pointer' );

// Check and declare the second function
if (!function_exists('gbt_handle_welcome_pointer_dismiss')) {
    function gbt_handle_welcome_pointer_dismiss() {
        if ( isset( $_POST['action'] ) && 'dismiss-getbowtied-welcome-pointer' == $_POST['action'] ) {
            update_user_meta(
                get_current_user_id(),
                'getbowtied-welcome-pointer-dismissed',
                sanitize_text_field( wp_unslash( $_POST['pointer'] ) ),
                true
            );
        }
    }
}
add_action( 'admin_init', 'gbt_handle_welcome_pointer_dismiss' );

// Check and declare the reset function
if (!function_exists('gbt_reset_getbowtied_welcome_pointer')) {
    function gbt_reset_getbowtied_welcome_pointer() {
        delete_metadata( 'user', null, 'getbowtied-welcome-pointer-dismissed', null, true );
    }
}
add_action( 'after_switch_theme', 'gbt_reset_getbowtied_welcome_pointer' );