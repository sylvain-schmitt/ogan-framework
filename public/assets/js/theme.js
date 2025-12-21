/**
 * Theme Toggle (Dark Mode)
 * Handles dark mode initialization and toggling with localStorage persistence
 * Compatible HTMX: réinitialise après swap de contenu
 */
(function () {
    'use strict';

    // Initialize theme on page load (before DOM ready to avoid FOUC)
    function initTheme() {
        if (localStorage.getItem('color-theme') === 'dark' ||
            (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    // Update icons based on current theme
    function updateIcons() {
        var darkIcon = document.getElementById('theme-toggle-dark-icon');
        var lightIcon = document.getElementById('theme-toggle-light-icon');

        if (!darkIcon || !lightIcon) return;

        if (document.documentElement.classList.contains('dark')) {
            darkIcon.classList.add('hidden');
            lightIcon.classList.remove('hidden');
        } else {
            darkIcon.classList.remove('hidden');
            lightIcon.classList.add('hidden');
        }
    }

    // Toggle theme
    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }
        updateIcons();
    }

    // Setup toggle button (réutilisable pour HTMX)
    function setupToggleButton() {
        updateIcons();
        var toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn && !toggleBtn.hasAttribute('data-theme-initialized')) {
            toggleBtn.setAttribute('data-theme-initialized', 'true');
            toggleBtn.addEventListener('click', toggleTheme);
        }
    }

    // Initialize immediately
    initTheme();

    // Setup toggle button after DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupToggleButton);
    } else {
        setupToggleButton();
    }

    // HTMX: Réinitialiser après swap de contenu
    document.addEventListener('htmx:afterSwap', function (event) {
        // Réappliquer le thème et setup le bouton
        initTheme();
        setupToggleButton();
    });

    // HTMX: Également sur htmx:load pour les éléments chargés dynamiquement
    document.addEventListener('htmx:load', function (event) {
        setupToggleButton();
    });
})();

