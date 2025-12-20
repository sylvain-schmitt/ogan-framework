<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📢 EXCEPTION EVENT - Déclenché lors d'une exception
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Event;

use Ogan\Http\Request;
use Ogan\Http\Response;

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
