/**
 * License Support Notification Handler
 * 
 * Handles dismissal of license support expired notifications
 */
jQuery(document).ready(function($) {
    // Handle notification dismissal
    $('.gbt-dashboard-notification[data-message-id="license_support_expired"]').on('click', '.notice-dismiss', function() {
        var $notification = $(this).closest('.gbt-dashboard-notification');
        var messageId = $notification.data('message-id');
        var themeSlug = $notification.data('theme-slug');
        
        // Send AJAX request to dismiss the notification
        $.ajax({
            url: gbtLicenseSupportData.ajaxurl,
            type: 'POST',
            data: {
                action: 'dismiss_license_support_notification',
                message_id: messageId,
                theme_slug: themeSlug,
                nonce: gbtLicenseSupportData.nonce
            },
            success: function(response) {
                // Notification is now dismissed on the server
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    });
}); 