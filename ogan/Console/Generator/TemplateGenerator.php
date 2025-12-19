<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎨 TEMPLATE GENERATOR - Générateur de templates Ogan
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère automatiquement les fichiers templates .ogan pour les contrôleurs CRUD.
 * 
 * UTILISATION :
 * -------------
 * 
 * $generator = new TemplateGenerator();
 * $generator->generate('Product', 'templates', ['list', 'show', 'create', 'edit'], false, null, false);
 * 
 * Avec HTMX :
 * $generator->generate('Product', 'templates', ['list'], false, null, true);
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator;

use Ogan\Console\Interactive\ModelAnalyzer;

class TemplateGenerator extends AbstractGenerator
{
    /**
     * Templates disponibles
     */
    public const AVAILABLE_TEMPLATES = ['list', 'show', 'create', 'edit'];
    
    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES TEMPLATES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $name Nom du modèle (ex: "Product")
     * @param string $templatesPath Chemin vers le dossier des templates
     * @param array $templates Templates à générer (vide = tous)
     * @param bool $force Forcer la création même si le fichier existe
     * @param string|null $modelsPath Chemin vers les modèles (pour analyser les propriétés)
     * @param bool $htmx Générer avec support HTMX
     * @return array Chemins des fichiers créés
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function generate(
        string $name, 
        string $templatesPath, 
        array $templates = [], 
        bool $force = false,
        ?string $modelsPath = null,
        bool $htmx = false
    ): array {
        // Si aucun template spécifié, générer tous
        if (empty($templates)) {
            $templates = self::AVAILABLE_TEMPLATES;
        }
        
        $className = $this->toClassName($name);
        $routeName = $this->toRouteName($className);
        
        // Créer le dossier des templates
        $templateDir = rtrim($templatesPath, '/') . '/' . $routeName;
        $this->ensureDirectory($templateDir);
        
        // Analyser le modèle si possible
        $properties = $this->analyzeModel($className, $modelsPath);
        
        $createdFiles = [];
        
        foreach ($templates as $template) {
            $filepath = $templateDir . '/' . $template . '.ogan';
            
            if ($this->fileExists($filepath) && !$force) {
                continue; // Skip si existe et pas --force
            }
            
            $content = match ($template) {
                'list' => $this->generateListTemplate($className, $routeName, $properties, $htmx),
                'show' => $this->generateShowTemplate($className, $routeName, $properties, $htmx),
                'create' => $this->generateFormTemplate($className, $routeName, 'create'),
                'edit' => $this->generateFormTemplate($className, $routeName, 'edit'),
                default => ''
            };
            
            if (!empty($content)) {
                $this->writeFile($filepath, $content);
                $createdFiles[] = $filepath;
            }
        }
        
        return $createdFiles;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANALYSER LE MODÈLE POUR RÉCUPÉRER SES PROPRIÉTÉS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function analyzeModel(string $className, ?string $modelsPath): array
    {
        $modelClass = "App\\Model\\{$className}";
        
        if (!class_exists($modelClass)) {
            // Retourner des propriétés par défaut
            return [
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'name', 'type' => 'string'],
            ];
        }
        
        try {
            $analyzer = new ModelAnalyzer();
            $analysis = $analyzer->analyze($modelClass);
            return $analysis['properties'] ?? [];
        } catch (\Exception $e) {
            return [
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'name', 'type' => 'string'],
            ];
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE TEMPLATE LIST
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateListTemplate(string $className, string $routeName, array $properties, bool $htmx = false): string
    {
        $pluralName = $routeName . 's';
        $displayProperties = $this->getDisplayProperties($properties);
        
        // Générer les headers de colonnes
        $headers = "                    <th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">ID</th>\n";
        foreach ($displayProperties as $prop) {
            $label = ucfirst($prop['name']);
            $headers .= "                    <th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">{$label}</th>\n";
        }
        $headers .= "                    <th class=\"px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>";
        
        // Générer les cellules
        $cells = "                    <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">{{ item.id }}</td>\n";
        foreach ($displayProperties as $prop) {
            $name = $prop['name'];
            $cells .= "                    <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">{{ item.{$name} }}</td>\n";
        }

        // Action de suppression (HTMX ou classique)
        if ($htmx) {
            $deleteAction = <<<HTMX
                        <a href="{{ path('{$routeName}_show', ['id' => item.id]) }}" class="text-blue-600 hover:text-blue-900 mr-3">Voir</a>
                        <a href="{{ path('{$routeName}_edit', ['id' => item.id]) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Éditer</a>
                        <button hx-delete="{{ path('{$routeName}_delete', ['id' => item.id]) }}"
                                hx-target="#row-{{ item.id }}"
                                hx-swap="outerHTML swap:0.3s"
                                hx-confirm="Êtes-vous sûr de vouloir supprimer cet élément ?"
                                class="text-red-600 hover:text-red-900 cursor-pointer">
                            Supprimer
                        </button>
HTMX;
            $rowId = ' id="row-{{ item.id }}"';
        } else {
            $deleteAction = <<<CLASSIC
                        <a href="{{ path('{$routeName}_show', ['id' => item.id]) }}" class="text-blue-600 hover:text-blue-900 mr-3">Voir</a>
                        <a href="{{ path('{$routeName}_edit', ['id' => item.id]) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Éditer</a>
                        <form action="{{ path('{$routeName}_delete', ['id' => item.id]) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr ?')">
                            <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                        </form>
CLASSIC;
            $rowId = '';
        }

        return <<<OGAN
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Liste des {$pluralName}</h1>
        <a href="{{ path('{$routeName}_create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
            + Créer un {$routeName}
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
{$headers}
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {% for item in items %}
                <tr{$rowId} class="hover:bg-gray-50 transition duration-150">
{$cells}
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
{$deleteAction}
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
        
        {% if count(items) == 0 %}
        <div class="text-center py-12 text-gray-500">
            Aucun élément trouvé.
            <a href="{{ path('{$routeName}_create') }}" class="text-blue-600 hover:underline">Créer le premier</a>
        </div>
        {% endif %}
    </div>
</div>
{{ end }}
OGAN;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE TEMPLATE SHOW
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateShowTemplate(string $className, string $routeName, array $properties, bool $htmx = false): string
    {
        $pluralName = $routeName . 's';
        $displayProperties = $this->getDisplayProperties($properties, true);
        
        // Générer les lignes de détail
        $details = "";
        foreach ($displayProperties as $prop) {
            $name = $prop['name'];
            $label = ucfirst($name);
            $details .= "            <div class=\"py-4 sm:grid sm:grid-cols-3 sm:gap-4\">\n";
            $details .= "                <dt class=\"text-sm font-medium text-gray-500\">{$label}</dt>\n";
            $details .= "                <dd class=\"mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2\">{{ item.{$name} }}</dd>\n";
            $details .= "            </div>\n";
        }

        // Action de suppression (HTMX ou classique)
        if ($htmx) {
            $deleteAction = <<<HTMX
        <button hx-delete="{{ path('{$routeName}_delete', ['id' => item.id]) }}"
                hx-confirm="Êtes-vous sûr de vouloir supprimer cet élément ?"
                class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
            Supprimer
        </button>
HTMX;
        } else {
            $deleteAction = <<<CLASSIC
        <form action="{{ path('{$routeName}_delete', ['id' => item.id]) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')">
            <button type="submit" 
                    class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                Supprimer
            </button>
        </form>
CLASSIC;
        }

        return <<<OGAN
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Détails du {$routeName}</h1>
        <div class="space-x-2">
            <a href="{{ path('{$routeName}_edit', ['id' => item.id]) }}" 
               class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                Éditer
            </a>
            <a href="{{ path('{$routeName}_list') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                Retour à la liste
            </a>
        </div>
    </div>

    <!-- Card détails -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">{$className} #{{ item.id }}</h2>
        </div>
        
        <dl class="px-6 divide-y divide-gray-200">
{$details}
        </dl>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex space-x-4">
{$deleteAction}
    </div>
</div>
{{ end }}
OGAN;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE TEMPLATE FORMULAIRE (CREATE/EDIT)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateFormTemplate(string $className, string $routeName, string $type): string
    {
        $pluralName = $routeName . 's';
        $isEdit = $type === 'edit';
        $title = $isEdit ? "Éditer le {$routeName}" : "Créer un {$routeName}";
        $action = $isEdit ? "{{ path('{$routeName}_update', ['id' => item.id]) }}" : "{{ path('{$routeName}_store') }}";

        return <<<OGAN
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{$title}</h1>
        <a href="{{ path('{$routeName}_list') }}" 
           class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
            Retour à la liste
        </a>
    </div>

    <!-- Formulaire -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">Informations</h2>
        </div>
        
        <div class="p-6">
            {{ formStart(form) }}
            
            <div class="space-y-6">
                {{ formRest(form) }}
            </div>
            
            <div class="mt-8 flex justify-end space-x-4">
                <a href="{{ path('{$routeName}_list') }}" 
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded-lg transition duration-200">
                    Annuler
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-200">
                    Enregistrer
                </button>
            </div>
            
            {{ formEnd(form) }}
        </div>
    </div>
</div>
{{ end }}
OGAN;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FILTRER LES PROPRIÉTÉS À AFFICHER
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getDisplayProperties(array $properties, bool $includeTimestamps = false): array
    {
        $exclude = ['id', 'attributes', 'exists'];
        if (!$includeTimestamps) {
            $exclude = array_merge($exclude, ['createdAt', 'updatedAt', 'created_at', 'updated_at']);
        }
        
        return array_filter($properties, function($prop) use ($exclude) {
            $name = $prop['name'] ?? '';
            // Exclure les propriétés système et les clés étrangères
            if (in_array($name, $exclude)) {
                return false;
            }
            // Exclure les relations (propriétés finissant par Id)
            if (preg_match('/Id$/', $name) && $name !== 'id') {
                return false;
            }
            return true;
        });
    }
}
