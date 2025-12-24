<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         COMMANDES SEO CLI
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Génération des fichiers SEO pour Google Search Console.
 *
 * Usage:
 *   php bin/console seo:sitemap           Génère sitemap.xml
 *   php bin/console seo:robots            Génère robots.txt
 *   php bin/console seo:all               Génère les deux fichiers
 *
 * Options:
 *   --base-url=URL    URL de base du site (ex: https://example.com)
 *   --output=PATH     Chemin de sortie (défaut: public/)
 *
 * ═══════════════════════════════════════════════════════════════════════
 */

use Ogan\Seo\SitemapGenerator;
use Ogan\Seo\RobotsGenerator;
use Ogan\Config\Config;

function registerSeoCommands($app)
{

    // ═══════════════════════════════════════════════════════════════
    // seo:sitemap - Générer sitemap.xml
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('seo:sitemap', function ($args) {
        $projectRoot = dirname(__DIR__, 2);

        // Récupérer l'URL de base
        $baseUrl = $args['base-url'] ?? getBaseUrl();
        $outputDir = $args['output'] ?? $projectRoot . '/public';
        $outputPath = rtrim($outputDir, '/') . '/sitemap.xml';

        echo "\n🗺️  Génération du sitemap.xml...\n\n";

        // Charger le router pour récupérer les routes
        require_once $projectRoot . '/vendor/autoload.php';

        // Initialiser la config si nécessaire
        $configPath = $projectRoot . '/config/parameters.php';
        if (file_exists($configPath) && !Config::has('app.env')) {
            Config::init($configPath);
        }

        $router = new \Ogan\Router\Router();
        $controllersPath = $projectRoot . '/src/Controller';

        if (is_dir($controllersPath)) {
            $router->loadRoutesFromControllers($controllersPath);
        }

        // Créer le sitemap
        $sitemap = new SitemapGenerator($baseUrl);

        // Ajouter automatiquement les routes
        $sitemap->addRoutesFromRouter($router, 0.5);

        // Sauvegarder
        if ($sitemap->save($outputPath)) {
            $urls = $sitemap->getUrls();
            echo "  ✓ Fichier généré : {$outputPath}\n";
            echo "  ✓ URLs incluses  : " . count($urls) . "\n";

            // Lister les URLs
            if (count($urls) > 0 && count($urls) <= 20) {
                echo "\n  URLs dans le sitemap :\n";
                foreach ($urls as $url) {
                    echo "    - {$url['loc']} (priority: {$url['priority']})\n";
                }
            }

            echo "\n✅ Sitemap généré avec succès !\n";
            echo "📋 Soumettez ce fichier à Google Search Console : {$baseUrl}/sitemap.xml\n\n";
            return 0;
        } else {
            echo "❌ Erreur lors de la génération du sitemap.\n\n";
            return 1;
        }
    }, 'Génère le fichier sitemap.xml (options: --base-url=URL, --output=PATH)');

    // ═══════════════════════════════════════════════════════════════
    // seo:robots - Générer robots.txt
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('seo:robots', function ($args) {
        $projectRoot = dirname(__DIR__, 2);

        // Récupérer l'URL de base
        $baseUrl = $args['base-url'] ?? getBaseUrl();
        $outputDir = $args['output'] ?? $projectRoot . '/public';
        $outputPath = rtrim($outputDir, '/') . '/robots.txt';

        echo "\n🤖 Génération du robots.txt...\n\n";

        // Créer le robots.txt avec les règles par défaut
        $robots = new RobotsGenerator($baseUrl, true);

        // Ajouter le sitemap
        $robots->sitemap('/sitemap.xml');

        // Sauvegarder
        if ($robots->save($outputPath)) {
            echo "  ✓ Fichier généré : {$outputPath}\n";
            echo "\n  Règles appliquées :\n";

            $rules = $robots->getRules();
            foreach ($rules as $userAgent => $agentRules) {
                echo "    User-agent: {$userAgent}\n";
                foreach ($agentRules['allow'] as $path) {
                    echo "      Allow: {$path}\n";
                }
                foreach ($agentRules['disallow'] as $path) {
                    echo "      Disallow: {$path}\n";
                }
            }

            echo "\n✅ robots.txt généré avec succès !\n";
            echo "📋 Testez ce fichier sur : https://www.google.com/webmasters/tools/robots-testing-tool\n\n";
            return 0;
        } else {
            echo "❌ Erreur lors de la génération du robots.txt.\n\n";
            return 1;
        }
    }, 'Génère le fichier robots.txt (options: --base-url=URL, --output=PATH)');

    // ═══════════════════════════════════════════════════════════════
    // seo:all - Générer tous les fichiers SEO
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('seo:all', function ($args) use ($app) {
        echo "\n═══════════════════════════════════════════════════════════\n";
        echo "               GÉNÉRATION DES FICHIERS SEO\n";
        echo "═══════════════════════════════════════════════════════════\n";

        // Exécuter les deux commandes
        $sitemapResult = $app->runCommand('seo:sitemap', $args);
        $robotsResult = $app->runCommand('seo:robots', $args);

        if ($sitemapResult === 0 && $robotsResult === 0) {
            echo "═══════════════════════════════════════════════════════════\n";
            echo "✅ Tous les fichiers SEO ont été générés avec succès !\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            return 0;
        }

        return 1;
    }, 'Génère sitemap.xml et robots.txt (options: --base-url=URL)');
}

// ═══════════════════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES
// ═══════════════════════════════════════════════════════════════════════

/**
 * Récupère l'URL de base depuis la config ou les variables d'environnement
 */
function getBaseUrl(): string
{
    // Essayer depuis la config
    if (class_exists(Config::class)) {
        try {
            $url = Config::get('app.url') ?? Config::get('app.base_url');
            if ($url) {
                return rtrim($url, '/');
            }
        } catch (\Exception $e) {
            // Config non initialisée
        }
    }

    // Essayer depuis l'environnement
    $envUrl = $_ENV['APP_URL'] ?? getenv('APP_URL');
    if ($envUrl) {
        return rtrim($envUrl, '/');
    }

    // Valeur par défaut
    return 'https://example.com';
}
