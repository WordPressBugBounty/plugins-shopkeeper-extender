jQuery(document).ready(function($) {
    $('.gbt-dashboard-notification').on('click', '.notice-dismiss', function() {
        var messageId = $(this).parent().data('message-id');
        var themeSlug = $(this).parent().data('theme-slug');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dismiss_gbt_dashboard_notification',
                message_id: messageId,
                theme_slug: themeSlug,
                nonce: gbtDashboard.nonce
            }
        });
    });
}); 