/**
 * Smooth scroll functionality for anchor links
 * 
 * This script provides smooth scrolling to anchor links on the page
 * with a small offset to account for fixed headers.
 */
jQuery(document).ready(function($) {
    // Function to handle smooth scrolling
    function smoothScrollTo(targetElement) {
        if (targetElement.length) {
            // Animate scroll to the target with a 50px offset (for fixed headers)
            $('html, body').animate({
                scrollTop: targetElement.offset().top - 50
            }, 500, 'swing');
        }
    }
    
    // Smooth scroll for "Renew Shopkeeper Support" button and "Learn more" link
    $('#renew-support-button, #learn-more-link').on('click', function(e) {
        // For links with href, prevent default behavior
        if ($(this).attr('href')) {
            e.preventDefault();
        }
        
        // Target the professional upgrade section
        var target = $('#professional-upgrade-section');
        
        // Use the common smooth scroll function
        smoothScrollTo(target);
    });
}); 