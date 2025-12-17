<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         CACHE INTERFACE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Interface commune pour tous les drivers de cache, inspirée de PSR-16.
 * 
 * Utilisation:
 * ---------
 * $cache->set('key', 'value', 3600);  // Cache 1 heure
 * $value = $cache->get('key');
 * $cache->delete('key');
 * 
 * // Pattern callback (le plus utile)
 * $users = $cache->remember('users.all', 300, fn() => User::all());
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Cache;

interface CacheInterface
{
    /**
     * Récupère une valeur du cache
     * 
     * @param string $key     Clé unique
     * @param mixed  $default Valeur par défaut si non trouvée
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Stocke une valeur dans le cache
     * 
     * @param string   $key   Clé unique
     * @param mixed    $value Valeur à stocker (sera sérialisée)
     * @param int|null $ttl   Durée de vie en secondes (null = défaut config)
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Vérifie si une clé existe dans le cache
     * 
     * @param string $key Clé à vérifier
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Supprime une entrée du cache
     * 
     * @param string $key Clé à supprimer
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Vide tout le cache
     * 
     * @return bool
     */
    public function clear(): bool;

    /**
     * Récupère ou calcule une valeur (cache-aside pattern)
     * 
     * Si la clé existe, retourne la valeur en cache.
     * Sinon, exécute le callback, met en cache et retourne le résultat.
     * 
     * @param string   $key      Clé unique
     * @param int      $ttl      Durée de vie en secondes
     * @param callable $callback Fonction qui génère la valeur
     * @return mixed
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Récupère plusieurs valeurs du cache
     * 
     * @param array $keys    Liste des clés
     * @param mixed $default Valeur par défaut pour les clés manquantes
     * @return array Tableau associatif clé => valeur
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Stocke plusieurs valeurs dans le cache
     * 
     * @param array    $values Tableau associatif clé => valeur
     * @param int|null $ttl    Durée de vie en secondes
     * @return bool
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Supprime plusieurs entrées du cache
     * 
     * @param array $keys Liste des clés à supprimer
     * @return bool
     */
    public function deleteMultiple(array $keys): bool;
}
