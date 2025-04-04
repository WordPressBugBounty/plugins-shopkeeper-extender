/**
 * GetBowtied License Refresh
 * 
 * Handles AJAX-based license refresh functionality
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Get elements
        var $refreshBtn = $('#refresh-license-btn');
        var $refreshStatus = $('#refresh-status');
        var $licenseResponse = $('#license-response');
        
        // Handle refresh button click
        $refreshBtn.on('click', function(e) {
            e.preventDefault();
            
            // Show refreshing status
            $refreshStatus.removeClass('hidden text-green-500 text-red-600')
                         .addClass('text-gray-700')
                         .text(gbtLicenseRefresh.refreshing_text);
            
            // Hide any previous responses
            $licenseResponse.addClass('hidden').empty();
            
            // Disable button during request
            $refreshBtn.prop('disabled', true).addClass('opacity-70');
            
            // Make AJAX request
            $.ajax({
                url: gbtLicenseRefresh.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gbt_refresh_license',
                    nonce: gbtLicenseRefresh.nonce,
                    theme_slug: gbtLicenseRefresh.theme_slug,
                    theme_id: gbtLicenseRefresh.theme_id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update UI for success
                        updateUIOnSuccess(response.data);
                    } else {
                        // Update UI for error
                        updateUIOnError(response.data);
                    }
                },
                error: function() {
                    // Show general error
                    $refreshStatus.removeClass('text-gray-700')
                                 .addClass('text-red-600')
                                 .text(gbtLicenseRefresh.error_text);
                },
                complete: function() {
                    // Re-enable button
                    $refreshBtn.prop('disabled', false).removeClass('opacity-70');
                }
            });
        });
        
        /**
         * Update UI on successful license refresh
         */
        function updateUIOnSuccess(data) {
            // Update status display
            $refreshStatus.removeClass('text-gray-700')
                         .addClass('text-green-500')
                         .text(gbtLicenseRefresh.success_text);
                         
            // Update license status
            updateLicenseStatus(data.license_status);
            
            // Update support status based on expiration date
            if (data.is_support_active !== undefined) {
                updateSupportStatus(data.is_support_active ? 'active' : 'expired');
            }
            
            // Update last verified date
            if (data.last_verified) {
                $('#last-verified-date').text(data.last_verified);
            }
            
            // Show success message
            $licenseResponse.removeClass('hidden border-red-200 bg-red-50 text-red-700')
                           .addClass('border-green-200 bg-green-50 text-green-700')
                           .html('<p>' + data.message + '</p>');
        }
        
        /**
         * Update UI on license refresh error
         */
        function updateUIOnError(data) {
            // Update status display
            $refreshStatus.removeClass('text-gray-700')
                         .addClass('text-red-600')
                         .text(gbtLicenseRefresh.error_text);
                         
            // Update status if provided
            if (data.license_status) {
                updateLicenseStatus(data.license_status);
            }
            
            // Update support status based on is_support_active
            if (data.is_support_active !== undefined) {
                updateSupportStatus(data.is_support_active ? 'active' : 'expired');
            }
            
            // Show error message
            $licenseResponse.removeClass('hidden border-green-200 bg-green-50 text-green-700')
                           .addClass('border-red-200 bg-red-50 text-red-700')
                           .html('<p>' + data.message + '</p>');
        }
        
        /**
         * Update license status display
         */
        function updateLicenseStatus(status) {
            var $licenseStatus = $('#license-status');
            var $licenseStatusText = $('#license-status-text');
            
            // Update text
            $licenseStatusText.text(status.charAt(0).toUpperCase() + status.slice(1));
            
            // Update styling
            if (status === 'active') {
                $licenseStatus.removeClass('text-red-600').addClass('text-green-500');
                $licenseStatus.html(
                    '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">' +
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />' +
                    '</svg>' +
                    '<span id="license-status-text">Active</span>'
                );
            } else {
                $licenseStatus.removeClass('text-green-500').addClass('text-red-600');
                $licenseStatus.html(
                    '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">' +
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />' +
                    '</svg>' +
                    '<span id="license-status-text">Inactive</span>'
                );
            }
        }
        
        /**
         * Update support status display
         */
        function updateSupportStatus(status) {
            var $supportStatus = $('#support-status');
            var $supportStatusText = $('#support-status-text');
            
            // Update text
            $supportStatusText.text(status.charAt(0).toUpperCase() + status.slice(1));
            
            // Update styling
            if (status === 'active') {
                $supportStatus.removeClass('text-red-600').addClass('text-green-500');
                $supportStatus.html(
                    '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">' +
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />' +
                    '</svg>' +
                    '<span id="support-status-text">Active</span>'
                );
            } else {
                $supportStatus.removeClass('text-green-500').addClass('text-red-600');
                $supportStatus.html(
                    '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">' +
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />' +
                    '</svg>' +
                    '<span id="support-status-text">Inactive</span>'
                );
            }
        }
    });
    
})(jQuery); 