<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📧 SMTP TRANSPORT - Envoi via SMTP
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Transport using SMTP protocol directly.
 * Supports authentication and TLS/SSL.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Mail\Transport;

use Ogan\Mail\Message;

class SmtpTransport implements TransportInterface
{
    private string $host;
    private int $port;
    private ?string $username;
    private ?string $password;
    private string $encryption; // 'tls', 'ssl', or ''
    private int $timeout = 30;

    public function __construct(
        string $host = 'localhost',
        int $port = 587,
        ?string $username = null,
        ?string $password = null,
        string $encryption = 'tls'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
    }

    /**
     * Create from DSN string
     * Format: smtp://user:pass@host:port?encryption=tls
     */
    public static function fromDsn(string $dsn): self
    {
        $parsed = parse_url($dsn);
        
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? 587;
        $username = isset($parsed['user']) ? urldecode($parsed['user']) : null;
        $password = isset($parsed['pass']) ? urldecode($parsed['pass']) : null;
        
        // Parse query string for encryption
        $encryption = 'tls';
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
            $encryption = $query['encryption'] ?? 'tls';
        }

        return new self($host, $port, $username, $password, $encryption);
    }

    /**
     * Send email via SMTP
     */
    public function send(Message $message): bool
    {
        $socket = $this->connect();
        
        try {
            // Initial greeting
            $this->read($socket);
            
            // EHLO
            $this->write($socket, "EHLO " . gethostname());
            $this->read($socket);
            
            // STARTTLS if needed
            if ($this->encryption === 'tls') {
                $this->write($socket, "STARTTLS");
                $response = $this->read($socket);
                
                if (strpos($response, '220') === 0) {
                    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    
                    // Re-send EHLO after TLS
                    $this->write($socket, "EHLO " . gethostname());
                    $this->read($socket);
                }
            }
            
            // Authentication
            if ($this->username && $this->password) {
                $this->write($socket, "AUTH LOGIN");
                $this->read($socket);
                
                $this->write($socket, base64_encode($this->username));
                $this->read($socket);
                
                $this->write($socket, base64_encode($this->password));
                $response = $this->read($socket);
                
                if (strpos($response, '235') !== 0) {
                    throw new \Exception('SMTP Authentication failed: ' . $response);
                }
            }
            
            // MAIL FROM
            $from = $message->getFrom() ?: 'noreply@' . gethostname();
            $this->write($socket, "MAIL FROM:<{$from}>");
            $this->read($socket);
            
            // RCPT TO
            $this->write($socket, "RCPT TO:<{$message->getTo()}>");
            $this->read($socket);
            
            // CC recipients
            foreach ($message->getCc() as $cc) {
                $this->write($socket, "RCPT TO:<{$cc}>");
                $this->read($socket);
            }
            
            // BCC recipients
            foreach ($message->getBcc() as $bcc) {
                $this->write($socket, "RCPT TO:<{$bcc}>");
                $this->read($socket);
            }
            
            // DATA
            $this->write($socket, "DATA");
            $response = $this->read($socket);
            
            if (strpos($response, '354') !== 0) {
                throw new \Exception('SMTP server not ready for data: ' . $response);
            }
            
            // Headers and body
            $emailData = $this->buildEmailData($message);
            $this->write($socket, $emailData);
            $this->write($socket, ".");
            $response = $this->read($socket);
            
            if (strpos($response, '250') !== 0) {
                throw new \Exception('SMTP send failed: ' . $response);
            }
            
            // QUIT
            $this->write($socket, "QUIT");
            $this->read($socket);
            
            fclose($socket);
            return true;
            
        } catch (\Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            throw $e;
        }
    }

    /**
     * Connect to SMTP server
     */
    private function connect()
    {
        $protocol = $this->encryption === 'ssl' ? 'ssl://' : '';
        $socket = @fsockopen(
            $protocol . $this->host,
            $this->port,
            $errno,
            $errstr,
            $this->timeout
        );

        if (!$socket) {
            throw new \Exception("Could not connect to SMTP server: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $this->timeout);
        
        return $socket;
    }

    /**
     * Write to socket
     */
    private function write($socket, string $data): void
    {
        fwrite($socket, $data . "\r\n");
    }

    /**
     * Read from socket
     */
    private function read($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Check if this is the last line (no dash after status code)
            if (isset($line[3]) && $line[3] !== '-') {
                break;
            }
        }
        return $response;
    }

    /**
     * Build email data (headers + body)
     */
    private function buildEmailData(Message $message): string
    {
        $data = [];
        
        // Headers
        $from = $message->getFromName() 
            ? $message->getFromName() . ' <' . $message->getFrom() . '>'
            : $message->getFrom();
        
        $data[] = "From: " . $from;
        $data[] = "To: " . $message->getTo();
        $data[] = "Subject: " . $this->encodeHeader($message->getSubject());
        $data[] = "Date: " . date('r');
        $data[] = "MIME-Version: 1.0";
        
        if ($message->getReplyTo()) {
            $data[] = "Reply-To: " . $message->getReplyTo();
        }
        
        foreach ($message->getCc() as $cc) {
            $data[] = "Cc: " . $cc;
        }
        
        if ($message->isHtml()) {
            $data[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $data[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        $data[] = "Content-Transfer-Encoding: 8bit";
        $data[] = ""; // Empty line between headers and body
        $data[] = $message->getBody();
        
        return implode("\r\n", $data);
    }

    /**
     * Encode header for UTF-8 support
     */
    private function encodeHeader(string $header): string
    {
        if (preg_match('/[^\x20-\x7E]/', $header)) {
            return '=?UTF-8?B?' . base64_encode($header) . '?=';
        }
        return $header;
    }
}
