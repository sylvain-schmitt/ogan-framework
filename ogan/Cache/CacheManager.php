<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         CACHE MANAGER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Factory et gestionnaire pour les différents stores de cache.
 * Permet de configurer et accéder aux différents drivers de cache.
 * 
 * Utilisation:
 * ---------
 * // Récupérer le store par défaut
 * $cache = CacheManager::store();
 * 
 * // Récupérer un store spécifique
 * $cache = CacheManager::store('array');
 * 
 * // Avec le helper global
 * cache()->set('key', 'value');
 * $value = cache('key'); // shorthand pour get
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Cache;

class CacheManager
{
    /**
     * Instance singleton
     */
    protected static ?self $instance = null;

    /**
     * Stores de cache instanciés
     */
    protected array $stores = [];

    /**
     * Configuration du cache
     */
    protected array $config = [];

    /**
     * Driver par défaut
     */
    protected string $defaultDriver = 'file';

    // ═══════════════════════════════════════════════════════════════════
    // SINGLETON & CONFIGURATION
    // ═══════════════════════════════════════════════════════════════════

    private function __construct() {}

    /**
     * Obtient l'instance singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Configure le manager
     */
    public static function configure(array $config): void
    {
        $instance = self::getInstance();
        $instance->config = $config;

        if (isset($config['driver'])) {
            $instance->defaultDriver = $config['driver'];
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // ACCÈS AUX STORES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Récupère un store de cache
     * 
     * @param string|null $name Nom du driver (null = défaut)
     */
    public static function store(?string $name = null): CacheInterface
    {
        $instance = self::getInstance();
        $name = $name ?? $instance->defaultDriver;

        // Retourne le store existant si déjà créé
        if (isset($instance->stores[$name])) {
            return $instance->stores[$name];
        }

        // Crée le store
        $store = $instance->createStore($name);
        $instance->stores[$name] = $store;

        return $store;
    }

    /**
     * Crée un store de cache
     */
    protected function createStore(string $name): CacheInterface
    {
        $config = $this->config['stores'][$name] ?? [];

        return match ($name) {
            'file' => $this->createFileStore($config),
            'array' => $this->createArrayStore($config),
            default => throw new \InvalidArgumentException(
                "Driver de cache inconnu: {$name}. Drivers disponibles: file, array"
            ),
        };
    }

    /**
     * Crée un store FileCache
     */
    protected function createFileStore(array $config): FileCache
    {
        $path = $config['path'] ?? ($this->config['path'] ?? getcwd() . '/var/cache/data');
        $ttl = $config['ttl'] ?? ($this->config['default_ttl'] ?? 3600);

        return new FileCache($path, $ttl);
    }

    /**
     * Crée un store ArrayCache
     */
    protected function createArrayStore(array $config): ArrayCache
    {
        $ttl = $config['ttl'] ?? ($this->config['default_ttl'] ?? 3600);

        return new ArrayCache($ttl);
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES PROXY (simplifient l'utilisation)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Récupère une valeur du cache par défaut
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::store()->get($key, $default);
    }

    /**
     * Stocke une valeur dans le cache par défaut
     */
    public static function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return self::store()->set($key, $value, $ttl);
    }

    /**
     * Vérifie si une clé existe dans le cache par défaut
     */
    public static function has(string $key): bool
    {
        return self::store()->has($key);
    }

    /**
     * Supprime une entrée du cache par défaut
     */
    public static function delete(string $key): bool
    {
        return self::store()->delete($key);
    }

    /**
     * Vide tout le cache par défaut
     */
    public static function clear(): bool
    {
        return self::store()->clear();
    }

    /**
     * Récupère ou calcule une valeur (cache-aside pattern)
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        return self::store()->remember($key, $ttl, $callback);
    }

    // ═══════════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Vide tous les stores
     */
    public static function flush(): bool
    {
        $instance = self::getInstance();
        foreach ($instance->stores as $store) {
            $store->clear();
        }
        return true;
    }

    /**
     * Retourne la liste des drivers disponibles
     */
    public static function getAvailableDrivers(): array
    {
        return ['file', 'array'];
    }

    /**
     * Retourne le nom du driver par défaut
     */
    public static function getDefaultDriver(): string
    {
        return self::getInstance()->defaultDriver;
    }

    /**
     * Définit le driver par défaut
     */
    public static function setDefaultDriver(string $driver): void
    {
        self::getInstance()->defaultDriver = $driver;
    }

    /**
     * Réinitialise le manager (utile pour les tests)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
