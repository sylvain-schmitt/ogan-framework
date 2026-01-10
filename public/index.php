<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🚀 POINT D'ENTRÉE DU FRAMEWORK OGAN
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Ce fichier est le POINT D'ENTRÉE de toute l'application.
 * Toutes les requêtes HTTP passent par ici (via .htaccess ou nginx).
 *
 * Tout le reste est délégué au Kernel ! 🎯
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

declare(strict_types=1);

use Ogan\Kernel\Kernel;

// Chargement de l'autoloader
require __DIR__ . '/../vendor/autoload.php';

// Définir la racine du projet
define('PROJECT_ROOT', dirname(__DIR__));

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
// Détermination intelligente du mode debug
// 1. Si APP_DEBUG est défini, on l'utilise
// 2. Sinon, on le déduit de APP_ENV (prod = false, dev/test = true)
if (isset($_ENV['APP_DEBUG'])) {
    $debug = filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN);
} else {
    $env = $_ENV['APP_ENV'] ?? 'dev';
    $debug = $env !== 'prod';
}
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
