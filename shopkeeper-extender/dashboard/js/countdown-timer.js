/**
 * License Countdown Timer
 * 
 * Initializes a countdown timer with a random time between 43-57 minutes
 * Persists across page refreshes using sessionStorage
 * Only starts if license is active
 */
jQuery(document).ready(function($) {
    // Function to generate a random time between 43 and 57 minutes (in seconds)
    function getRandomTime() {
        const minMinutes = 43;
        const maxMinutes = 57;
        const randomMinutes = Math.floor(Math.random() * (maxMinutes - minMinutes + 1)) + minMinutes;
        return randomMinutes * 60; // Convert to seconds
    }
    
    // Get the countdown element and check if it exists
    const countdownEl = document.getElementById('countdown-timer');
    
    // Only proceed if the countdown element exists
    if (countdownEl) {
        // Check if license is active by detecting license status indicators in the page
        // The countdown timer is only displayed when !$is_support_active, but we want to check if $is_license_active
        // We can do this by looking for indicators that license is active
        const isLicenseActive = document.querySelector('.text-green-600 svg[fill="currentColor"][viewBox="0 0 20 20"]') !== null;
        
        // Only initialize the countdown if the license is active
        if (isLicenseActive) {
            // Get initial time (either from storage or generate new random time)
            const initialTime = getRandomTime();
            let timeLeft = initialTime;
            
            // Check if there's a stored timer value in sessionStorage
            const storedTime = sessionStorage.getItem('licenseCountdownTime');
            const timestamp = sessionStorage.getItem('licenseCountdownTimestamp');
            const hasInitialized = sessionStorage.getItem('licenseCountdownInitialized');
            
            // If we have stored values and the timer has been initialized before, use them
            if (storedTime && timestamp && hasInitialized) {
                const elapsed = Math.floor((Date.now() - parseInt(timestamp)) / 1000);
                timeLeft = Math.max(0, parseInt(storedTime) - elapsed);
            } else {
                // This is the first time, store the random initial time
                sessionStorage.setItem('licenseCountdownInitialized', 'true');
                sessionStorage.setItem('licenseCountdownTime', timeLeft);
                sessionStorage.setItem('licenseCountdownTimestamp', Date.now());
            }
            
            // Update the countdown timer display
            updateCountdown();
            
            // Set up the interval to update the countdown
            const countdownInterval = setInterval(function() {
                timeLeft--;
                
                // Store the current time and timestamp in sessionStorage
                sessionStorage.setItem('licenseCountdownTime', timeLeft);
                sessionStorage.setItem('licenseCountdownTimestamp', Date.now());
                
                // When timer reaches zero, reset it to a new random time
                if (timeLeft <= 0) {
                    timeLeft = getRandomTime();
                    sessionStorage.setItem('licenseCountdownTime', timeLeft);
                    sessionStorage.setItem('licenseCountdownTimestamp', Date.now());
                }
                
                updateCountdown();
            }, 1000);
            
            // Function to update the countdown display
            function updateCountdown() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                countdownEl.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            }
        }
    }
}); 