<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠️ ROUTINGEXCEPTION - Exception pour les erreurs de routage
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * QUAND LANCER CETTE EXCEPTION ?
 * -------------------------------
 * - Nom de route en double
 * - Route invalide (path mal formé)
 * - Paramètres manquants lors de generateUrl()
 * - Contrainte de paramètre invalide
 * 
 * DIFFÉRENCE AVEC NotFoundException
 * ----------------------------------
 * NotFoundException : La route demandée n'existe pas (404)
 * RoutingException : Erreur de configuration du routage (500)
 * 
 * EXEMPLES :
 * - addRoute() avec un nom déjà utilisé
 * - generateUrl() sans tous les paramètres requis
 * - Pattern de route invalide
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Exception;

use Exception;

/**
 * Exception lancée pour les erreurs de configuration du routage
 */
class RoutingException extends Exception
{
    /**
     * Route avec nom en double
     */
    public static function duplicateRouteName(string $name): self
    {
        return new self("Route name '{$name}' is already registered");
    }

    /**
     * Paramètres manquants pour générer l'URL
     */
    public static function missingParameters(string $routeName, array $missingParams): self
    {
        $params = implode(', ', $missingParams);
        return new self("Missing parameters for route '{$routeName}': {$params}");
    }

    /**
     * Route non trouvée par son nom
     */
    public static function routeNotFound(string $name): self
    {
        return new self("Route with name '{$name}' not found");
    }

    /**
     * Pattern de route invalide
     */
    public static function invalidPattern(string $pattern, string $reason): self
    {
        return new self("Invalid route pattern '{$pattern}': {$reason}");
    }
}
