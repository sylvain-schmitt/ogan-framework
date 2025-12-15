<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📧 TRANSPORT INTERFACE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Interface for email transport implementations.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Mail\Transport;

use Ogan\Mail\Message;

interface TransportInterface
{
    /**
     * Send an email message
     * 
     * @param Message $message The message to send
     * @return bool Whether the email was sent successfully
     * @throws \Exception If sending fails
     */
    public function send(Message $message): bool;
}
