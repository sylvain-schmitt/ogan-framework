<?php

use Ogan\Console\Generator\Pagination\PaginationGenerator;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📄 COMMANDES PAGINATION - Génération d'exemple de pagination
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Génère un contrôleur et des templates pour la pagination.
 * 
 * Usage:
 *   php bin/console make:pagination User
 *   php bin/console make:pagination Article --htmx
 *   php bin/console make:pagination Product --htmx --force
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
function registerPaginationCommands($app) {
    $projectRoot = dirname(__DIR__, 2);

    // make:pagination
    $app->addCommand('make:pagination', function($args) use ($projectRoot) {
        // Extraire les options
        $force = in_array('--force', $args);
        $htmx = in_array('--htmx', $args);
        
        // Filtrer pour obtenir le nom du modèle
        $args = array_filter($args, fn($a) => !str_starts_with($a, '--'));
        $args = array_values($args);
        
        if (empty($args)) {
            echo "❌ Usage: make:pagination <ModelName> [--htmx] [--force]\n\n";
            echo "Exemples:\n";
            echo "   php bin/console make:pagination User\n";
            echo "   php bin/console make:pagination Article --htmx\n";
            return 1;
        }
        
        $modelName = ucfirst($args[0]);
        $modelLower = strtolower($modelName);
        
        echo "📄 Génération de la pagination pour {$modelName}...\n";
        if ($htmx) {
            echo "   (avec support HTMX activé)\n";
        }
        echo "\n";

        $generator = new PaginationGenerator();
        $result = $generator->generate($projectRoot, $modelName, $force, $htmx);

        // Afficher les fichiers générés
        if (!empty($result['generated'])) {
            echo "✅ Fichiers générés :\n";
            foreach ($result['generated'] as $file) {
                echo "   📄 {$file}\n";
            }
        }

        // Afficher les fichiers ignorés
        if (!empty($result['skipped'])) {
            echo "\n⏭️  Fichiers ignorés (utilisez --force pour écraser) :\n";
            foreach ($result['skipped'] as $file) {
                echo "   ⚠️  {$file}\n";
            }
        }

        echo "\n🎉 Pagination générée avec succès !\n\n";
        echo "📋 Prochaines étapes :\n";
        echo "   1. Vérifier que le modèle App\\Model\\{$modelName} existe\n";
        echo "   2. Accéder à /{$modelLower}s pour voir la liste paginée\n";
        if ($htmx) {
            echo "   3. Activer HTMX dans config/parameters.yaml :\n";
            echo "      frontend.htmx.enabled: true\n";
        }

        return 0;
    }, 'Génère un contrôleur et templates de pagination (--htmx pour HTMX, --force pour écraser)');
}
