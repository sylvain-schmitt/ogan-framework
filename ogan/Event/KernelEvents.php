<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📢 KERNEL EVENTS - Événements du cycle de vie HTTP
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Event;

use Ogan\Http\Request;
use Ogan\Http\Response;

// ─────────────────────────────────────────────────────────────────────────
// REQUEST EVENT - Déclenché au début de la requête
// ─────────────────────────────────────────────────────────────────────────

class RequestEvent extends Event
{
    private ?Response $response = null;

    public function __construct(
        private Request $request
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Définit une réponse, ce qui court-circuite le controller
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
        $this->stopPropagation();
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}

// ─────────────────────────────────────────────────────────────────────────
// RESPONSE EVENT - Déclenché après le controller
// ─────────────────────────────────────────────────────────────────────────

class ResponseEvent extends Event
{
    public function __construct(
        private Request $request,
        private Response $response
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}

// ─────────────────────────────────────────────────────────────────────────
// EXCEPTION EVENT - Déclenché lors d'une exception
// ─────────────────────────────────────────────────────────────────────────

class ExceptionEvent extends Event
{
    private ?Response $response = null;

    public function __construct(
        private Request $request,
        private \Throwable $exception
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }

    public function setException(\Throwable $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * Définit une réponse de gestion de l'exception
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}

// ─────────────────────────────────────────────────────────────────────────
// CONTROLLER EVENT - Déclenché avant l'exécution du controller
// ─────────────────────────────────────────────────────────────────────────

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

// ─────────────────────────────────────────────────────────────────────────
// TERMINATE EVENT - Déclenché après l'envoi de la réponse
// ─────────────────────────────────────────────────────────────────────────

class TerminateEvent extends Event
{
    public function __construct(
        private Request $request,
        private Response $response
    ) {}

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
