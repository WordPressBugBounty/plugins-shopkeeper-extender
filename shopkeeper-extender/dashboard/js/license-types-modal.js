/**
 * License Types Help Modal Functionality
 * 
 * Provides functionality for showing and hiding the license types help modal
 * that explains the differences between regular and extended licenses from Envato.
 */
jQuery(document).ready(function ($) {
    // Reusable modal functionality
    const createModalHandler = function (modalId, showButtonSelector, closeButtonId) {
        const $modal = $(modalId);
        const $showButton = $(showButtonSelector);
        const $closeButton = $(closeButtonId);
        const $backdrop = $modal.find('.fixed.inset-0.bg-gray-500\\/75');
        const $modalContent = $modal.find('.relative.transform');

        // Show modal with animation
        const showModal = function () {
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
        const hideModal = function () {
            // Stop any YouTube videos in the modal by sending pause command
            $modal.find('iframe[src*="youtube.com"], iframe[src*="youtu.be"]').each(function () {
                const iframe = this;
                try {
                    // Pause video using YouTube iframe API postMessage
                    iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
                } catch (e) {
                    // Silently fail if postMessage doesn't work
                    console.log('Could not pause YouTube video:', e);
                }
            });

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
            setTimeout(function () {
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
        $backdrop.on('click', function (e) {
            if (e.target === this) {
                hideModal();
            }
        });

        // Close modal on ESC key
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && !$modal.hasClass('hidden')) {
                hideModal();
            }
        });
    };

    // Initialize license types help modal
    createModalHandler(
        '#license-types-help-modal',
        '.show-license-types-help',
        '#close-license-types-help'
    );

    // Initialize video-only modal
    createModalHandler(
        '#video-only-modal',
        '.show-video-modal',
        '#close-video-modal'
    );
}); 