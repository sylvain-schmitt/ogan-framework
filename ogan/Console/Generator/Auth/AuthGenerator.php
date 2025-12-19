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
     * 
     * @param string $projectRoot Chemin racine du projet
     * @param bool $force Forcer l'écrasement des fichiers existants
     * @param bool $htmx Générer avec support HTMX
     */
    public function generate(string $projectRoot, bool $force = false, bool $htmx = false): array
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

        // 6. Templates (avec support HTMX optionnel)
        $this->runTemplateGenerator(new SecurityTemplateGenerator(), $projectRoot, $force, $htmx);
        $this->runGenerator(new EmailTemplateGenerator(), $projectRoot, $force);
        $this->runTemplateGenerator(new DashboardTemplateGenerator(), $projectRoot, $force, $htmx);
        $this->runTemplateGenerator(new DashboardComponentGenerator(), $projectRoot, $force, $htmx);
        $this->runTemplateGenerator(new ProfileTemplateGenerator(), $projectRoot, $force, $htmx);

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

    /**
     * Exécute un générateur de templates avec support HTMX
     */
    private function runTemplateGenerator(object $generator, string $projectRoot, bool $force, bool $htmx): void
    {
        // Vérifier si le générateur supporte HTMX
        if (method_exists($generator, 'generate')) {
            $reflection = new \ReflectionMethod($generator, 'generate');
            $params = $reflection->getParameters();
            
            // Si le générateur a un paramètre htmx, le passer
            if (count($params) >= 3) {
                $result = $generator->generate($projectRoot, $force, $htmx);
            } else {
                $result = $generator->generate($projectRoot, $force);
            }
        } else {
            $result = $generator->generate($projectRoot, $force);
        }
        
        if (isset($result['generated'])) {
            $this->generated = array_merge($this->generated, $result['generated']);
        }
        if (isset($result['skipped'])) {
            $this->skipped = array_merge($this->skipped, $result['skipped']);
        }
    }
}
