<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🗄️ DATABASE - Gestionnaire de Connexion PDO
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Gère la connexion PDO à la base de données avec :
 * - Pattern Singleton (une seule connexion)
 * - Configuration depuis Config
 * - Gestion des transactions
 * - Gestion des erreurs
 * 
 * POURQUOI UNE CLASSE DATABASE ?
 * ------------------------------
 * 
 * 1. SINGLETON :
 *    Une seule connexion PDO pour toute l'application.
 *    Évite d'ouvrir plusieurs connexions inutilement.
 * 
 * 2. CENTRALISATION :
 *    Toute la configuration DB est au même endroit.
 *    Facile à modifier et maintenir.
 * 
 * 3. SÉCURITÉ :
 *    Utilise des requêtes préparées par défaut.
 *    Protection contre les injections SQL.
 * 
 * EXEMPLES D'UTILISATION :
 * ------------------------
 * 
 * // Récupérer la connexion
 * $pdo = Database::getConnection();
 * 
 * // Exécuter une requête
 * $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
 * $stmt->execute([123]);
 * $user = $stmt->fetch(PDO::FETCH_ASSOC);
 * 
 * // Transaction
 * Database::beginTransaction();
 * try {
 *     // ... opérations DB ...
 *     Database::commit();
 * } catch (\Exception $e) {
 *     Database::rollback();
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database;

use PDO;
use PDOException;

class Database
{
    /**
     * @var PDO|null Instance PDO (Singleton)
     */
    private static ?PDO $pdo = null;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUIRE LE DSN SELON LE DRIVER
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Supporte plusieurs bases de données :
     * - mysql / mariadb : MySQL et MariaDB
     * - pgsql : PostgreSQL
     * - sqlite : SQLite
     * - sqlsrv : SQL Server
     * 
     * @param string $driver Type de base de données
     * @param string $host Hôte
     * @param int|null $port Port
     * @param string $dbname Nom de la base de données
     * @param string $charset Charset (pour MySQL/MariaDB)
     * @return string DSN construit
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private static function buildDsn(string $driver, string $host, ?int $port, string $dbname, string $charset): string
    {
        return match (strtolower($driver)) {
            'mysql', 'mariadb' => self::buildMysqlDsn($host, $port ?? 3306, $dbname, $charset),
            'pgsql', 'postgresql' => self::buildPostgresqlDsn($host, $port ?? 5432, $dbname),
            'sqlite' => self::buildSqliteDsn($dbname),
            'sqlsrv', 'mssql' => self::buildSqlServerDsn($host, $port ?? 1433, $dbname),
            default => throw new \InvalidArgumentException("Driver de base de données non supporté: {$driver}. Drivers supportés: mysql, pgsql, sqlite, sqlsrv")
        };
    }

    /**
     * Construire le DSN pour MySQL/MariaDB
     */
    private static function buildMysqlDsn(string $host, int $port, string $dbname, string $charset): string
    {
        return "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
    }

    /**
     * Construire le DSN pour PostgreSQL
     */
    private static function buildPostgresqlDsn(string $host, int $port, string $dbname): string
    {
        return "pgsql:host={$host};port={$port};dbname={$dbname}";
    }

    /**
     * Construire le DSN pour SQLite
     */
    private static function buildSqliteDsn(string $dbname): string
    {
        // Si $dbname est un chemin absolu, l'utiliser tel quel
        // Sinon, le considérer comme un nom de fichier dans le dossier du projet
        if (strpos($dbname, '/') === 0 || strpos($dbname, '\\') === 0) {
            return "sqlite:{$dbname}";
        }

        // Chemin relatif : créer dans var/db/
        $dbPath = __DIR__ . '/../../var/db/' . $dbname;
        $dbDir = dirname($dbPath);

        // Créer le dossier s'il n'existe pas
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        return "sqlite:{$dbPath}";
    }

    /**
     * Construire le DSN pour SQL Server
     */
    private static function buildSqlServerDsn(string $host, int $port, string $dbname): string
    {
        return "sqlsrv:Server={$host},{$port};Database={$dbname}";
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LA CONNEXION PDO
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Pattern Singleton : retourne toujours la même instance.
     * 
     * Supporte plusieurs bases de données :
     * - MySQL / MariaDB (driver: 'mysql' ou 'mariadb')
     * - PostgreSQL (driver: 'pgsql' ou 'postgresql')
     * - SQLite (driver: 'sqlite')
     * - SQL Server (driver: 'sqlsrv' ou 'mssql')
     * 
     * @return PDO Instance PDO
     * @throws PDOException Si la connexion échoue
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function getConnection(): PDO
    {
        // Si la connexion existe déjà, la retourner
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Charger la configuration
        $driver = \Ogan\Config\Config::get('database.driver', 'mysql'); // mysql, pgsql, sqlite, sqlsrv
        $host = \Ogan\Config\Config::get('database.host', 'localhost');
        $port = \Ogan\Config\Config::get('database.port', null);
        $dbname = \Ogan\Config\Config::get('database.name', '');
        $username = \Ogan\Config\Config::get('database.user', 'root');
        // Support de database.password ET database.pass (pour compatibilité avec DB_PASS)
        $password = \Ogan\Config\Config::get('database.password', \Ogan\Config\Config::get('database.pass', ''));
        $charset = \Ogan\Config\Config::get('database.charset', 'utf8mb4');

        // Construire le DSN (Data Source Name) selon le driver
        $dsn = self::buildDsn($driver, $host, $port, $dbname, $charset);

        // Options PDO
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Lever des exceptions en cas d'erreur
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Retourner des tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES => false,                // Désactiver l'émulation des requêtes préparées (sécurité)
            PDO::ATTR_STRINGIFY_FETCHES => false,               // Ne pas convertir les nombres en strings
        ];

        try {
            // Créer la connexion
            self::$pdo = new PDO($dsn, $username, $password, $options);

            return self::$pdo;
        } catch (PDOException $e) {
            // Logger l'erreur si un logger est disponible
            if (class_exists(\Ogan\Logger\Logger::class)) {
                $logger = new \Ogan\Logger\Logger(__DIR__ . '/../../var/log');
                $logger->critical('Échec de connexion à la base de données', [
                    'error' => $e->getMessage(),
                    'host' => $host,
                    'dbname' => $dbname
                ]);
            }

            throw new \RuntimeException('Impossible de se connecter à la base de données: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉMARRER UNE TRANSACTION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Les transactions permettent de regrouper plusieurs opérations.
     * Si une opération échoue, toutes les opérations sont annulées.
     * 
     * EXEMPLE :
     * ---------
     * Database::beginTransaction();
     * try {
     *     // Créer un utilisateur
     *     $pdo->exec("INSERT INTO users ...");
     *     
     *     // Créer son profil
     *     $pdo->exec("INSERT INTO profiles ...");
     *     
     *     Database::commit(); // Tout s'est bien passé
     * } catch (\Exception $e) {
     *     Database::rollback(); // Annuler toutes les opérations
     *     throw $e;
     * }
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function beginTransaction(): void
    {
        $pdo = self::getConnection();
        $pdo->beginTransaction();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VALIDER UNE TRANSACTION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Valide toutes les opérations effectuées depuis beginTransaction().
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function commit(): void
    {
        $pdo = self::getConnection();
        $pdo->commit();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANNULER UNE TRANSACTION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Annule toutes les opérations effectuées depuis beginTransaction().
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function rollback(): void
    {
        $pdo = self::getConnection();
        $pdo->rollBack();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FERMER LA CONNEXION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utile pour les tests ou pour forcer une nouvelle connexion.
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function close(): void
    {
        self::$pdo = null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI ON EST DANS UNE TRANSACTION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return bool TRUE si une transaction est active
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function inTransaction(): bool
    {
        $pdo = self::getConnection();
        return $pdo->inTransaction();
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * PATTERN SINGLETON
 * -----------------
 * 
 * Le pattern Singleton garantit qu'il n'y a qu'UNE SEULE instance
 * d'un objet dans toute l'application.
 * 
 * Pourquoi utiliser un Singleton pour la DB ?
 * 
 * 1. PERFORMANCE :
 *    Ouvrir une connexion DB est coûteux.
 *    On veut réutiliser la même connexion.
 * 
 * 2. RESSOURCES :
 *    MySQL limite le nombre de connexions simultanées.
 *    Un Singleton évite d'ouvrir trop de connexions.
 * 
 * 3. SIMPLICITÉ :
 *    On peut appeler Database::getConnection() partout
 *    sans se soucier de créer une nouvelle connexion.
 * 
 * SÉCURITÉ : REQUÊTES PRÉPARÉES
 * ------------------------------
 * 
 * ⚠️ IMPORTANT : Toujours utiliser des requêtes préparées !
 * 
 * ❌ MAUVAIS (vulnérable aux injections SQL) :
 * $pdo->query("SELECT * FROM users WHERE id = {$id}");
 * 
 * ✅ BON (sécurisé) :
 * $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 * $stmt->execute([$id]);
 * 
 * Les requêtes préparées :
 * - Échappent automatiquement les valeurs
 * - Protègent contre les injections SQL
 * - Sont plus performantes (cache du plan d'exécution)
 * 
 * TRANSACTIONS
 * ------------
 * 
 * Les transactions permettent de regrouper plusieurs opérations.
 * 
 * PROPRIÉTÉS (ACID) :
 * 
 * - ATOMICITÉ : Toutes les opérations réussissent ou échouent ensemble
 * - COHÉRENCE : La DB reste dans un état valide
 * - ISOLATION : Les transactions ne se voient pas mutuellement
 * - DURABILITÉ : Les changements sont persistants après commit
 * 
 * EXEMPLE RÉEL :
 * 
 * // Transfert d'argent entre deux comptes
 * Database::beginTransaction();
 * try {
 *     // Débiter le compte A
 *     $pdo->exec("UPDATE accounts SET balance = balance - 100 WHERE id = 1");
 *     
 *     // Créditer le compte B
 *     $pdo->exec("UPDATE accounts SET balance = balance + 100 WHERE id = 2");
 *     
 *     Database::commit(); // Les deux opérations sont validées
 * } catch (\Exception $e) {
 *     Database::rollback(); // Si une opération échoue, tout est annulé
 *     throw $e;
 * }
 * 
 * CONFIGURATION
 * -------------
 * 
 * La configuration est chargée depuis Config :
 * 
 * // config/parameters.php
 * return [
 *     'database' => [
 *         'host' => 'localhost',
 *         'port' => 3306,
 *         'name' => 'myapp',
 *         'user' => 'root',
 *         'password' => 'secret',
 *         'charset' => 'utf8mb4',
 *     ],
 * ];
 * 
 * Ou depuis .env :
 * 
 * DB_HOST=localhost
 * DB_PORT=3306
 * DB_NAME=myapp
 * DB_USER=root
 * DB_PASS=secret
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
