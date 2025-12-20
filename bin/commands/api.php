<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔌 COMMANDES API
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Commandes pour générer des controllers API REST.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

use Ogan\Console\Application;
use Ogan\Console\Generator\ApiControllerGenerator;

function registerApiCommands(Application $app): void
{
    // ─────────────────────────────────────────────────────────────────────
    // make:api - Génère un controller API REST
    // ─────────────────────────────────────────────────────────────────────
    $app->addCommand('make:api', function (array $args) {
        $projectRoot = getcwd();
        $force = in_array('--force', $args) || in_array('-f', $args);
        
        // Récupérer le nom du modèle (premier argument non-option)
        $modelName = null;
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '-')) {
                $modelName = $arg;
                break;
            }
        }
        
        if (!$modelName) {
            echo "\n\033[31m❌ Nom du modèle requis\033[0m\n";
            echo "\n  Usage: php bin/console make:api <ModelName>\n\n";
            echo "  Exemple:\n";
            echo "    php bin/console make:api User\n";
            echo "    php bin/console make:api Article --force\n\n";
            return 1;
        }
        
        // Vérifier que le modèle existe
        $modelClass = 'App\\Model\\' . ucfirst($modelName);
        $modelPath = $projectRoot . '/src/Model/' . ucfirst($modelName) . '.php';
        
        if (!file_exists($modelPath)) {
            echo "\n\033[33m⚠️  Attention: Le modèle {$modelClass} n'existe pas encore.\033[0m\n";
            echo "  Créez-le d'abord avec: php bin/console make:model {$modelName}\n\n";
        }
        
        $generator = new ApiControllerGenerator();
        
        echo "\n";
        echo "🔌 Génération de l'API REST pour \033[36m{$modelName}\033[0m...\n\n";
        
        $result = $generator->generateForModel($projectRoot, $modelName, $force);
        
        if (!empty($result['generated'])) {
            echo "\033[32m✅ Fichiers générés:\033[0m\n";
            foreach ($result['generated'] as $file) {
                echo "   ├─ {$file}\n";
            }
        }
        
        if (!empty($result['skipped'])) {
            echo "\n\033[33m⚠️  Fichiers ignorés (--force pour écraser):\033[0m\n";
            foreach ($result['skipped'] as $file) {
                echo "   ├─ {$file}\n";
            }
        }
        
        // Afficher les endpoints générés
        $routePrefix = '/api/' . strtolower($modelName) . 's';
        echo "\n";
        echo "📍 \033[36mEndpoints disponibles:\033[0m\n";
        echo "   ├─ GET    {$routePrefix}          → Liste\n";
        echo "   ├─ GET    {$routePrefix}/{id}     → Afficher\n";
        echo "   ├─ POST   {$routePrefix}          → Créer\n";
        echo "   ├─ PUT    {$routePrefix}/{id}     → Modifier\n";
        echo "   └─ DELETE {$routePrefix}/{id}     → Supprimer\n";
        echo "\n";
        
        return 0;
    });
}
