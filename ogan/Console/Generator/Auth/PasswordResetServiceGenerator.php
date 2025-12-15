<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔑 PASSWORD RESET SERVICE GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class PasswordResetServiceGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $path = $projectRoot . '/src/Security/PasswordResetService.php';
        $this->ensureDirectory(dirname($path));

        if (!$this->fileExists($path) || $force) {
            $this->writeFile($path, $this->getTemplate());
            $generated[] = 'src/Security/PasswordResetService.php';
        } else {
            $skipped[] = 'src/Security/PasswordResetService.php (existe déjà)';
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getTemplate(): string
    {
        return <<<'PHP'
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔑 PASSWORD RESET SERVICE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Gère la réinitialisation des mots de passe (via email ou direct).
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Security;

use App\Model\User;
use Ogan\Config\Config;
use Ogan\Mail\Mailer;
use Ogan\Mail\Email;
use Ogan\Security\PasswordHasher;

class PasswordResetService
{
    private PasswordHasher $hasher;

    public function __construct()
    {
        $this->hasher = new PasswordHasher();
    }

    /**
     * Envoie un email de réinitialisation
     */
    public function sendResetEmail(User $user): bool
    {
        $token = bin2hex(random_bytes(32));
        $user->setPasswordResetToken($token);
        $user->setPasswordResetExpiresAt(date('Y-m-d H:i:s', strtotime('+1 hour')));
        $user->save();

        try {
            $mailer = new Mailer(Config::get('mail.dsn', 'smtp://localhost:1025'));
            
            $resetUrl = $this->getBaseUrl() . '/reset-password/' . $token;
            
            // S'assurer que mail.from est une string
            $fromEmail = Config::get('mail.from', 'noreply@example.com');
            if (is_array($fromEmail)) {
                $fromEmail = $fromEmail[0] ?? 'noreply@example.com';
            }
            $fromName = Config::get('mail.from_name', '');
            if (is_array($fromName)) {
                $fromName = $fromName[0] ?? '';
            }
            
            $email = (new Email())
                ->from((string) $fromEmail, (string) $fromName)
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->html($this->getEmailTemplate($user, $resetUrl));
            
            $mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Valide un token de réinitialisation
     */
    public function validateToken(string $token): ?User
    {
        $result = User::where('password_reset_token', '=', $token)->first();
        
        if (!$result) {
            return null;
        }

        // Récupérer l'utilisateur via find() pour une hydratation correcte
        $userId = is_array($result) ? ($result['id'] ?? null) : ($result->id ?? null);
        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        // Vérifier l'expiration
        if ($user->getPasswordResetExpiresAt() < date('Y-m-d H:i:s')) {
            return null;
        }
        
        return $user;
    }

    /**
     * Réinitialise le mot de passe avec un token
     */
    public function resetPassword(User $user, string $newPassword): bool
    {
        $user->setPassword($this->hasher->hash($newPassword));
        $user->setPasswordResetToken(null);
        $user->setPasswordResetExpiresAt(null);
        return $user->save();
    }

    /**
     * Réinitialisation directe (sans email)
     */
    public function resetPasswordDirect(string $email, string $newPassword): bool
    {
        $user = User::findByEmail($email);
        
        if (!$user) {
            return false;
        }

        $user->setPassword($this->hasher->hash($newPassword));
        return $user->save();
    }

    /**
     * Récupère l'URL de base
     */
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Template de l'email de réinitialisation
     */
    private function getEmailTemplate(User $user, string $url): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 24px; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .button { display: inline-block; background: #ef4444; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
        .warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Réinitialisation de mot de passe</h1>
    </div>
    <div class="content">
        <p>Bonjour {$user->getName()},</p>
        <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
        <p style="text-align: center;">
            <a href="{$url}" class="button">Réinitialiser mon mot de passe</a>
        </p>
        <p>Ou copiez ce lien dans votre navigateur :</p>
        <p style="word-break: break-all; color: #ef4444;">{$url}</p>
        <div class="warning">
            Important : Si vous n'avez pas demandé cette réinitialisation, ignorez cet email. Votre mot de passe restera inchangé.
        </div>
    </div>
    <div class="footer">
        <p>Ce lien expire dans 1 heure.</p>
    </div>
</body>
</html>
HTML;
    }
}
PHP;
    }
}
