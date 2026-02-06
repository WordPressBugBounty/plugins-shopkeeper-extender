/**
 * License Key Validator
 *
 * Validates the license key field and manages the submit button state
 * based on validation results.
 */
jQuery(document).ready(function($) {
    // License key input element
    const $licenseKeyInput = $('#license_key');
    // Submit buttons (both Activate and Deactivate)
    const $submitButtons = $('button[name="save_license"]');
    
    // License key pattern - only checks for correct number of characters in each segment (8-4-4-4-12)
    const LICENSE_KEY_PATTERN = /^.{8}-.{4}-.{4}-.{4}-.{12}$/;
    
    // Error message container
    let $errorContainer = $('<div class="license-validation-error text-[var(--color-wp-red)] text-sm mt-1" style="display: none;"></div>');
    $licenseKeyInput.after($errorContainer);
    
    // Flag to track if user has interacted with the input
    let userInteracted = false;
    
    /**
     * Validate the license key
     * 
     * @return {Object} Validation result with isValid and message
     */
    function validateLicenseKey() {
        const licenseKey = $licenseKeyInput.val().trim();
        
        // Check if we're in deactivation mode (license key field is being cleared)
        const isDeactivation = licenseKey === '' && $licenseKeyInput.data('has-value') === true;
        
        // If it's a deactivation, allow it to proceed
        if (isDeactivation) {
            return { isValid: true, message: '' };
        }
        
        // If empty, show error message only if user has interacted with the field
        if (licenseKey === '') {
            return { 
                isValid: false, 
                message: userInteracted ? 'Purchase code is required' : '' 
            };
        }
        
        // Check if the license key matches the pattern
        if (!LICENSE_KEY_PATTERN.test(licenseKey)) {
            return { isValid: false, message: 'Invalid purchase code format. Please enter a code in 8-4-4-4-12 format.' };
        }
        
        return { isValid: true, message: '' };
    }
    
    /**
     * Update UI based on validation results
     */
    function updateUI() {
        const result = validateLicenseKey();
        
        if (result.isValid) {
            $errorContainer.hide();
            $submitButtons.prop('disabled', false);
            $submitButtons.removeClass('opacity-50 cursor-not-allowed');
        } else {
            if (result.message) {
                $errorContainer.text(result.message).show();
            } else {
                $errorContainer.hide();
            }
            $submitButtons.prop('disabled', true);
            $submitButtons.addClass('opacity-50 cursor-not-allowed');
        }
    }
    
    // Store initial value state
    $licenseKeyInput.data('has-value', $licenseKeyInput.val().trim() !== '');
    
    // Set up event listeners
    $licenseKeyInput.on('input', function() {
        userInteracted = true;
        updateUI();
    });
    
    // Also mark as interacted on focus
    $licenseKeyInput.on('focus', function() {
        userInteracted = true;
    });
    
    // Validate on form submission
    $('form').on('submit', function(e) {
        userInteracted = true;
        const result = validateLicenseKey();
        if (!result.isValid) {
            e.preventDefault();
            updateUI();
        }
    });
    
    // Initial validation without showing "required" error
    updateUI();
    
    // Handle the deactivation button click
    $('button[name="save_license"]').on('click', function() {
        if ($(this).text().trim().includes('Deactivate')) {
            // Set the license key to empty
            $licenseKeyInput.val('');
            // Mark this as an intentional deactivation
            $licenseKeyInput.data('has-value', true);
            // Update the UI
            updateUI();
        }
    });
}); 