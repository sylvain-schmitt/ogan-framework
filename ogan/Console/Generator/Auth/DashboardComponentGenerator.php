<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🧩 DASHBOARD COMPONENT GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class DashboardComponentGenerator extends AbstractGenerator
{
    private bool $htmx = false;

    public function generate(string $projectRoot, bool $force = false, bool $htmx = false): array
    {
        $this->htmx = $htmx;
        $generated = [];
        $skipped = [];

        // Dashboard components
        $dashboardComponentsDir = $projectRoot . '/templates/components/dashboard';
        $this->ensureDirectory($dashboardComponentsDir);

        $dashboardComponents = [
            'sidebar.ogan' => 'getSidebarTemplate',
            'navbar.ogan' => 'getNavbarTemplate',
        ];

        foreach ($dashboardComponents as $filename => $method) {
            $path = $dashboardComponentsDir . '/' . $filename;
            if (!$this->fileExists($path) || $force) {
                $this->writeFile($path, $this->$method());
                $generated[] = "templates/components/dashboard/{$filename}";
            } else {
                $skipped[] = "templates/components/dashboard/{$filename} (existe déjà)";
            }
        }

        // Flashes component
        $componentsDir = $projectRoot . '/templates/components';
        $this->ensureDirectory($componentsDir);
        
        $flashesPath = $componentsDir . '/flashes.ogan';
        if (!$this->fileExists($flashesPath) || $force) {
            $this->writeFile($flashesPath, $this->getFlashesTemplate());
            $generated[] = 'templates/components/flashes.ogan';
        } else {
            $skipped[] = 'templates/components/flashes.ogan (existe déjà)';
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getSidebarTemplate(): string
    {
        // Avec HTMX, on peut utiliser hx-boost pour la navigation
        $hxBoost = $this->htmx ? ' hx-boost="true"' : '';

        return <<<HTML
<div class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-sm transition-transform -translate-x-full md:translate-x-0" id="sidebar">
    <div class="flex items-center justify-center h-16 border-b border-gray-200 dark:border-gray-700 px-6">
        <span class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">DELNYX</span>
    </div>
    
    <nav class="p-4 space-y-1"{$hxBoost}>
        <a href="{{ path('dashboard_index') }}" class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 group transition-colors">
            <svg class="w-5 h-5 mr-3 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Gestion</p>
        </div>
        
        <a href="#" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 group transition-colors">
            <svg class="w-5 h-5 mr-3 text-gray-400 dark:text-gray-500 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Utilisateurs
        </a>

        <a href="#" class="flex items-center px-4 py-3 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 group transition-colors">
            <svg class="w-5 h-5 mr-3 text-gray-400 dark:text-gray-500 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Documents
        </a>
    </nav>
</div>
HTML;
    }

    private function getNavbarTemplate(): string
    {
        $hxBoost = $this->htmx ? ' hx-boost="true"' : '';

        return <<<HTML
<header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
    <!-- Mobile menu button -->
    <button type="button" class="md:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none" onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    </button>

    <div class="flex-1 flex justify-end items-center space-x-4">
        
        <!-- Dark Mode Toggle -->
        <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
            <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
        </button>

        <!-- User Dropdown -->
        <div class="relative ml-3 group">
            <button type="button" class="peer flex items-center max-w-xs text-sm bg-white dark:bg-gray-800 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                <span class="sr-only">Open user menu</span>
                <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold">
                    {{ app.user.name|first|upper }}
                </div>
                <span class="ml-3 hidden md:block text-sm font-medium text-gray-700 dark:text-gray-200">{{ app.user.name }}</span>
                <svg class="ml-2 h-5 w-5 text-gray-400 group-hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            </button>
            
            <!-- Dropdown menu - appears on focus or hover -->
            <div class="hidden peer-focus:block hover:block focus-within:block absolute right-0 w-56 mt-2 origin-top-right bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Connecté en tant que</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ app.user.email }}</p>
                </div>
                <div class="py-1">
                    <a href="{{ path('user_profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Mon Profil
                    </a>
                </div>
                <div class="border-t border-gray-100 dark:border-gray-700 py-1">
                    <a href="{{ path('logout') }}" class="flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
HTML;
    }

    private function getFlashesTemplate(): string
    {
        return <<<'OGAN'
{% for type, messages in getAllFlashes() %}
	{% for message in messages %}
		<div class="flash-message mb-4 px-4 py-3 rounded-lg relative flex items-center justify-between {% if type == 'success' %}bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300{%
		 elseif type == 'error' %}bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300{%
		 elseif type == 'warning' %}bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300{%
		 elseif type == 'info' %}bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300{%
		 else %}bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300{% endif %}">
			<span>{{ message }}</span>
			<button type="button" onclick="this.parentElement.remove()" class="ml-4 inline-flex items-center justify-center p-1 rounded-full hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
				<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
					<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
				</svg>
			</button>
		</div>
	{% endfor %}
{% endfor %}

<script src="{{ asset('/assets/js/flashes.js') }}"></script>
OGAN;
    }
}
