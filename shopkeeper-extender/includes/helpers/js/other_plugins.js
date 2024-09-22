jQuery(function($) {

	"use strict";

	// dismiss dashboard notices
	$( document ).on( 'click', '.remove-additional-js-notice .notice-dismiss', function () {
		var data = {
            'action' : 'sk_ext_notifications_dismiss',
            'notice' : 'remove-additional-js-notice'
        };

        jQuery.post( 'admin-ajax.php', data );
	});

	//Custom CSS and JS
	$(".ccj__notice a:not(.dismiss_notice), .ccj_only_premium a")
		.attr("href", "https://www.silkypress.com/simple-custom-css-js-pro/?ref=getbowtied");
});