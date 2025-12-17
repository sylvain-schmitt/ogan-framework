<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         FILE CACHE DRIVER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Driver de cache utilisant le système de fichiers.
 * Chaque entrée est stockée dans un fichier sérialisé.
 * 
 * Utilisation:
 * ---------
 * $cache = new FileCache('/path/to/cache');
 * $cache->set('users.all', $users, 300);
 * $users = $cache->get('users.all');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Cache;

class FileCache extends AbstractCache
{
    /**
     * Répertoire de stockage du cache
     */
    protected string $directory;

    /**
     * Extension des fichiers cache
     */
    protected string $extension = '.cache';

    // ═══════════════════════════════════════════════════════════════════
    // CONSTRUCTEUR
    // ═══════════════════════════════════════════════════════════════════

    /**
     * @param string $directory Répertoire de stockage
     * @param int    $defaultTtl TTL par défaut en secondes
     */
    public function __construct(string $directory, int $defaultTtl = 3600)
    {
        $this->directory = rtrim($directory, '/\\');
        $this->defaultTtl = $defaultTtl;

        // Créer le répertoire si nécessaire
        if (!is_dir($this->directory)) {
            if (!mkdir($this->directory, 0755, true)) {
                throw new \RuntimeException(
                    "Impossible de créer le répertoire de cache: {$this->directory}"
                );
            }
        }

        if (!is_writable($this->directory)) {
            throw new \RuntimeException(
                "Le répertoire de cache n'est pas accessible en écriture: {$this->directory}"
            );
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // IMPLÉMENTATION DES MÉTHODES ABSTRAITES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * @inheritDoc
     */
    protected function read(string $key): ?array
    {
        $path = $this->getPath($key);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = @unserialize($content);
        if ($data === false) {
            // Fichier corrompu, on le supprime
            @unlink($path);
            return null;
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function write(string $key, array $data): bool
    {
        $path = $this->getPath($key);
        $dir = dirname($path);

        // Créer le sous-répertoire si nécessaire
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Écriture atomique avec fichier temporaire
        $temp = $path . '.tmp.' . uniqid('', true);
        
        if (file_put_contents($temp, serialize($data), LOCK_EX) === false) {
            return false;
        }

        // Renommer atomiquement
        if (!rename($temp, $path)) {
            @unlink($temp);
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function remove(string $key): bool
    {
        $path = $this->getPath($key);

        if (!file_exists($path)) {
            return true; // Déjà supprimé
        }

        return @unlink($path);
    }

    /**
     * @inheritDoc
     */
    protected function flush(): bool
    {
        return $this->deleteDirectory($this->directory, false);
    }

    // ═══════════════════════════════════════════════════════════════════
    // MÉTHODES UTILITAIRES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Génère le chemin du fichier cache pour une clé
     * 
     * Utilise une structure de sous-répertoires basée sur le hash
     * pour éviter les problèmes de performances avec beaucoup de fichiers.
     */
    protected function getPath(string $key): string
    {
        $hash = sha1($key);
        
        // Structure: cache/ab/cd/abcdef123456.cache
        $subDir = substr($hash, 0, 2) . DIRECTORY_SEPARATOR . substr($hash, 2, 2);
        
        return $this->directory 
            . DIRECTORY_SEPARATOR 
            . $subDir 
            . DIRECTORY_SEPARATOR 
            . $hash 
            . $this->extension;
    }

    /**
     * Supprime un répertoire et son contenu
     * 
     * @param string $dir       Répertoire à supprimer
     * @param bool   $deleteDir Supprimer aussi le répertoire racine
     */
    protected function deleteDirectory(string $dir, bool $deleteDir = true): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        if ($deleteDir) {
            @rmdir($dir);
        }

        return true;
    }

    /**
     * Nettoie les entrées expirées du cache
     * 
     * @return int Nombre d'entrées supprimées
     */
    public function gc(): int
    {
        $deleted = 0;

        if (!is_dir($this->directory)) {
            return $deleted;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($items as $item) {
            if ($item->isFile() && $item->getExtension() === 'cache') {
                $content = file_get_contents($item->getRealPath());
                $data = @unserialize($content);

                if ($data === false || 
                    ($data['expires_at'] !== null && $data['expires_at'] < time())) {
                    @unlink($item->getRealPath());
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Retourne des statistiques sur le cache
     */
    public function getStats(): array
    {
        $count = 0;
        $size = 0;
        $expired = 0;

        if (!is_dir($this->directory)) {
            return compact('count', 'size', 'expired');
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($items as $item) {
            if ($item->isFile() && $item->getExtension() === 'cache') {
                $count++;
                $size += $item->getSize();

                $content = file_get_contents($item->getRealPath());
                $data = @unserialize($content);
                
                if ($data !== false && 
                    $data['expires_at'] !== null && 
                    $data['expires_at'] < time()) {
                    $expired++;
                }
            }
        }

        return [
            'count' => $count,
            'size' => $size,
            'size_human' => $this->formatBytes($size),
            'expired' => $expired,
            'directory' => $this->directory,
        ];
    }

    /**
     * Formate une taille en octets de manière lisible
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
