<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🌱 COMMANDES SEEDER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Commandes pour générer et exécuter des seeders.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

use Ogan\Console\Application;
use Ogan\Console\Generator\SeederGenerator;

function registerSeederCommands(Application $app): void
{
    $projectRoot = dirname(__DIR__, 2);

    // ─────────────────────────────────────────────────────────────────────
    // make:seeder - Génère un fichier seeder
    // ─────────────────────────────────────────────────────────────────────
    $app->addCommand('make:seeder', function (array $args) use ($projectRoot) {
        $force = in_array('--force', $args) || in_array('-f', $args);
        
        // Récupérer le nom
        $name = null;
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '-')) {
                $name = $arg;
                break;
            }
        }
        
        if (!$name) {
            echo "\n\033[31m❌ Nom du seeder requis\033[0m\n";
            echo "\n  Usage: php bin/console make:seeder <Name>\n\n";
            echo "  Exemple:\n";
            echo "    php bin/console make:seeder User\n";
            echo "    php bin/console make:seeder Article --force\n\n";
            return 1;
        }
        
        $generator = new SeederGenerator();
        
        echo "\n";
        echo "🌱 Génération du seeder pour \033[36m{$name}\033[0m...\n\n";
        
        $result = $generator->generateSeeder($projectRoot, $name, $force);
        
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
        
        echo "\n";
        echo "📍 \033[36mExécuter le seeder:\033[0m\n";
        echo "   php bin/console db:seed " . ucfirst($name) . "Seeder\n";
        echo "\n";
        
        return 0;
    });

    // ─────────────────────────────────────────────────────────────────────
    // db:seed - Exécute les seeders
    // ─────────────────────────────────────────────────────────────────────
    $app->addCommand('db:seed', function (array $args) use ($projectRoot) {
        // Récupérer le nom du seeder spécifique
        $specificSeeder = null;
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '-')) {
                $specificSeeder = $arg;
                break;
            }
        }
        
        $seedersDir = $projectRoot . '/database/seeders';
        
        if (!is_dir($seedersDir)) {
            echo "\n\033[33m⚠️  Aucun seeder trouvé.\033[0m\n";
            echo "  Créez un seeder avec: php bin/console make:seeder User\n\n";
            return 1;
        }
        
        echo "\n";
        echo "🌱 \033[36mExécution des seeders...\033[0m\n\n";
        
        $seederFiles = glob($seedersDir . '/*Seeder.php');
        $executed = 0;
        
        foreach ($seederFiles as $file) {
            $className = basename($file, '.php');
            $fullClass = 'Database\\Seeders\\' . $className;
            
            // Si un seeder spécifique est demandé, ne l'exécuter que lui
            if ($specificSeeder && $className !== $specificSeeder) {
                continue;
            }
            
            // Charger le fichier
            require_once $file;
            
            if (!class_exists($fullClass)) {
                echo "\033[33m⚠️  Classe {$fullClass} non trouvée dans {$file}\033[0m\n";
                continue;
            }
            
            echo "▶️  \033[34m{$className}\033[0m\n";
            
            try {
                $seeder = new $fullClass();
                $seeder->run();
                $executed++;
            } catch (\Exception $e) {
                echo "\033[31m❌ Erreur: " . $e->getMessage() . "\033[0m\n";
            }
            
            echo "\n";
        }
        
        if ($executed === 0) {
            if ($specificSeeder) {
                echo "\033[33m⚠️  Seeder '{$specificSeeder}' non trouvé.\033[0m\n\n";
            } else {
                echo "\033[33m⚠️  Aucun seeder exécuté.\033[0m\n\n";
            }
        } else {
            echo "\033[32m✅ {$executed} seeder(s) exécuté(s)\033[0m\n\n";
        }
        
        return 0;
    });
}
