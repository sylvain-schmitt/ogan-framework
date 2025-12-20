<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📄 PAGINATION TEMPLATE GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Pagination;

use Ogan\Console\Generator\AbstractGenerator;

class PaginationTemplateGenerator extends AbstractGenerator
{
    private string $modelName;
    private bool $htmx;

    public function __construct(string $modelName, bool $htmx = false)
    {
        $this->modelName = $modelName;
        $this->htmx = $htmx;
    }

    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $modelLower = strtolower($this->modelName);
        $templateDir = $projectRoot . '/templates/' . $modelLower;
        $this->ensureDirectory($templateDir);

        // Template principal (list.ogan)
        $listPath = $templateDir . '/list.ogan';
        if (!$this->fileExists($listPath) || $force) {
            $this->writeFile($listPath, $this->getListTemplate());
            $generated[] = "templates/{$modelLower}/list.ogan";
        } else {
            $skipped[] = "templates/{$modelLower}/list.ogan (existe déjà)";
        }

        // Template partiel (seulement si HTMX)
        if ($this->htmx) {
            $partialPath = $templateDir . '/_list_partial.ogan';
            if (!$this->fileExists($partialPath) || $force) {
                $this->writeFile($partialPath, $this->getPartialTemplate());
                $generated[] = "templates/{$modelLower}/_list_partial.ogan";
            } else {
                $skipped[] = "templates/{$modelLower}/_list_partial.ogan (existe déjà)";
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getListTemplate(): string
    {
        $modelLower = strtolower($this->modelName);
        $modelPlural = $modelLower . 's';
        $modelTitle = ucfirst($modelPlural);
        
        $paginationLinks = $this->htmx 
            ? "{{ {$modelPlural}.linksHtmx('#content', 'innerHTML')|raw }}"
            : "{{ {$modelPlural}.links()|raw }}";

        $contentWrapper = $this->htmx ? '<div id="content">' : '';
        $contentWrapperEnd = $this->htmx ? '</div>' : '';

        return <<<OGAN
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="max-w-6xl mx-auto py-8 px-4">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ title }}</h1>

    {$contentWrapper}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {% for item in {$modelPlural} %}
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.createdAt }}</td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {$paginationLinks}
    </div>
    {$contentWrapperEnd}
</div>
{{ end }}
OGAN;
    }

    private function getPartialTemplate(): string
    {
        $modelLower = strtolower($this->modelName);
        $modelPlural = $modelLower . 's';

        return <<<OGAN
{# Template partiel pour requêtes HTMX - Ne pas inclure de layout #}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            {% for item in {$modelPlural} %}
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.createdAt }}</td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ {$modelPlural}.linksHtmx('#content', 'innerHTML')|raw }}
</div>
OGAN;
    }
}
