<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📢 CONTROLLER EVENT - Déclenché avant l'exécution du controller
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Event;

use Ogan\Http\Request;

class ControllerEvent extends Event
{
    public function __construct(
        private Request $request,
        private mixed $controller,
        private string $method
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getController(): mixed
    {
        return $this->controller;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setController(mixed $controller): void
    {
        $this->controller = $controller;
    }
}
