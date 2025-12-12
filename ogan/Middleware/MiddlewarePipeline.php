<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔗 MIDDLEWARE PIPELINE (Chain of Responsibility Implementation)
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Le Pipeline est le chef d'orchestre qui exécute tous les middlewares
 * dans l'ordre, puis appelle le contrôleur final.
 * 
 * CONCEPT :
 * ---------
 * Imaginez une série de tubes empilés. La requête entre par le haut et
 * traverse chaque tube (middleware) avant d'atteindre le contrôleur.
 * La réponse remonte ensuite par les mêmes tubes en sens inverse.
 * 
 * FLUX D'EXÉCUTION :
 * ------------------
 *     Request
 *        ⬇
 *    [ MW 1 ] ──┐ Before Controller
 *        ⬇      │
 *    [ MW 2 ] ──┤
 *        ⬇      │
 *    [ MW 3 ] ──┤
 *        ⬇      │
 *  [Controller] │
 *        ⬆      │
 *    [ MW 3 ] ──┤ After Controller
 *        ⬆      │
 *    [ MW 2 ] ──┤
 *        ⬆      │
 *    [ MW 1 ] ──┘
 *        ⬆
 *    Response
 * 
 * IMPLÉMENTATION :
 * ----------------
 * Utilise array_reduce() pour créer une fonction imbriquée (onion layers).
 * Chaque middleware enveloppe le suivant dans une closure.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware;

use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;

class MiddlewarePipeline
{
    /**
     * @var array<MiddlewareInterface>
     * Liste ordonnée des middlewares à exécuter
     */
    private array $middlewares = [];

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UN MIDDLEWARE AU PIPELINE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Ajoute un middleware à la fin du pipeline.
     * L'ordre d'ajout détermine l'ordre d'exécution.
     * 
     * EXEMPLE :
     * ---------
     * $pipeline->pipe(new CorsMiddleware());
     * $pipeline->pipe(new AuthMiddleware());
     * $pipeline->pipe(new LoggerMiddleware());
     * 
     * Ordre d'exécution :
     * Request → CORS → Auth → Logger → Controller → Logger → Auth → CORS → Response
     * 
     * @param MiddlewareInterface $middleware Le middleware à ajouter
     * @return self Pour permettre le chaînage (fluent interface)
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXÉCUTER LE PIPELINE (La Magie Opère Ici !)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Exécute tous les middlewares dans l'ordre, puis le handler final.
     * 
     * ALGORITHME :
     * ------------
     * 1. On part du handler final (le contrôleur)
     * 2. On l'enveloppe dans le dernier middleware
     * 3. On enveloppe ce résultat dans l'avant-dernier middleware
     * 4. Et ainsi de suite jusqu'au premier middleware
     * 5. On exécute le premier middleware qui déclenche toute la chaîne
     * 
     * TECHNIQUE PHP : array_reduce() + Closures
     * ------------------------------------------
     * array_reduce() prend un tableau et une fonction, et combine
     * tous les éléments en une seule valeur.
     * 
     * Ici, on combine les middlewares en une seule fonction imbriquée.
     * 
     * DÉTAIL DE array_reduce() :
     * ---------------------------
     * array_reduce(
     *     array $array,           // Le tableau à réduire
     *     callable $callback,     // fn($carry, $item) => nouvelle valeur
     *     mixed $initial          // Valeur initiale de $carry
     * )
     * 
     * $callback reçoit :
     * - $carry : résultat de l'itération précédente (ou $initial au début)
     * - $item : élément courant du tableau
     * 
     * EXEMPLE CONCRET :
     * -----------------
     * Middlewares : [MW1, MW2, MW3]
     * Handler final : $finalHandler
     * 
     * Étape 0 (initial) : $pipeline = $finalHandler
     * Étape 1 : $pipeline = fn($req) => MW3->handle($req, $finalHandler)
     * Étape 2 : $pipeline = fn($req) => MW2->handle($req, fn($req) => MW3->handle($req, $finalHandler))
     * Étape 3 : $pipeline = fn($req) => MW1->handle($req, fn($req) => MW2->handle($req, fn($req) => MW3->handle($req, $finalHandler)))
     * 
     * Résultat : Une fonction qui exécute MW1 → MW2 → MW3 → Controller
     * 
     * @param RequestInterface $request La requête à traiter
     * @param callable $finalHandler Le handler final (généralement le contrôleur)
     *                                Signature : fn(RequestInterface): ResponseInterface
     * @return ResponseInterface La réponse finale
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function handle(RequestInterface $request, callable $finalHandler): ResponseInterface
    {
        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Créer le pipeline imbriqué (Onion Layers)
        // ─────────────────────────────────────────────────────────────
        // On utilise array_reduce() pour construire une fonction imbriquée
        // qui enveloppe le handler final dans tous les middlewares.
        
        $pipeline = array_reduce(
            // On inverse le tableau pour commencer par le dernier middleware
            // (car array_reduce() construit de la fin vers le début)
            array_reverse($this->middlewares),
            
            // Fonction de réduction : enveloppe $next dans $middleware
            function (callable $next, MiddlewareInterface $middleware) {
                // Retourne une nouvelle fonction qui :
                // 1. Appelle le middleware avec la requête
                // 2. Passe $next comme deuxième paramètre
                return function (RequestInterface $request) use ($middleware, $next) {
                    return $middleware->handle($request, $next);
                };
            },
            
            // Valeur initiale : le handler final (contrôleur)
            $finalHandler
        );

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Exécuter le pipeline
        // ─────────────────────────────────────────────────────────────
        // On appelle la fonction créée, ce qui déclenche l'exécution
        // du premier middleware, qui appelle le deuxième, etc.
        return $pipeline($request);
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * POURQUOI array_reduce() ?
 * --------------------------
 * On pourrait utiliser une boucle, mais array_reduce() permet de créer
 * élégamment une structure imbriquée de fonctions (closures).
 * 
 * C'est un pattern fonctionnel très puissant pour composer des fonctions.
 * 
 * ALTERNATIVE AVEC UNE BOUCLE (moins élégant) :
 * ----------------------------------------------
 * $pipeline = $finalHandler;
 * foreach (array_reverse($this->middlewares) as $middleware) {
 *     $next = $pipeline;
 *     $pipeline = fn($request) => $middleware->handle($request, $next);
 * }
 * return $pipeline($request);
 * 
 * CONCEPT : HIGHER-ORDER FUNCTIONS
 * ----------------------------------
 * Une fonction qui retourne une fonction est appelée "higher-order function".
 * C'est très utilisé en programmation fonctionnelle.
 * 
 * Ici, chaque middleware retourne une fonction qui encapsule le suivant.
 * C'est comme des poupées russes (matryoshka).
 * 
 * VISUALISATION :
 * ---------------
 * Si on a 3 middlewares [Auth, CORS, Logger] et un contrôleur :
 * 
 * Pipeline final = 
 *   Auth( CORS( Logger( Controller ) ) )
 * 
 * Quand on exécute :
 * 1. Auth s'exécute, reçoit CORS(Logger(Controller)) comme $next
 * 2. Auth fait son travail avant, puis appelle $next
 * 3. CORS s'exécute, reçoit Logger(Controller) comme $next
 * 4. CORS fait son travail avant, puis appelle $next
 * 5. Logger s'exécute, reçoit Controller comme $next
 * 6. Logger fait son travail avant, puis appelle $next
 * 7. Controller s'exécute et retourne une Response
 * 8. La Response remonte : Logger après → CORS après → Auth après
 * 
 * PATTERN DECORATOR :
 * -------------------
 * Les middlewares sont aussi un exemple du pattern Decorator :
 * chaque middleware "décore" (ajoute du comportement) au handler suivant.
 * 
 * FRAMEWORKS QUI UTILISENT CE PATTERN :
 * --------------------------------------
 * - Laravel : Illuminate\Pipeline\Pipeline
 * - Symfony : HttpKernel (listeners)
 * - PSR-15 : HTTP Server Request Handlers
 * - Express.js : middleware stack
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
