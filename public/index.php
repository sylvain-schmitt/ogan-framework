<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🚀 POINT D'ENTRÉE DU FRAMEWORK OGAN
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce fichier est le POINT D'ENTRÉE de toute l'application.
 * Toutes les requêtes HTTP passent par ici (via .htaccess ou nginx).
 * 
 * AVANT (40+ lignes) :
 * - Gestion d'erreurs
 * - Initialisation du Container
 * - Enregistrement des services
 * - Configuration du Router
 * - Dispatch de la requête
 * 
 * APRÈS (3 lignes) :
 * - Autoload
 * - Crée le Kernel
 * - Lance l'application
 * 
 * Tout le reste est délégué au Kernel ! 🎯
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

use Ogan\Kernel\Kernel;

// Chargement de l'autoloader
require __DIR__ . '/../vendor/autoload.php';

// Charger le .env pour lire APP_DEBUG avant d'initialiser le Kernel
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Enlever les guillemets si présents
        $value = trim($value, '"\'');
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Création et lancement du Kernel
// Le mode debug est lu depuis APP_DEBUG dans .env
// debug: true → erreurs détaillées (développement)  
// debug: false → page d'erreur générique (production)
$debug = filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
$kernel = new Kernel(debug: $debug);
$kernel->run();

/**
 * C'est TOUT ! 🎉
 * 
 * Le Kernel s'occupe de :
 * ✅ Enregistrer l'ErrorHandler
 * ✅ Initialiser le Container
 * ✅ Enregistrer les services (Request, Response, Router)
 * ✅ Charger les routes
 * ✅ Dispatcher la requête
 * 
 * index.php reste ultra-simple et lisible !
 */
