<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔐 AUTH GENERATOR - Orchestrateur principal
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Génère le système d'authentification complet en déléguant
 * aux générateurs spécialisés.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class AuthGenerator extends AbstractGenerator
{
    private array $generated = [];
    private array $skipped = [];

    /**
     * Génère le système d'authentification complet
     */
    public function generate(string $projectRoot, bool $force = false): array
    {
        // 1. Model User
        $this->runGenerator(new UserModelGenerator(), $projectRoot, $force);

        // 2. Services (UserAuthenticator, EmailVerificationService, PasswordResetService)
        $this->runGenerator(new UserAuthenticatorGenerator(), $projectRoot, $force);
        $this->runGenerator(new EmailVerificationServiceGenerator(), $projectRoot, $force);
        $this->runGenerator(new PasswordResetServiceGenerator(), $projectRoot, $force);

        // 3. Repository
        $this->runGenerator(new UserRepositoryGenerator(), $projectRoot, $force);

        // 4. Controllers
        $this->runGenerator(new SecurityControllerGenerator(), $projectRoot, $force);
        $this->runGenerator(new DashboardControllerGenerator(), $projectRoot, $force);

        // 5. FormTypes
        $this->runGenerator(new AuthFormTypeGenerator(), $projectRoot, $force);

        // 6. Templates
        $this->runGenerator(new SecurityTemplateGenerator(), $projectRoot, $force);
        $this->runGenerator(new EmailTemplateGenerator(), $projectRoot, $force);
        $this->runGenerator(new DashboardTemplateGenerator(), $projectRoot, $force);
        $this->runGenerator(new DashboardComponentGenerator(), $projectRoot, $force);
        $this->runGenerator(new ProfileTemplateGenerator(), $projectRoot, $force);

        // 7. Assets JS
        $this->runGenerator(new JsAssetGenerator(), $projectRoot, $force);

        // 8. Migrations (jamais régénérées même avec --force)
        $this->runGenerator(new AuthMigrationGenerator(), $projectRoot, false);

        return [
            'generated' => $this->generated,
            'skipped' => $this->skipped
        ];
    }

    /**
     * Exécute un générateur et collecte les résultats
     */
    private function runGenerator(object $generator, string $projectRoot, bool $force): void
    {
        $result = $generator->generate($projectRoot, $force);
        
        if (isset($result['generated'])) {
            $this->generated = array_merge($this->generated, $result['generated']);
        }
        if (isset($result['skipped'])) {
            $this->skipped = array_merge($this->skipped, $result['skipped']);
        }
    }
}
