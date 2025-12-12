<?php

namespace Ogan\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Route
{
    public string $path;
    public array $methods;
    public ?string $name;

    /**
     * @param string $path Exemple : "/users/{id}"
     * @param array $methods Méthodes HTTP : ['GET', 'POST']
     * @param string|null $name Nom unique optionnel, ex: "app_login"
     */
    public function __construct(string $path, array $methods = ['GET'], ?string $name = null)
    {
        $this->path = $path;
        $this->methods = $methods;
        $this->name = $name;
    }
}
