<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📧 MAILERINTERFACE - Interface du service d'envoi d'emails
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Mail;

interface MailerInterface
{
    /**
     * Envoie un email
     * 
     * @param Email $email L'email à envoyer
     * @return bool True si l'envoi a réussi
     * @throws \RuntimeException Si l'envoi échoue
     */
    public function send(Email $email): bool;
}
