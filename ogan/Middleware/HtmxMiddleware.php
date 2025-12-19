<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔄 HTMX MIDDLEWARE - Détection et gestion des requêtes HTMX
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ce middleware détecte les requêtes HTMX et stocke les informations
 * dans un registre statique accessible via HtmxContext.
 * 
 * ACTIVATION :
 * ------------
 * Dans config/middlewares.yaml :
 * global:
 *   - Ogan\Middleware\HtmxMiddleware (si HTMX activé)
 * 
 * UTILISATION :
 * -------------
 * Dans les contrôleurs :
 *   if (HtmxContext::isHtmxRequest()) { ... }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware;

use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Config\Config;

class HtmxMiddleware implements MiddlewareInterface
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * TRAITER LA REQUÊTE
     * ═══════════════════════════════════════════════════════════════════
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Vérifier si HTMX est activé
        if (!Config::get('frontend.htmx.enabled', false)) {
            return $next($request);
        }

        // Détecter et stocker les informations HTMX via $_SERVER
        HtmxContext::init();

        return $next($request);
    }
}
