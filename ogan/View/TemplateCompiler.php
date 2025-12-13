<?php

namespace Ogan\View;

use Ogan\Exception\ViewException;
use Ogan\View\Compiler\Utility\PlaceholderManager;
use Ogan\View\Compiler\Utility\PhpKeywordChecker;
use Ogan\View\Compiler\Utility\StringProtector;
use Ogan\View\Compiler\Variable\VariableProtector;
use Ogan\View\Compiler\Variable\VariableTransformer;
use Ogan\View\Compiler\Syntax\DotSyntaxTransformer;
use Ogan\View\Compiler\Syntax\FilterTransformer;
use Ogan\View\Compiler\Expression\ExpressionParser;
use Ogan\View\Compiler\Expression\ExpressionCompiler;
use Ogan\View\Compiler\Control\ControlStructureCompiler;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔧 COMPILATEUR DE TEMPLATES
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Compile les templates avec la syntaxe {{ }} en PHP natif.
 * 
 * SYNTAXE SUPPORTÉE :
 * -------------------
 * variable entre doubles accolades → <?= $this->e($variable) ?>
 * variable avec ! pour sans échappement → <?= $variable ?>
 * section('name') → <?= $this->section('name') ?>
 * route('name', array('id' => 1)) → <?= $this->route('name', array('id' => 1)) ?>
 * asset('path') → <?= $this->asset('path') ?>
 * component('name', array(...)) → <?= $this->component('name', array(...)) ?>
 * variable|filter → filter(variable)
 * 
 * STRUCTURES DE CONTRÔLE :
 * ------------------------
 * {{ if (condition): }} → <?php if (condition): ?>
 * {{ endif; }} → <?php endif; ?>
 * {{ else: }} → <?php else: ?>
 * {{ elseif (condition): }} → <?php elseif (condition): ?>
 * {{ foreach ($items as $item): }} → <?php foreach ($items as $item): ?>
 * {{ endforeach; }} → <?php endforeach; ?>
 * {{ while (condition): }} → <?php while (condition): ?>
 * {{ endwhile; }} → <?php endwhile; ?>
 * {{ for ($i = 0; $i < 10; $i++): }} → <?php for ($i = 0; $i < 10; $i++): ?>
 * {{ endfor; }} → <?php endfor; ?>
 * 
 * EXEMPLES :
 * ----------
 * Template source : titre entre doubles accolades
 * Template compilé : <?= $this->e($title) ?>
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */
class TemplateCompiler
{
    private string $cacheDir;
    private bool $autoReload;
    private ?\Ogan\View\Compiler\Expression\ExpressionParser $expressionParser = null;
    private ?\Ogan\View\Compiler\Control\ControlStructureCompiler $controlStructureCompiler = null;
    private ?\Ogan\View\Compiler\Expression\ExpressionCompiler $expressionCompiler = null;

    public function __construct(string $cacheDir, bool $autoReload = true)
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->autoReload = $autoReload;

        // Créer le répertoire de cache s'il n'existe pas
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Initialise les compilateurs (lazy loading)
     */
    private function initializeCompilers(): void
    {
        if ($this->expressionParser === null) {
            $placeholderManager = new PlaceholderManager();
            $keywordChecker = new PhpKeywordChecker();
            $stringProtector = new StringProtector($placeholderManager);
            $variableProtector = new VariableProtector($placeholderManager);
            $dotSyntaxTransformer = new DotSyntaxTransformer($placeholderManager);
            $filterTransformer = new FilterTransformer($placeholderManager);
            $variableTransformer = new VariableTransformer($keywordChecker, $variableProtector, $placeholderManager, $stringProtector);

            $this->expressionParser = new ExpressionParser(
                $dotSyntaxTransformer,
                $filterTransformer,
                $variableTransformer,
                $stringProtector,
                $placeholderManager
            );

            $this->controlStructureCompiler = new ControlStructureCompiler($this->expressionParser);
            $this->expressionCompiler = new ExpressionCompiler($this->expressionParser);
        }
    }

    /**
     * Compile un template et retourne le chemin du fichier compilé
     */
    public function compile(string $templatePath): string
    {
        if (!file_exists($templatePath)) {
            throw new ViewException("Template introuvable : $templatePath");
        }

        // Chemin du fichier compilé
        $cacheKey = $this->getCacheKey($templatePath);
        $compiledPath = $this->cacheDir . '/' . $cacheKey . '.php';

        // Vérifier si le cache est valide
        if (!$this->autoReload && file_exists($compiledPath)) {
            if (filemtime($compiledPath) >= filemtime($templatePath)) {
                return $compiledPath;
            }
        }

        // Lire le contenu du template
        $content = file_get_contents($templatePath);

        // Compiler le contenu
        $compiled = $this->compileContent($content);

        // Écrire le fichier compilé
        file_put_contents($compiledPath, $compiled);

        return $compiledPath;
    }

    /**
     * Compile le contenu d'un template
     */
    private function compileContent(string $content): string
    {
        // Initialiser les compilateurs
        $this->initializeCompilers();

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 1 : Traiter les structures de contrôle (if, foreach, etc.)
        // ─────────────────────────────────────────────────────────────
        $content = $this->controlStructureCompiler->compile($content);

        // ─────────────────────────────────────────────────────────────
        // ÉTAPE 2 : Traiter les expressions normales (variables, helpers)
        // ─────────────────────────────────────────────────────────────
        $content = $this->expressionCompiler->compile($content);

        return $content;
    }

    /**
     * Génère une clé de cache unique pour un template
     */
    private function getCacheKey(string $templatePath): string
    {
        return md5($templatePath);
    }

    /**
     * Vide le cache des templates compilés
     */
    public function clearCache(): void
    {
        $files = glob($this->cacheDir . '/*.php');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Vérifie si un template doit être recompilé
     */
    public function needsRecompilation(string $templatePath): bool
    {
        if ($this->autoReload) {
            return true;
        }

        $cacheKey = $this->getCacheKey($templatePath);
        $compiledPath = $this->cacheDir . '/' . $cacheKey . '.php';

        if (!file_exists($compiledPath)) {
            return true;
        }

        return filemtime($compiledPath) < filemtime($templatePath);
    }
}
