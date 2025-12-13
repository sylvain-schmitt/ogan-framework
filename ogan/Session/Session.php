<?php

namespace Ogan\Session;

class Session implements SessionInterface
{
    private const FLASH_KEY = '_flash';

    /**
     * @var array Configuration de la session
     */
    private array $config = [];

    public function __construct(?array $config = null)
    {
        // Charger la configuration depuis Config si disponible
        if ($config === null && class_exists(\Ogan\Config\Config::class)) {
            $this->config = \Ogan\Config\Config::get('session', []);
        } else {
            $this->config = $config ?? [];
        }
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurer les paramètres du cookie de session AVANT session_start()
            $this->configureSessionCookie();
            
            session_start();
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONFIGURER LE COOKIE DE SESSION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Configure les paramètres de sécurité du cookie de session
     * selon la configuration du framework.
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function configureSessionCookie(): void
    {
        $name = $this->config['name'] ?? 'OGAN_SESSION';
        $lifetime = $this->config['lifetime'] ?? 7200;
        $path = $this->config['path'] ?? '/';
        $domain = $this->config['domain'] ?? '';
        $secure = $this->config['secure'] ?? false;
        $httponly = $this->config['httponly'] ?? true;
        $samesite = $this->config['samesite'] ?? 'Lax';

        // Configurer le nom de la session
        session_name($name);

        // Configurer les paramètres du cookie
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);

        // Configurer la durée de vie de la session
        ini_set('session.gc_maxlifetime', (string)$lifetime);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $_SESSION);
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $this->start();
        $_SESSION = [];
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        session_destroy();
    }

    public function setFlash(string $type, string $message): void
    {
        $this->start();
        $_SESSION[self::FLASH_KEY][$type][] = $message;
    }

    public function getFlashes(string $type): array
    {
        $this->start();
        if (!isset($_SESSION[self::FLASH_KEY][$type])) {
            return [];
        }

        $messages = $_SESSION[self::FLASH_KEY][$type];
        // Les messages flash doivent être supprimés après lecture
        unset($_SESSION[self::FLASH_KEY][$type]);
        
        return $messages;
    }

    public function hasFlash(string $type): bool
    {
        $this->start();
        return isset($_SESSION[self::FLASH_KEY][$type]) && !empty($_SESSION[self::FLASH_KEY][$type]);
    }

    public function getFlash(string $type, ?string $default = null): ?string
    {
        $this->start();
        if (!isset($_SESSION[self::FLASH_KEY][$type]) || empty($_SESSION[self::FLASH_KEY][$type])) {
            return $default;
        }

        $messages = $_SESSION[self::FLASH_KEY][$type];
        // Récupérer le premier message
        $message = is_array($messages) ? ($messages[0] ?? null) : $messages;
        
        // Supprimer le message après lecture
        if (is_array($messages) && count($messages) > 1) {
            array_shift($_SESSION[self::FLASH_KEY][$type]);
        } else {
            unset($_SESSION[self::FLASH_KEY][$type]);
        }
        
        return $message ?? $default;
    }

    /**
     * Récupère TOUS les messages flash de tous les types et les supprime
     */
    public function getAllFlashes(): array
    {
        $this->start();
        
        if (!isset($_SESSION[self::FLASH_KEY]) || empty($_SESSION[self::FLASH_KEY])) {
            return [];
        }

        $allFlashes = $_SESSION[self::FLASH_KEY];
        
        // Supprimer tous les flash messages après lecture
        unset($_SESSION[self::FLASH_KEY]);
        
        return $allFlashes;
    }

    public function migrate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function regenerate(): void
    {
        $this->migrate();
    }

    public function getId(): string
    {
        $this->start();
        return session_id();
    }
}
