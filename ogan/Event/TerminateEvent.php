<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📢 TERMINATE EVENT - Déclenché après l'envoi de la réponse
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Event;

use Ogan\Http\Request;
use Ogan\Http\Response;

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
