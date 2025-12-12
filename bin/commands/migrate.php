<?php

use Ogan\Database\Database;
use Ogan\Database\Migration\{MigrationManager, MigrationGenerator, MigrationScanner};

/**
 * Commandes Migrate (migrations de base de données)
 */
function registerMigrateCommands($app) {
    $projectRoot = dirname(__DIR__, 2);
    $migrationsPath = $projectRoot . '/database/migrations';
    $modelsPath = $projectRoot . '/src/Model';

    // migrate
    $app->addCommand('migrate', function($args) use ($migrationsPath) {
        try {
            $pdo = Database::getConnection();
        } catch (\Exception $e) {
            echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
            return 1;
        }
        
        $manager = new MigrationManager($pdo, $migrationsPath);
        
        echo "🔄 Exécution des migrations en attente...\n\n";
        $executed = $manager->migrate();
        
        if (empty($executed)) {
            echo "ℹ️  Aucune migration en attente.\n";
        }
        
        return 0;
    }, 'Exécute les migrations en attente');

    // migrate:rollback
    $app->addCommand('migrate:rollback', function($args) use ($migrationsPath) {
        try {
            $pdo = Database::getConnection();
        } catch (\Exception $e) {
            echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
            return 1;
        }
        
        $manager = new MigrationManager($pdo, $migrationsPath);
        $steps = 1;
        
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--steps=')) {
                $steps = (int)substr($arg, 8);
            }
        }
        
        echo "🔄 Annulation de {$steps} migration(s)...\n\n";
        $rolledBack = $manager->rollback($steps);
        
        if (empty($rolledBack)) {
            echo "ℹ️  Aucune migration à annuler.\n";
        }
        
        return 0;
    }, 'Annule les migrations (--steps=N)');

    // migrate:status
    $app->addCommand('migrate:status', function($args) use ($migrationsPath) {
        try {
            $pdo = Database::getConnection();
        } catch (\Exception $e) {
            echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
            return 1;
        }
        
        $manager = new MigrationManager($pdo, $migrationsPath);
        
        echo "📊 Statut des migrations\n\n";
        $status = $manager->status();
        
        echo "Total : {$status['total']}\n";
        echo "Exécutées : {$status['executed']}\n";
        echo "En attente : {$status['pending']}\n\n";
        
        if (!empty($status['migrations'])) {
            echo "Détails :\n";
            echo str_repeat('─', 80) . "\n";
            printf("%-50s %-15s %s\n", "Migration", "Statut", "Batch");
            echo str_repeat('─', 80) . "\n";
            
            foreach ($status['migrations'] as $migration) {
                $statusText = $migration['executed'] ? '✅ Exécutée' : '⏳ En attente';
                $batchText = $migration['batch'] !== null ? "#{$migration['batch']}" : '-';
                printf("%-50s %-15s %s\n", $migration['filename'], $statusText, $batchText);
            }
        }
        
        return 0;
    }, 'Affiche le statut des migrations');

    // migrate:make
    $app->addCommand('migrate:make', function($args) use ($migrationsPath, $modelsPath) {
        $modelInput = $args[0] ?? null;
        $force = in_array('--force', $args);
        
        if (!$modelInput) {
            // Scanner tous les modèles
            echo "🔍 Scan automatique des modèles...\n\n";
            
            try {
                $scanner = new MigrationScanner($migrationsPath, $modelsPath);
                $generated = $scanner->generateMissingMigrations($force);
                
                if (empty($generated)) {
                    echo "\n✅ Tous les modèles ont déjà une migration.\n";
                }
            } catch (\Exception $e) {
                echo "❌ Erreur : " . $e->getMessage() . "\n";
                return 1;
            }
        } else {
            // Modèle spécifique
            if (!str_contains($modelInput, '\\')) {
                echo "🔍 Recherche du modèle : {$modelInput}\n";
                $modelClass = findModelClass($modelInput, $modelsPath);
                
                if (!$modelClass) {
                    echo "❌ Modèle '{$modelInput}' non trouvé\n";
                    return 1;
                }
                
                echo "✅ Modèle trouvé : {$modelClass}\n\n";
            } else {
                $modelClass = $modelInput;
            }
            
            echo "🔧 Génération de la migration : {$modelClass}\n\n";
            
            try {
                $generator = new MigrationGenerator();
                $filepath = $generator->generateFromModel($modelClass, $migrationsPath, $force);
                
                echo "✅ Migration générée : " . basename($filepath) . "\n";
                echo "📁 Fichier : {$filepath}\n";
            } catch (\Exception $e) {
                echo "❌ Erreur : " . $e->getMessage() . "\n";
                return 1;
            }
        }
        
        return 0;
    }, 'Génère une migration depuis un modèle');
}

/**
 * Trouve une classe de modèle par son nom
 */
function findModelClass(string $className, string $modelsPath): ?string
{
    if (!is_dir($modelsPath)) {
        return null;
    }

    $files = glob($modelsPath . '/*.php');

    foreach ($files as $file) {
        $content = file_get_contents($file);

        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            continue;
        }
        $namespace = $namespaceMatch[1];

        if (!preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            continue;
        }
        $foundClassName = $classMatch[1];

        $normalizedInput = ucfirst($className);

        if (strcasecmp($foundClassName, $normalizedInput) === 0 || strcasecmp($foundClassName, $className) === 0) {
            $fullClassName = $namespace . '\\' . $foundClassName;

            if (class_exists($fullClassName)) {
                $reflection = new \ReflectionClass($fullClassName);
                if ($reflection->isSubclassOf(\Ogan\Database\Model::class)) {
                    return $fullClassName;
                }
            }
        }
    }

    return null;
}
