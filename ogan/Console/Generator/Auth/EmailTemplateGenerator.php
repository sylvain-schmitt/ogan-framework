<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ✉️ EMAIL TEMPLATE GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class EmailTemplateGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $emailsDir = $projectRoot . '/templates/emails';
        $this->ensureDirectory($emailsDir);

        $templates = [
            'verify_email.ogan' => 'getVerifyEmailTemplate',
            'password_reset.ogan' => 'getPasswordResetTemplate',
        ];

        foreach ($templates as $filename => $method) {
            $path = $emailsDir . '/' . $filename;
            if (!$this->fileExists($path) || $force) {
                $this->writeFile($path, $this->$method());
                $generated[] = "templates/emails/{$filename}";
            } else {
                $skipped[] = "templates/emails/{$filename} (existe déjà)";
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getVerifyEmailTemplate(): string
    {
        return <<<'OGAN'
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
        .button:hover { background: #4338ca; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
        .link { color: #4f46e5; word-break: break-all; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Vérification de votre email</h1>
    </div>
    <div class="content">
        <p>Bonjour {{ user.name }},</p>
        
        <p>Merci de vous être inscrit ! Pour activer votre compte et accéder à toutes les fonctionnalités, veuillez vérifier votre adresse email en cliquant sur le bouton ci-dessous :</p>
        
        <p style="text-align: center;">
            <a href="{{ url }}" class="button">Vérifier mon email</a>
        </p>
        
        <p>Ou copiez ce lien dans votre navigateur :</p>
        <p class="link">{{ url }}</p>
        
        <p>Si vous n'avez pas créé de compte, ignorez simplement cet email.</p>
        
        <p>Cordialement,<br>L'équipe</p>
    </div>
    <div class="footer">
        <p>Ce lien expire dans 24 heures.</p>
    </div>
</body>
</html>
OGAN;
    }

    private function getPasswordResetTemplate(): string
    {
        return <<<'OGAN'
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
        .button:hover { background: #dc2626; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
        .link { color: #ef4444; word-break: break-all; }
        .warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Réinitialisation de mot de passe</h1>
    </div>
    <div class="content">
        <p>Bonjour {{ user.name }},</p>
        
        <p>Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
        
        <p style="text-align: center;">
            <a href="{{ url }}" class="button">Réinitialiser mon mot de passe</a>
        </p>
        
        <p>Ou copiez ce lien dans votre navigateur :</p>
        <p class="link">{{ url }}</p>
        
        <div class="warning">
            Important : Si vous n'avez pas demandé cette réinitialisation, ignorez cet email. Votre mot de passe restera inchangé.
        </div>
        
        <p>Cordialement,<br>L'équipe</p>
    </div>
    <div class="footer">
        <p>Ce lien expire dans 1 heure.</p>
    </div>
</body>
</html>
OGAN;
    }
}
