<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎯 HTMX CONTEXT - Registre statique des informations HTMX
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Cette classe stocke les informations de la requête HTMX de manière
 * accessible globalement dans l'application.
 * 
 * UTILISATION :
 * -------------
 * use Ogan\Middleware\HtmxContext;
 * 
 * if (HtmxContext::isHtmxRequest()) {
 *     // C'est une requête HTMX
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Middleware;

class HtmxContext
{
    private static bool $initialized = false;
    private static bool $isHtmx = false;
    private static ?string $target = null;
    private static ?string $trigger = null;
    private static ?string $triggerName = null;
    private static ?string $currentUrl = null;
    private static bool $boosted = false;

    /**
     * Initialiser le contexte depuis les headers HTTP
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$isHtmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
        self::$target = $_SERVER['HTTP_HX_TARGET'] ?? null;
        self::$trigger = $_SERVER['HTTP_HX_TRIGGER'] ?? null;
        self::$triggerName = $_SERVER['HTTP_HX_TRIGGER_NAME'] ?? null;
        self::$currentUrl = $_SERVER['HTTP_HX_CURRENT_URL'] ?? null;
        self::$boosted = isset($_SERVER['HTTP_HX_BOOSTED']);
        self::$initialized = true;
    }

    public static function isHtmxRequest(): bool
    {
        self::init();
        return self::$isHtmx;
    }

    public static function getTarget(): ?string
    {
        self::init();
        return self::$target;
    }

    public static function getTrigger(): ?string
    {
        self::init();
        return self::$trigger;
    }

    public static function getTriggerName(): ?string
    {
        self::init();
        return self::$triggerName;
    }

    public static function getCurrentUrl(): ?string
    {
        self::init();
        return self::$currentUrl;
    }

    public static function isBoosted(): bool
    {
        self::init();
        return self::$boosted;
    }

    /**
     * Reset pour les tests
     */
    public static function reset(): void
    {
        self::$initialized = false;
        self::$isHtmx = false;
        self::$target = null;
        self::$trigger = null;
        self::$triggerName = null;
        self::$currentUrl = null;
        self::$boosted = false;
    }
}
