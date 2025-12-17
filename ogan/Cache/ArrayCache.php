<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         ARRAY CACHE DRIVER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Driver de cache en mémoire (tableau PHP).
 * Idéal pour les tests et le cache temporaire pendant une requête.
 * 
 * ATTENTION: Le cache est perdu à la fin de la requête !
 * 
 * Utilisation:
 * ---------
 * $cache = new ArrayCache();
 * $cache->set('key', 'value', 300);
 * $value = $cache->get('key');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Cache;

class ArrayCache extends AbstractCache
{
    /**
     * Stockage en mémoire
     */
    protected array $storage = [];

    // ═══════════════════════════════════════════════════════════════════
    // CONSTRUCTEUR
    // ═══════════════════════════════════════════════════════════════════

    /**
     * @param int $defaultTtl TTL par défaut en secondes
     */
    public function __construct(int $defaultTtl = 3600)
    {
        $this->defaultTtl = $defaultTtl;
    }

    // ═══════════════════════════════════════════════════════════════════
    // IMPLÉMENTATION DES MÉTHODES ABSTRAITES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * @inheritDoc
     */
    protected function read(string $key): ?array
    {
        return $this->storage[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    protected function write(string $key, array $data): bool
    {
        $this->storage[$key] = $data;
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function remove(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function flush(): bool
    {
        $this->storage = [];
        return true;
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES UTILITAIRES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Retourne le nombre d'entrées en cache
     */
    public function count(): int
    {
        return count($this->storage);
    }

    /**
     * Retourne toutes les clés en cache
     */
    public function keys(): array
    {
        return array_keys($this->storage);
    }

    /**
     * Retourne des statistiques sur le cache
     */
    public function getStats(): array
    {
        $valid = 0;
        $expired = 0;

        foreach ($this->storage as $data) {
            if ($data['expires_at'] === null || $data['expires_at'] >= time()) {
                $valid++;
            } else {
                $expired++;
            }
        }

        return [
            'count' => count($this->storage),
            'valid' => $valid,
            'expired' => $expired,
            'memory' => strlen(serialize($this->storage)),
        ];
    }
}
