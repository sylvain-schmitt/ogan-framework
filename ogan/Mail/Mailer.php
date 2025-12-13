<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📧 MAILER - Service d'envoi d'emails
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Envoie des emails via SMTP en utilisant un DSN configurable.
 * Compatible avec Mailhog (dev) et serveurs SMTP (prod).
 * 
 * CONFIGURATION (.env) :
 * ----------------------
 * # Dev (Mailhog)
 * MAILER_DSN=smtp://localhost:1025
 * 
 * # Prod avec authentification
 * MAILER_DSN=smtp://user:password@smtp.example.com:587
 * 
 * # Gmail (avec mot de passe d'application)
 * MAILER_DSN=smtp://user@gmail.com:app_password@smtp.gmail.com:587
 * 
 * USAGE :
 * -------
 * $mailer = new Mailer('smtp://localhost:1025');
 * 
 * $email = (new Email())
 *     ->from('no-reply@example.com', 'My App')
 *     ->to('user@example.com')
 *     ->subject('Welcome!')
 *     ->html('<h1>Hello!</h1>');
 * 
 * $mailer->send($email);
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Mail;

class Mailer implements MailerInterface
{
    private string $host;
    private int $port;
    private ?string $username = null;
    private ?string $password = null;
    private string $encryption = ''; // '', 'tls', 'ssl'
    private int $timeout = 30;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $dsn DSN de connexion (smtp://user:pass@host:port)
     */
    public function __construct(string $dsn)
    {
        $this->parseDsn($dsn);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * PARSER LE DSN
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Formats supportés :
     * - smtp://localhost:1025
     * - smtp://user:pass@host:port
     * - smtps://user:pass@host:465 (SSL)
     */
    private function parseDsn(string $dsn): void
    {
        // Valider le schéma
        if (!preg_match('/^(smtp|smtps):\/\//', $dsn)) {
            throw new \InvalidArgumentException("DSN invalide. Format attendu: smtp://host:port ou smtp://user:pass@host:port");
        }

        // Parser l'URL
        $parts = parse_url($dsn);
        
        if ($parts === false || !isset($parts['host'])) {
            throw new \InvalidArgumentException("DSN invalide: impossible de parser l'URL");
        }

        // Schéma (smtp ou smtps)
        $scheme = $parts['scheme'] ?? 'smtp';
        $this->encryption = ($scheme === 'smtps') ? 'ssl' : '';

        // Host
        $this->host = $parts['host'];

        // Port (défaut: 25 pour smtp, 465 pour smtps, 587 pour tls)
        if (isset($parts['port'])) {
            $this->port = (int)$parts['port'];
        } else {
            $this->port = ($scheme === 'smtps') ? 465 : 25;
        }

        // Authentification
        if (isset($parts['user'])) {
            $this->username = urldecode($parts['user']);
        }
        if (isset($parts['pass'])) {
            $this->password = urldecode($parts['pass']);
        }

        // Query params (encryption=tls, timeout=30)
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['encryption'])) {
                $this->encryption = $query['encryption'];
            }
            if (isset($query['timeout'])) {
                $this->timeout = (int)$query['timeout'];
            }
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ENVOYER UN EMAIL
     * ═══════════════════════════════════════════════════════════════════
     */
    public function send(Email $email): bool
    {
        // Valider l'email
        $this->validateEmail($email);

        // Ouvrir la connexion SMTP
        $socket = $this->connect();

        try {
            // Handshake SMTP
            $this->readResponse($socket); // Greeting
            $this->sendCommand($socket, "EHLO " . gethostname());

            // STARTTLS si nécessaire
            if ($this->encryption === 'tls') {
                $this->sendCommand($socket, "STARTTLS");
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException("Impossible d'activer TLS");
                }
                $this->sendCommand($socket, "EHLO " . gethostname());
            }

            // Authentification si nécessaire
            if ($this->username && $this->password) {
                $this->sendCommand($socket, "AUTH LOGIN");
                $this->sendCommand($socket, base64_encode($this->username), '334');
                $this->sendCommand($socket, base64_encode($this->password), '235');
            }

            // MAIL FROM
            $this->sendCommand($socket, "MAIL FROM:<{$email->getFrom()}>");

            // RCPT TO (tous les destinataires)
            foreach ($email->getTo() as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<{$recipient['email']}>");
            }
            foreach ($email->getCc() as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<{$recipient['email']}>");
            }
            foreach ($email->getBcc() as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<{$recipient['email']}>");
            }

            // DATA
            $this->sendCommand($socket, "DATA", '354');

            // Headers et contenu
            $message = $this->buildMessage($email);
            fwrite($socket, $message . "\r\n.\r\n");
            $this->readResponse($socket, '250');

            // QUIT
            $this->sendCommand($socket, "QUIT", '221');

            return true;
        } finally {
            fclose($socket);
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * OUVRIR LA CONNEXION SMTP
     * ═══════════════════════════════════════════════════════════════════
     */
    private function connect()
    {
        $protocol = ($this->encryption === 'ssl') ? 'ssl' : 'tcp';
        $address = "{$protocol}://{$this->host}:{$this->port}";

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = stream_socket_client(
            $address,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new \RuntimeException("Connexion SMTP échouée: [{$errno}] {$errstr}");
        }

        stream_set_timeout($socket, $this->timeout);

        return $socket;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ENVOYER UNE COMMANDE SMTP
     * ═══════════════════════════════════════════════════════════════════
     */
    private function sendCommand($socket, string $command, string $expectedCode = '250'): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->readResponse($socket, $expectedCode);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * LIRE LA RÉPONSE SMTP
     * ═══════════════════════════════════════════════════════════════════
     */
    private function readResponse($socket, ?string $expectedCode = null): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Fin de réponse multi-ligne
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($expectedCode !== null) {
            $code = substr($response, 0, 3);
            if ($code !== $expectedCode) {
                throw new \RuntimeException("Erreur SMTP: attendu {$expectedCode}, reçu: {$response}");
            }
        }

        return $response;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUIRE LE MESSAGE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function buildMessage(Email $email): string
    {
        $boundary = md5(uniqid((string)time()));
        $headers = [];

        // Headers de base
        $headers[] = "From: " . $email->getFormattedFrom();
        $headers[] = "To: " . $this->formatRecipients($email->getTo());
        
        if (!empty($email->getCc())) {
            $headers[] = "Cc: " . $this->formatRecipients($email->getCc());
        }
        
        foreach ($email->getReplyTo() as $replyTo) {
            $headers[] = "Reply-To: " . $email->formatAddress($replyTo['email'], $replyTo['name']);
        }
        
        $headers[] = "Subject: " . $this->encodeHeader($email->getSubject());
        $headers[] = "Date: " . date('r');
        $headers[] = "MIME-Version: 1.0";

        // Contenu
        $body = '';
        
        if ($email->hasHtml() && $email->hasText()) {
            // Multipart alternative
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $body .= $email->getText() . "\r\n\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $body .= $email->getHtml() . "\r\n\r\n";
            $body .= "--{$boundary}--";
        } elseif ($email->hasHtml()) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $body = $email->getHtml();
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
            $body = $email->getText();
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HELPERS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function validateEmail(Email $email): void
    {
        if (empty($email->getFrom())) {
            throw new \InvalidArgumentException("L'email doit avoir un expéditeur (from)");
        }
        if (empty($email->getTo())) {
            throw new \InvalidArgumentException("L'email doit avoir au moins un destinataire (to)");
        }
        if (empty($email->getSubject())) {
            throw new \InvalidArgumentException("L'email doit avoir un sujet");
        }
        if (!$email->hasText() && !$email->hasHtml()) {
            throw new \InvalidArgumentException("L'email doit avoir un contenu (text ou html)");
        }
    }

    private function formatRecipients(array $recipients): string
    {
        $formatted = [];
        foreach ($recipients as $r) {
            $formatted[] = (new Email())->formatAddress($r['email'], $r['name']);
        }
        return implode(', ', $formatted);
    }

    private function encodeHeader(string $value): string
    {
        if (!preg_match('/[^\x20-\x7E]/', $value)) {
            return $value;
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GETTERS (pour debug)
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
