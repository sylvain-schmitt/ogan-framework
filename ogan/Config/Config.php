<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ⚙️ CONFIG - Gestionnaire de Configuration
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Centralise la gestion de la configuration de l'application.
 * Supporte plusieurs sources :
 * - Fichiers PHP (parameters.php)
 * - Variables d'environnement (.env)
 * - Valeurs par défaut
 * 
 * POURQUOI UN GESTIONNAIRE DE CONFIG ?
 * -------------------------------------
 * 
 * 1. SÉPARATION DES CONFIGURATIONS :
 *    - Développement : config/dev.php
 *    - Production : config/prod.php
 *    - Test : config/test.php
 * 
 * 2. SÉCURITÉ :
 *    - Les secrets (DB password, API keys) dans .env (non versionné)
 *    - Les configs publiques dans parameters.php (versionné)
 * 
 * 3. FLEXIBILITÉ :
 *    - Changer de config sans modifier le code
 *    - Support de différents environnements
 * 
 * EXEMPLES D'UTILISATION :
 * ------------------------
 * 
 * // Récupérer une valeur
 * $dbHost = Config::get('database.host', 'localhost');
 * 
 * // Récupérer toute une section
 * $dbConfig = Config::get('database');
 * 
 * // Vérifier si une clé existe
 * if (Config::has('app.debug')) {
 *     // Mode debug activé
 * }
 * 
 * HIÉRARCHIE DES CONFIGURATIONS :
 * --------------------------------
 * 1. Variables d'environnement (.env) → PRIORITÉ MAXIMALE
 * 2. Fichier de config PHP (parameters.php)
 * 3. Valeurs par défaut
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Config;

class Config
{
    /**
     * @var array Configuration chargée
     */
    private static array $config = [];

    /**
     * @var bool Indique si la config a été initialisée
     */
    private static bool $initialized = false;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * INITIALISER LA CONFIGURATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Charge la configuration depuis :
     * 1. Le fichier .env (si présent)
     * 2. Le fichier parameters.yaml ou parameters.php
     * 
     * @param string $configPath Chemin vers le fichier parameters.yaml ou parameters.php
     * @param string|null $envPath Chemin vers le fichier .env (optionnel)
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function init(string $configPath, ?string $envPath = null): void
    {
        if (self::$initialized) {
            return; // Déjà initialisé
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Charger les fichiers .env (priorité maximale)
        // ─────────────────────────────────────────────────────────────
        // Hiérarchie : .env.local > .env
        if ($envPath === null) {
            // Chercher .env à la racine du projet
            $envPath = dirname($configPath, 2) . '/.env';
        }

        $projectRoot = dirname($envPath);

        // Charger .env d'abord (valeurs de base)
        if (file_exists($envPath)) {
            self::loadEnv($envPath);
        }

        // Charger .env.local ensuite (surcharge .env)
        $envLocalPath = $projectRoot . '/.env.local';
        if (file_exists($envLocalPath)) {
            self::loadEnv($envLocalPath);
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Charger le fichier de configuration (YAML ou PHP)
        // ─────────────────────────────────────────────────────────────
        $configLoaded = false;
        
        // Essayer YAML en priorité (.yaml ou .yml)
        $yamlPath = preg_replace('/\.php$/', '.yaml', $configPath);
        if (file_exists($yamlPath)) {
            $yamlConfig = YamlParser::parseFile($yamlPath);
            if (is_array($yamlConfig)) {
                self::$config = array_merge(self::$config, $yamlConfig);
                $configLoaded = true;
            }
        } else {
            $ymlPath = preg_replace('/\.php$/', '.yml', $configPath);
            if (file_exists($ymlPath)) {
                $yamlConfig = YamlParser::parseFile($ymlPath);
                if (is_array($yamlConfig)) {
                    self::$config = array_merge(self::$config, $yamlConfig);
                    $configLoaded = true;
                }
            }
        }
        
        // Fallback sur PHP si YAML non trouvé
        if (!$configLoaded && file_exists($configPath)) {
            $phpConfig = require $configPath;
            if (is_array($phpConfig)) {
                self::$config = array_merge(self::$config, $phpConfig);
            }
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 3 : Remplacer les valeurs par les variables d'env
        // ─────────────────────────────────────────────────────────────
        self::mergeEnvIntoConfig();

        self::$initialized = true;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHARGER LE FICHIER .ENV
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Parse un fichier .env et charge les variables dans $_ENV.
     * 
     * FORMAT DU FICHIER .ENV :
     * ------------------------
     * APP_ENV=prod
     * APP_DEBUG=false
     * DB_HOST=localhost
     * DB_NAME=myapp
     * DB_USER=root
     * DB_PASS=secret
     * 
     * NOTES :
     * - Les lignes vides sont ignorées
     * - Les lignes commençant par # sont des commentaires
     * - Les valeurs peuvent être entre guillemets
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private static function loadEnv(string $envPath): void
    {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parser KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Enlever les guillemets
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                // Charger dans $_ENV et putenv()
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * FUSIONNER LES VARIABLES D'ENVIRONNEMENT DANS LA CONFIG
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Les variables d'environnement ont la priorité sur le fichier PHP.
     * 
     * CONVENTION DE NOMMAGE :
     * -----------------------
     * Les variables d'env utilisent des underscores :
     * - APP_ENV → app.env
     * - DB_HOST → database.host
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private static function mergeEnvIntoConfig(): void
    {
        // Convertir les variables d'env en structure de config
        foreach ($_ENV as $key => $value) {
            // Convertir APP_ENV → app.env
            $configKey = strtolower(str_replace('_', '.', $key));

            // Convertir en structure imbriquée
            // DB_HOST → database.host
            if (str_starts_with($configKey, 'db.')) {
                $configKey = 'database.' . substr($configKey, 3);
            }
            
            // SESSION_NAME → session.name, SESSION_LIFETIME → session.lifetime, etc.
            if (str_starts_with($configKey, 'session.')) {
                // Déjà au bon format
            } elseif (str_starts_with($configKey, 'session_')) {
                $sessionKey = strtolower(substr($configKey, 8));
                // Convertir SESSION_NAME → session.name
                // Convertir SESSION_LIFETIME → session.lifetime
                $configKey = 'session.' . $sessionKey;
            }

            // Définir la valeur (les variables d'env ont la priorité)
            self::setNested($configKey, $value);
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉFINIR UNE VALEUR IMBRIQUÉE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Permet de définir database.host au lieu de ['database']['host'].
     * 
     * EXEMPLE :
     * ---------
     * setNested('database.host', 'localhost')
     * → $config['database']['host'] = 'localhost'
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private static function setNested(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER UNE VALEUR DE CONFIGURATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Récupère une valeur de configuration avec support de clés imbriquées.
     * 
     * EXEMPLES :
     * ----------
     * Config::get('app.env')           → 'prod'
     * Config::get('database.host')     → 'localhost'
     * Config::get('database')           → ['host' => 'localhost', ...]
     * Config::get('missing', 'default') → 'default'
     * 
     * @param string $key Clé de configuration (supporte la notation point)
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed La valeur de configuration ou la valeur par défaut
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$initialized) {
            throw new \RuntimeException('Config n\'a pas été initialisée. Appelez Config::init() d\'abord.');
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UNE CLÉ EXISTE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $key Clé de configuration
     * @return bool TRUE si la clé existe, FALSE sinon
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function has(string $key): bool
    {
        if (!self::$initialized) {
            return false;
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉFINIR UNE VALEUR DE CONFIGURATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Utile pour les tests ou pour modifier la config à la volée.
     * 
     * @param string $key Clé de configuration
     * @param mixed $value Valeur à définir
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::$initialized) {
            self::$config = [];
            self::$initialized = true;
        }

        self::setNested($key, $value);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER TOUTE LA CONFIGURATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @return array Toute la configuration
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function all(): array
    {
        if (!self::$initialized) {
            return [];
        }

        return self::$config;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * POURQUOI UNE CLASSE STATIQUE ?
 * --------------------------------
 * 
 * Config est une classe statique car :
 * 1. Il n'y a qu'UNE SEULE configuration pour toute l'application
 * 2. On veut y accéder facilement : Config::get('key')
 * 3. Pas besoin d'instancier plusieurs fois
 * 
 * ALTERNATIVE : Singleton Pattern
 * --------------------------------
 * 
 * On pourrait aussi utiliser un singleton :
 * 
 * $config = Config::getInstance();
 * $config->get('key');
 * 
 * Mais la classe statique est plus simple pour ce cas d'usage.
 * 
 * SÉCURITÉ DES VARIABLES D'ENVIRONNEMENT
 * ---------------------------------------
 * 
 * ⚠️ IMPORTANT : Ne JAMAIS commiter le fichier .env dans Git !
 * 
 * Le fichier .env contient des secrets :
 * - Mots de passe de base de données
 * - Clés API
 * - Tokens d'authentification
 * 
 * Ajouter .env dans .gitignore :
 * 
 * # .gitignore
 * .env
 * .env.local
 * 
 * HIÉRARCHIE DES CONFIGURATIONS
 * ------------------------------
 * 
 * 1. Variables d'environnement (.env) → PRIORITÉ MAXIMALE
 *    Utile pour : secrets, configs spécifiques à l'environnement
 * 
 * 2. Fichier PHP (parameters.php) → PRIORITÉ MOYENNE
 *    Utile pour : configs par défaut, structure de l'app
 * 
 * 3. Valeurs par défaut dans le code → PRIORITÉ MINIMALE
 *    Utile pour : fallback, valeurs sûres
 * 
 * EXEMPLE D'UTILISATION DANS LE KERNEL
 * -------------------------------------
 * 
 * // Dans Kernel.php
 * Config::init(__DIR__ . '/../config/parameters.php');
 * 
 * $debug = Config::get('app.debug', false);
 * $dbHost = Config::get('database.host', 'localhost');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
