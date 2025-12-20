<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📊 DASHBOARD TEMPLATE GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class DashboardTemplateGenerator extends AbstractGenerator
{
    private bool $htmx = false;

    public function generate(string $projectRoot, bool $force = false, bool $htmx = false): array
    {
        $this->htmx = $htmx;
        $generated = [];
        $skipped = [];

        $dashboardDir = $projectRoot . '/templates/dashboard';
        $this->ensureDirectory($dashboardDir);

        $templates = [
            'layout.ogan' => 'getLayoutTemplate',
            'index.ogan' => 'getIndexTemplate',
        ];

        foreach ($templates as $filename => $method) {
            $path = $dashboardDir . '/' . $filename;
            if (!$this->fileExists($path) || $force) {
                $this->writeFile($path, $this->$method());
                $generated[] = "templates/dashboard/{$filename}";
            } else {
                $skipped[] = "templates/dashboard/{$filename} (existe déjà)";
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getLayoutTemplate(): string
    {
        $htmxScript = $this->htmx ? "\n    {{ htmx_script() }}" : '';
        $hxBoostAttr = $this->htmx ? ' hx-boost="true" hx-target="#page-content" hx-swap="innerHTML" hx-select="#page-content"' : '';

        return <<<HTML
{{ title = title ?? 'Dashboard' }}
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ title }} - Delnyx</title>
    <link rel="stylesheet" href="{{ asset('/assets/css/app.css') }}">
    <!-- Theme Script (in head to avoid FOUC) -->
    <script src="{{ asset('/assets/js/theme.js') }}"></script>
</head>
<body class="h-full dark:bg-gray-900 transition-colors duration-200"{$hxBoostAttr}>

    <div id="page-content">
        <div class="min-h-full">
            
            <!-- Sidebar -->
            {{ component('dashboard/sidebar') }}

            <!-- Main Content -->
            <div class="md:pl-64 flex flex-col min-h-screen transition-all duration-300">
                
                <!-- Navbar -->
                {{ component('dashboard/navbar', ['user' => user]) }}

                <!-- Main Content Area -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-900">
                    {{ component('flashes') }}

                    {{ section('content') }}
                </main>
            </div>
        </div>
    </div>{$htmxScript}
</body>
</html>
HTML;
    }

    private function getIndexTemplate(): string
    {
        return <<<'HTML'
{{ extend('dashboard/layout.ogan') }}

{{ start('content') }}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Bienvenue, {{ app.user.name }}</h1>
        <p class="text-gray-600 dark:text-gray-300">
            Ceci est votre tableau de bord. Commencez à gérer votre application dès maintenant.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Stat Card 1 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Utilisateurs</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">1,234</p>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Revenus</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">€45,678</p>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Documents</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">89</p>
                </div>
            </div>
        </div>
    </div>
{{ end }}
HTML;
    }
}
