/**
 * Theme Controller - Toggle Dark Mode
 *
 * Usage:
 *   <button data-controller="theme" data-action="click->theme#toggle">
 *     <svg data-theme-target="darkIcon" class="hidden">...</svg>
 *     <svg data-theme-target="lightIcon" class="hidden">...</svg>
 *   </button>
 */
import { Controller } from '../ogan-stimulus.js';

export default class ThemeController extends Controller {
    static targets = ['darkIcon', 'lightIcon'];

    connect() {
        // Initialise le thème
        this._initTheme();
        this._updateIcons();
    }

    toggle() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }
        this._updateIcons();
    }

    _initTheme() {
        if (localStorage.getItem('color-theme') === 'dark' ||
            (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    _updateIcons() {
        const isDark = document.documentElement.classList.contains('dark');

        if (this.hasDarkIconTarget) {
            this.darkIconTarget.classList.toggle('hidden', isDark);
        }
        if (this.hasLightIconTarget) {
            this.lightIconTarget.classList.toggle('hidden', !isDark);
        }
    }
}

// Initialiser le thème immédiatement (avant DOMContentLoaded pour éviter FOUC)
(function () {
    if (localStorage.getItem('color-theme') === 'dark' ||
        (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
})();
