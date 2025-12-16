<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔧 DEBUG HELPERS - Fonctions globales de debug
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Fonctions inspirées de Symfony pour le debugging :
 * - dump()  : Affiche une ou plusieurs variables
 * - dd()    : Dump and Die - affiche et arrête l'exécution
 * - d()     : Retourne le HTML sans l'afficher
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

use Ogan\Debug\Dumper;

if (!function_exists('dump')) {
    /**
     * Affiche une ou plusieurs variables de manière élégante
     * 
     * @param mixed ...$vars Les variables à afficher
     * @return void
     * 
     * @example
     * dump($user);
     * dump($request, $response);
     */
    function dump(mixed ...$vars): void
    {
        // Récupérer le fichier et la ligne d'appel
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $backtrace[0]['file'] ?? null;
        $line = $backtrace[0]['line'] ?? null;
        
        foreach ($vars as $var) {
            echo Dumper::dump($var, $file, $line);
        }
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and Die - Affiche les variables et arrête l'exécution
     * 
     * @param mixed ...$vars Les variables à afficher
     * @return never
     * 
     * @example
     * dd($user);
     * dd($request, $response, $data);
     */
    function dd(mixed ...$vars): never
    {
        // Récupérer le fichier et la ligne d'appel
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $backtrace[0]['file'] ?? null;
        $line = $backtrace[0]['line'] ?? null;
        
        // Nettoyer la sortie
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Header HTML minimal
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        echo '<!DOCTYPE html><html><head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>dd() - Debug</title>';
        echo '<style>body{font-family:system-ui,-apple-system,sans-serif;background:#1e1e2e;margin:20px;}</style>';
        echo '</head><body>';
        
        foreach ($vars as $var) {
            echo Dumper::dump($var, $file, $line);
        }
        
        // Footer avec info
        echo '<div style="font-family:monospace;font-size:11px;color:#6c7086;margin-top:20px;padding:10px;background:rgba(0,0,0,0.2);border-radius:4px;">';
        echo '🛑 Execution stopped by <code>dd()</code>';
        if ($file && $line) {
            echo ' at <strong>' . basename($file) . ':' . $line . '</strong>';
        }
        echo '</div>';
        
        echo '</body></html>';
        
        exit(1);
    }
}

if (!function_exists('d')) {
    /**
     * Retourne le HTML du dump sans l'afficher
     * Utile pour stocker dans une variable ou logger
     * 
     * @param mixed $var La variable à dumper
     * @return string Le HTML généré
     * 
     * @example
     * $html = d($user);
     * error_log(strip_tags($html));
     */
    function d(mixed $var): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $backtrace[0]['file'] ?? null;
        $line = $backtrace[0]['line'] ?? null;
        
        return Dumper::dump($var, $file, $line);
    }
}
