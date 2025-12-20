<?php

namespace Ogan\View;

use Ogan\Exception\ViewException;
use Ogan\View\Helper\FormHelper;
use Ogan\View\Helper\ViewHelper;
use Ogan\View\Helper\SessionHelper;
use Ogan\View\Helper\SecurityHelper;
use Ogan\View\Helper\AppGlobal;

class View implements ViewInterface
{
    private string $basePath;
    private ?string $layoutPath = null;
    private array $blocks = [];
    private array $blockStack = [];
    private ?TemplateCompiler $compiler = null;
    private bool $useCompiler = false;
    
    // Helpers
    private FormHelper $formHelper;
    private ViewHelper $viewHelper;
    private SessionHelper $sessionHelper;
    private SecurityHelper $securityHelper;
    private AppGlobal $appGlobal;

    public function __construct(string $basePath, bool $useCompiler = false, ?string $cacheDir = null)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->useCompiler = $useCompiler;
        
        // Initialiser les helpers
        $this->formHelper = new FormHelper();
        $this->viewHelper = new ViewHelper();
        $this->sessionHelper = new SessionHelper();
        $this->securityHelper = new SecurityHelper();
        $this->appGlobal = new AppGlobal();

        if ($useCompiler) {
            $cacheDir = $cacheDir ?? __DIR__ . '/../../var/cache/templates';
            $autoReload = \Ogan\Config\Config::get('app.env', 'dev') === 'dev';
            $this->compiler = new TemplateCompiler($cacheDir, $autoReload);
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════
     * RÉSOUT LE CHEMIN D'UN TEMPLATE
     * ═══════════════════════════════════════════════════════════════
     */
    private function resolveTemplatePath(string $relativePath): ?string
    {
        $basePath = $this->basePath . '/' . $relativePath;

        if (file_exists($basePath)) {
            return $basePath;
        }

        if (file_exists($basePath . '.ogan')) {
            return $basePath . '.ogan';
        }

        if (file_exists($basePath . '.html.php')) {
            return $basePath . '.html.php';
        }

        return null;
    }

    /**
     * Définit le layout parent
     */
    public function extend(string $layout): void
    {
        $this->layoutPath = $layout;
    }

    /**
     * Inclut un composant avec des variables isolées
     */
    public function component(string $name, array $props = []): string
    {
        $componentPath = $this->resolveTemplatePath('components/' . $name);

        if (!$componentPath) {
            $componentPath = $this->resolveTemplatePath($name);
        }

        if (!$componentPath) {
            return "<!-- Component '$name' not found -->";
        }

        if ($this->useCompiler && $this->compiler) {
            $componentPath = $this->compiler->compile($componentPath);
        }

        extract($props, EXTR_SKIP);
        ob_start();
        include $componentPath;
        return ob_get_clean();
    }

    /**
     * Rend un template
     */
    public function render(string $template, array $params = []): string
    {
        $this->layoutPath = null;
        $this->blocks = [];

        $relPath = ltrim($template, '/');
        $fullPath = $this->resolveTemplatePath($relPath);

        if (!$fullPath) {
            throw new ViewException("Template introuvable : $template");
        }

        if ($this->useCompiler && $this->compiler) {
            $fullPath = $this->compiler->compile($fullPath);
        }

        extract($params, EXTR_SKIP);

        ob_start();
        include $fullPath;
        $content = ob_get_clean();

        if ($this->layoutPath) {
            $layoutFile = $this->resolveTemplatePath(ltrim($this->layoutPath, '/'));
            
            if (!$layoutFile) {
                throw new ViewException("Layout introuvable : $this->layoutPath");
            }

            if ($this->useCompiler && $this->compiler) {
                $layoutFile = $this->compiler->compile($layoutFile);
            }

            ob_start();
            include $layoutFile;
            return ob_get_clean();
        }

        return $content;
    }

    /**
     * Définit le layout à utiliser (Deprecated: use extend)
     */
    public function layout(string $layout): void
    {
        $this->extend($layout);
    }

    /**
     * Début d'un bloc
     */
    public function start(string $name): void
    {
        $this->blockStack[] = $name;
        ob_start();
    }

    /**
     * Fin d'un bloc
     */
    public function end(): void
    {
        $content = ob_get_clean();
        $blockName = array_pop($this->blockStack);
        $this->blocks[$blockName] = $content;
    }

    /**
     * Affiche un bloc
     */
    public function block(string $name, string $default = ''): void
    {
        echo $this->blocks[$name] ?? $default;
    }

    /**
     * Retourne le contenu d'un bloc
     */
    public function section(string $name): string
    {
        return $this->blocks[$name] ?? '';
    }

    /**
     * Active ou désactive le compilateur de templates
     */
    public function setUseCompiler(bool $useCompiler, ?string $cacheDir = null): void
    {
        $this->useCompiler = $useCompiler;

        if ($useCompiler && !$this->compiler) {
            $cacheDir = $cacheDir ?? __DIR__ . '/../../var/cache/templates';
            $this->compiler = new TemplateCompiler($cacheDir, true);
        }
    }

    /**
     * Vide le cache des templates compilés
     */
    public function clearTemplateCache(): void
    {
        if ($this->compiler) {
            $this->compiler->clearCache();
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // DÉLÉGATION AUX HELPERS
    // ═══════════════════════════════════════════════════════════════

    // ViewHelper
    public function setRouter(\Ogan\Router\RouterInterface $router): void
    {
        $this->viewHelper->setRouter($router);
    }

    public function e(mixed $value): string
    {
        return $this->viewHelper->escape($value);
    }

    public function escape(string $value): string
    {
        return $this->viewHelper->escape($value);
    }

    public function asset(string $path): string
    {
        return $this->viewHelper->asset($path);
    }

    public function route(string $name, array $params = [], bool $absolute = false): string
    {
        return $this->viewHelper->route($name, $params, $absolute);
    }

    public function url(string $path = '', bool $absolute = false): string
    {
        return $this->viewHelper->url($path, $absolute);
    }

    public function css(string $path, array $attributes = []): string
    {
        return $this->viewHelper->css($path, $attributes);
    }

    public function js(string $path, array $attributes = []): string
    {
        return $this->viewHelper->js($path, $attributes);
    }

    public function hasRoute(string $name): bool
    {
        return $this->viewHelper->hasRoute($name);
    }

    public function authInstalled(): bool
    {
        return $this->viewHelper->authInstalled();
    }

    /**
     * Génère une URL relative depuis un nom de route (alias Symfony)
     */
    public function path(string $name, array $params = []): string
    {
        return $this->viewHelper->path($name, $params);
    }

    /**
     * Retourne l'objet app global (pour app.user, app.session, etc.)
     */
    public function app(): AppGlobal
    {
        return $this->appGlobal;
    }

    /**
     * Définit la requête dans AppGlobal
     */
    public function setRequest(\Ogan\Http\RequestInterface $request): void
    {
        $this->appGlobal->setRequest($request);
    }

    /**
     * Définit l'utilisateur dans AppGlobal
     */
    public function setUser(mixed $user): void
    {
        $this->appGlobal->setUser($user);
    }

    // SessionHelper
    public function setSession(\Ogan\Session\SessionInterface $session): void
    {
        $this->sessionHelper->setSession($session);
    }

    public function getSession(): ?\Ogan\Session\SessionInterface
    {
        return $this->sessionHelper->getSession();
    }

    public function hasFlash(string $key): bool
    {
        return $this->sessionHelper->hasFlash($key);
    }

    public function getFlash(string $key, ?string $default = null): ?string
    {
        return $this->sessionHelper->getFlash($key, $default);
    }

    public function get(string $key, $default = null)
    {
        return $this->sessionHelper->get($key, $default);
    }

    public function set(string $key, $value): void
    {
        $this->sessionHelper->set($key, $value);
    }

    public function has(string $key): bool
    {
        return $this->sessionHelper->has($key);
    }

    public function getAllFlashes(): array
    {
        return $this->sessionHelper->getAllFlashes();
    }

    /**
     * Alias de getAllFlashes() - Récupère tous les messages flash et les supprime
     */
    public function getFlashes(): array
    {
        return $this->getAllFlashes();
    }

    // SecurityHelper
    public function setCsrfManager(\Ogan\Security\CsrfManager $manager): void
    {
        $this->securityHelper->setCsrfManager($manager);
    }

    public function csrf_token(): string
    {
        return $this->securityHelper->csrfToken();
    }

    public function csrf_input(): string
    {
        return $this->securityHelper->csrfInput();
    }

    // FormHelper
    public function formStart($form, array $options = []): string
    {
        return $this->formHelper->formStart($form, $options);
    }

    public function formEnd($form): string
    {
        return $this->formHelper->formEnd($form);
    }

    public function formRow($field, array $options = []): string
    {
        return $this->formHelper->formRow($field, $options);
    }

    public function formLabel($field, ?string $label = null): string
    {
        return $this->formHelper->formLabel($field, $label);
    }

    public function formWidget($field, array $options = []): string
    {
        return $this->formHelper->formWidget($field, $options);
    }

    public function formErrors($field): string
    {
        return $this->formHelper->formErrors($field);
    }

    public function formRest($form): string
    {
        return $this->formHelper->formRest($form);
    }

    // ═══════════════════════════════════════════════════════════════
    // DEBUG HELPER
    // ═══════════════════════════════════════════════════════════════

    /**
     * Dump une variable dans le template (pour le debug)
     * Usage: {{ dump(variable) }}
     */
    public function dump(mixed ...$vars): string
    {
        $output = '';
        foreach ($vars as $var) {
            $output .= \Ogan\Debug\Dumper::dump($var);
        }
        return $output;
    }
}
