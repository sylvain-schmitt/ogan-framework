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

// Création et lancement du Kernel
// debug: true pour le mode développement (affiche les erreurs détaillées)
// debug: false pour la production (page d'erreur générique)
$kernel = new Kernel(debug: true);
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
