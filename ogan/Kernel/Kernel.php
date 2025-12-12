<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🎯 KERNEL - Cœur du Framework Ogan
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * RÔLE DU KERNEL
 * --------------
 * Le Kernel est le **chef d'orchestre** du framework. C'est lui qui :
 * 1. Initialise le système de gestion d'erreurs
 * 2. Configure le Container (Dependency Injection)
 * 3. Enregistre les services (Request, Response, Router...)
 * 4. Charge les routes
 * 5. Dispatch la requête vers le bon contrôleur
 * 
 * POURQUOI UN KERNEL ?
 * --------------------
 * **Avant** : index.php faisait TOUT (40+ lignes, mélange de responsabilités)
 * **Après** : index.php = 3 lignes, Kernel = toute la logique
 * 
 * AVANTAGES :
 * -----------
 * 1. **index.php ultra-léger** : Facile à lire et maintenir
 * 2. **Réutilisable** : On peut utiliser le Kernel dans les tests, CLI...
 * 3. **Organisé** : Toute la config au même endroit
 * 4. **Testable** : On peut tester le Kernel isolément
 * 5. **Évolutif** : Facile d'ajouter de nouvelles initialisations
 * 
 * INSPIRATION
 * -----------
 * Inspiré de Symfony\Component\HttpKernel\Kernel
 * Mais en version simplifiée et pédagogique !
 * 
 * EXEMPLE D'UTILISATION
 * ---------------------
 * ```php
 * // public/index.php
 * require __DIR__ . '/../autoload.php';
 * 
 * $kernel = new Ogan\Kernel\Kernel(debug: true);
 * $kernel->run();
 * ```
 * 
 * C'est TOUT ! Le Kernel s'occupe du reste. 🎉
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Kernel;

use Ogan\DependencyInjection\Container;
use Ogan\Router\Router;
use Ogan\Http\Request;
use Ogan\Http\Response;
use Ogan\Error\ErrorHandler;
use Ogan\Session\Session;
use Ogan\Session\SessionInterface;

class Kernel
{
    private bool $debug;
    private Container $container;
    private string $projectDir;

    /**
     * @param bool $debug Mode debug (true = dev, false = prod)
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
        
        // Détermine le répertoire racine du projet
        // __DIR__ = ogan/Kernel, donc on remonte de 2 niveaux
        $this->projectDir = dirname(__DIR__, 2);
    }

    /**
     * Point d'entrée principal du framework
     * 
     * Cette méthode :
     * 1. Initialise l'ErrorHandler
     * 2. Boot le Container
     * 3. Dispatch la requête
     */
    public function run(): void
    {
        // Étape 1 : Gestion des erreurs
        $this->registerErrorHandler();

        // Étape 2 : Initialisation du Container
        $this->boot();

        // Étape 3 : Handle de la requête HTTP
        $this->handleRequest();
    }

    /**
     * Enregistre le gestionnaire d'erreurs global
     */
    private function registerErrorHandler(): void
    {
        $errorHandler = new ErrorHandler($this->debug);
        $errorHandler->register();
    }

    /**
     * Boot : Initialise le Container et enregistre les services
     */
    private function boot(): void
    {
        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Initialiser la configuration (Config + .env)
        // ─────────────────────────────────────────────────────────────
        $configPath = $this->projectDir . '/config/parameters.yaml';
        $envPath = $this->projectDir . '/.env';
        \Ogan\Config\Config::init($configPath, $envPath);

        $this->container = new Container();

        // Enregistre les services core du framework
        $this->registerCoreServices();
    }



    /**
     * Enregistre les services essentiels du framework
     */
    private function registerCoreServices(): void
    {
        // ─────────────────────────────────────────────────────────────
        // Service : Session
        // ─────────────────────────────────────────────────────────────
        $this->container->set(SessionInterface::class, function () {
            // Charger la configuration de la session
            $sessionConfig = \Ogan\Config\Config::get('session', []);
            return new Session($sessionConfig);
        });
        
        // Alias Session::class -> SessionInterface
        $this->container->set(Session::class, function (Container $c) {
            return $c->get(SessionInterface::class);
        });

        // ─────────────────────────────────────────────────────────────
        // Service : CsrfManager
        // ─────────────────────────────────────────────────────────────
        $this->container->set(\Ogan\Security\CsrfManager::class, function (Container $c) {
            return new \Ogan\Security\CsrfManager($c->get(SessionInterface::class));
        });

        // ─────────────────────────────────────────────────────────────
        // Service : Validator
        // ─────────────────────────────────────────────────────────────
        $this->container->set(\Ogan\Validation\Validator::class, fn() => new \Ogan\Validation\Validator());

        // ─────────────────────────────────────────────────────────────
        // Service : PasswordHasher
        // ─────────────────────────────────────────────────────────────
        $this->container->set(\Ogan\Security\PasswordHasher::class, fn() => new \Ogan\Security\PasswordHasher());

        // ─────────────────────────────────────────────────────────────
        // Service : FormFactory
        // ─────────────────────────────────────────────────────────────
        $this->container->set(\Ogan\Form\FormFactory::class, function (Container $c) {
            $validator = $c->has(\Ogan\Validation\Validator::class)
                ? $c->get(\Ogan\Validation\Validator::class)
                : null;
            return new \Ogan\Form\FormFactory($validator);
        });

        // ─────────────────────────────────────────────────────────────
        // Service : Request (Requête HTTP)
        // ─────────────────────────────────────────────────────────────
        $this->container->set(Request::class, function (Container $c) {
            $request = new Request(
                $_GET,
                $_POST,
                $_SERVER,
                $_COOKIE,
                $_FILES,
                file_get_contents('php://input')
            );
            
            // On injecte le service session
            $request->setSession($c->get(SessionInterface::class));
            
            return $request;
        });

        // ─────────────────────────────────────────────────────────────
        // Service : Response (Réponse HTTP)
        // ─────────────────────────────────────────────────────────────
        $this->container->set(Response::class, fn() => new Response());

        // ─────────────────────────────────────────────────────────────
        // Service : Router (Système de routage)
        // ─────────────────────────────────────────────────────────────
        $this->container->set(Router::class, function (Container $c) {
            $router = new Router();
            
            // Charge les routes depuis les contrôleurs
            $controllersPath = $this->projectDir . '/src/Controller';
            $router->loadRoutesFromControllers($controllersPath);
            
            // Configure les middlewares depuis YAML (avec fallback sur PHP)
            $middlewaresConfigPath = $this->projectDir . '/config/middlewares.yaml';
            \Ogan\Config\MiddlewareLoader::loadFromYaml($middlewaresConfigPath, $router);
            
            return $router;
        });
    }

    /**
     * Gère la requête HTTP entrante
     */
    private function handleRequest(): void
    {
        // Récupère les services depuis le Container
        $request = $this->container->get(Request::class);
        $response = $this->container->get(Response::class);
        $router = $this->container->get(Router::class);

        // Extrait URI et méthode de la requête
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // Dispatch vers le bon contrôleur
        $router->dispatch($uri, $method, $request, $response, $this->container);
    }

    /**
     * Retourne le Container (utile pour les tests)
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Retourne le répertoire racine du projet
     */
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    /**
     * Vérifie si on est en mode debug
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * CYCLE DE VIE D'UNE REQUÊTE
 * ---------------------------
 * 
 * 1. **index.php** : Crée le Kernel et appelle run()
 * 2. **registerErrorHandler()** : Active la gestion d'erreurs
 * 3. **boot()** : Initialise le Container
 * 4. **registerCoreServices()** : Enregistre Request, Response, Router
 * 5. **handleRequest()** : Récupère les services et dispatch
 * 6. **Router::dispatch()** : Trouve la route et exécute le contrôleur
 * 7. **Contrôleur** : Génère la réponse
 * 8. **Response::send()** : Envoie au client
 * 
 * SÉPARATION DES RESPONSABILITÉS
 * -------------------------------
 * 
 * index.php :
 * - Point d'entrée web (très simple)
 * - Crée le Kernel
 * - Lance l'application
 * 
 * Kernel :
 * - Orchestration de l'initialisation
 * - Configuration des services
 * - Cycle de vie de la requête
 * 
 * Container :
 * - Gestion des dépendances
 * - Instanciation des services
 * 
 * Router :
 * - Matching des routes
 * - Dispatch vers contrôleurs
 * 
 * MÉTHODES UTILES
 * ---------------
 * 
 * getContainer() :
 * - Accès au Container depuis l'extérieur
 * - Utile pour les tests
 * 
 * getProjectDir() :
 * - Chemin absolu vers la racine du projet
 * - Utile pour construire des chemins
 * 
 * isDebug() :
 * - Vérifie le mode (dev/prod)
 * - Permet d'adapter le comportement
 * 
 * ÉVOLUTIONS FUTURES
 * ------------------
 * 
 * On pourra ajouter dans le Kernel :
 * 
 * - registerBundles() : Charger des bundles/plugins
 * - configureContainer() : Config avancée du Container
 * - registerMiddlewares() : Middlewares globaux
 * - initDatabase() : Connexion BDD
 * - startSession() : Gestion de session
 * - loadConfig() : Charger config YAML/PHP
 * - warmCache() : Préchauffer le cache
 * 
 * Tout ça sans toucher à index.php ! 🎉
 * 
 * COMPARAISON SYMFONY
 * -------------------
 * 
 * Notre Kernel :
 * - Simplifié pour l'apprentissage
 * - Tout en un seul fichier
 * - ~150 lignes
 * 
 * Symfony Kernel :
 * - Beaucoup plus complexe
 * - Bundles, environments, cache...
 * - ~1000+ lignes
 * 
 * Mais le PRINCIPE est le même ! 💪
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
