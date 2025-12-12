<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔍 MIGRATION SCANNER - Scanner de modèles et génération automatique
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Scanne tous les modèles du projet et génère automatiquement
 * les migrations manquantes (inspiré de Symfony/Doctrine).
 * 
 * UTILISATION :
 * -------------
 * 
 * $scanner = new MigrationScanner($migrationsPath, $modelsPath);
 * $scanner->generateMissingMigrations();
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Migration;

class MigrationScanner
{
    /**
     * @var string Chemin vers le dossier des migrations
     */
    private string $migrationsPath;

    /**
     * @var string Chemin vers le dossier des modèles
     */
    private string $modelsPath;

    /**
     * @var MigrationGenerator Générateur de migrations
     */
    private MigrationGenerator $generator;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $migrationsPath Chemin vers le dossier des migrations
     * @param string $modelsPath Chemin vers le dossier des modèles
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(string $migrationsPath, string $modelsPath)
    {
        $this->migrationsPath = rtrim($migrationsPath, '/');
        $this->modelsPath = rtrim($modelsPath, '/');
        $this->generator = new MigrationGenerator();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SCANNER TOUS LES MODÈLES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array Liste des classes de modèles trouvées
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function scanModels(): array
    {
        if (!is_dir($this->modelsPath)) {
            return [];
        }

        $models = [];
        $files = glob($this->modelsPath . '/*.php');

        foreach ($files as $file) {
            $className = $this->extractClassNameFromFile($file);
            
            if ($className && $this->isModelClass($className)) {
                $models[] = $className;
            }
        }

        return $models;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES MIGRATIONS EXISTANTES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array Liste des modèles qui ont déjà une migration
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getExistingMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $migrations = [];
        $files = glob($this->migrationsPath . '/*.php');

        foreach ($files as $file) {
            $modelClass = $this->extractModelFromMigration($file);
            if ($modelClass) {
                $migrations[] = $modelClass;
            }
        }

        return $migrations;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES MIGRATIONS MANQUANTES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param bool $force Forcer la génération même si une migration existe
     * @return array Liste des migrations générées
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function generateMissingMigrations(bool $force = false): array
    {
        // Scanner tous les modèles
        $allModels = $this->scanModels();
        
        if (empty($allModels)) {
            echo "ℹ️  Aucun modèle trouvé dans {$this->modelsPath}\n";
            return [];
        }

        // Récupérer les migrations existantes
        $existingMigrations = $this->getExistingMigrations();

        // Trouver les modèles sans migration
        $modelsWithoutMigration = array_diff($allModels, $existingMigrations);

        if (empty($modelsWithoutMigration) && !$force) {
            echo "✅ Tous les modèles ont déjà une migration.\n";
            return [];
        }

        $generated = [];

        echo "🔍 Scan des modèles...\n";
        echo "   Modèles trouvés : " . count($allModels) . "\n";
        echo "   Migrations existantes : " . count($existingMigrations) . "\n";
        echo "   Migrations à générer : " . count($modelsWithoutMigration) . "\n\n";

        // Générer les migrations manquantes
        foreach ($modelsWithoutMigration as $modelClass) {
            try {
                echo "🔧 Génération de la migration pour : {$modelClass}\n";
                $filepath = $this->generator->generateFromModel($modelClass, $this->migrationsPath, $force);
                $filename = basename($filepath);
                echo "   ✅ Migration créée : {$filename}\n";
                $generated[] = [
                    'model' => $modelClass,
                    'file' => $filename,
                    'path' => $filepath
                ];
            } catch (\Exception $e) {
                echo "   ❌ Erreur : " . $e->getMessage() . "\n";
            }
        }

        if (!empty($generated)) {
            echo "\n✅ " . count($generated) . " migration(s) générée(s) avec succès.\n";
        }

        return $generated;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXTRAIRE LE NOM DE CLASSE D'UN FICHIER
     * ═══════════════════════════════════════════════════════════════════
     */
    private function extractClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        
        // Extraire le namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            return null;
        }
        $namespace = $namespaceMatch[1];

        // Extraire le nom de la classe
        if (!preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return null;
        }
        $className = $classMatch[1];

        return $namespace . '\\' . $className;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UNE CLASSE EST UN MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function isModelClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflection = new \ReflectionClass($className);
        
        // Vérifier qu'elle étend Model
        return $reflection->isSubclassOf(\Ogan\Database\Model::class);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXTRAIRE LE MODÈLE D'UNE MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Analyse le contenu d'une migration pour trouver le modèle associé.
     * 
     */
    private function extractModelFromMigration(string $file): ?string
    {
        $content = file_get_contents($file);
        
        // Chercher le commentaire qui indique le modèle
        // Format : "Modèle : App\Model\User"
        if (preg_match('/Modèle\s*:\s*([^\s\n]+)/', $content, $matches)) {
            $modelClass = trim($matches[1]);
            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        // Fallback : essayer de déduire depuis le nom de la table
        // Chercher "CREATE TABLE IF NOT EXISTS table_name"
        if (preg_match('/CREATE TABLE IF NOT EXISTS\s+(\w+)/', $content, $matches)) {
            $tableName = $matches[1];
            
            // Essayer de trouver le modèle correspondant
            $models = $this->scanModels();
            foreach ($models as $modelClass) {
                try {
                    $reflection = new \ReflectionClass($modelClass);
                    if (method_exists($modelClass, 'getTableName')) {
                        $modelTable = $modelClass::getTableName();
                        if ($modelTable === $tableName) {
                            return $modelClass;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AFFICHER LE STATUT DES MIGRATIONS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array Statut détaillé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getStatus(): array
    {
        $allModels = $this->scanModels();
        $existingMigrations = $this->getExistingMigrations();
        $modelsWithoutMigration = array_diff($allModels, $existingMigrations);

        return [
            'total_models' => count($allModels),
            'models_with_migration' => count($existingMigrations),
            'models_without_migration' => count($modelsWithoutMigration),
            'all_models' => $allModels,
            'models_with_migration_list' => $existingMigrations,
            'models_without_migration_list' => array_values($modelsWithoutMigration),
        ];
    }
}

