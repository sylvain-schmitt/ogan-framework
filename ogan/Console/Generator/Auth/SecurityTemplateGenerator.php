<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 SECURITY TEMPLATE GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class SecurityTemplateGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $templatesDir = $projectRoot . '/templates/security';
        $this->ensureDirectory($templatesDir);

        $templates = [
            'login.ogan' => 'getLoginTemplate',
            'register.ogan' => 'getRegisterTemplate',
            'forgot_password.ogan' => 'getForgotPasswordTemplate',
            'reset_password.ogan' => 'getResetPasswordTemplate',
        ];

        foreach ($templates as $filename => $method) {
            $path = $templatesDir . '/' . $filename;
            if (!$this->fileExists($path) || $force) {
                $this->writeFile($path, $this->$method());
                $generated[] = "templates/security/{$filename}";
            } else {
                $skipped[] = "templates/security/{$filename} (existe déjà)";
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getLoginTemplate(): string
    {
        return <<<'OGAN'
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Connexion</h1>
            <p class="text-gray-600 mt-2">Connectez-vous à votre compte</p>
        </div>

        {% form.render() %}

        {% if show_forgot_password %}
        <div class="mt-6 text-center text-sm">
            <a href="/forgot-password" class="text-indigo-600 hover:text-indigo-500">
                Mot de passe oublié ?
            </a>
        </div>
        {% endif %}

        <div class="mt-4 text-center text-sm text-gray-600">
            Pas encore de compte ?
            <a href="/register" class="text-indigo-600 hover:text-indigo-500 font-semibold">
                Créer un compte
            </a>
        </div>
    </div>
</div>
{{ end }}
OGAN;
    }

    private function getRegisterTemplate(): string
    {
        return <<<'OGAN'
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Inscription</h1>
            <p class="text-gray-600 mt-2">Créez votre compte gratuitement</p>
        </div>

        {% form.render() %}

        <div class="mt-6 text-center text-sm text-gray-600">
            Déjà un compte ?
            <a href="/login" class="text-indigo-600 hover:text-indigo-500 font-semibold">
                Se connecter
            </a>
        </div>
    </div>
</div>
{{ end }}
OGAN;
    }

    private function getForgotPasswordTemplate(): string
    {
        return <<<'OGAN'
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mot de passe oublié</h1>
            <p class="text-gray-600 mt-2">Entrez votre email pour recevoir un lien de réinitialisation</p>
        </div>

        {% if emailSent %}
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <p class="font-semibold">Email envoyé !</p>
            <p class="text-sm">Si un compte existe avec cette adresse, vous recevrez un email avec les instructions.</p>
        </div>
        {% endif %}

        {% form.render() %}

        <div class="mt-6 text-center text-sm">
            <a href="/login" class="text-indigo-600 hover:text-indigo-500">
                ← Retour à la connexion
            </a>
        </div>
    </div>
</div>
{{ end }}
OGAN;
    }

    private function getResetPasswordTemplate(): string
    {
        return <<<'OGAN'
{{ extend('layouts/base.ogan') }}

{{ start('body') }}
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Nouveau mot de passe</h1>
            <p class="text-gray-600 mt-2">Choisissez un nouveau mot de passe sécurisé</p>
        </div>

        {% form.render() %}

        <div class="mt-6 text-center text-sm">
            <a href="/login" class="text-indigo-600 hover:text-indigo-500">
                ← Retour à la connexion
            </a>
        </div>
    </div>
</div>
{{ end }}
OGAN;
    }
}
