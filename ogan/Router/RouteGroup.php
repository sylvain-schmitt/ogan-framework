<?php

namespace Ogan\Router;

class RouteGroup
{
    /**
     * Préfixe d'URL du groupe (ex: /admin)
     */
    private string $prefix;

    /**
     * Middlewares à appliquer à toutes les routes du groupe
     */
    private array $middlewares = [];

    /**
     * Namespace des contrôleurs (optionnel)
     */
    private ?string $namespace = null;

    /**
     * Domaine ou sous-domaine (ex: admin.example.com)
     */
    private ?string $domain = null;

    public function __construct(string $prefix = '', array $middlewares = [], ?string $namespace = null, ?string $domain = null)
    {
        $this->prefix = $prefix;
        $this->middlewares = $middlewares;
        $this->namespace = $namespace;
        $this->domain = $domain;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Fusionne ce groupe avec un groupe parent
     */
    public function mergeWith(RouteGroup $parent): self
    {
        // On combine les préfixes (ex: /api + /v1 = /api/v1)
        $newPrefix = rtrim($parent->getPrefix(), '/') . '/' . ltrim($this->prefix, '/');
        
        // On nettoie le slash final s'il existe (pour le cas racine)
        if ($newPrefix !== '/') {
            $newPrefix = rtrim($newPrefix, '/');
        }

        // On fusionne les middlewares (parent d'abord)
        $newMiddlewares = array_merge($parent->getMiddlewares(), $this->middlewares);

        // On gère le namespace
        $newNamespace = $this->namespace;
        if ($parent->getNamespace()) {
            if ($this->namespace) {
                $newNamespace = rtrim($parent->getNamespace(), '\\') . '\\' . ltrim($this->namespace, '\\');
            } else {
                $newNamespace = $parent->getNamespace();
            }
        }

        // On gère le domaine (le plus spécifique l'emporte, ici le groupe enfant)
        $newDomain = $this->domain ?? $parent->getDomain();

        return new self($newPrefix, $newMiddlewares, $newNamespace, $newDomain);
    }
}
