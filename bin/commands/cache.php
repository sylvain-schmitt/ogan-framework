<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         COMMANDES CACHE CLI
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Commandes pour gérer le cache de l'application.
 * 
 * Usage:
 *   php bin/console cache:clear           Vider tout le cache
 *   php bin/console cache:clear --type=X  Vider un type spécifique
 *   php bin/console cache:stats           Statistiques du cache
 *   php bin/console cache:routes          Compiler les routes
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

use Ogan\Cache\CacheManager;
use Ogan\Cache\FileCache;

function registerCacheCommands($app) {
    
    // ═══════════════════════════════════════════════════════════════
    // cache:clear - Vider le cache
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('cache:clear', function($args) {
        $projectRoot = dirname(__DIR__, 2);
        $type = $args['type'] ?? 'all';
        $total = 0;

        echo "\n🗑️  Nettoyage du cache...\n\n";

        // Cache des templates
        if ($type === 'all' || $type === 'templates') {
            $count = clearDirectory($projectRoot . '/var/cache/templates', 'Templates');
            $total += $count;
        }

        // Cache des données (FileCache)
        if ($type === 'all' || $type === 'data') {
            $count = clearDirectory($projectRoot . '/var/cache/data', 'Données');
            $total += $count;
        }

        // Cache des routes compilées
        if ($type === 'all' || $type === 'routes') {
            $routeFile = $projectRoot . '/var/cache/routes.php';
            if (file_exists($routeFile)) {
                unlink($routeFile);
                echo "  ✓ Routes     : fichier compilé supprimé\n";
                $total++;
            } else {
                echo "  ○ Routes     : aucun cache\n";
            }
        }

        echo "\n" . str_repeat('─', 50) . "\n";
        echo "✅ Total : {$total} élément(s) supprimé(s)\n\n";

        return 0;
    }, 'Vide le cache de l\'application (options: --type=templates|data|routes|all)');

    // ═══════════════════════════════════════════════════════════════
    // cache:stats - Statistiques du cache
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('cache:stats', function($args) {
        $projectRoot = dirname(__DIR__, 2);

        echo "\n📊 Statistiques du cache\n\n";
        echo str_repeat('─', 60) . "\n";
        printf("%-20s %-15s %-15s %-10s\n", "Type", "Fichiers", "Taille", "Expirés");
        echo str_repeat('─', 60) . "\n";

        // Cache des templates
        $templatesStats = getDirectoryStats($projectRoot . '/var/cache/templates');
        printf("%-20s %-15s %-15s %-10s\n", 
            "Templates", 
            $templatesStats['count'],
            formatBytes($templatesStats['size']),
            "-"
        );

        // Cache des données
        $dataDir = $projectRoot . '/var/cache/data';
        if (is_dir($dataDir)) {
            $cache = new FileCache($dataDir);
            $stats = $cache->getStats();
            printf("%-20s %-15s %-15s %-10s\n",
                "Données (file)",
                $stats['count'],
                $stats['size_human'],
                $stats['expired']
            );
        } else {
            printf("%-20s %-15s %-15s %-10s\n", "Données (file)", "0", "0 B", "-");
        }

        // Routes compilées
        $routeFile = $projectRoot . '/var/cache/routes.php';
        if (file_exists($routeFile)) {
            printf("%-20s %-15s %-15s %-10s\n",
                "Routes compilées",
                "1",
                formatBytes(filesize($routeFile)),
                "-"
            );
        } else {
            printf("%-20s %-15s %-15s %-10s\n", "Routes compilées", "0", "0 B", "-");
        }

        echo str_repeat('─', 60) . "\n\n";

        return 0;
    }, 'Affiche les statistiques du cache');

    // ═══════════════════════════════════════════════════════════════
    // cache:routes - Compiler les routes
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('cache:routes', function($args) {
        $projectRoot = dirname(__DIR__, 2);
        $cacheFile = $projectRoot . '/var/cache/routes.php';
        
        echo "\n🔄 Compilation des routes...\n\n";

        // Charger le router
        require_once $projectRoot . '/vendor/autoload.php';

        $router = new \Ogan\Router\Router();
        $controllersPath = $projectRoot . '/src/Controller';

        if (!is_dir($controllersPath)) {
            echo "❌ Dossier des contrôleurs non trouvé : {$controllersPath}\n";
            return 1;
        }

        // Charger les routes depuis les contrôleurs
        $router->loadRoutesFromControllers($controllersPath);

        // Charger les middlewares
        $middlewaresConfigPath = $projectRoot . '/config/middlewares.yaml';
        if (file_exists($middlewaresConfigPath)) {
            \Ogan\Config\MiddlewareLoader::loadFromYaml($middlewaresConfigPath, $router);
        }

        $routes = $router->getRoutes();

        if (empty($routes)) {
            echo "⚠️  Aucune route à compiler.\n";
            return 0;
        }

        // Générer le fichier de cache
        $compiled = generateRoutesCache($routes);

        // S'assurer que le répertoire existe
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cacheFile, $compiled);

        echo "  ✓ " . count($routes) . " routes compilées\n";
        echo "  ✓ Fichier généré : var/cache/routes.php\n";
        echo "  ✓ Taille : " . formatBytes(strlen($compiled)) . "\n";
        echo "\n✅ Routes compilées avec succès !\n\n";

        return 0;
    }, 'Compile les routes en fichier PHP pour de meilleures performances');

    // ═══════════════════════════════════════════════════════════════
    // cache:gc - Garbage collection (nettoie les entrées expirées)
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('cache:gc', function($args) {
        $projectRoot = dirname(__DIR__, 2);
        $dataDir = $projectRoot . '/var/cache/data';

        echo "\n🧹 Nettoyage des entrées expirées...\n\n";

        if (!is_dir($dataDir)) {
            echo "ℹ️  Aucun cache de données à nettoyer.\n\n";
            return 0;
        }

        $cache = new FileCache($dataDir);
        $deleted = $cache->gc();

        echo "✅ {$deleted} entrée(s) expirée(s) supprimée(s)\n\n";

        return 0;
    }, 'Nettoie les entrées de cache expirées');
}

// ═══════════════════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES
// ═══════════════════════════════════════════════════════════════════════

/**
 * Vide un répertoire de cache
 */
function clearDirectory(string $dir, string $label): int {
    if (!is_dir($dir)) {
        echo "  ○ {$label}   : aucun cache\n";
        return 0;
    }

    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isFile()) {
            @unlink($item->getRealPath());
            $count++;
        } elseif ($item->isDir()) {
            @rmdir($item->getRealPath());
        }
    }

    echo "  ✓ {$label}   : {$count} fichier(s)\n";
    return $count;
}

/**
 * Statistiques d'un répertoire
 */
function getDirectoryStats(string $dir): array {
    if (!is_dir($dir)) {
        return ['count' => 0, 'size' => 0];
    }

    $count = 0;
    $size = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $count++;
            $size += $file->getSize();
        }
    }

    return compact('count', 'size');
}

/**
 * Formate une taille en octets
 */
function formatBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Génère le code PHP pour le cache des routes
 */
function generateRoutesCache(array $routes): string {
    $code = "<?php\n\n";
    $code .= "/**\n";
    $code .= " * Cache des routes - Généré automatiquement\n";
    $code .= " * Date: " . date('Y-m-d H:i:s') . "\n";
    $code .= " * Ne pas modifier manuellement !\n";
    $code .= " */\n\n";
    $code .= "return [\n";

    foreach ($routes as $route) {
        $methods = var_export($route->httpMethods, true);
        $path = var_export($route->path, true);
        $controller = var_export($route->controllerClass, true);
        $method = var_export($route->controllerMethod, true);
        $name = var_export($route->name ?? null, true);
        $middlewares = var_export($route->middlewares ?? [], true);

        $code .= "    [\n";
        $code .= "        'methods' => {$methods},\n";
        $code .= "        'path' => {$path},\n";
        $code .= "        'controller' => {$controller},\n";
        $code .= "        'method' => {$method},\n";
        $code .= "        'name' => {$name},\n";
        $code .= "        'middlewares' => {$middlewares},\n";
        $code .= "    ],\n";
    }

    $code .= "];\n";

    return $code;
}
