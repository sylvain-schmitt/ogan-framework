<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         ABSTRACT CACHE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Classe abstraite fournissant l'implémentation commune des méthodes
 * de cache. Les drivers concrets doivent implémenter les méthodes de base.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Cache;

abstract class AbstractCache implements CacheInterface
{
    /**
     * TTL par défaut en secondes (1 heure)
     */
    protected int $defaultTtl = 3600;

    /**
     * Préfixe pour les clés (évite les collisions)
     */
    protected string $prefix = 'ogan_';

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES ABSTRAITES (à implémenter par les drivers)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Lit les données brutes du backend
     */
    abstract protected function read(string $key): ?array;

    /**
     * Écrit les données brutes dans le backend
     */
    abstract protected function write(string $key, array $data): bool;

    /**
     * Supprime une entrée du backend
     */
    abstract protected function remove(string $key): bool;

    /**
     * Vide tout le backend
     */
    abstract protected function flush(): bool;

    // ═══════════════════════════════════════════════════════════════════
    // IMPLÉMENTATION COMMUNE
    // ═══════════════════════════════════════════════════════════════════

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $data = $this->read($this->prefixKey($key));

        if ($data === null) {
            return $default;
        }

        // Vérifier l'expiration
        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $expiresAt = $ttl > 0 ? time() + $ttl : null;

        return $this->write($this->prefixKey($key), [
            'value' => $value,
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return $this->remove($this->prefixKey($key));
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->flush();
    }

    /**
     * @inheritDoc
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key, $this);

        // Si trouvé en cache (on compare avec $this comme sentinel)
        if ($value !== $this) {
            return $value;
        }

        // Calculer la valeur
        $value = $callback();

        // Stocker en cache
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    // ═══════════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Ajoute le préfixe à une clé
     */
    protected function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Définit le TTL par défaut
     */
    public function setDefaultTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;
        return $this;
    }

    /**
     * Définit le préfixe des clés
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Valide une clé de cache
     * 
     * @throws \InvalidArgumentException Si la clé est invalide
     */
    protected function validateKey(string $key): void
    {
        if ($key === '') {
            throw new \InvalidArgumentException('La clé de cache ne peut pas être vide.');
        }

        if (preg_match('/[{}()\/@:\\\\]/', $key)) {
            throw new \InvalidArgumentException(
                "La clé de cache contient des caractères interdits: {}()/\\@:"
            );
        }
    }
}
