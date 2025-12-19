<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🚀 HTMX HELPERS - Fonctions globales pour HTMX
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ces fonctions sont disponibles globalement dans les templates Ogan.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

use Ogan\View\Helper\HtmxHelper;

if (!function_exists('htmx_script')) {
    /**
     * Génère la balise script HTMX (si activé)
     * 
     * Usage dans les templates :
     *   {{ htmx_script() }}
     */
    function htmx_script(): string
    {
        return HtmxHelper::script();
    }
}

if (!function_exists('htmx_enabled')) {
    /**
     * Vérifie si HTMX est activé
     * 
     * Usage :
     *   {% if htmx_enabled() %} ... {% endif %}
     */
    function htmx_enabled(): bool
    {
        return HtmxHelper::isEnabled();
    }
}

if (!function_exists('htmx_request')) {
    /**
     * Vérifie si c'est une requête HTMX
     * 
     * Usage :
     *   {% if htmx_request() %} ... {% endif %}
     */
    function htmx_request(): bool
    {
        return HtmxHelper::isHtmxRequest();
    }
}

if (!function_exists('htmx_delete')) {
    /**
     * Génère les attributs pour un bouton de suppression HTMX
     * 
     * Usage :
     *   <button {{ htmx_delete('/user/1', '#user-1') }}>Supprimer</button>
     */
    function htmx_delete(string $url, string $target, string $confirm = 'Êtes-vous sûr ?'): string
    {
        return HtmxHelper::deleteButton($url, $target, $confirm);
    }
}

if (!function_exists('htmx_form')) {
    /**
     * Génère les attributs pour un formulaire HTMX
     * 
     * Usage :
     *   <form {{ htmx_form('/user/store', '#result') }}>
     */
    function htmx_form(string $url, string $target, string $swap = 'outerHTML'): string
    {
        return HtmxHelper::formAttributes($url, $target, $swap);
    }
}

if (!function_exists('authInstalled')) {
    /**
     * Vérifie si le module d'authentification est installé
     * 
     * Usage :
     *   {% if authInstalled() %} ... {% endif %}
     */
    function authInstalled(): bool
    {
        return class_exists(\App\Controller\SecurityController::class);
    }
}

