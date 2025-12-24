<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 *                         COMMANDES SKELETON CLI
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Synchronisation du projet avec le skeleton de référence.
 *
 * Usage:
 *   php bin/console skeleton:sync     Synchroniser avec le skeleton
 *   php bin/console skeleton:diff     Voir les différences sans modifier
 *
 * ═══════════════════════════════════════════════════════════════════════
 */

function registerSkeletonCommands($app)
{

    // URL du repository skeleton
    $skeletonRepo = 'https://github.com/sylvain-schmitt/ogan-framework.git';

    // Fichiers/dossiers à synchroniser (uniquement les fichiers framework)
    $syncPaths = [
        'bin/commands/',       // Commandes console
        'docs/',               // Documentation
        '.env.example',        // Exemple de configuration
        'bin/console',         // Point d'entrée console
    ];

    // Fichiers à ignorer (jamais synchronisés)
    $ignorePaths = [
        'src/',                // Code utilisateur
        'templates/',          // Templates utilisateur
        'config/',             // Configuration utilisateur
        'public/',             // Assets utilisateur
        'var/',                // Cache/logs
        'vendor/',             // Dépendances
        '.env',                // Configuration locale
        '.git/',               // Git local
        'composer.lock',       // Lock file
    ];

    // ═══════════════════════════════════════════════════════════════
    // skeleton:sync - Synchroniser avec le skeleton
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('skeleton:sync', function ($args) use ($skeletonRepo, $syncPaths, $ignorePaths) {
        $projectRoot = dirname(__DIR__, 2);
        $tempDir = sys_get_temp_dir() . '/ogan-skeleton-' . uniqid();

        echo "\n═══════════════════════════════════════════════════════════\n";
        echo "               🔄 SKELETON SYNC\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        // Étape 1: Cloner le skeleton
        echo "📥 Téléchargement du skeleton depuis GitHub...\n";
        $cloneCmd = "git clone --depth 1 --quiet {$skeletonRepo} {$tempDir} 2>&1";
        exec($cloneCmd, $output, $returnCode);

        if ($returnCode !== 0) {
            echo "❌ Erreur lors du téléchargement du skeleton.\n";
            echo "   Vérifiez votre connexion internet.\n\n";
            return 1;
        }

        echo "   ✓ Skeleton téléchargé\n\n";

        // Étape 2: Comparer les fichiers
        echo "📊 Analyse des différences...\n\n";

        $newFiles = [];
        $modifiedFiles = [];
        $unchangedFiles = [];

        foreach ($syncPaths as $path) {
            $skeletonPath = $tempDir . '/' . $path;
            $projectPath = $projectRoot . '/' . $path;

            if (is_dir($skeletonPath)) {
                // Parcourir le dossier
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($skeletonPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    $relativePath = str_replace($tempDir . '/', '', $file->getRealPath());
                    $localPath = $projectRoot . '/' . $relativePath;

                    if (!file_exists($localPath)) {
                        $newFiles[] = $relativePath;
                    } elseif (md5_file($file->getRealPath()) !== md5_file($localPath)) {
                        $modifiedFiles[] = $relativePath;
                    } else {
                        $unchangedFiles[] = $relativePath;
                    }
                }
            } elseif (file_exists($skeletonPath)) {
                // Fichier unique
                if (!file_exists($projectPath)) {
                    $newFiles[] = $path;
                } elseif (md5_file($skeletonPath) !== md5_file($projectPath)) {
                    $modifiedFiles[] = $path;
                } else {
                    $unchangedFiles[] = $path;
                }
            }
        }

        // Afficher le résumé
        if (empty($newFiles) && empty($modifiedFiles)) {
            echo "✅ Votre projet est à jour avec le skeleton !\n\n";
            cleanupTempDir($tempDir);
            return 0;
        }

        if (!empty($newFiles)) {
            echo "🆕 Nouveaux fichiers disponibles (" . count($newFiles) . ") :\n";
            foreach ($newFiles as $file) {
                echo "   + {$file}\n";
            }
            echo "\n";
        }

        if (!empty($modifiedFiles)) {
            echo "📝 Fichiers modifiés dans le skeleton (" . count($modifiedFiles) . ") :\n";
            foreach ($modifiedFiles as $file) {
                echo "   ~ {$file}\n";
            }
            echo "\n";
        }

        echo "ℹ️  Fichiers inchangés : " . count($unchangedFiles) . "\n\n";

        // Étape 3: Menu interactif
        echo "═══════════════════════════════════════════════════════════\n";
        echo "Que voulez-vous faire ?\n";
        echo "═══════════════════════════════════════════════════════════\n";
        echo "[1] Copier tous les NOUVEAUX fichiers (sans écraser)\n";
        echo "[2] Voir les différences (diff) d'un fichier modifié\n";
        echo "[3] Copier un fichier spécifique\n";
        echo "[4] Tout copier (avec confirmation pour chaque modification)\n";
        echo "[0] Annuler\n\n";

        echo "Votre choix : ";
        $choice = trim(fgets(STDIN));

        switch ($choice) {
            case '1':
                // Copier les nouveaux fichiers
                if (empty($newFiles)) {
                    echo "\n⚠️  Aucun nouveau fichier à copier.\n\n";
                } else {
                    echo "\n";
                    foreach ($newFiles as $file) {
                        $src = $tempDir . '/' . $file;
                        $dest = $projectRoot . '/' . $file;

                        // Créer le dossier parent si nécessaire
                        $destDir = dirname($dest);
                        if (!is_dir($destDir)) {
                            mkdir($destDir, 0755, true);
                        }

                        copy($src, $dest);
                        echo "   ✓ Copié : {$file}\n";
                    }
                    echo "\n✅ " . count($newFiles) . " fichier(s) copié(s) !\n\n";
                }
                break;

            case '2':
                // Voir les diffs
                if (empty($modifiedFiles)) {
                    echo "\n⚠️  Aucun fichier modifié.\n\n";
                } else {
                    echo "\nFichiers modifiés :\n";
                    foreach ($modifiedFiles as $i => $file) {
                        echo "  [{$i}] {$file}\n";
                    }
                    echo "\nNuméro du fichier à comparer : ";
                    $fileIndex = (int)trim(fgets(STDIN));

                    if (isset($modifiedFiles[$fileIndex])) {
                        $file = $modifiedFiles[$fileIndex];
                        $src = $tempDir . '/' . $file;
                        $dest = $projectRoot . '/' . $file;

                        echo "\n═══════════════════════════════════════════════════════════\n";
                        echo "Différences pour : {$file}\n";
                        echo "═══════════════════════════════════════════════════════════\n\n";

                        // Afficher le diff
                        $diffCmd = "diff -u \"{$dest}\" \"{$src}\" 2>&1";
                        passthru($diffCmd);

                        echo "\n\nVoulez-vous remplacer ce fichier ? (o/N) : ";
                        $confirm = strtolower(trim(fgets(STDIN)));

                        if ($confirm === 'o' || $confirm === 'oui' || $confirm === 'y') {
                            // Backup avant écrasement
                            $backupPath = $dest . '.backup-' . date('Ymd-His');
                            copy($dest, $backupPath);
                            copy($src, $dest);
                            echo "\n✓ Fichier remplacé (backup créé : " . basename($backupPath) . ")\n\n";
                        } else {
                            echo "\n✓ Fichier non modifié.\n\n";
                        }
                    }
                }
                break;

            case '3':
                // Copier un fichier spécifique
                $allFiles = array_merge($newFiles, $modifiedFiles);
                if (empty($allFiles)) {
                    echo "\n⚠️  Aucun fichier disponible.\n\n";
                } else {
                    echo "\nFichiers disponibles :\n";
                    foreach ($allFiles as $i => $file) {
                        $status = in_array($file, $newFiles) ? '🆕' : '📝';
                        echo "  [{$i}] {$status} {$file}\n";
                    }
                    echo "\nNuméro du fichier à copier : ";
                    $fileIndex = (int)trim(fgets(STDIN));

                    if (isset($allFiles[$fileIndex])) {
                        $file = $allFiles[$fileIndex];
                        $src = $tempDir . '/' . $file;
                        $dest = $projectRoot . '/' . $file;

                        // Créer le dossier parent si nécessaire
                        $destDir = dirname($dest);
                        if (!is_dir($destDir)) {
                            mkdir($destDir, 0755, true);
                        }

                        // Backup si le fichier existe
                        if (file_exists($dest)) {
                            $backupPath = $dest . '.backup-' . date('Ymd-His');
                            copy($dest, $backupPath);
                            echo "\n   📦 Backup créé : " . basename($backupPath) . "\n";
                        }

                        copy($src, $dest);
                        echo "   ✓ Copié : {$file}\n\n";
                    }
                }
                break;

            case '4':
                // Tout copier avec confirmation
                echo "\n";
                $copied = 0;

                // Nouveaux fichiers (pas de confirmation)
                foreach ($newFiles as $file) {
                    $src = $tempDir . '/' . $file;
                    $dest = $projectRoot . '/' . $file;

                    $destDir = dirname($dest);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }

                    copy($src, $dest);
                    echo "   ✓ Nouveau : {$file}\n";
                    $copied++;
                }

                // Fichiers modifiés (avec confirmation)
                foreach ($modifiedFiles as $file) {
                    echo "\n   📝 {$file}\n";
                    echo "      Remplacer ? (o/N/d=diff) : ";
                    $confirm = strtolower(trim(fgets(STDIN)));

                    if ($confirm === 'd' || $confirm === 'diff') {
                        $src = $tempDir . '/' . $file;
                        $dest = $projectRoot . '/' . $file;
                        passthru("diff -u \"{$dest}\" \"{$src}\" 2>&1");
                        echo "      Remplacer ? (o/N) : ";
                        $confirm = strtolower(trim(fgets(STDIN)));
                    }

                    if ($confirm === 'o' || $confirm === 'oui' || $confirm === 'y') {
                        $src = $tempDir . '/' . $file;
                        $dest = $projectRoot . '/' . $file;

                        // Backup
                        $backupPath = $dest . '.backup-' . date('Ymd-His');
                        copy($dest, $backupPath);

                        copy($src, $dest);
                        echo "      ✓ Remplacé (backup: " . basename($backupPath) . ")\n";
                        $copied++;
                    } else {
                        echo "      ○ Ignoré\n";
                    }
                }

                echo "\n✅ {$copied} fichier(s) synchronisé(s) !\n\n";
                break;

            default:
                echo "\n✓ Opération annulée.\n\n";
        }

        // Nettoyage
        cleanupTempDir($tempDir);

        return 0;
    }, 'Synchronise le projet avec la dernière version du skeleton Ogan');

    // ═══════════════════════════════════════════════════════════════
    // skeleton:diff - Voir les différences sans modifier
    // ═══════════════════════════════════════════════════════════════
    $app->addCommand('skeleton:diff', function ($args) use ($skeletonRepo, $syncPaths) {
        $projectRoot = dirname(__DIR__, 2);
        $tempDir = sys_get_temp_dir() . '/ogan-skeleton-' . uniqid();

        echo "\n📊 Téléchargement et analyse du skeleton...\n\n";

        // Cloner le skeleton
        exec("git clone --depth 1 --quiet {$skeletonRepo} {$tempDir} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            echo "❌ Erreur lors du téléchargement.\n\n";
            return 1;
        }

        $newFiles = [];
        $modifiedFiles = [];

        foreach ($syncPaths as $path) {
            $skeletonPath = $tempDir . '/' . $path;
            $projectPath = $projectRoot . '/' . $path;

            if (is_dir($skeletonPath)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($skeletonPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    $relativePath = str_replace($tempDir . '/', '', $file->getRealPath());
                    $localPath = $projectRoot . '/' . $relativePath;

                    if (!file_exists($localPath)) {
                        $newFiles[] = $relativePath;
                    } elseif (md5_file($file->getRealPath()) !== md5_file($localPath)) {
                        $modifiedFiles[] = $relativePath;
                    }
                }
            } elseif (file_exists($skeletonPath)) {
                if (!file_exists($projectPath)) {
                    $newFiles[] = $path;
                } elseif (md5_file($skeletonPath) !== md5_file($projectPath)) {
                    $modifiedFiles[] = $path;
                }
            }
        }

        if (empty($newFiles) && empty($modifiedFiles)) {
            echo "✅ Votre projet est à jour !\n\n";
        } else {
            if (!empty($newFiles)) {
                echo "🆕 Nouveaux fichiers :\n";
                foreach ($newFiles as $file) {
                    echo "   + {$file}\n";
                }
                echo "\n";
            }

            if (!empty($modifiedFiles)) {
                echo "📝 Fichiers modifiés :\n";
                foreach ($modifiedFiles as $file) {
                    echo "   ~ {$file}\n";
                }
                echo "\n";
            }

            echo "💡 Utilisez 'php bin/console skeleton:sync' pour synchroniser.\n\n";
        }

        cleanupTempDir($tempDir);
        return 0;
    }, 'Affiche les différences avec le skeleton sans modifier les fichiers');
}

/**
 * Supprime un répertoire temporaire
 */
function cleanupTempDir(string $dir): void
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
            @rmdir($item->getRealPath());
        } else {
            @unlink($item->getRealPath());
        }
    }

    @rmdir($dir);
}
