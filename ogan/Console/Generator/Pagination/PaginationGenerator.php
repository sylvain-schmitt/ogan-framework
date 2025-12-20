<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📄 PAGINATION GENERATOR - Orchestrateur principal
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Génère un exemple complet de pagination avec support HTMX optionnel.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Pagination;

use Ogan\Console\Generator\AbstractGenerator;

class PaginationGenerator extends AbstractGenerator
{
    private array $generated = [];
    private array $skipped = [];

    /**
     * Génère l'exemple de pagination
     * 
     * @param string $projectRoot Chemin racine du projet
     * @param string $modelName Nom du modèle à paginer (ex: User, Article)
     * @param bool $force Forcer l'écrasement des fichiers existants
     * @param bool $htmx Générer avec support HTMX
     */
    public function generate(string $projectRoot, string $modelName, bool $force = false, bool $htmx = false): array
    {
        // 1. Controller avec pagination
        $this->runGenerator(
            new PaginationControllerGenerator($modelName, $htmx),
            $projectRoot,
            $force
        );

        // 2. Templates
        $this->runGenerator(
            new PaginationTemplateGenerator($modelName, $htmx),
            $projectRoot,
            $force
        );

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
