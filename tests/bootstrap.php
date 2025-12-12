<?php

/**
 * Bootstrap pour les tests PHPUnit
 * 
 * Ce fichier est exécuté avant chaque test pour initialiser
 * l'environnement de test.
 */

// Charger l'autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Définir le chemin de base pour les tests
define('TEST_BASE_PATH', __DIR__ . '/../');

// Configuration de l'environnement de test
$_ENV['APP_ENV'] = 'test';
$_ENV['APP_DEBUG'] = 'true';

// Créer les répertoires nécessaires pour les tests
$testDirs = [
    TEST_BASE_PATH . 'var/cache/templates',
    TEST_BASE_PATH . 'var/log',
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}
