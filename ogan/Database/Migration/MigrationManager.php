<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔄 MIGRATION MANAGER - Gestionnaire de migrations versionnées
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Gère l'exécution, le suivi et le rollback des migrations.
 * 
 * FONCTIONNALITÉS :
 * -----------------
 * - Exécuter les migrations en attente (migrate)
 * - Annuler les migrations (rollback)
 * - Voir le statut des migrations (status)
 * - Créer la table de suivi automatiquement
 * 
 * UTILISATION :
 * -------------
 * 
 * $manager = new MigrationManager($pdo, __DIR__ . '/../../database/migrations');
 * $manager->migrate(); // Exécute toutes les migrations en attente
 * $manager->rollback(1); // Annule la dernière migration
 * $manager->status(); // Affiche le statut
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Migration;

use PDO;
use PDOException;

class MigrationManager
{
    /**
     * @var PDO Connexion à la base de données
     */
    private PDO $pdo;

    /**
     * @var string Chemin vers le dossier des migrations
     */
    private string $migrationsPath;

    /**
     * @var string Nom de la table de suivi des migrations
     */
    private string $migrationsTable = 'migrations';

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param PDO $pdo Connexion à la base de données
     * @param string $migrationsPath Chemin vers le dossier des migrations
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(PDO $pdo, string $migrationsPath)
    {
        $this->pdo = $pdo;
        $this->migrationsPath = rtrim($migrationsPath, '/');
        
        // Créer la table de suivi si elle n'existe pas
        $this->ensureMigrationsTable();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER LA TABLE DE SUIVI DES MIGRATIONS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Crée automatiquement la table `migrations` si elle n'existe pas.
     * 
     * Note : Cette méthode est appelée avant toute transaction pour éviter
     * les problèmes d'auto-commit avec CREATE TABLE IF NOT EXISTS.
     * 
     * @return void
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function ensureMigrationsTable(): void
    {
        // Vérifier si la table existe déjà
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $tableExists = false;

        try {
            switch (strtolower($driver)) {
                case 'mysql':
                case 'mariadb':
                    $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->migrationsTable}'");
                    $tableExists = $stmt->rowCount() > 0;
                    break;
                case 'pgsql':
                case 'postgresql':
                    $stmt = $this->pdo->query(
                        "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '{$this->migrationsTable}')"
                    );
                    $tableExists = $stmt->fetchColumn();
                    break;
                case 'sqlite':
                    $stmt = $this->pdo->query(
                        "SELECT name FROM sqlite_master WHERE type='table' AND name='{$this->migrationsTable}'"
                    );
                    $tableExists = $stmt->rowCount() > 0;
                    break;
            }
        } catch (\PDOException $e) {
            // Si la table n'existe pas, on continue pour la créer
            $tableExists = false;
        }

        // Si la table existe déjà, on ne fait rien
        if ($tableExists) {
            return;
        }

        // Créer la table (hors transaction pour éviter les problèmes d'auto-commit)
        $sql = match (strtolower($driver)) {
            'mysql', 'mariadb' => "
                CREATE TABLE {$this->migrationsTable} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    batch INT NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_migration (migration),
                    INDEX idx_batch (batch)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql', 'postgresql' => "
                CREATE TABLE {$this->migrationsTable} (
                    id SERIAL PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    batch INT NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX idx_migration ON {$this->migrationsTable}(migration);
                CREATE INDEX idx_batch ON {$this->migrationsTable}(batch);
            ",
            'sqlite' => "
                CREATE TABLE {$this->migrationsTable} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    batch INTEGER NOT NULL,
                    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX idx_migration ON {$this->migrationsTable}(migration);
                CREATE INDEX idx_batch ON {$this->migrationsTable}(batch);
            ",
            default => throw new \RuntimeException("Driver de base de données non supporté: {$driver}")
        };

        $this->pdo->exec($sql);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER TOUTES LES MIGRATIONS DISPONIBLES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Scanne le dossier des migrations et retourne la liste des fichiers.
     * 
     * @return array Liste des migrations disponibles (nom de fichier => chemin)
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getAvailableMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        $migrations = [];

        foreach ($files as $file) {
            $filename = basename($file);
            // Ignorer les fichiers qui ne suivent pas le format YYYY_MM_DD_HHMMSS_description.php
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.+\.php$/', $filename)) {
                $migrations[$filename] = $file;
            }
        }

        // Trier par nom de fichier (ordre chronologique)
        ksort($migrations);

        return $migrations;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES MIGRATIONS DÉJÀ EXÉCUTÉES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array Liste des migrations exécutées (nom de fichier => batch)
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getExecutedMigrations(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT migration, batch FROM {$this->migrationsTable} ORDER BY batch ASC, id ASC");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $migrations = [];
            foreach ($results as $row) {
                $migrations[$row['migration']] = (int)$row['batch'];
            }
            
            return $migrations;
        } catch (PDOException $e) {
            // Si la table n'existe pas encore, retourner un tableau vide
            return [];
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE PROCHAIN NUMÉRO DE BATCH
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return int Numéro du prochain batch
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getNextBatch(): int
    {
        try {
            $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result && $result['max_batch'] !== null) ? (int)$result['max_batch'] + 1 : 1;
        } catch (PDOException $e) {
            return 1;
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHARGER UNE CLASSE DE MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $filePath Chemin vers le fichier de migration
     * @return AbstractMigration Instance de la migration
     * @throws \RuntimeException Si la classe ne peut pas être chargée
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function loadMigration(string $filePath): AbstractMigration
    {
        require_once $filePath;

        // Extraire le nom de la classe depuis le nom du fichier
        $filename = basename($filePath, '.php');
        $className = $this->filenameToClassName($filename);

        // Chercher la classe dans les namespaces possibles
        $possibleNamespaces = [
            'App\\Database\\Migration',
            'Database\\Migration',
            'Ogan\\Database\\Migration',
            ''
        ];

        foreach ($possibleNamespaces as $namespace) {
            $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
            if (class_exists($fullClassName)) {
                return new $fullClassName($this->pdo);
            }
        }

        throw new \RuntimeException("Impossible de charger la classe de migration : {$filename}");
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR UN NOM DE FICHIER EN NOM DE CLASSE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Exemple : 2024_01_15_120000_create_users_table.php → CreateUsersTable
     * 
     * @param string $filename Nom du fichier (sans extension)
     * @return string Nom de la classe
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function filenameToClassName(string $filename): string
    {
        // Enlever le préfixe timestamp (YYYY_MM_DD_HHMMSS_)
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
        
        // Convertir snake_case en PascalCase
        $parts = explode('_', $name);
        $parts = array_map('ucfirst', $parts);
        
        return implode('', $parts);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXÉCUTER LES MIGRATIONS EN ATTENTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Exécute toutes les migrations qui n'ont pas encore été appliquées.
     * 
     * @return array Liste des migrations exécutées
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function migrate(): array
    {
        $available = $this->getAvailableMigrations();
        $executed = $this->getExecutedMigrations();
        $pending = array_diff_key($available, $executed);
        
        if (empty($pending)) {
            return [];
        }

        $batch = $this->getNextBatch();
        $executedMigrations = [];

        $this->pdo->beginTransaction();
        try {
            foreach ($pending as $filename => $filePath) {
                echo "🔄 Exécution de la migration : {$filename}\n";
                
                $migration = $this->loadMigration($filePath);
                $migration->up();
                
                // Enregistrer la migration
                $stmt = $this->pdo->prepare(
                    "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)"
                );
                $stmt->execute([$filename, $batch]);
                
                $executedMigrations[] = $filename;
                echo "✅ Migration {$filename} exécutée avec succès\n";
            }
            
            // Vérifier si une transaction est active avant de faire le commit
            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
            echo "\n✅ Toutes les migrations ont été exécutées (batch #{$batch})\n";
            
            return $executedMigrations;
        } catch (\Exception $e) {
            // Vérifier si une transaction est active avant de faire le rollback
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo "\n❌ Erreur lors de l'exécution des migrations : " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANNULER LES DERNIÈRES MIGRATIONS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param int $steps Nombre de migrations à annuler (par défaut : 1)
     * @return array Liste des migrations annulées
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function rollback(int $steps = 1): array
    {
        $executed = $this->getExecutedMigrations();
        
        if (empty($executed)) {
            echo "ℹ️  Aucune migration à annuler\n";
            return [];
        }

        // Récupérer les migrations du dernier batch
        $stmt = $this->pdo->query(
            "SELECT migration, batch FROM {$this->migrationsTable} ORDER BY batch DESC, id DESC LIMIT {$steps}"
        );
        $migrationsToRollback = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($migrationsToRollback)) {
            echo "ℹ️  Aucune migration à annuler\n";
            return [];
        }

        $rolledBackMigrations = [];
        $available = $this->getAvailableMigrations();

        $this->pdo->beginTransaction();
        try {
            foreach ($migrationsToRollback as $row) {
                $filename = $row['migration'];
                
                if (!isset($available[$filename])) {
                    echo "⚠️  Fichier de migration introuvable : {$filename}\n";
                    continue;
                }
                
                echo "🔄 Annulation de la migration : {$filename}\n";
                
                $migration = $this->loadMigration($available[$filename]);
                $migration->down();
                
                // Supprimer l'enregistrement
                $stmt = $this->pdo->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
                $stmt->execute([$filename]);
                
                $rolledBackMigrations[] = $filename;
                echo "✅ Migration {$filename} annulée avec succès\n";
            }
            
            // Vérifier si une transaction est active avant de faire le commit
            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
            echo "\n✅ Rollback terminé\n";
            
            return $rolledBackMigrations;
        } catch (\Exception $e) {
            // Vérifier si une transaction est active avant de faire le rollback
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo "\n❌ Erreur lors du rollback : " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AFFICHER LE STATUT DES MIGRATIONS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array Statut des migrations
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function status(): array
    {
        $available = $this->getAvailableMigrations();
        $executed = $this->getExecutedMigrations();
        
        $status = [
            'total' => count($available),
            'executed' => count($executed),
            'pending' => count($available) - count($executed),
            'migrations' => []
        ];

        foreach ($available as $filename => $filePath) {
            $status['migrations'][] = [
                'filename' => $filename,
                'executed' => isset($executed[$filename]),
                'batch' => $executed[$filename] ?? null
            ];
        }

        return $status;
    }
}

