/**
 * License Subscription Notification Handler
 * 
 * Handles dismissal of license subscription notifications
 */
(function($) {
    'use strict';
    
    // Configuration
    const config = {
        autoExpandNoLicense: false // Set to true to auto-expand no-license notifications
    };
    
    // Wait for DOM to be ready
    $(function() {
        // Target both expired and expiring soon notifications
        const $notifications = $('.gbt-dashboard-notification[data-message-id^="license_subscription_"], .getbowtied_ext_notice');
        
        if (!$notifications.length) {
            return;
        }
        
        // Auto-expand no-license notification when configured
        if (config.autoExpandNoLicense && !window.location.href.includes('page=getbowtied-license')) {
            // Add a slight delay to ensure DOM is fully loaded and ready
            setTimeout(function() {
                const $noLicenseNotification = $('.no-license-notification');
                if ($noLicenseNotification.length) {
                    const $content = $noLicenseNotification.find('.getbowtied_ext_notice__content');
                    $content.addClass('expanded');
                    $content.find('.getbowtied_ext_notice__toggle_link').text('Hide details');
                }
            }, 500); // 500ms delay
        }
        
        // Function to toggle content visibility
        function toggleContent($content) {
            // Toggle the expanded class
            $content.toggleClass('expanded');
            
            // Update the More details link text based on expanded state
            const $link = $content.find('.getbowtied_ext_notice__toggle_link');
            $link.text($content.hasClass('expanded') ? 'Hide details' : 'Click for details');
        }
        
        // Toggle collapsible content when clicking on title
        $notifications.on('click', '.title', function(e) {
            e.preventDefault();
            const $content = $(this).closest('.getbowtied_ext_notice__content');
            toggleContent($content);
        });
        
        // Toggle collapsible content when clicking on the "More details" link
        $notifications.on('click', '.getbowtied_ext_notice__toggle_link', function(e) {
            e.preventDefault();
            const $content = $(this).closest('.getbowtied_ext_notice__content');
            toggleContent($content);
        });
        
        // Handle both "View License Details" and "Activate Your License Now" button clicks
        $notifications.on('click', 'a[href*="page=getbowtied-license"]', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            
            // Navigate to the license page if we're not already there
            if (!window.location.href.includes('page=getbowtied-license')) {
                // Simply navigate to the URL without any scroll parameter
                window.location.href = href;
                return;
            }
            
            // If we're already on the license page, scroll to the dashboard scope
            scrollToDashboard();
        });
        
        // Check if we need to scroll on page load (coming from external page)
        function scrollToDashboard() {
            var $target = $('.gbt-dashboard-scope');
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top
                }, 500);
            }
        }
        
        // Handle dismiss button click - updated for individual reminder buttons
        $notifications.on('click', '.dismiss-notification', function(e) {
            // Prevent default action
            e.preventDefault();
            
            const $button = $(this);
            const messageId = $button.data('message-id');
            const themeSlug = $button.data('theme-slug');
            const days = $button.data('days');
            const $notification = $button.closest('.gbt-dashboard-notification');
            
            // Validate data attributes
            if (!messageId || !themeSlug) {
                console.error('Missing required data attributes for notification dismissal');
                return;
            }
            
            // Send AJAX request
            $.ajax({
                url: gbtLicenseSubscriptionData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dismiss_license_subscription_notification',
                    message_id: messageId,
                    theme_slug: themeSlug,
                    days: days,
                    nonce: gbtLicenseSubscriptionData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove the notification
                        $notification.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        console.error('Error dismissing notification:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        });
        
        // For backward compatibility, keep the original notice-dismiss handler
        $notifications.on('click', '.notice-dismiss', function() {
            const $notification = $(this).closest('.gbt-dashboard-notification');
            const messageId = $notification.data('message-id');
            const themeSlug = $notification.data('theme-slug');
            
            // Validate data attributes
            if (!messageId || !themeSlug) {
                console.error('Missing required data attributes for notification dismissal');
                return;
            }
            
            // Send AJAX request
            $.ajax({
                url: gbtLicenseSubscriptionData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dismiss_license_subscription_notification',
                    message_id: messageId,
                    theme_slug: themeSlug,
                    nonce: gbtLicenseSubscriptionData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove the notification
                        $notification.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        console.error('Error dismissing notification:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        });
    });
})(jQuery); 