jQuery(function($) {

	"use strict";

	// dismiss dashboard notices
	$( document ).on( 'click', '.black_friday_2023_notice .notice-dismiss', function () {
		var data = {
            'action' : 'shopkeeper_extender_dismiss_dashboard_notice',
            'notice' : 'black_friday_2023'
        };

        jQuery.post( 'admin-ajax.php', data );
	});
});
