<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 AUTH MIDDLEWARE (Exemple Pédagogique)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Vérifie si l'utilisateur est authentifié avant d'accéder à une ressource.
 * Si non authentifié, retourne une erreur 401 sans appeler le contrôleur.
 * 
 * CAS D'USAGE :
 * -------------
 * - Routes admin (dashboard, paramètres, etc.)
 * - API protégées nécessitant un token
 * - Sections membres d'un site
 * 
 * COMPORTEMENT :
 * --------------
 * 1. Vérifie la présence du header "Authorization"
 * 2. Si absent → 401 Unauthorized (court-circuite)
 * 3. Si présent → continue vers le contrôleur
 * 
 * ⚠️  NOTE : Ceci est une version SIMPLIFIÉE à but pédagogique.
 * En production, vous devriez :
 * - Valider le token JWT/OAuth
 * - Vérifier en base de données
 * - Gérer les sessions PHP
 * - Implémenter un vrai système d'auth (Symfony Security, Laravel Auth...)
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware\Examples;

use Ogan\Middleware\MiddlewareInterface;
use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER L'AUTHENTIFICATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * FLUX :
     * ------
     * 1. Récupère le header "Authorization"
     * 2. Si absent ou invalide → Retourne 401 (COURT-CIRCUITE)
     * 3. Si valide → Appelle le middleware suivant / contrôleur
     * 
     * EXEMPLE DE REQUÊTE VALIDE :
     * ---------------------------
     * GET /admin/dashboard HTTP/1.1
     * Authorization: Bearer abc123xyz
     * 
     * EXEMPLE DE REQUÊTE INVALIDE :
     * ------------------------------
     * GET /admin/dashboard HTTP/1.1
     * (pas de header Authorization)
     * 
     * → Retourne 401 sans exécuter le contrôleur
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Récupérer le header Authorization
        // ─────────────────────────────────────────────────────────────
        // Format attendu : "Authorization: Bearer <token>"
        $authHeader = $request->getHeader('Authorization');

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Vérification (simplifiée)
        // ─────────────────────────────────────────────────────────────
        if (!$authHeader) {
            // Pas de header → COURT-CIRCUITE l'exécution
            // Le contrôleur ne sera JAMAIS appelé
            return (new Response())
                ->setStatusCode(401)
                ->setContent(json_encode([
                    'error' => 'Unauthorized',
                    'message' => 'Missing Authorization header'
                ], JSON_PRETTY_PRINT));
        }

        // En production, on validerait le token ici :
        // - Vérifier le format (Bearer token)
        // - Décoder le JWT
        // - Vérifier la signature
        // - Vérifier l'expiration
        // - Charger l'utilisateur depuis la DB
        //
        // Exemple :
        // if (!$this->tokenValidator->isValid($authHeader)) {
        //     return new Response('Invalid token', 401);
        // }

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 3 : Authentification OK → Continue
        // ─────────────────────────────────────────────────────────────
        // Appelle le middleware suivant dans la chaîne ou le contrôleur
        $response = $next($request);

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 4 (Optionnel) : Code après le contrôleur
        // ─────────────────────────────────────────────────────────────
        // On peut modifier la réponse ici
        // Exemple : ajouter un header indiquant qu'on est authentifié
        $response->setHeader('X-Authenticated', 'true');

        return $response;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * UTILISATION DANS LE ROUTER :
 * -----------------------------
 * // Route protégée
 * $router->get('/admin/dashboard', [AdminController::class, 'dashboard'])
 *     ->middleware(new AuthMiddleware());
 * 
 * // Groupe de routes protégées
 * $router->group(['middleware' => new AuthMiddleware()], function($group) {
 *     $group->get('/admin/users', [AdminController::class, 'users']);
 *     $group->get('/admin/settings', [AdminController::class, 'settings']);
 * });
 * 
 * AVANTAGES :
 * -----------
 * 1. SÉPARATION DES PRÉOCCUPATIONS : Le contrôleur ne gère pas l'auth
 * 2. RÉUTILISABILITÉ : Même middleware pour toutes les routes admin
 * 3. TESTABILITÉ : On peut tester le middleware indépendamment
 * 4. ORDRE D'EXÉCUTION : L'auth se fait AVANT d'entrer dans le contrôleur
 * 
 * ÉVOLUTION POSSIBLE :
 * --------------------
 * - Gérer les rôles (admin, user, guest)
 * - Vérifier les permissions (peut éditer, peut supprimer)
 * - Support de plusieurs méthodes d'auth (session, JWT, OAuth)
 * - Rate limiting par utilisateur
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
