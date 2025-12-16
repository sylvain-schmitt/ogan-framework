<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔧 DEBUG BAR MIDDLEWARE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Middleware pour injecter automatiquement la Debug Bar dans les réponses HTML.
 * S'active uniquement en mode dev.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware;

use Ogan\Debug\DebugBar;
use Ogan\Config\Config;
use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Http\Response;

class DebugBarMiddleware implements MiddlewareInterface
{
    /**
     * Traite la requête et injecte la debug bar dans la réponse
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Démarrer la collecte
        DebugBar::start();
        
        // Vérifier si on doit activer la debug bar
        $env = Config::get('app.env', 'dev');
        $debugEnabled = Config::get('debug.enabled', true);
        $debugBarEnabled = Config::get('debug.debug_bar', true);
        
        $shouldInject = ($env === 'dev') && $debugEnabled && $debugBarEnabled;
        
        DebugBar::setEnabled($shouldInject);
        
        // Collecter les infos de la requête
        DebugBar::addMessage('Request started', 'info');
        
        // Exécuter la requête
        $response = $next($request);
        
        // Capturer les infos utilisateur depuis la session
        if ($shouldInject && session_status() === PHP_SESSION_ACTIVE) {
            $userId = $_SESSION['_auth_user_id'] ?? null;
            $userRoles = $_SESSION['_auth_user_roles'] ?? [];
            
            if ($userId) {
                // Essayer de trouver plus d'infos dans la session
                $userName = $_SESSION['_auth_user_name'] ?? null;
                $userEmail = $_SESSION['_auth_user_email'] ?? null;
                
                DebugBar::setUser([
                    'id' => $userId,
                    'name' => $userName ?? 'User #' . $userId,
                    'email' => $userEmail,
                    'roles' => implode(', ', $userRoles)
                ]);
            }
        }
        
        // Si pas d'injection, retourner directement
        if (!$shouldInject) {
            return $response;
        }
        
        // Si la réponse est un ResponseInterface et contient du HTML
        if ($response instanceof Response) {
            $content = $response->getContent();
            if (is_string($content) && stripos($content, '</body>') !== false) {
                $debugBar = DebugBar::render();
                $newContent = str_ireplace('</body>', $debugBar . '</body>', $content);
                $response->setContent($newContent);
            }
        }
        
        return $response;
    }
}
