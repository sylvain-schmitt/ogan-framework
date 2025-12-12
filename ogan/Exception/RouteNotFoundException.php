<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🔍 ROUTENOTFOUNDEXCEPTION - Route HTTP introuvable (404)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * QUAND LANCER CETTE EXCEPTION ?
 * -------------------------------
 * Quand aucune route ne matche l'URI + méthode HTTP demandées.
 * 
 * DIFFÉRENCE AVEC RoutingException
 * ---------------------------------
 * RouteNotFoundException : L'utilisateur demande une URL qui n'existe pas (404)
 * RoutingException : Erreur de configuration des routes (500)
 * 
 * EXEMPLE :
 * Requête : GET /page-inexistante
 * Aucune route ne matche → RouteNotFoundException
 * 
 * GESTION :
 * - En production : Afficher une jolie page 404
 * - En dev : Afficher les routes disponibles pour aider au debug
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Exception;

use Exception;

/**
 * Exception lancée quand aucune route ne matche la requête
 */
class RouteNotFoundException extends Exception
{
    private string $uri;
    private string $method;

    public function __construct(string $uri, string $method)
    {
        $this->uri = $uri;
        $this->method = $method;
        
        parent::__construct("No route found for '{$method} {$uri}'", 404);
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
