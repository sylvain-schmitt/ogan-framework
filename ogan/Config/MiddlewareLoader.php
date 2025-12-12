<?php

namespace Ogan\Config;

use Ogan\Router\Router;
use Ogan\Middleware\MiddlewareInterface;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔧 MIDDLEWARE LOADER
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Charge la configuration des middlewares depuis YAML et les applique aux routes.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
class MiddlewareLoader
{
    /**
     * Charge et applique les middlewares depuis un fichier YAML
     */
    public static function loadFromYaml(string $yamlPath, Router $router): void
    {
        if (!file_exists($yamlPath)) {
            // Fallback sur .php si YAML n'existe pas
            $phpPath = str_replace('.yaml', '.php', $yamlPath);
            if (file_exists($phpPath)) {
                require $phpPath;
                if (function_exists('configureMiddlewares')) {
                    configureMiddlewares($router);
                }
            }
            return;
        }

        $config = YamlParser::parseFile($yamlPath);
        
        // Récupérer les routes nommées via réflexion
        $reflection = new \ReflectionClass($router);
        $property = $reflection->getProperty('namedRoutes');
        $property->setAccessible(true);
        $namedRoutes = $property->getValue($router);

        // ─────────────────────────────────────────────────────────────
        // MIDDLEWARES GLOBAUX
        // ─────────────────────────────────────────────────────────────
        if (isset($config['global']) && is_array($config['global'])) {
            foreach ($namedRoutes as $route) {
                foreach ($config['global'] as $middlewareConfig) {
                    $middleware = self::instantiateMiddleware($middlewareConfig);
                    if ($middleware) {
                        $route->middleware($middleware);
                    }
                }
            }
        }

        // ─────────────────────────────────────────────────────────────
        // MIDDLEWARES PAR ROUTE
        // ─────────────────────────────────────────────────────────────
        if (isset($config['routes']) && is_array($config['routes'])) {
            foreach ($config['routes'] as $routeName => $middlewares) {
                if (isset($namedRoutes[$routeName]) && is_array($middlewares)) {
                    foreach ($middlewares as $middlewareConfig) {
                        $middleware = self::instantiateMiddleware($middlewareConfig);
                        if ($middleware) {
                            $namedRoutes[$routeName]->middleware($middleware);
                        }
                    }
                }
            }
        }
    }

    /**
     * Instancie un middleware depuis sa configuration
     * 
     * @param string|array $config Configuration du middleware
     * @return MiddlewareInterface|string|null Instance ou nom de classe
     */
    private static function instantiateMiddleware(string|array $config): MiddlewareInterface|string|null
    {
        // Configuration simple : nom de classe
        if (is_string($config)) {
            // Si la classe existe, on peut soit l'instancier soit retourner le nom
            // pour que le container l'instancie plus tard
            if (class_exists($config)) {
                // Essayer d'instancier sans paramètres
                try {
                    return new $config();
                } catch (\Exception $e) {
                    // Si ça échoue, retourner le nom de classe pour DI
                    return $config;
                }
            }
            return null;
        }

        // Configuration avec paramètres
        if (is_array($config) && isset($config['class'])) {
            $class = $config['class'];
            $params = $config['params'] ?? [];

            if (!class_exists($class)) {
                return null;
            }

            // Instancier avec les paramètres nommés
            try {
                $reflection = new \ReflectionClass($class);
                
                if (empty($params)) {
                    return $reflection->newInstance();
                }

                // Construire les arguments dans l'ordre du constructeur
                $constructor = $reflection->getConstructor();
                if (!$constructor) {
                    return $reflection->newInstance();
                }

                $args = [];
                foreach ($constructor->getParameters() as $param) {
                    $paramName = $param->getName();
                    if (isset($params[$paramName])) {
                        $args[] = $params[$paramName];
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        // Paramètre requis manquant
                        throw new \Exception("Paramètre requis manquant : {$paramName}");
                    }
                }

                return $reflection->newInstanceArgs($args);
            } catch (\Exception $e) {
                error_log("Erreur lors de l'instanciation du middleware {$class}: " . $e->getMessage());
                return null;
            }
        }

        return null;
    }
}
