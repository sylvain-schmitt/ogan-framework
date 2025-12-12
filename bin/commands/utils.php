<?php

/**
 * Commandes utilitaires
 */
function registerUtilsCommands($app) {
    $app->addCommand('cache:clear', function($args) {
        $projectRoot = dirname(__DIR__, 2);
        $cacheDir = $projectRoot . '/var/cache/templates';
        
        if (!is_dir($cacheDir)) {
            echo "ℹ️  Aucun cache à vider\n";
            return 0;
        }
        
        $files = glob($cacheDir . '/*');
        $count = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }
        
        echo "✅ {$count} fichier(s) de cache supprimé(s)\n";
        return 0;
    }, 'Vide le cache des templates');

    $app->addCommand('routes:list', function($args) {
    $projectRoot = dirname(__DIR__, 2);
    
    // Charger le router avec toutes les routes
    require_once $projectRoot . '/vendor/autoload.php';
    
    $router = new \Ogan\Router\Router();
    $controllersPath = $projectRoot . '/src/Controller';
    
    if (!is_dir($controllersPath)) {
        echo "❌ Dossier des contrôleurs non trouvé : {$controllersPath}\n";
        return 1;
    }
    
    $router->loadRoutesFromControllers($controllersPath);
    
    // Charger les middlewares
    $middlewaresConfigPath = $projectRoot . '/config/middlewares.yaml';
    \Ogan\Config\MiddlewareLoader::loadFromYaml($middlewaresConfigPath, $router);
    
    $routes = $router->getRoutes();
    
    if (empty($routes)) {
        echo "ℹ️  Aucune route trouvée.\n";
        return 0;
    }
    
    echo "\n📋 Liste des routes\n\n";
    echo str_repeat('─', 120) . "\n";
    printf("%-25s %-10s %-35s %-40s\n", "Nom", "Méthode", "URI", "Action");
    echo str_repeat('─', 120) . "\n";
    
    foreach ($routes as $route) {
        $name = $route->name ?? '-';
        $methods = implode('|', $route->httpMethods);
        $path = $route->path;
        $action = basename(str_replace('\\', '/', $route->controllerClass)) . '@' . $route->controllerMethod;
        
        printf("%-25s %-10s %-35s %-40s\n", 
            substr($name, 0, 24), 
            substr($methods, 0, 9),
            substr($path, 0, 34),
            substr($action, 0, 39)
        );
    }
    
    echo str_repeat('─', 120) . "\n";
    echo "\nTotal : " . count($routes) . " route(s)\n\n";
    
    return 0;
}, 'Liste toutes les routes de l\'application');
}
