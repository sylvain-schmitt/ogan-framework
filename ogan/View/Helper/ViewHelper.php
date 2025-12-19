<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🛠️ VIEWHELPER - Helpers généraux pour les vues
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Fournit des helpers pour générer des URLs, assets, balises CSS/JS, etc.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\View\Helper;

use Ogan\Router\RouterInterface;
use Ogan\Router\Router;

class ViewHelper
{
    private ?RouterInterface $router = null;

    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }

    /**
     * Génère une URL pour un asset (CSS, JS, Image)
     */
    public function asset(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    /**
     * Génère une URL depuis un nom de route
     */
    public function route(string $name, array $params = [], bool $absolute = false): string
    {
        if (!$this->router) {
            throw new \RuntimeException('Router not set in ViewHelper.');
        }

        $referenceType = $absolute ? Router::ABSOLUTE_URL : Router::ABSOLUTE_PATH;
        return $this->router->generateUrl($name, $params, $referenceType);
    }

    /**
     * Génère une URL absolue ou relative
     */
    public function url(string $path = '', bool $absolute = false): string
    {
        $path = '/' . ltrim($path, '/');

        if (!$absolute) {
            return $path;
        }

        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $path;
    }

    /**
     * Génère une balise <link> pour un fichier CSS
     */
    public function css(string $path, array $attributes = []): string
    {
        $href = $this->asset($path);
        $attrs = '';

        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $attrs . '>';
    }

    /**
     * Génère une balise <script> pour un fichier JS
     */
    public function js(string $path, array $attributes = []): string
    {
        $src = $this->asset($path);
        $attrs = '';

        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $attrs .= ' ' . $key;
            } elseif ($value !== false && $value !== null) {
                $attrs .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"' . $attrs . '></script>';
    }

    /**
     * Échappe une chaîne pour l'affichage (XSS Protection)
     */
    public function escape(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        
        if ($value instanceof \DateTimeInterface) {
            return htmlspecialchars($value->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Vérifie si une route existe
     */
    public function hasRoute(string $name): bool
    {
        if (!$this->router) {
            return false;
        }

        try {
            $this->router->generateUrl($name);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si le composant auth est installé
     */
    public function authInstalled(): bool
    {
        return $this->hasRoute('security_login');
    }

    /**
     * Génère une URL relative depuis un nom de route (alias Symfony)
     * 
     * Usage: path('user_show', ['id' => 1]) → /user/1
     */
    public function path(string $name, array $params = []): string
    {
        return $this->route($name, $params, false);
    }
}
