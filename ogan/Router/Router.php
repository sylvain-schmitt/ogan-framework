<?php

namespace Ogan\Router;

use ReflectionClass;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Ogan\DependencyInjection\ContainerInterface;
use Ogan\Http\RequestInterface;
use Ogan\Http\ResponseInterface;
use Ogan\Router\Attributes\Route as RouteAttribute;
use Ogan\Exception\RouteNotFoundException;

class Router implements RouterInterface
{
    /**
     * @var Route[]
     */
    private array $routes = [];

    /**
     * Routes indexées par nom
     * @var Route[]
     */
    private array $namedRoutes = [];

    /**
     * Pile des groupes de routes actifs
     * @var RouteGroup[]
     */
    private array $groupStack = [];

    public function loadRoutesFromControllers(string $controllersPath): void
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersPath));

        foreach ($rii as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassFullNameFromFile($file->getPathname());
            if (!$className || !class_exists($className)) {
                continue;
            }

            $refClass = new ReflectionClass($className);
            
            // Vérifier si la classe a un attribut #[Route] (pour le préfixe)
            $classAttributes = $refClass->getAttributes(RouteAttribute::class);
            $classRoute = !empty($classAttributes) ? $classAttributes[0]->newInstance() : null;

            // Fonction pour charger les routes (avec ou sans groupe)
            $loader = function (Router $router) use ($refClass, $className) {
                foreach ($refClass->getMethods() as $method) {
                    $attributes = $method->getAttributes(RouteAttribute::class);
                    foreach ($attributes as $attribute) {
                        /** @var RouteAttribute $routeAttr */
                        $routeAttr = $attribute->newInstance();

                        $router->addRoute(
                            $routeAttr->path,
                            $routeAttr->methods,
                            $className,
                            $method->getName(),
                            $routeAttr->name
                        );
                    }
                }
            };

            // Si attribut de classe présent, on crée un groupe
            if ($classRoute) {
                $this->group(['prefix' => $classRoute->path], $loader);
            } else {
                // Sinon on charge directement
                $loader($this);
            }
        }
    }

    /**
     * Crée un groupe de routes partageant des attributs (préfixe, middlewares...)
     */
    public function group(array $attributes, callable $callback): void
    {
        // Créer le nouveau groupe
        $prefix = $attributes['prefix'] ?? '';
        $middlewares = $attributes['middlewares'] ?? [];
        $namespace = $attributes['namespace'] ?? null;
        $domain = $attributes['domain'] ?? null;
        
        $newGroup = new RouteGroup($prefix, $middlewares, $namespace, $domain);

        // Si un groupe parent existe, on fusionne
        if (!empty($this->groupStack)) {
            $parentGroup = end($this->groupStack);
            $newGroup = $newGroup->mergeWith($parentGroup);
        }

        // Empiler le groupe
        $this->groupStack[] = $newGroup;

        // Exécuter le callback avec le routeur
        $callback($this);

        // Dépiler le groupe
        array_pop($this->groupStack);
    }

    public function addRoute(string $path, array $httpMethods, string $controllerClass, string $controllerMethod, ?string $name = null): void
    {
        // Appliquer les attributs du groupe actif
        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);
            
            // Préfixe
            $path = rtrim($group->getPrefix(), '/') . '/' . ltrim($path, '/');
            if ($path !== '/') {
                $path = rtrim($path, '/');
            }

            // Namespace contrôleur
            // Si la classe est relative (ne commence pas par \) et qu'un namespace est défini
            if ($group->getNamespace() && !str_starts_with($controllerClass, '\\')) {
                $controllerClass = rtrim($group->getNamespace(), '\\') . '\\' . $controllerClass;
            }
        }

        $route = new Route($path, $httpMethods, $controllerClass, $controllerMethod, $name);

        // Appliquer le domaine du groupe
        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);
            if ($group->getDomain()) {
                $route->setDomain($group->getDomain());
            }
        }

        // Ajouter les middlewares du groupe
        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);
            foreach ($group->getMiddlewares() as $middleware) {
                $route->middleware($middleware);
            }
        }

        $this->routes[] = $route;

        if ($name !== null) {
            if (isset($this->namedRoutes[$name])) {
                throw new \Exception("Route name '{$name}' already used.");
            }
            $this->namedRoutes[$name] = $route;
        }
    }

    public const ABSOLUTE_PATH = 1;
    public const ABSOLUTE_URL = 2;

    public function generateUrl(string $name, array $params = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route '{$name}' not found.");
        }

        $route = $this->namedRoutes[$name];
        $url = $route->path;
        $domain = $route->getDomain();

        // Liste des paramètres automatiquement optionnels
        $autoOptional = ['query', 'search', 'filter'];

        // 1. Remplacer les paramètres dans l'URL (ex: /users/{id} ou /posts/{slug?})
        // Regex pour trouver {param} ou {param:regex} ou {param?}
        $url = preg_replace_callback('/\/?\\{(\\w+)(:.*?)?(\\?)?\\}/', function ($matches) use (&$params, $autoOptional) {
            $fullMatch = $matches[0];
            $hasLeadingSlash = str_starts_with($fullMatch, '/');
            $paramName = $matches[1];
            $isOptional = (isset($matches[3]) && $matches[3] === '?') || in_array($paramName, $autoOptional);

            if (isset($params[$paramName])) {
                $val = $params[$paramName];
                unset($params[$paramName]); // On le retire pour qu'il ne finisse pas en query string
                return ($hasLeadingSlash ? '/' : '') . urlencode((string)$val);
            }

            if ($isOptional) {
                return ''; // Paramètre optionnel non fourni = vide
            }

            throw new \Exception("Missing parameter '{$paramName}' for route.");
        }, $url);

        // Nettoyage des doubles slashes (si params optionnels vides)
        $url = preg_replace('#/+#', '/', $url);
        // Nettoyage de la fin (sauf si c'est juste /)
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }
        // S'assurer que l'URL commence par /
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        // 2. Gérer le domaine (ex: {subdomain}.example.com)
        if ($domain) {
            // Remplacer les params du domaine
            $domain = preg_replace_callback('/\{(\w+)(:.*?)?(\?)?\}/', function ($matches) use (&$params) {
                $paramName = $matches[1];
                if (isset($params[$paramName])) {
                    $val = $params[$paramName];
                    unset($params[$paramName]);
                    return $val;
                }
                throw new \Exception("Missing domain parameter '{$paramName}' for route.");
            }, $domain);

            // Si le domaine est différent du host actuel, on force l'URL absolue
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
            if ($domain !== $currentHost || $referenceType === self::ABSOLUTE_URL) {
                $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $url = $scheme . '://' . $domain . $url;
            }
        } elseif ($referenceType === self::ABSOLUTE_URL) {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $url = $scheme . '://' . $host . $url;
        }

        // 3. Ajouter les paramètres restants en Query String
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }



    public function dispatch(string $uri, string $method, RequestInterface $request, ResponseInterface $response, ContainerInterface $container): void
    {
        // On récupère le host depuis la requête pour le matching de sous-domaine
        $host = $_SERVER['HTTP_HOST'] ?? null;

        foreach ($this->routes as $route) {
            $params = $route->match($uri, $method, $host);
            if ($params !== false) {
                // ─────────────────────────────────────────────────────────────
                // DEBUG BAR : Enregistrer les infos de route
                // ─────────────────────────────────────────────────────────────
                if (class_exists(\Ogan\Debug\DebugBar::class)) {
                    \Ogan\Debug\DebugBar::setRoute([
                        'name' => $route->name ?? 'anonymous',
                        'path' => $route->path,
                        'controller' => basename(str_replace('\\', '/', $route->controllerClass)),
                        'action' => $route->controllerMethod,
                        'params' => $params
                    ]);
                }
                
                // ─────────────────────────────────────────────────────────────
                // ÉTAPE 1 : Récupérer les middlewares de la route
                // ─────────────────────────────────────────────────────────────
                $middlewares = $route->getMiddlewares();
                
                // ─────────────────────────────────────────────────────────────
                // ÉTAPE 2 : Définir le handler final (contrôleur)
                // ─────────────────────────────────────────────────────────────
                $finalHandler = function(RequestInterface $request) use ($route, $params, $container) {
                    // Instanciation via container => injection constructeur automatique
                    $controller = $container->get($route->controllerClass);

                    // Si tu as besoin d'injecter manuellement Request/Response (en setter)
                    if (method_exists($controller, 'setRequestResponse')) {
                        // Note: $response n'est pas disponible ici dans le closure
                        // Il faudrait le passer si nécessaire
                        $controller->setRequestResponse($request, new \Ogan\Http\Response(), $container);
                    }

                    // ─────────────────────────────────────────────────────────
                    // VÉRIFICATION DES ATTRIBUTS #[IsGranted]
                    // ─────────────────────────────────────────────────────────
                    $accessDenied = $this->checkIsGrantedAttributes(
                        $route->controllerClass,
                        $route->controllerMethod,
                        $params,
                        $request
                    );
                    
                    if ($accessDenied !== null) {
                        return $accessDenied;
                    }

                    // Appel de la méthode avec les params extraits
                    $result = call_user_func_array([$controller, $route->controllerMethod], $params);
                    
                    // Si le contrôleur retourne une réponse, on l'utilise
                    if ($result instanceof \Ogan\Http\ResponseInterface) {
                        return $result;
                    }
                    
                    // Sinon on retourne la réponse par défaut (cas où le contrôleur a fait des echo)
                    return new \Ogan\Http\Response();
                };
                
                // ─────────────────────────────────────────────────────────────
                // ÉTAPE 3 : Exécuter les middlewares (s'il y en a)
                // ─────────────────────────────────────────────────────────────
                if (!empty($middlewares)) {
                    $pipeline = new \Ogan\Middleware\MiddlewarePipeline();
                    
                    // Ajouter tous les middlewares au pipeline
                    foreach ($middlewares as $middleware) {
                        if (is_string($middleware) && $container->has($middleware)) {
                            $middleware = $container->get($middleware);
                        }
                        
                        if ($middleware instanceof \Ogan\Middleware\MiddlewareInterface) {
                            $pipeline->pipe($middleware);
                        }
                    }
                    
                    // Exécuter le pipeline
                    $response = $pipeline->handle($request, $finalHandler);
                } else {
                    // Pas de middlewares, appeler directement le contrôleur
                    $response = $finalHandler($request);
                }
                
                if ($response instanceof ResponseInterface) {
                    $response->send();
                }
                
                return;
            }
        }

        // Aucune route ne matche
        throw new RouteNotFoundException($uri, $method);
    }

    /**
     * Récupère le FQCN d’une classe à partir d’un fichier PHP
     */
    private function getClassFullNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        $namespace = null;
        $class = null;

        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1];
        }

        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $class = $matches[1];
        }

        if ($namespace && $class) {
            return $namespace . '\\' . $class;
        }

        return null;
    }

    /**
     * Retourne toutes les routes enregistrées
     * 
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Vérifie les attributs #[IsGranted] sur la classe et la méthode du contrôleur
     * 
     * @return ResponseInterface|null null si autorisé, Response de redirection sinon
     */
    private function checkIsGrantedAttributes(
        string $controllerClass,
        string $methodName,
        array $params,
        RequestInterface $request
    ): ?ResponseInterface {
        // Vérifier si l'attribut existe
        if (!class_exists(\Ogan\Security\Attribute\IsGranted::class)) {
            return null;
        }

        $refClass = new ReflectionClass($controllerClass);
        $refMethod = $refClass->getMethod($methodName);

        // Collecter les attributs IsGranted de la classe et de la méthode
        $classGrants = $refClass->getAttributes(\Ogan\Security\Attribute\IsGranted::class);
        $methodGrants = $refMethod->getAttributes(\Ogan\Security\Attribute\IsGranted::class);

        $allGrants = array_merge($classGrants, $methodGrants);

        if (empty($allGrants)) {
            return null; // Pas de contrainte d'accès
        }

        // Récupérer l'utilisateur depuis la session
        $user = $this->getCurrentUser($request);

        // Créer l'authorization checker
        $checker = new \Ogan\Security\Authorization\AuthorizationChecker($user);

        foreach ($allGrants as $grantAttribute) {
            /** @var \Ogan\Security\Attribute\IsGranted $grant */
            $grant = $grantAttribute->newInstance();
            
            // Résoudre le sujet si spécifié
            $subject = null;
            if ($grant->subject !== null && isset($params[$grant->subject])) {
                $subject = $params[$grant->subject];
            }

            // Vérifier l'autorisation
            if (!$checker->isGranted($grant->attribute, $subject)) {
                // Non autorisé - rediriger vers login ou afficher 403
                $accessDeniedUrl = \Ogan\Config\Config::get('security.access_denied_url', '/login');
                
                // Si l'utilisateur n'est pas connecté, rediriger vers login
                if ($user === null) {
                    return (new \Ogan\Http\Response())->redirect($accessDeniedUrl);
                }
                
                // Sinon, afficher une erreur 403
                $response = new \Ogan\Http\Response();
                $response->setStatusCode(403);
                
                // Essayer de charger le template 403
                try {
                    $templatesPath = \Ogan\Config\Config::get('view.templates_path', 'templates');
                    $view = new \Ogan\View\View($templatesPath, true);
                    $content = $view->render('errors/403.ogan', ['message' => $grant->message]);
                    $response->setContent($content);
                } catch (\Exception $e) {
                    $response->setContent('<h1>403 - Access Denied</h1><p>' . htmlspecialchars($grant->message) . '</p>');
                }
                
                return $response;
            }
        }

        return null; // Toutes les vérifications passées
    }

    /**
     * Récupère l'utilisateur courant depuis la session
     */
    private function getCurrentUser(RequestInterface $request): ?\Ogan\Security\UserInterface
    {
        if (!$request->hasSession()) {
            return null;
        }

        $session = $request->getSession();
        $userId = $session->get('_auth_user_id');
        
        if (!$userId) {
            return null;
        }

        // Récupérer la classe User configurée
        $userClass = \Ogan\Config\Config::get('security.user_class', 'App\\Model\\User');
        
        if (!class_exists($userClass) || !method_exists($userClass, 'find')) {
            return null;
        }

        return $userClass::find($userId);
    }
}

