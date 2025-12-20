<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📢 RESPONSE EVENT - Déclenché après le controller
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Event;

use Ogan\Http\Request;
use Ogan\Http\Response;

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
