/**
 * Flash Messages Auto-Dismiss
 * Automatically dismisses flash messages after 5 seconds
 */
(function() {
    'use strict';

    function initFlashMessages() {
        var flashes = document.querySelectorAll('.flash-message');
        flashes.forEach(function(flash) {
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                flash.style.transition = 'opacity 0.3s ease-out';
                flash.style.opacity = '0';
                setTimeout(function() {
                    flash.remove();
                }, 300);
            }, 5000);
        });
    }

    // Initialize on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFlashMessages);
    } else {
        initFlashMessages();
    }
})();