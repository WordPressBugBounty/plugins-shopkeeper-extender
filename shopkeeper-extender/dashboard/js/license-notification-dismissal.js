/**
 * License Notification Dismissal Handler
 * 
 * Handles permanent dismissal of license status notifications
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle WordPress native dismiss button clicks on notices with IDs
        $(document).on('click', '.notice[id].is-dismissible .notice-dismiss', function(e) {
            var $notice = $(this).closest('.notice[id]');
            var messageId = $notice.attr('id');
            
            // Only process if ID exists and looks like our hash (12 chars alphanumeric)
            if (!messageId || !/^[a-f0-9]{12}$/.test(messageId)) return;
            
            // Send AJAX request to save dismissal permanently
            $.ajax({
                url: licenseNotificationData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dismiss_license_notification',
                    message_id: messageId,
                    nonce: licenseNotificationData.nonce
                }
            });
        });
    });

})(jQuery);

