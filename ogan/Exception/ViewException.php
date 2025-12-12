<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * 🎨 VIEWEXCEPTION - Exception pour les erreurs de templates
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * QUAND LANCER CETTE EXCEPTION ?
 * -------------------------------
 * - Template introuvable
 * - Layout introuvable
 * - Erreur lors du rendu (variable non définie, erreur PHP...)
 * - Bloc/section non défini
 * 
 * EXEMPLES :
 * - render('inexistant.html.php') → Template not found
 * - section('content') mais start('content') jamais appelé
 * - Erreur de syntaxe PHP dans le template
 * 
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Exception;

use Exception;

/**
 * Exception lancée pour les erreurs liées aux templates
 */
class ViewException extends Exception
{
    /**
     * Template introuvable
     */
    public static function templateNotFound(string $path): self
    {
        return new self("Template not found: {$path}");
    }

    /**
     * Layout introuvable
     */
    public static function layoutNotFound(string $layout): self
    {
        return new self("Layout not found: {$layout}");
    }

    /**
     * Section/bloc non défini
     */
    public static function sectionNotFound(string $name): self
    {
        return new self("Section '{$name}' not defined. Did you forget start('{$name}') ?");
    }

    /**
     * Erreur lors du rendu
     */
    public static function renderError(string $template, string $error): self
    {
        return new self("Error rendering template '{$template}': {$error}");
    }
}
