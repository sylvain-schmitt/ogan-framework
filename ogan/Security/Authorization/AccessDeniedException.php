<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🚫 ACCESS DENIED EXCEPTION
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Authorization;

class AccessDeniedException extends \Exception
{
    public function __construct(string $message = 'Access Denied.', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
