<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 LOGGER HELPERS - Fonctions globales de logging
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ces fonctions sont automatiquement disponibles dans toute l'application.
 * 
 * USAGE :
 * -------
 * logger()->info('User logged in', ['user_id' => 123]);
 * logger()->error('Database error', ['error' => $e->getMessage()]);
 * logger('security')->warning('Failed login attempt');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

use Ogan\Logger\Logger;

/**
 * Instance singleton du logger
 */
$_logger_instance = null;

if (!function_exists('logger')) {
    /**
     * Retourne l'instance du logger.
     * 
     * EXEMPLES :
     * ----------
     * // Logging basique
     * logger()->info('Message');
     * logger()->error('Error', ['details' => $e->getMessage()]);
     * 
     * // Avec channel spécifique
     * logger('security')->warning('Login failed');
     * logger('database')->debug('Query executed', ['sql' => $query]);
     * 
     * @param string|null $channel Channel optionnel (app, security, database, etc.)
     * @return Logger
     */
    function logger(?string $channel = null): Logger
    {
        global $_logger_instance;
        
        if ($_logger_instance === null) {
            // Déterminer le chemin des logs
            $logPath = defined('LOG_PATH') 
                ? LOG_PATH 
                : dirname(__DIR__, 2) . '/var/log';
            
            // Créer le répertoire si nécessaire
            if (!is_dir($logPath)) {
                mkdir($logPath, 0755, true);
            }
            
            // Niveau de log selon l'environnement
            $minLevel = ($_ENV['APP_ENV'] ?? 'dev') === 'prod' ? 'info' : 'debug';
            
            // Format JSON en production
            $jsonFormat = ($_ENV['APP_ENV'] ?? 'dev') === 'prod';
            
            $_logger_instance = new Logger($logPath, $minLevel, 'app', $jsonFormat);
        }
        
        if ($channel !== null) {
            return $_logger_instance->channel($channel);
        }
        
        return $_logger_instance;
    }
}

if (!function_exists('log_exception')) {
    /**
     * Log une exception avec le contexte complet.
     * 
     * @param Throwable $exception L'exception à logger
     * @param string $channel Channel optionnel
     */
    function log_exception(\Throwable $exception, string $channel = 'app'): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
        
        // Ajouter les infos de requête si disponibles
        if (isset($_SERVER['REQUEST_URI'])) {
            $context['url'] = $_SERVER['REQUEST_URI'];
            $context['method'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        }
        
        logger($channel)->error($exception->getMessage(), $context);
    }
}

if (!function_exists('log_info')) {
    /**
     * Shortcut pour logger une info.
     */
    function log_info(string $message, array $context = []): void
    {
        logger()->info($message, $context);
    }
}

if (!function_exists('log_error')) {
    /**
     * Shortcut pour logger une erreur.
     */
    function log_error(string $message, array $context = []): void
    {
        logger()->error($message, $context);
    }
}

if (!function_exists('log_debug')) {
    /**
     * Shortcut pour logger un message de debug.
     */
    function log_debug(string $message, array $context = []): void
    {
        logger()->debug($message, $context);
    }
}

if (!function_exists('log_warning')) {
    /**
     * Shortcut pour logger un warning.
     */
    function log_warning(string $message, array $context = []): void
    {
        logger()->warning($message, $context);
    }
}
