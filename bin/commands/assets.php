<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🗺️ COMMANDES ASSETS - OganAssetMapper
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Gère les assets JavaScript :
 * - Télécharge HTMX
 * - Crée les symlinks (dev) ou copies (prod)
 *
 * Usage :
 *   php bin/console assets:install          # Symlinks (dev)
 *   php bin/console assets:install --env=prod  # Copies (prod)
 *   php bin/console assets:install --update    # Force la mise à jour de HTMX
 *
 * ═══════════════════════════════════════════════════════════════════════
 */
function registerAssetsCommands($app)
{
    $projectRoot = dirname(__DIR__, 2);

    // assets:install
    $app->addCommand('assets:install', function ($args) use ($projectRoot) {
        $isProd = in_array('--env=prod', $args) || in_array('--prod', $args);
        $forceUpdate = in_array('--update', $args);

        $sourceDir = $projectRoot . '/assets/js';
        $publicDir = $projectRoot . '/public/assets/js';

        echo "🗺️  OganAssetMapper - Installation des assets...\n\n";

        // 1. Créer le dossier public/assets/js si absent
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
            echo "📁 Créé: public/assets/js/\n";
        }

        // 2. Télécharger HTMX si absent ou --update
        $htmxPath = $publicDir . '/htmx.min.js';
        if (!file_exists($htmxPath) || $forceUpdate) {
            echo "📥 Téléchargement de HTMX...\n";
            $htmxUrl = 'https://unpkg.com/htmx.org@latest/dist/htmx.min.js';
            $htmxContent = @file_get_contents($htmxUrl);

            if ($htmxContent === false) {
                echo "   ⚠️  Échec du téléchargement. Utilisation de la version locale si disponible.\n";
            } else {
                file_put_contents($htmxPath, $htmxContent);
                echo "   ✅ HTMX téléchargé (dernière version)\n";
            }
        } else {
            echo "✅ HTMX déjà présent (utilisez --update pour mettre à jour)\n";
        }

        // 3. Créer les symlinks ou copies
        $filesToLink = [
            'app.js',
            'ogan-stimulus.js',
            'controllers'
        ];

        echo "\n";

        foreach ($filesToLink as $file) {
            $source = $sourceDir . '/' . $file;
            $target = $publicDir . '/' . $file;

            if (!file_exists($source)) {
                echo "   ⚠️  Source manquante: assets/js/{$file}\n";
                continue;
            }

            // Supprimer l'ancien lien/fichier si existe
            if (is_link($target) || file_exists($target)) {
                if (is_dir($target) && !is_link($target)) {
                    // Dossier réel, le supprimer récursivement
                    deleteDirectory($target);
                } else {
                    unlink($target);
                }
            }

            if ($isProd) {
                // Mode production : copie avec hash optionnel
                if (is_dir($source)) {
                    copyDirectory($source, $target);
                    echo "📄 Copié: {$file}/ (prod)\n";
                } else {
                    copy($source, $target);
                    echo "📄 Copié: {$file} (prod)\n";
                }
            } else {
                // Mode dev : symlink relatif
                $relativeSource = getRelativePath($target, $source);

                if (symlink($relativeSource, $target)) {
                    echo "🔗 Symlink: {$file} -> {$relativeSource}\n";
                } else {
                    // Fallback: copie si symlink échoue (Windows...)
                    if (is_dir($source)) {
                        copyDirectory($source, $target);
                    } else {
                        copy($source, $target);
                    }
                    echo "📄 Copié: {$file} (symlink non supporté)\n";
                }
            }
        }

        echo "\n🎉 Assets installés avec succès !\n";

        if (!$isProd) {
            echo "\n💡 Les fichiers sources sont dans: assets/js/\n";
            echo "   Modifiez-les directement, les changements seront visibles immédiatement.\n";
        }

        return 0;
    }, 'Installe les assets JS (symlinks en dev, copies en prod)');
}

/**
 * Calcule le chemin relatif entre deux fichiers
 * public/assets/js/ -> assets/js/ = ../../../assets/js/
 */
function getRelativePath(string $from, string $to): string
{
    // Depuis public/assets/js/ vers assets/js/ = 3 niveaux (public -> racine)
    return '../../../assets/js/' . basename($to);
}

/**
 * Copie un dossier récursivement
 */
function copyDirectory(string $source, string $dest): void
{
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $targetPath = $dest . '/' . $iterator->getSubPathname();
        if ($item->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            copy($item->getPathname(), $targetPath);
        }
    }
}

/**
 * Supprime un dossier récursivement
 */
function deleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($dir);
}
