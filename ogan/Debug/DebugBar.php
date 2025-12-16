<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📊 DEBUG BAR - Collecteur de données de debug
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Collecte les informations de debug pendant l'exécution de la requête
 * et les rend disponibles pour affichage dans la barre de debug.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Debug;

class DebugBar
{
    private static ?self $instance = null;
    
    private float $startTime;
    private int $startMemory;
    private array $queries = [];
    private array $messages = [];
    private ?array $route = null;
    private ?array $user = null;
    private bool $enabled = true;
    
    private function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }
    
    /**
     * Récupère l'instance singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Démarre la collecte (appelé au début de la requête)
     */
    public static function start(): void
    {
        self::getInstance();
    }
    
    /**
     * Vérifie si la debug bar est activée
     */
    public static function isEnabled(): bool
    {
        return self::getInstance()->enabled;
    }
    
    /**
     * Active/désactive la debug bar
     */
    public static function setEnabled(bool $enabled): void
    {
        self::getInstance()->enabled = $enabled;
    }
    
    /**
     * Ajoute une requête SQL
     */
    public static function addQuery(string $sql, float $time, array $params = []): void
    {
        self::getInstance()->queries[] = [
            'sql' => $sql,
            'time' => $time,
            'params' => $params,
            'backtrace' => self::getShortBacktrace()
        ];
    }
    
    /**
     * Ajoute un message de debug
     */
    public static function addMessage(string $message, string $type = 'info'): void
    {
        self::getInstance()->messages[] = [
            'message' => $message,
            'type' => $type,
            'time' => microtime(true) - self::getInstance()->startTime
        ];
    }
    
    /**
     * Définit les informations de route
     */
    public static function setRoute(array $route): void
    {
        self::getInstance()->route = $route;
    }
    
    /**
     * Définit les informations utilisateur
     */
    public static function setUser(?array $user): void
    {
        self::getInstance()->user = $user;
    }
    
    /**
     * Récupère toutes les données collectées
     */
    public static function getData(): array
    {
        $instance = self::getInstance();
        
        $totalQueryTime = array_sum(array_column($instance->queries, 'time'));
        $executionTime = (microtime(true) - $instance->startTime) * 1000;
        $memoryPeak = memory_get_peak_usage(true);
        $memoryUsed = memory_get_usage(true);
        
        return [
            'time' => [
                'start' => $instance->startTime,
                'execution_ms' => round($executionTime, 2),
                'execution_formatted' => self::formatTime($executionTime)
            ],
            'memory' => [
                'current' => self::formatBytes($memoryUsed),
                'peak' => self::formatBytes($memoryPeak),
                'current_bytes' => $memoryUsed,
                'peak_bytes' => $memoryPeak
            ],
            'queries' => [
                'count' => count($instance->queries),
                'total_time_ms' => round($totalQueryTime * 1000, 2),
                'list' => $instance->queries
            ],
            'route' => $instance->route,
            'user' => $instance->user,
            'messages' => $instance->messages,
            'request' => self::getRequestData(),
            'response' => self::getResponseData(),
            'config' => self::getConfigData(),
            'session' => self::getSessionData(),
            'includes' => [
                'count' => count(get_included_files()),
                'files' => get_included_files()
            ]
        ];
    }
    
    /**
     * Rend la debug bar en HTML
     */
    public static function render(): string
    {
        if (!self::getInstance()->enabled) {
            return '';
        }
        
        return DebugBarRenderer::render(self::getData());
    }
    
    /**
     * Données de la requête
     */
    private static function getRequestData(): array
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
            'get' => $_GET,
            'post' => $_POST
        ];
    }
    
    /**
     * Données de la réponse
     */
    private static function getResponseData(): array
    {
        return [
            'status_code' => http_response_code() ?: 200,
            'headers' => headers_list()
        ];
    }
    
    /**
     * Données de configuration
     */
    private static function getConfigData(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'framework_version' => 'Ogan 1.0',
            'env' => $_ENV['APP_ENV'] ?? 'dev',
            'debug' => $_ENV['APP_DEBUG'] ?? true,
            'timezone' => date_default_timezone_get()
        ];
    }
    
    /**
     * Données de session
     */
    private static function getSessionData(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return ['status' => 'inactive'];
        }
        
        return [
            'status' => 'active',
            'id' => session_id(),
            'data' => $_SESSION ?? []
        ];
    }
    
    /**
     * Backtrace court pour les queries
     */
    private static function getShortBacktrace(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
        $relevant = [];
        
        foreach ($trace as $frame) {
            if (isset($frame['file']) && strpos($frame['file'], '/ogan/') === false) {
                $relevant[] = basename($frame['file']) . ':' . ($frame['line'] ?? '?');
            }
        }
        
        return implode(' → ', array_slice($relevant, 0, 3));
    }
    
    /**
     * Formate le temps
     */
    private static function formatTime(float $ms): string
    {
        if ($ms < 1) {
            return round($ms * 1000, 2) . ' µs';
        }
        if ($ms < 1000) {
            return round($ms, 2) . ' ms';
        }
        return round($ms / 1000, 2) . ' s';
    }
    
    /**
     * Formate les bytes
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Reset la debug bar (pour les tests)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
