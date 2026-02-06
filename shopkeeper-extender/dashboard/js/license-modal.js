/**
 * License Help Modal Functionality
 * 
 * Provides functionality for showing and hiding the license help modal
 * with smooth transitions using jQuery.
 */
jQuery(document).ready(function($) {
    // Generic modal handler function
    function initModal(modalId, showButtonId, closeButtonId) {
        const $modal = $('#' + modalId);
        if (!$modal.length) return;
        
        const $showButton = $('#' + showButtonId);
        const $closeButton = $('#' + closeButtonId);
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
            
            // Prevent body scroll
            $('body').css('overflow', 'hidden');
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
                // Restore body scroll
                $('body').css('overflow', '');
            }, 200);
        };
        
        // Event handlers
        if ($showButton.length) {
            $showButton.on('click', function(e) {
                e.preventDefault();
                showModal();
            });
        }
        
        if ($closeButton.length) {
            $closeButton.on('click', function(e) {
                e.preventDefault();
                hideModal();
            });
        }
        
        // Close modal when clicking on backdrop
        $backdrop.on('click', function(e) {
            if (e.target === this) {
                hideModal();
            }
        });
        
        // Close modal on ESC key
        $(document).on('keydown.modal-' + modalId, function(e) {
            if (e.key === 'Escape' && !$modal.hasClass('hidden')) {
                hideModal();
            }
        });
    }
    
    // Initialize License Help Modal
    initModal('license-help-modal', 'show-license-help', 'close-license-help');
    
    // Initialize Low Star Reviews Modal
    initModal('low-star-reviews-modal', 'show-low-star-reviews-modal', 'close-low-star-reviews-modal');
}); 