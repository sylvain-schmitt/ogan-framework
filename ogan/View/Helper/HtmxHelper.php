<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🚀 HTMX HELPER - Utilitaires pour l'intégration HTMX
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * HTMX permet d'ajouter de l'interactivité aux pages sans écrire de JavaScript.
 * Ce helper fournit des fonctions pour intégrer HTMX dans les templates Ogan.
 * 
 * ACTIVATION :
 * ------------
 * Dans config/parameters.yaml :
 * frontend:
 *   htmx:
 *     enabled: true
 * 
 * UTILISATION :
 * -------------
 * Dans les templates :
 *   {{ htmx_script() }}               - Inclut le script HTMX
 *   <button hx-delete="/user/1">      - Suppression sans rechargement
 *   <form hx-post="/user/store">      - Formulaire dynamique
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\View\Helper;

use Ogan\Config\Config;

class HtmxHelper
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI HTMX EST ACTIVÉ
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function isEnabled(): bool
    {
        try {
            return Config::get('frontend.htmx.enabled', false);
        } catch (\Exception $e) {
            // Config pas encore initialisé
            return false;
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LA BALISE SCRIPT HTMX
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne la balise <script> pour charger HTMX.
     * Inclut également la barre de progression si activée.
     * Ne retourne rien si HTMX est désactivé.
     */
    public static function script(): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        $scriptPath = Config::get('frontend.htmx.script', '/js/htmx.min.js');
        $progressBar = Config::get('frontend.htmx.progress_bar', true);
        
        $html = '<script src="' . htmlspecialchars($scriptPath) . '"></script>';
        
        // Ajouter la barre de progression si activée
        if ($progressBar) {
            $html .= self::progressBarStyles();
            $html .= self::progressBarScript();
        }
        
        return $html;
    }

    /**
     * STYLES CSS POUR LA BARRE DE PROGRESSION
     */
    private static function progressBarStyles(): string
    {
        return <<<'CSS'
<style>
.htmx-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, #3b82f6, #60a5fa, #93c5fd);
    z-index: 9999;
    box-shadow: 0 0 10px rgba(59, 130, 246, 0.7);
    pointer-events: none;
}
.htmx-progress.htmx-progress-loading {
    animation: htmx-progress-animate 1.5s ease-in-out infinite;
}
@keyframes htmx-progress-animate {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 85%; }
}
.htmx-progress.htmx-progress-done {
    width: 100% !important;
    animation: htmx-progress-complete 0.4s ease-out forwards;
}
@keyframes htmx-progress-complete {
    0% { width: 100%; opacity: 1; }
    50% { width: 100%; opacity: 1; }
    100% { width: 100%; opacity: 0; }
}
</style>
CSS;
    }

    private static function progressBarScript(): string
    {
        return <<<'JS'
<script>
(function() {
    // Attendre que le DOM soit prêt
    function init() {
        // Créer la barre de progression
        let bar = document.getElementById('htmx-progress-bar');
        if (!bar) {
            bar = document.createElement('div');
            bar.className = 'htmx-progress';
            bar.id = 'htmx-progress-bar';
            document.body.appendChild(bar);
        }
    }

    // Écouter les événements HTMX sur document (capture phase)
    document.addEventListener('htmx:beforeRequest', function(evt) {
        const bar = document.getElementById('htmx-progress-bar');
        if (bar) {
            bar.style.opacity = '1';
            bar.classList.remove('htmx-progress-done');
            bar.classList.add('htmx-progress-loading');
        }
    }, true);

    document.addEventListener('htmx:afterRequest', function(evt) {
        const bar = document.getElementById('htmx-progress-bar');
        if (bar) {
            setTimeout(function() {
                bar.classList.remove('htmx-progress-loading');
                bar.classList.add('htmx-progress-done');
            }, 300);
        }
    }, true);

    document.addEventListener('htmx:responseError', function(evt) {
        const bar = document.getElementById('htmx-progress-bar');
        if (bar) {
            bar.style.background = 'linear-gradient(90deg, #ef4444, #f87171)';
            bar.classList.remove('htmx-progress-loading');
            bar.classList.add('htmx-progress-done');
        }
    }, true);

    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
JS;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI LA REQUÊTE COURANTE EST UNE REQUÊTE HTMX
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Les requêtes HTMX envoient le header HX-Request: true
     */
    public static function isHtmxRequest(): bool
    {
        return isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LA CIBLE DE LA REQUÊTE HTMX
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne l'ID de l'élément cible (header HX-Target)
     */
    public static function getTarget(): ?string
    {
        return $_SERVER['HTTP_HX_TARGET'] ?? null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER L'ÉLÉMENT DÉCLENCHEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne l'ID de l'élément qui a déclenché la requête (header HX-Trigger)
     */
    public static function getTrigger(): ?string
    {
        return $_SERVER['HTTP_HX_TRIGGER'] ?? null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER L'URL COURANTE CÔTÉ CLIENT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Retourne l'URL de la page qui a fait la requête (header HX-Current-URL)
     */
    public static function getCurrentUrl(): ?string
    {
        return $_SERVER['HTTP_HX_CURRENT_URL'] ?? null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES ATTRIBUTS HTMX POUR UN BOUTON DELETE
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function deleteButton(string $url, string $target, string $confirmMessage = 'Êtes-vous sûr ?'): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        return sprintf(
            'hx-delete="%s" hx-target="%s" hx-swap="outerHTML swap:0.3s" hx-confirm="%s"',
            htmlspecialchars($url),
            htmlspecialchars($target),
            htmlspecialchars($confirmMessage)
        );
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES ATTRIBUTS HTMX POUR UN FORMULAIRE
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function formAttributes(string $url, string $target, string $swap = 'outerHTML'): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        return sprintf(
            'hx-post="%s" hx-target="%s" hx-swap="%s"',
            htmlspecialchars($url),
            htmlspecialchars($target),
            htmlspecialchars($swap)
        );
    }
}
