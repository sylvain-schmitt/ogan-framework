<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🗺️ ROUTERINTERFACE - Interface pour le Routeur
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * RÔLE DE CETTE INTERFACE
 * -----------------------
 * Définit le CONTRAT pour le système de routage du framework.
 * 
 * Le routeur est responsable de :
 * - Enregistrer les routes (path → contrôleur)
 * - Matcher une URL avec une route
 * - Dispatcher la requête vers le bon contrôleur
 * - Générer des URLs depuis les noms de routes
 * 
 * POURQUOI UNE INTERFACE ?
 * ------------------------
 * 
 * 1. FLEXIBILITÉ :
 *    On pourrait avoir différentes implémentations :
 *    - AttributeRouter : Routes définies via attributs PHP 8
 *    - ConfigRouter : Routes définies en YAML/PHP
 *    - CachedRouter : Router avec cache compilé
 * 
 * 2. TESTABILITÉ :
 *    Dans les tests, on peut créer un FakeRouter qui retourne
 *    toujours le même contrôleur sans scanner les fichiers
 * 
 * 3. PRINCIPE SOLID "D" :
 *    L'application dépend de l'interface, pas de l'implémentation
 * 
 * CONCEPTS DE ROUTAGE
 * -------------------
 * 
 * ROUTE STATIQUE :
 * /users → UserController::index
 * 
 * ROUTE DYNAMIQUE :
 * /users/{id} → UserController::show
 * Exemple : /users/42 → ['id' => '42']
 * 
 * ROUTE AVEC CONTRAINTES :
 * /articles/{id:\d+} → ArticleController::show
 * Matche : /articles/123
 * Ne matche pas : /articles/abc
 * 
 * ROUTE NOMMÉE :
 * name: 'user_show' permet de faire :
 * $router->generateUrl('user_show', ['id' => 42])
 * → '/users/42'
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Router;

use Ogan\DependencyInjection\ContainerInterface;
use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;

/**
 * Interface pour le système de routage
 */
interface RouterInterface
{
    /**
     * ───────────────────────────────────────────────────────────────────────
     * CHARGER LES ROUTES DEPUIS LES CONTRÔLEURS
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Scanne un dossier de contrôleurs et charge les routes définies
     * avec des attributs #[Route].
     * 
     * EXEMPLE :
     * $router->loadRoutesFromControllers('src/Controller');
     * 
     * Va scanner tous les fichiers PHP dans src/Controller/
     * et lire les attributs #[Route] de chaque méthode.
     * 
     * @param string $controllersPath Chemin vers le dossier des contrôleurs
     * @return void
     */
    public function loadRoutesFromControllers(string $controllersPath): void;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * AJOUTER UNE ROUTE MANUELLEMENT
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Enregistre une route manuellement dans le router.
     * 
     * EXEMPLES :
     * // Route simple
     * $router->addRoute('/blog', ['GET'], BlogController::class, 'index');
     * 
     * // Route avec paramètres
     * $router->addRoute('/blog/{id}', ['GET'], BlogController::class, 'show');
     * 
     * // Route avec nom
     * $router->addRoute('/users/{id}', ['GET'], UserController::class, 'show', 'user_show');
     * 
     * @param string $path Chemin de la route (ex: '/blog/{id}')
     * @param array $httpMethods Méthodes HTTP acceptées (['GET', 'POST'])
     * @param string $controllerClass Nom complet de la classe contrôleur
     * @param string $controllerMethod Nom de la méthode à appeler
     * @param string|null $name Nom optionnel de la route (pour generateUrl)
     * @return void
     */
    public function addRoute(
        string $path,
        array $httpMethods,
        string $controllerClass,
        string $controllerMethod,
        ?string $name = null
    ): void;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * DISPATCHER UNE REQUÊTE
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Trouve la route qui matche l'URI et la méthode HTTP,
     * puis exécute le contrôleur correspondant.
     * 
     * PROCESSUS :
     * 1. Parcourt toutes les routes enregistrées
     * 2. Vérifie si l'URI matche (avec regex pour les paramètres)
     * 3. Vérifie si la méthode HTTP correspond
     * 4. Extrait les paramètres de l'URL (/users/42 → ['id' => '42'])
     * 5. Instancie le contrôleur via le Container
     * 6. Appelle la méthode avec les paramètres
     * 7. Si aucune route ne matche → 404
     * 
     * EXEMPLE :
     * $router->dispatch('/users/42', 'GET', $request, $response, $container);
     * 
     * @param string $uri URI demandée (ex: '/users/42')
     * @param string $method Méthode HTTP (ex: 'GET')
     * @param RequestInterface $request Objet requête
     * @param ResponseInterface $response Objet réponse
     * @param ContainerInterface $container Container DI pour instancier le contrôleur
     * @return void
     */
    public function dispatch(
        string $uri,
        string $method,
        RequestInterface $request,
        ResponseInterface $response,
        ContainerInterface $container
    ): void;

    /**
     * ───────────────────────────────────────────────────────────────────────
     * GÉNÉRER UNE URL DEPUIS LE NOM DE ROUTE
     * ───────────────────────────────────────────────────────────────────────
     * 
     * Génère une URL à partir du nom d'une route et de ses paramètres.
     * 
     * AVANTAGES :
     * - Plus besoin de hardcoder les URLs dans le code
     * - Si tu changes le path, les URLs se mettent à jour automatiquement
     * - Évite les erreurs de typo dans les URLs
     * 
     * EXEMPLES :
     * // Route définie : /users/{id} avec name: 'user_show'
     * $url = $router->generateUrl('user_show', ['id' => 42]);
     * // Retourne : '/users/42'
     * 
     * // Route : /blog/{year}/{month}/{slug}
     * $url = $router->generateUrl('blog_post', [
     *     'year' => 2024,
     *     'month' => 12,
     *     'slug' => 'ogan-framework'
     * ]);
     * // Retourne : '/blog/2024/12/ogan-framework'
     * 
     * // URL absolue
     * $url = $router->generateUrl('user_show', ['id' => 42], Router::ABSOLUTE_URL);
     * // Retourne : 'http://localhost/users/42'
     * 
     * UTILISATION DANS LES TEMPLATES :
     * <a href="<?= $router->generateUrl('user_show', ['id' => $user->id]) ?>">
     *     Voir le profil
     * </a>
     * 
     * @param string $name Nom de la route
     * @param array $params Paramètres à injecter dans l'URL
     * @param int $referenceType Type de référence (ABSOLUTE_PATH ou ABSOLUTE_URL)
     * @return string|null L'URL générée, ou null si la route n'existe pas
     */
    public function generateUrl(string $name, array $params = [], int $referenceType = 0): ?string;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * POURQUOI DES INTERFACES DANS LES PARAMÈTRES ?
 * ----------------------------------------------
 * 
 * Dans dispatch(), on demande :
 * - RequestInterface (pas Request)
 * - ResponseInterface (pas Response)
 * - ContainerInterface (pas Container)
 * 
 * AVANTAGES :
 * 1. Le Router ne dépend PAS des implémentations concrètes
 * 2. On peut passer N'IMPORTE quelle implémentation
 * 3. Plus facile à tester (on peut passer des mocks)
 * 
 * C'est le principe SOLID "D" (Dependency Inversion) en action !
 * 
 * MÉTHODES À AJOUTER PLUS TARD (Phase 3)
 * ---------------------------------------
 * 
 * Pour un router plus avancé :
 * 
 * - group(string $prefix, callable $callback)
 *   → Grouper des routes avec un préfixe commun
 * 
 * - middleware(string $name, callable $middleware)
 *   → Ajouter des middlewares (auth, CORS...)
 * 
 * - match(string $uri, string $method): ?Route
 *   → Retourner la route matchée sans l'exécuter
 * 
 * - getRoutes(): array
 *   → Lister toutes les routes (debug)
 * 
 * DIFFÉRENCE loadRoutesFromControllers() vs addRoute()
 * -----------------------------------------------------
 * 
 * loadRoutesFromControllers() :
 * - Automatique
 * - Scan les fichiers PHP
 * - Lit les attributs #[Route]
 * - Pratique pour beaucoup de routes
 * 
 * addRoute() :
 * - Manuel
 * - Enregistrement explicite
 * - Utile pour routes dynamiques ou tests
 * 
 * On peut utiliser les DEUX ensemble !
 * 
 * PROCHAINES ÉTAPES
 * -----------------
 * 1. Modifier Router.php pour implémenter cette interface
 * 2. S'assurer que toutes les méthodes sont présentes
 * 3. Vérifier les signatures (types de paramètres/retours)
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
