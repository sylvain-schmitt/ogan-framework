<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📧 NATIVE TRANSPORT - Envoi via mail() PHP
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Transport using PHP's native mail() function.
 * Simple but depends on server configuration.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Mail\Transport;

use Ogan\Mail\Message;

class NativeTransport implements TransportInterface
{
    /**
     * Send email using PHP's mail() function
     */
    public function send(Message $message): bool
    {
        $to = $message->getTo();
        $subject = $message->getSubject();
        $body = $message->getBody();

        // Build headers
        $headers = [];
        
        if ($message->getFrom()) {
            $from = $message->getFromName() 
                ? $message->getFromName() . ' <' . $message->getFrom() . '>'
                : $message->getFrom();
            $headers[] = 'From: ' . $from;
        }

        if ($message->getReplyTo()) {
            $headers[] = 'Reply-To: ' . $message->getReplyTo();
        }

        foreach ($message->getCc() as $cc) {
            $headers[] = 'Cc: ' . $cc;
        }

        foreach ($message->getBcc() as $bcc) {
            $headers[] = 'Bcc: ' . $bcc;
        }

        if ($message->isHtml()) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }

        $headerString = implode("\r\n", $headers);

        return mail($to, $subject, $body, $headerString);
    }
}
