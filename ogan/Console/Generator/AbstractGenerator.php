<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔧 ABSTRACT GENERATOR - Classe de base pour les générateurs de code
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Classe abstraite de base pour tous les générateurs de code.
 * Fournit des méthodes utilitaires communes.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator;

abstract class AbstractGenerator
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UN DOSSIER S'IL N'EXISTE PAS
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR UN NOM EN NAMESPACE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Exemple : "UserController" → "App\Controller"
     */
    protected function getNamespace(string $baseNamespace, string $subNamespace = ''): string
    {
        $namespace = $baseNamespace;
        if ($subNamespace) {
            $namespace .= '\\' . $subNamespace;
        }
        return $namespace;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR UN NOM EN NOM DE CLASSE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Exemple : "user" → "User", "user_controller" → "UserController"
     */
    public function toClassName(string $name): string
    {
        // Si le nom est déjà en PascalCase, le garder tel quel
        if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) && !str_contains($name, '_') && !str_contains($name, '-')) {
            return $name;
        }

        // Enlever les suffixes communs s'ils sont présents
        $name = preg_replace('/_(controller|form|type|model)$/i', '', $name);

        // Convertir en PascalCase
        $parts = preg_split('/[_\s-]+/', $name);
        $parts = array_map(function ($part) {
            // Préserver la casse si déjà en PascalCase
            if (preg_match('/^[A-Z]/', $part)) {
                return $part;
            }
            return ucfirst(strtolower($part));
        }, $parts);

        return implode('', $parts);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR UN NOM EN NOM DE FICHIER
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Exemple : "UserController" → "UserController.php"
     */
    protected function toFileName(string $className): string
    {
        return $className . '.php';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR UN NOM EN ROUTE (snake_case)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Exemple : "UserController" → "user"
     */
    protected function toRouteName(string $name): string
    {
        // Enlever les suffixes
        $name = preg_replace('/(Controller|FormType|Model)$/i', '', $name);

        // Convertir en snake_case
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);

        return strtolower($name);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UN FICHIER EXISTE DÉJÀ
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function fileExists(string $filepath): bool
    {
        return file_exists($filepath);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ÉCRIRE UN FICHIER
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function writeFile(string $filepath, string $content): void
    {
        file_put_contents($filepath, $content);
        clearstatcache(true, $filepath);
    }
}
