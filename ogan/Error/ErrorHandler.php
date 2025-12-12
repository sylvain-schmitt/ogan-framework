<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🚨 ERRORHANDLER - Gestionnaire Global d'Erreurs
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * RÔLE
 * ----
 * Capture TOUTES les erreurs et exceptions de l'application et les affiche
 * de manière propre et utile.
 * 
 * TYPES D'ERREURS GÉRÉES
 * -----------------------
 * 1. Exceptions non catchées (throw new Exception())
 * 2. Erreurs fatales PHP (parse error, call to undefined function...)
 * 3. Warnings et notices PHP (si configuré)
 * 
 * MODES D'AFFICHAGE
 * -----------------
 * - **DEV** : Affichage complet (stack trace, fichier, ligne, variables...)
 * - **PROD** : Page d'erreur générique sans détails techniques (sécurité)
 * 
 * UTILISATION
 * -----------
 * Dans public/index.php, au tout début :
 * 
 * ```php
 * use Ogan\Error\ErrorHandler;
 * 
 * $errorHandler = new ErrorHandler(debug: true); // true = mode dev
 * $errorHandler->register();
 * ```
 * 
 * FONCTIONNEMENT INTERNE
 * ----------------------
 * ErrorHandler s'enregistre avec :
 * - set_exception_handler() : Catch les exceptions non gérées
 * - set_error_handler() : Convertit les erreurs PHP en exceptions
 * - register_shutdown_function() : Catch les erreurs fatales
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Error;

use Throwable;
use Ogan\Exception\RouteNotFoundException;

class ErrorHandler
{
    private bool $debug;

    /**
     * @param bool $debug Mode debug (true = dev, false = prod)
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Enregistre le handler comme gestionnaire global
     */
    public function register(): void
    {
        // Capture les exceptions non catchées
        set_exception_handler([$this, 'handleException']);

        // Convertit les erreurs PHP (warnings, notices...) en exceptions
        set_error_handler([$this, 'handleError']);

        // Capture les erreurs fatales (parse error, etc.)
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Gère les exceptions non catchées
     */
    public function handleException(Throwable $exception): void
    {
        // Détermine le code HTTP selon le type d'exception
        $statusCode = $this->getStatusCode($exception);
        http_response_code($statusCode);

        if ($this->debug) {
            $this->renderDebugPage($exception);
        } else {
            $this->renderProductionPage($exception, $statusCode);
        }

        exit(1);
    }

    /**
     * Convertit les erreurs PHP en exceptions
     */
    public function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        // Ne pas convertir les erreurs supprimées avec @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Gère les erreurs fatales
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        // Vérifier si c'est une erreur fatale
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleException(
                new \ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                )
            );
        }
    }

    /**
     * Détermine le code HTTP selon le type d'exception
     */
    private function getStatusCode(Throwable $exception): int
    {
        // Si l'exception a déjà un code défini
        if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
            return $exception->getCode();
        }

        // Selon le type d'exception
        if ($exception instanceof RouteNotFoundException) {
            return 404;
        }

        // Par défaut : 500 Internal Server Error
        return 500;
    }

    /**
     * Affiche une page d'erreur détaillée (mode dev)
     */
    private function renderDebugPage(Throwable $exception): void
    {
        $class = get_class($exception);
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8');
        $line = $exception->getLine();
        $trace = htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        $shortClass = substr(strrchr($class, '\\'), 1) ?: $class;

        echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - Framework Ogan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-orange-600 text-white rounded-t-xl shadow-2xl overflow-hidden">
            <div class="px-8 py-6">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <h1 class="text-3xl font-bold">Une erreur s'est produite</h1>
                </div>
                <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2 inline-block">
                    <code class="text-sm font-mono">{$shortClass}</code>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-b-xl shadow-2xl overflow-hidden">
            <div class="p-8">
                <!-- Message -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mb-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-lg text-gray-800 font-semibold">{$message}</p>
                    </div>
                </div>

                <!-- Location -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-4">Détails de l'erreur</h3>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <span class="text-gray-600 font-medium w-24">Fichier:</span>
                            <code class="text-sm text-gray-800 font-mono bg-white px-3 py-1 rounded flex-1 break-all">{$file}</code>
                        </div>
                        <div class="flex items-start">
                            <span class="text-gray-600 font-medium w-24">Ligne:</span>
                            <code class="text-sm text-gray-800 font-mono bg-white px-3 py-1 rounded">{$line}</code>
                        </div>
                        <div class="flex items-start">
                            <span class="text-gray-600 font-medium w-24">Classe:</span>
                            <code class="text-sm text-gray-800 font-mono bg-white px-3 py-1 rounded flex-1 break-all">{$class}</code>
                        </div>
                    </div>
                </div>

                <!-- Stack Trace -->
                <div class="bg-gray-900 rounded-lg p-6 overflow-hidden">
                    <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Stack Trace
                    </h3>
                    <pre class="text-green-400 font-mono text-xs leading-relaxed overflow-x-auto">{$trace}</pre>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 border-t border-gray-200 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span class="text-sm font-medium">Framework Ogan 🐕</span>
                    </div>
                    <span class="text-xs text-gray-500 bg-white px-3 py-1 rounded-full">Mode DEBUG</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Affiche une page d'erreur générique (mode production)
     */
    private function renderProductionPage(Throwable $exception, int $statusCode): void
    {
        $title = $statusCode === 404 ? 'Page non trouvée' : 'Erreur serveur';
        $message = $statusCode === 404
            ? 'La page que vous recherchez n\'existe pas.'
            : 'Une erreur s\'est produite. Veuillez réessayer plus tard.';
        $icon = $statusCode === 404 ? 'M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';

        echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 min-h-screen flex items-center justify-center px-4">
    <div class="text-center text-white max-w-2xl">
        <!-- Error Code -->
        <div class="mb-8">
            <div class="text-9xl font-bold opacity-20 mb-4">{$statusCode}</div>
            <div class="flex justify-center mb-6">
                <svg class="w-24 h-24 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{$icon}"></path>
                </svg>
            </div>
        </div>

        <!-- Title and Message -->
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{$title}</h1>
        <p class="text-xl md:text-2xl opacity-90 mb-8">{$message}</p>

        <!-- Action Button -->
        <a href="/" class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-semibold px-6 py-3 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour à l'accueil
        </a>

        <!-- Footer -->
        <div class="mt-12 text-white/60 text-sm">
            Framework Ogan 🐕
        </div>
    </div>
</body>
</html>
HTML;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 📚 NOTES PÉDAGOGIQUES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * HANDLERS PHP
 * ------------
 * 
 * set_exception_handler() :
 * - Attrape les exceptions NON catchées
 * - Exemple : throw new Exception() sans try/catch
 * 
 * set_error_handler() :
 * - Converti les warnings/notices PHP en exceptions
 * - Permet de gérer uniform_ement toutes les erreurs
 * 
 * register_shutdown_function() :
 * - S'exécute à la fin du script (même en cas d'erreur fatale)
 * - Seul moyen de catcher les parse errors, fatal errors
 * 
 * MODE DEBUG vs PRODUCTION
 * ------------------------
 * 
 * DEBUG (dev) :
 * - Affiche tous les détails (fichier, ligne, stack trace)
 * - Aide au debugging
 * - NE JAMAIS activer en production (fuite d'informations sensibles)
 * 
 * PRODUCTION :
 * - Page générique sans détails techniques
 * - Sécurité : ne révèle pas la structure du code
 * - Logger les erreurs dans un fichier plutôt qu'à l'écran
 * 
 * CODES HTTP
 * ----------
 * - 404 : Not Found (route introuvable)
 * - 500 : Internal Server Error (erreur générale)
 * - 400 : Bad Request (validation échouée)
 * - 403 : Forbidden (accès interdit)
 * - 503 : Service Unavailable (maintenance)
 * 
 * AMÉLIORATIONS FUTURES
 * ---------------------
 * - Logger les erreurs dans un fichier (logs/error.log)
 * - Envoyer des emails pour les erreurs critiques (500)
 * - Intégrer avec Sentry/Bugsnag pour monitoring
 * - Pages d'erreur personnalisables par template
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */
