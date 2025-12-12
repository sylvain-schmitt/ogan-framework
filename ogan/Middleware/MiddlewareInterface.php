<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔗 MIDDLEWARE INTERFACE (Chain of Responsibility Pattern)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Définit le contrat pour tous les middlewares du framework.
 * Un middleware est une couche qui enveloppe le contrôleur et peut :
 * 1. Modifier la requête AVANT le contrôleur
 * 2. Court-circuiter l'exécution (ex: authentification échouée)
 * 3. Modifier la réponse APRÈS le contrôleur
 * 
 * PATTERN CHAIN OF RESPONSIBILITY :
 * ----------------------------------
 * Les middlewares forment une chaîne où chaque maillon peut :
 * - Traiter la requête et passer au suivant
 * - Ou stopper la chaîne et retourner une réponse immédiatement
 * 
 * FLUX D'EXÉCUTION :
 * ------------------
 * Request → MW1 (before) → MW2 (before) → Controller → MW2 (after) → MW1 (after) → Response
 * 
 * EXEMPLES D'UTILISATION :
 * ------------------------
 * - Authentification : vérifier si l'utilisateur est connecté
 * - CORS : ajouter les headers pour les API
 * - Logging : enregistrer chaque requête
 * - Cache : retourner une réponse en cache si disponible
 * - Rate Limiting : limiter le nombre de requêtes par IP
 * - CSRF Protection : vérifier le token CSRF
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware;

use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * TRAITER LA REQUÊTE (Handle)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Cette méthode est appelée pour chaque middleware dans la chaîne.
     * 
     * PARAMÈTRES :
     * ------------
     * @param RequestInterface $request La requête entrante
     * @param callable $next Fonction pour appeler le middleware suivant
     *                       Signature : fn(RequestInterface): ResponseInterface
     * 
     * RETOUR :
     * --------
     * @return ResponseInterface La réponse à renvoyer
     * 
     * COMPORTEMENTS POSSIBLES :
     * -------------------------
     * 
     * 1. PASSER AU SUIVANT (comportement normal) :
     *    return $next($request);
     * 
     * 2. MODIFIER LA REQUÊTE puis passer au suivant :
     *    $request->setAttribute('user', $user);
     *    return $next($request);
     * 
     * 3. COURT-CIRCUITER (stopper l'exécution) :
     *    if (!$authenticated) {
     *        return new Response('Unauthorized', 401);
     *    }
     * 
     * 4. MODIFIER LA RÉPONSE après le contrôleur :
     *    $response = $next($request);
     *    $response->setHeader('X-Custom', 'value');
     *    return $response;
     * 
     * EXEMPLE CONCRET :
     * -----------------
     * class AuthMiddleware implements MiddlewareInterface {
     *     public function handle(RequestInterface $request, callable $next): ResponseInterface {
     *         // 1. Code AVANT le contrôleur
     *         $token = $request->getHeader('Authorization');
     *         
     *         if (!$token) {
     *             // Court-circuite : retourne 401 sans appeler le contrôleur
     *             return new Response('Unauthorized', 401);
     *         }
     *         
     *         // 2. Appelle le middleware suivant / contrôleur
     *         $response = $next($request);
     *         
     *         // 3. Code APRÈS le contrôleur (optionnel)
     *         $response->setHeader('X-Auth', 'verified');
     *         
     *         return $response;
     *     }
     * }
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface;
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * POURQUOI UTILISER DES MIDDLEWARES ?
 * ------------------------------------
 * 1. MODULARITÉ : Chaque middleware a une responsabilité unique (SRP)
 * 2. RÉUTILISABILITÉ : Un middleware peut être utilisé sur plusieurs routes
 * 3. ORDRE D'EXÉCUTION : Contrôle précis de l'ordre de traitement
 * 4. SÉPARATION DES PRÉOCCUPATIONS : Le contrôleur ne gère que la logique métier
 * 
 * PATTERN CHAIN OF RESPONSIBILITY :
 * ----------------------------------
 * Chaque maillon de la chaîne peut :
 * - Traiter la demande et passer au suivant
 * - Ou traiter la demande et stopper la chaîne
 * 
 * C'est comme une série de filtres empilés :
 * Request → [Auth] → [CORS] → [Logger] → Controller
 * 
 * DIFFÉRENCE AVEC LES ÉVÉNEMENTS :
 * ---------------------------------
 * - Middlewares : Séquence ordonnée, peut court-circuiter
 * - Événements : Pas d'ordre garanti, tous les listeners s'exécutent
 * 
 * EXEMPLES DANS D'AUTRES FRAMEWORKS :
 * ------------------------------------
 * - Laravel : Illuminate\Http\Middleware
 * - Symfony : HttpKernel Component
 * - Express.js : app.use(middleware)
 * - ASP.NET : Middleware Pipeline
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
