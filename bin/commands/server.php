<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                      COMMANDES SERVEUR CLI
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Serveur de développement intégré PHP.
 *
 * Usage:
 *   php bin/console server:start           Démarrer le serveur (port 8000)
 *   php bin/console server:start --port=3000   Port personnalisé
 *
 * ═══════════════════════════════════════════════════════════════════════
 */

function registerServerCommands($app)
{

    // ═══════════════════════════════════════════════════════════════
    // server:start - Démarrer le serveur de développement
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('server:start', function ($args) {
        $projectRoot = dirname(__DIR__, 2);

        // Parser les arguments
        $parsed = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $arg = substr($arg, 2);
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', $arg, 2);
                    $parsed[$key] = $value;
                } else {
                    $parsed[$arg] = true;
                }
            }
        }

        $host = $parsed['host'] ?? '127.0.0.1';
        $port = $parsed['port'] ?? '8000';
        $docroot = $projectRoot . '/public';

        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║  🚀 Serveur de développement Ogan Framework                  ║\n";
        echo "╠══════════════════════════════════════════════════════════════╣\n";
        echo "║                                                              ║\n";
        echo "║  Adresse : http://{$host}:{$port}                           ║\n";
        echo "║  Racine  : public/                                           ║\n";
        echo "║                                                              ║\n";
        echo "║  Appuyez sur Ctrl+C pour arrêter le serveur                  ║\n";
        echo "║                                                              ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Démarrer le serveur PHP intégré
        $command = sprintf(
            'php -S %s:%s -t %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($docroot)
        );

        // Exécuter le serveur (bloquant)
        passthru($command, $returnCode);

        return $returnCode;
    }, 'Démarre le serveur de développement PHP (options: --port=8000, --host=127.0.0.1)');

    // ═══════════════════════════════════════════════════════════════
    // server:stop - Aide pour arrêter le serveur
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('server:stop', function ($args) {
        echo "\n";
        echo "ℹ️  Pour arrêter le serveur, utilisez Ctrl+C dans le terminal\n";
        echo "   où le serveur est en cours d'exécution.\n\n";

        echo "💡 Astuce : Vous pouvez aussi trouver et tuer le processus :\n";
        echo "   lsof -i :8000         # Trouver le processus sur le port 8000\n";
        echo "   kill -9 <PID>         # Tuer le processus\n\n";

        return 0;
    }, 'Affiche comment arrêter le serveur de développement');
}
