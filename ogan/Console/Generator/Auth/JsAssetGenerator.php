<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📜 JS ASSET GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class JsAssetGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $jsDir = $projectRoot . '/public/assets/js';
        $this->ensureDirectory($jsDir);

        $assets = [
            'theme.js' => 'getThemeJs',
            'flashes.js' => 'getFlashesJs',
        ];

        foreach ($assets as $filename => $method) {
            $path = $jsDir . '/' . $filename;
            if (!$this->fileExists($path) || $force) {
                $this->writeFile($path, $this->$method());
                $generated[] = "public/assets/js/{$filename}";
            } else {
                $skipped[] = "public/assets/js/{$filename} (existe déjà)";
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getThemeJs(): string
    {
        return <<<'JS'
/**
 * Theme Toggle (Dark Mode)
 * Handles dark mode initialization and toggling with localStorage persistence
 */
(function() {
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

    // Initialize immediately
    initTheme();

    // Setup toggle button after DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            updateIcons();
            var toggleBtn = document.getElementById('theme-toggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleTheme);
            }
        });
    } else {
        updateIcons();
        var toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    }
})();
JS;
    }

    private function getFlashesJs(): string
    {
        return <<<'JS'
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
JS;
    }
}
