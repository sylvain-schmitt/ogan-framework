<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🌐 CORS MIDDLEWARE (Cross-Origin Resource Sharing)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Ajoute les headers CORS nécessaires pour permettre aux applications
 * front-end (React, Vue, Angular...) hébergées sur un autre domaine
 * d'accéder à votre API.
 * 
 * PROBLÈME RÉSOLU :
 * -----------------
 * Sans CORS, le navigateur bloque les requêtes AJAX entre différents domaines :
 * 
 * ❌ Front-end sur http://localhost:3000 (React)
 *    → API sur http://localhost:8000 (PHP)
 *    → BLOQUÉ par le navigateur (CORS error)
 * 
 * ✅ Avec CorsMiddleware :
 *    → Headers CORS ajoutés
 *    → Navigateur autorise la requête
 * 
 * HEADERS CORS :
 * --------------
 * - Access-Control-Allow-Origin : Domaines autorisés
 * - Access-Control-Allow-Methods : Méthodes HTTP autorisées (GET, POST...)
 * - Access-Control-Allow-Headers : Headers autorisés
 * - Access-Control-Allow-Credentials : Autoriser les cookies
 * 
 * REQUÊTE PREFLIGHT :
 * -------------------
 * Pour certaines requêtes (POST avec JSON, headers customs...),
 * le navigateur envoie d'abord une requête OPTIONS (preflight).
 * Le middleware doit répondre à cette requête avec les bons headers.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware\Examples;

use Ogan\Middleware\MiddlewareInterface;
use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER LES HEADERS CORS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * FLUX :
     * ------
     * 1. Si requête OPTIONS (preflight) → Retourne 200 avec headers CORS
     * 2. Sinon → Continue vers le contrôleur et ajoute les headers à la réponse
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Gérer la requête OPTIONS (Preflight)
        // ─────────────────────────────────────────────────────────────
        // Le navigateur envoie une requête OPTIONS avant la vraie requête
        // pour vérifier si le serveur accepte les requêtes CORS.
        if ($request->getMethod() === 'OPTIONS') {
            // Retourne immédiatement avec les headers CORS
            // Sans appeler le contrôleur
            return $this->createCorsResponse(new Response());
        }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Requête normale → Appeler le contrôleur
        // ─────────────────────────────────────────────────────────────
        $response = $next($request);

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 3 : Ajouter les headers CORS à la réponse
        // ─────────────────────────────────────────────────────────────
        return $this->createCorsResponse($response);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UNE RÉPONSE AVEC HEADERS CORS
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Ajoute tous les headers CORS nécessaires à une réponse.
     * 
     * @param ResponseInterface $response Réponse à enrichir
     * @return ResponseInterface Réponse avec headers CORS
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function createCorsResponse(ResponseInterface $response): ResponseInterface
    {
        // ────────────────────────────────────────────────────────────
        // Header 1 : Access-Control-Allow-Origin
        // ────────────────────────────────────────────────────────────
        // Domaines autorisés à faire des requêtes
        // 
        // OPTIONS :
        // - '*' : Tous les domaines (⚠️  moins sécurisé)
        // - 'http://localhost:3000' : Un domaine spécifique (recommandé)
        // - Dynamique : Lire depuis la requête et valider
        $response->setHeader('Access-Control-Allow-Origin', '*');

        // ────────────────────────────────────────────────────────────
        // Header 2 : Access-Control-Allow-Methods
        // ────────────────────────────────────────────────────────────
        // Méthodes HTTP autorisées
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');

        // ────────────────────────────────────────────────────────────
        // Header 3 : Access-Control-Allow-Headers
        // ────────────────────────────────────────────────────────────
        // Headers que le client peut envoyer
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        // ────────────────────────────────────────────────────────────
        // Header 4 : Access-Control-Allow-Credentials
        // ────────────────────────────────────────────────────────────
        // Autoriser l'envoi de cookies
        // ⚠️  Si true, Access-Control-Allow-Origin ne peut PAS être '*'
        $response->setHeader('Access-Control-Allow-Credentials', 'true');

        // ────────────────────────────────────────────────────────────
        // Header 5 : Access-Control-Max-Age
        // ────────────────────────────────────────────────────────────
        // Durée de cache de la réponse preflight (en secondes)
        // Évite au navigateur de refaire des OPTIONS à chaque requête
        $response->setHeader('Access-Control-Max-Age', '86400'); // 24 heures

        return $response;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * QUAND UTILISER CE MIDDLEWARE ?
 * -------------------------------
 * - API REST consommée par un front-end (React, Vue, Angular)
 * - Application mobile qui appelle votre API
 * - Widgets embarqués sur d'autres sites
 * 
 * UTILISATION :
 * -------------
 * // Toutes les routes API
 * $router->group(['prefix' => '/api', 'middleware' => new CorsMiddleware()], function($api) {
 *     $api->get('/users', [ApiController::class, 'users']);
 *     $api->post('/users', [ApiController::class, 'create']);
 * });
 * 
 * // Ou globalement pour toute l'application
 * $router->middleware(new CorsMiddleware());
 * 
 * SÉCURITÉ - BONNES PRATIQUES :
 * ------------------------------
 * ⚠️  NE PAS utiliser '*' en production !
 * 
 * Version sécurisée :
 * ```php
 * private array $allowedOrigins = [
 *     'http://localhost:3000',
 *     'https://myapp.com'
 * ];
 * 
 * public function handle(...) {
 *     $origin = $request->getHeader('Origin');
 *     if (in_array($origin, $this->allowedOrigins)) {
 *         $response->setHeader('Access-Control-Allow-Origin', $origin);
 *     }
 * }
 * ```
 * 
 * REQUÊTE PREFLIGHT EXPLIQUÉE :
 * ------------------------------
 * 1. Le navigateur détecte une requête "non-simple" (POST avec JSON par ex.)
 * 2. Il envoie d'abord OPTIONS au serveur
 * 3. Le serveur répond avec les headers CORS
 * 4. Si OK, le navigateur envoie la vraie requête (POST)
 * 5. Sinon, il bloque et affiche une erreur CORS
 * 
 * Requête simple = GET/POST avec Content-Type: text/plain ou application/x-www-form-urlencoded
 * Requête complexe = Tout le reste (JSON, headers customs, PUT, DELETE...)
 * 
 * DEBUG CORS :
 * ------------
 * En cas d'erreur CORS :
 * 1. Ouvrir la console du navigateur (F12)
 * 2. Regarder l'onglet Network
 * 3. Vérifier la requête OPTIONS
 * 4. S'assurer que les headers sont bien retournés
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
