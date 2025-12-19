<?php

use Ogan\Console\Generator\Auth\AuthGenerator;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 COMMANDES AUTH - Génération du système d'authentification
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Utilise les générateurs modulaires dans ogan/Console/Generator/Auth/
 * 
 * Options :
 *   --force   Écrase les fichiers existants
 *   --htmx    Génère les templates avec support HTMX
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
function registerAuthCommands($app) {
    $projectRoot = dirname(__DIR__, 2);

    // make:auth
    $app->addCommand('make:auth', function($args) use ($projectRoot) {
        $force = in_array('--force', $args);
        $htmx = in_array('--htmx', $args);
        
        echo "🔐 Génération du système d'authentification...\n";
        if ($htmx) {
            echo "   (avec support HTMX activé)\n";
        }
        echo "\n";

        $generator = new AuthGenerator();
        $result = $generator->generate($projectRoot, $force, $htmx);

        // Afficher les fichiers générés
        if (!empty($result['generated'])) {
            echo "✅ Fichiers générés :\n";
            foreach ($result['generated'] as $file) {
                echo "   📄 {$file}\n";
            }
        }

        // Afficher les fichiers ignorés
        if (!empty($result['skipped'])) {
            echo "\n⏭️  Fichiers ignorés (utilisez --force pour écraser) :\n";
            foreach ($result['skipped'] as $file) {
                echo "   ⚠️  {$file}\n";
            }
        }

        echo "\n🎉 Système d'authentification et Dashboard générés avec succès !\n\n";
        echo "📋 Prochaines étapes :\n";
        echo "   1. php bin/console migrate      # Créer les tables\n";
        echo "   2. Configurer MAILER_DSN dans .env\n";
        echo "   3. Accéder à /register pour créer un compte\n";
        echo "   4. Accéder à /dashboard pour voir le back-office\n";

        return 0;
    }, 'Génère le système d\'authentification complet (--htmx pour HTMX, --force pour écraser)');
}

