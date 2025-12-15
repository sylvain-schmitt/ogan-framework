<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📧 MESSAGE - Représentation d'un Email
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Objet représentant un email à envoyer.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Mail;

class Message
{
    private string $to = '';
    private string $from = '';
    private string $fromName = '';
    private string $replyTo = '';
    private string $subject = '';
    private string $body = '';
    private string $altBody = '';
    private bool $isHtml = true;
    private array $attachments = [];
    private array $cc = [];
    private array $bcc = [];

    /**
     * Set recipient
     */
    public function to(string $email): self
    {
        $this->to = $email;
        return $this;
    }

    /**
     * Set sender
     */
    public function from(string $email, string $name = ''): self
    {
        $this->from = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * Set reply-to address
     */
    public function replyTo(string $email): self
    {
        $this->replyTo = $email;
        return $this;
    }

    /**
     * Set subject
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set HTML body
     */
    public function html(string $body): self
    {
        $this->body = $body;
        $this->isHtml = true;
        return $this;
    }

    /**
     * Set plain text body
     */
    public function text(string $body): self
    {
        $this->body = $body;
        $this->isHtml = false;
        return $this;
    }

    /**
     * Set alternative text body (for HTML emails)
     */
    public function altBody(string $text): self
    {
        $this->altBody = $text;
        return $this;
    }

    /**
     * Add attachment
     */
    public function attach(string $path, ?string $name = null): self
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?? basename($path)
        ];
        return $this;
    }

    /**
     * Add CC recipient
     */
    public function cc(string $email): self
    {
        $this->cc[] = $email;
        return $this;
    }

    /**
     * Add BCC recipient
     */
    public function bcc(string $email): self
    {
        $this->bcc[] = $email;
        return $this;
    }

    // Getters
    public function getTo(): string { return $this->to; }
    public function getFrom(): string { return $this->from; }
    public function getFromName(): string { return $this->fromName; }
    public function getReplyTo(): string { return $this->replyTo; }
    public function getSubject(): string { return $this->subject; }
    public function getBody(): string { return $this->body; }
    public function getAltBody(): string { return $this->altBody; }
    public function isHtml(): bool { return $this->isHtml; }
    public function getAttachments(): array { return $this->attachments; }
    public function getCc(): array { return $this->cc; }
    public function getBcc(): array { return $this->bcc; }
}
