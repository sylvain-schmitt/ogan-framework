<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ✉️ EMAIL VERIFICATION SERVICE GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class EmailVerificationServiceGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $path = $projectRoot . '/src/Security/EmailVerificationService.php';
        $this->ensureDirectory(dirname($path));

        if (!$this->fileExists($path) || $force) {
            $this->writeFile($path, $this->getTemplate());
            $generated[] = 'src/Security/EmailVerificationService.php';
        } else {
            $skipped[] = 'src/Security/EmailVerificationService.php (existe déjà)';
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getTemplate(): string
    {
        return <<<'PHP'
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ✉️ EMAIL VERIFICATION SERVICE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Gère la vérification d'email des utilisateurs.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Security;

use App\Model\User;
use Ogan\Config\Config;
use Ogan\Mail\Mailer;
use Ogan\Mail\Email;

class EmailVerificationService
{
    /**
     * Envoie un email de vérification
     */
    public function sendVerification(User $user): bool
    {
        $token = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($token);
        $user->save();

        try {
            $mailer = new Mailer(Config::get('mail.dsn', 'smtp://localhost:1025'));
            
            $verifyUrl = $this->getBaseUrl() . '/verify-email/' . $token;
            
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
                ->subject('Vérifiez votre adresse email')
                ->html($this->getEmailTemplate($user, $verifyUrl));
            
            $mailer->send($email);
            return true;
        } catch (\Exception $e) {
            // Log error but don't break registration
            return false;
        }
    }

    /**
     * Vérifie un token de vérification
     */
    public function verify(string $token): ?User
    {
        $result = User::where('email_verification_token', '=', $token)->first();
        
        if (!$result) {
            return null;
        }

        // Récupérer l'utilisateur via find() pour une hydratation correcte
        $userId = is_array($result) ? ($result['id'] ?? null) : ($result->id ?? null);
        $user = User::find($userId);
        if (!$user) {
            return null;
        }
        
        // Marquer comme vérifié
        $user->setEmailVerifiedAt(date('Y-m-d H:i:s'));
        $user->setEmailVerificationToken(null);
        $user->save();
        
        return $user;
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
     * Template de l'email de vérification
     */
    private function getEmailTemplate(User $user, string $url): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifiez votre email</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 24px; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .button { display: inline-block; background: #4f46e5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Vérification de votre email</h1>
    </div>
    <div class="content">
        <p>Bonjour {$user->getName()},</p>
        <p>Merci de vous être inscrit ! Pour activer votre compte, veuillez vérifier votre adresse email en cliquant sur le bouton ci-dessous :</p>
        <p style="text-align: center;">
            <a href="{$url}" class="button">Vérifier mon email</a>
        </p>
        <p>Ou copiez ce lien dans votre navigateur :</p>
        <p style="word-break: break-all; color: #4f46e5;">{$url}</p>
        <p>Si vous n'avez pas créé de compte, ignorez simplement cet email.</p>
    </div>
    <div class="footer">
        <p>Ce lien expire dans 24 heures.</p>
    </div>
</body>
</html>
HTML;
    }
}
PHP;
    }
}
