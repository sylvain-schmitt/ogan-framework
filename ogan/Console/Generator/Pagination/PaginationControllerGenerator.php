<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📄 PAGINATION CONTROLLER GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Pagination;

use Ogan\Console\Generator\AbstractGenerator;

class PaginationControllerGenerator extends AbstractGenerator
{
    private string $modelName;
    private bool $htmx;

    public function __construct(string $modelName, bool $htmx = false)
    {
        $this->modelName = $modelName;
        $this->htmx = $htmx;
    }

    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $controllerName = $this->modelName . 'ListController';
        $path = $projectRoot . '/src/Controller/' . $controllerName . '.php';
        
        if (!$this->fileExists($path) || $force) {
            $this->ensureDirectory(dirname($path));
            $this->writeFile($path, $this->getControllerContent());
            $generated[] = "src/Controller/{$controllerName}.php";
        } else {
            $skipped[] = "src/Controller/{$controllerName}.php (existe déjà)";
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getControllerContent(): string
    {
        $modelName = $this->modelName;
        $modelLower = strtolower($modelName);
        $modelPlural = $modelLower . 's';
        $controllerName = $modelName . 'ListController';
        $routePath = '/' . $modelPlural;
        $routeName = $modelLower . '_list';
        
        $htmxImport = $this->htmx ? "\nuse Ogan\\View\\Helper\\HtmxHelper;" : '';
        $htmxCheck = $this->htmx ? <<<PHP

        // Requête HTMX : retourner seulement le contenu partiel
        if (HtmxHelper::isHtmxRequest()) {
            return \$this->render('{$modelLower}/_list_partial.ogan', [
                '{$modelPlural}' => \${$modelPlural}
            ]);
        }
PHP : '';

        return <<<PHP
<?php

namespace App\Controller;

use App\Model\\{$modelName};
use Ogan\Controller\AbstractController;
use Ogan\Http\Response;
use Ogan\Router\Attributes\Route;{$htmxImport}

/**
 * Contrôleur de liste avec pagination
 * Généré par: php bin/console make:pagination {$modelName}
 */
class {$controllerName} extends AbstractController
{
    #[Route(path: '{$routePath}', methods: ['GET'], name: '{$routeName}')]
    public function index(): Response
    {
        \${$modelPlural} = {$modelName}::paginate(15);
{$htmxCheck}

        return \$this->render('{$modelLower}/list.ogan', [
            'title' => 'Liste des {$modelPlural}',
            '{$modelPlural}' => \${$modelPlural}
        ]);
    }
}
PHP;
    }
}
