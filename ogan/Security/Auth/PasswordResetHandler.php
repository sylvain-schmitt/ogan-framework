<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔑 PASSWORDRESETHANDLER - Gestion du reset de mot de passe
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Gère le processus de réinitialisation de mot de passe :
 * 1. Génération de token temporaire
 * 2. Envoi d'email avec lien de reset
 * 3. Validation du token et mise à jour du mot de passe
 * 
 * TABLE REQUISE :
 * ---------------
 * CREATE TABLE password_resets (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     email VARCHAR(255) NOT NULL,
 *     token VARCHAR(255) NOT NULL,
 *     expires_at DATETIME NOT NULL,
 *     created_at DATETIME DEFAULT CURRENT_TIMESTAMP
 * );
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Security\Auth;

use PDO;
use Ogan\Mail\MailerInterface;
use Ogan\Mail\Email;
use Ogan\Security\PasswordHasher;

class PasswordResetHandler
{
    private const TOKEN_LENGTH = 64;
    private const EXPIRY_HOURS = 1;

    public function __construct(
        private PDO $pdo,
        private UserProviderInterface $userProvider,
        private PasswordHasher $passwordHasher,
        private ?MailerInterface $mailer = null,
        private string $fromEmail = 'no-reply@example.com',
        private string $fromName = 'My App',
        private string $resetUrl = '/reset-password'
    ) {}

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UNE DEMANDE DE RESET
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $email Email de l'utilisateur
     * @return string|null Token généré ou null si email non trouvé
     */
    public function createResetRequest(string $email): ?string
    {
        // Vérifier que l'utilisateur existe
        $user = $this->userProvider->loadUserByIdentifier($email);
        
        if (!$user) {
            // Ne pas révéler si l'email existe ou non (sécurité)
            return null;
        }

        // Supprimer les anciennes demandes pour cet email
        $this->clearResetRequests($email);

        // Générer un token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
        $hashedToken = hash('sha256', $token);
        
        $expiresAt = new \DateTime("+" . self::EXPIRY_HOURS . " hours");

        // Stocker le token hashé en BDD
        $stmt = $this->pdo->prepare(
            "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $email,
            $hashedToken,
            $expiresAt->format('Y-m-d H:i:s')
        ]);

        return $token;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ENVOYER L'EMAIL DE RESET
     * ═══════════════════════════════════════════════════════════════════
     */
    public function sendResetEmail(string $email, string $token, ?string $baseUrl = null): bool
    {
        if (!$this->mailer) {
            throw new \RuntimeException("Mailer non configuré");
        }

        $baseUrl = $baseUrl ?? $this->getBaseUrl();
        $resetLink = $baseUrl . $this->resetUrl . '/' . $token;

        $mailEmail = (new Email())
            ->from($this->fromEmail, $this->fromName)
            ->to($email)
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->getEmailHtml($resetLink))
            ->text($this->getEmailText($resetLink));

        return $this->mailer->send($mailEmail);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VALIDER UN TOKEN ET RETOURNER L'EMAIL
     * ═══════════════════════════════════════════════════════════════════
     */
    public function validateToken(string $token): ?string
    {
        $hashedToken = hash('sha256', $token);

        $stmt = $this->pdo->prepare(
            "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()"
        );
        $stmt->execute([$hashedToken]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['email'] : null;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉINITIALISER LE MOT DE PASSE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $token Token de reset
     * @param string $newPassword Nouveau mot de passe en clair
     * @return bool True si le reset a réussi
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $email = $this->validateToken($token);
        
        if (!$email) {
            return false;
        }

        // Hasher le nouveau mot de passe
        $hashedPassword = $this->passwordHasher->hash($newPassword);

        // Mettre à jour le mot de passe
        $stmt = $this->pdo->prepare(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?"
        );
        $stmt->execute([$hashedPassword, $email]);

        if ($stmt->rowCount() === 0) {
            return false;
        }

        // Supprimer le token utilisé
        $this->clearResetRequests($email);

        return true;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HELPERS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function clearResetRequests(string $email): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
    }

    public function purgeExpiredTokens(): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$protocol}://{$host}";
    }

    private function getEmailHtml(string $resetLink): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4F46E5;">Réinitialisation de mot de passe</h1>
        
        <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
        
        <p>Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe :</p>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="{$resetLink}" 
               style="background-color: #4F46E5; color: white; padding: 12px 24px; 
                      text-decoration: none; border-radius: 6px; display: inline-block;">
                Réinitialiser mon mot de passe
            </a>
        </p>
        
        <p style="color: #666; font-size: 14px;">
            Ce lien expire dans 1 heure.
        </p>
        
        <p style="color: #666; font-size: 14px;">
            Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
        </p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        
        <p style="color: #999; font-size: 12px;">
            Lien direct : <a href="{$resetLink}">{$resetLink}</a>
        </p>
    </div>
</body>
</html>
HTML;
    }

    private function getEmailText(string $resetLink): string
    {
        return <<<TEXT
Réinitialisation de mot de passe
================================

Vous avez demandé la réinitialisation de votre mot de passe.

Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe :

{$resetLink}

Ce lien expire dans 1 heure.

Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
TEXT;
    }

    /**
     * Configurer les paramètres d'email
     */
    public function setFromEmail(string $email, string $name = ''): self
    {
        $this->fromEmail = $email;
        if ($name) {
            $this->fromName = $name;
        }
        return $this;
    }

    public function setResetUrl(string $url): self
    {
        $this->resetUrl = $url;
        return $this;
    }
}
