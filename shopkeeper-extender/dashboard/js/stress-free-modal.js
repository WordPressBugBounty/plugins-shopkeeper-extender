/**
 * Stress-Free Plan Help Modal Functionality
 * 
 * Provides functionality for showing and hiding the stress-free plan help modal
 * with smooth transitions using jQuery.
 */
jQuery(document).ready(function($) {
    // Cache DOM elements
    const $modal = $('#stress-free-help-modal');
    const $showButton = $('#show-stress-free-help');
    const $closeButton = $('#close-stress-free-help');
    const $backdrop = $modal.find('.fixed.inset-0.bg-gray-500\\/75');
    const $modalContent = $modal.find('.relative.transform');
    
    // Show modal with animation
    const showModal = function() {
        // First make the modal visible but with opacity 0
        $modal.removeClass('hidden');
        $backdrop.css('opacity', 0);
        $modalContent.css({
            'opacity': 0,
            'transform': 'translate-y-4 scale-95'
        });
        
        // Trigger reflow before adding transitions
        $modal[0].offsetHeight;
        
        // Add transitions
        $backdrop.css({
            'transition': 'opacity 300ms ease-out',
            'opacity': 1
        });
        
        $modalContent.css({
            'transition': 'opacity 300ms ease-out, transform 300ms ease-out',
            'opacity': 1,
            'transform': 'translate-y-0 scale-100'
        });
    };
    
    // Hide modal with animation
    const hideModal = function() {
        // Add transitions for hiding
        $backdrop.css({
            'transition': 'opacity 200ms ease-in',
            'opacity': 0
        });
        
        $modalContent.css({
            'transition': 'opacity 200ms ease-in, transform 200ms ease-in',
            'opacity': 0,
            'transform': 'translate-y-4 scale-95'
        });
        
        // After animation completes, hide the modal
        setTimeout(function() {
            $modal.addClass('hidden');
            // Reset styles
            $backdrop.css('transition', '');
            $modalContent.css('transition', '');
        }, 200);
    };
    
    // Event handlers
    $showButton.on('click', showModal);
    $closeButton.on('click', hideModal);
    
    // Close modal when clicking on backdrop
    $modal.on('click', function(e) {
        if (e.target === this) {
            hideModal();
        }
    });
    
    // Close modal on ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && !$modal.hasClass('hidden')) {
            hideModal();
        }
    });
}); 