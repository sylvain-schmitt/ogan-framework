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
     * Ne retourne rien si HTMX est désactivé.
     */
    public static function script(): string
    {
        if (!self::isEnabled()) {
            return '';
        }

        $scriptPath = Config::get('frontend.htmx.script', '/js/htmx.min.js');
        
        return '<script src="' . htmlspecialchars($scriptPath) . '"></script>';
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
