<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📢 REQUEST EVENT - Déclenché au début de la requête
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Event;

use Ogan\Http\Request;
use Ogan\Http\Response;

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
