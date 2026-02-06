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
    
    // Handle all anchor links that point to #license-options or #license-area
    $('a[href*="#license-options"], a[href*="#license-area"]').on('click', function(e) {
        e.preventDefault();
        
        // Extract the hash from the href
        var hash = $(this).attr('href').split('#')[1];
        var target = $('#' + hash);
        
        // Use the common smooth scroll function
        smoothScrollTo(target);
    });
}); 
