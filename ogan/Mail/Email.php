<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📧 EMAIL - Représentation d'un email
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Classe représentant un email avec tous ses attributs.
 * Utilise le pattern fluent pour une construction chainée.
 * 
 * USAGE :
 * -------
 * $email = (new Email())
 *     ->from('sender@example.com', 'Sender Name')
 *     ->to('recipient@example.com')
 *     ->subject('Hello!')
 *     ->text('Plain text content')
 *     ->html('<h1>Hello!</h1>');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Mail;

class Email
{
    private string $from = '';
    private string $fromName = '';
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private array $replyTo = [];
    private string $subject = '';
    private string $text = '';
    private string $html = '';
    private array $attachments = [];

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXPÉDITEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function from(string $email, string $name = ''): self
    {
        $this->from = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DESTINATAIRE(S)
     * ═══════════════════════════════════════════════════════════════════
     */
    public function to(string $email, string $name = ''): self
    {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function addTo(string $email, string $name = ''): self
    {
        return $this->to($email, $name);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * COPIE CARBONE
     * ═══════════════════════════════════════════════════════════════════
     */
    public function cc(string $email, string $name = ''): self
    {
        $this->cc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function bcc(string $email, string $name = ''): self
    {
        $this->bcc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * REPLY-TO
     * ═══════════════════════════════════════════════════════════════════
     */
    public function replyTo(string $email, string $name = ''): self
    {
        $this->replyTo[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SUJET
     * ═══════════════════════════════════════════════════════════════════
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONTENU
     * ═══════════════════════════════════════════════════════════════════
     */
    public function text(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function html(string $html): self
    {
        $this->html = $html;
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * PIÈCES JOINTES
     * ═══════════════════════════════════════════════════════════════════
     */
    public function attach(string $path, ?string $name = null, ?string $mimeType = null): self
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?? basename($path),
            'mimeType' => $mimeType ?? mime_content_type($path) ?: 'application/octet-stream'
        ];
        return $this;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GETTERS
     * ═══════════════════════════════════════════════════════════════════
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function getReplyTo(): array
    {
        return $this->replyTo;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HELPERS
     * ═══════════════════════════════════════════════════════════════════
     */
    public function hasHtml(): bool
    {
        return !empty($this->html);
    }

    public function hasText(): bool
    {
        return !empty($this->text);
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * Formate une adresse email avec le nom
     */
    public function formatAddress(string $email, string $name = ''): string
    {
        if (empty($name)) {
            return $email;
        }
        return sprintf('"%s" <%s>', str_replace('"', '\\"', $name), $email);
    }

    /**
     * Formate le From pour les headers
     */
    public function getFormattedFrom(): string
    {
        return $this->formatAddress($this->from, $this->fromName);
    }
}
