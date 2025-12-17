<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         CACHE HELPERS
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Fonctions globales pour un accès simplifié au cache.
 * 
 * Utilisation:
 * ---------
 * // Accéder au manager
 * cache()->set('key', 'value', 3600);
 * $value = cache()->get('key');
 * 
 * // Shorthand pour get
 * $value = cache('key');
 * $value = cache('key', 'default');
 * 
 * // Pattern remember
 * $users = cache()->remember('users.all', 300, fn() => User::all());
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

use Ogan\Cache\CacheManager;
use Ogan\Cache\CacheInterface;

if (!function_exists('cache')) {
    /**
     * Helper global pour le cache
     * 
     * Sans arguments : retourne le CacheManager pour accéder aux méthodes
     * Avec un argument : raccourci pour cache()->get($key)
     * Avec deux arguments : raccourci pour cache()->get($key, $default)
     * 
     * @param string|null $key     Clé à récupérer
     * @param mixed       $default Valeur par défaut
     * @return CacheInterface|mixed
     * 
     * @example
     * // Accès au manager
     * cache()->set('user.42', $user, 3600);
     * cache()->delete('user.42');
     * 
     * // Raccourcis get
     * $user = cache('user.42');
     * $user = cache('user.42', null);
     * 
     * // Pattern remember
     * $users = cache()->remember('users.active', 300, function() {
     *     return User::where('active', true)->get();
     * });
     */
    function cache(?string $key = null, mixed $default = null): mixed
    {
        $store = CacheManager::store();

        // Sans argument, retourne le store
        if ($key === null) {
            return $store;
        }

        // Avec clé, c'est un raccourci pour get
        return $store->get($key, $default);
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Supprime une entrée du cache
     * 
     * @param string $key Clé à supprimer
     * @return bool
     */
    function cache_forget(string $key): bool
    {
        return CacheManager::delete($key);
    }
}

if (!function_exists('cache_clear')) {
    /**
     * Vide tout le cache
     * 
     * @return bool
     */
    function cache_clear(): bool
    {
        return CacheManager::clear();
    }
}

if (!function_exists('cache_remember')) {
    /**
     * Récupère ou calcule une valeur (cache-aside pattern)
     * 
     * @param string   $key      Clé unique
     * @param int      $ttl      Durée de vie en secondes
     * @param callable $callback Fonction qui génère la valeur
     * @return mixed
     * 
     * @example
     * $users = cache_remember('users.all', 300, function() {
     *     return DB::table('users')->get();
     * });
     */
    function cache_remember(string $key, int $ttl, callable $callback): mixed
    {
        return CacheManager::remember($key, $ttl, $callback);
    }
}
