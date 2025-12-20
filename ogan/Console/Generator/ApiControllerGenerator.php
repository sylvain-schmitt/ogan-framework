<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔌 API CONTROLLER GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Génère un controller API REST complet avec CRUD.
 * 
 * USAGE :
 * -------
 * php bin/console make:api User
 * # Génère: src/Controller/Api/UserController.php
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator;

class ApiControllerGenerator extends AbstractGenerator
{
    private string $modelName;
    private string $modelClass;
    private string $controllerClass;
    private string $routePrefix;

    public function generate(string $projectRoot, bool $force = false): array
    {
        throw new \InvalidArgumentException('Use generateForModel() instead');
    }

    /**
     * Génère un controller API pour un modèle donné
     */
    public function generateForModel(string $projectRoot, string $modelName, bool $force = false): array
    {
        $this->modelName = ucfirst($modelName);
        $this->modelClass = 'App\\Model\\' . $this->modelName;
        $this->controllerClass = $this->modelName . 'Controller';
        $this->routePrefix = '/api/' . strtolower($this->modelName) . 's';

        $generated = [];
        $skipped = [];

        // Créer le répertoire Api si nécessaire
        $apiDir = $projectRoot . '/src/Controller/Api';
        $this->ensureDirectory($apiDir);

        $controllerPath = $apiDir . '/' . $this->controllerClass . '.php';

        if (!$this->fileExists($controllerPath) || $force) {
            $this->writeFile($controllerPath, $this->getControllerContent());
            $generated[] = "src/Controller/Api/{$this->controllerClass}.php";
        } else {
            $skipped[] = "src/Controller/Api/{$this->controllerClass}.php (existe déjà)";
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    /**
     * Génère le contenu du controller API
     */
    private function getControllerContent(): string
    {
        $modelVar = lcfirst($this->modelName);
        $modelPlural = $modelVar . 's';

        return <<<PHP
<?php

namespace App\\Controller\\Api;

use {$this->modelClass};
use Ogan\\Controller\\ApiController;
use Ogan\\Http\\Response;
use Ogan\\Router\\Attributes\\Route;

/**
 * API REST pour {$this->modelName}
 * 
 * Endpoints :
 * - GET    {$this->routePrefix}          → Liste tous les {$modelPlural}
 * - GET    {$this->routePrefix}/{id}     → Affiche un {$modelVar}
 * - POST   {$this->routePrefix}          → Crée un {$modelVar}
 * - PUT    {$this->routePrefix}/{id}     → Met à jour un {$modelVar}
 * - DELETE {$this->routePrefix}/{id}     → Supprime un {$modelVar}
 */
class {$this->controllerClass} extends ApiController
{
    /**
     * Liste tous les {$modelPlural}
     */
    #[Route(path: '{$this->routePrefix}', methods: ['GET'], name: 'api_{$modelVar}_index')]
    public function index(): Response
    {
        \${$modelPlural} = {$this->modelName}::all();
        return \$this->success(\${$modelPlural});
    }

    /**
     * Affiche un {$modelVar} par ID
     */
    #[Route(path: '{$this->routePrefix}/{id}', methods: ['GET'], name: 'api_{$modelVar}_show')]
    public function show(int \$id): Response
    {
        \${$modelVar} = {$this->modelName}::find(\$id);
        
        if (!\${$modelVar}) {
            return \$this->notFound('{$this->modelName} not found');
        }
        
        return \$this->success(\${$modelVar});
    }

    /**
     * Crée un nouveau {$modelVar}
     */
    #[Route(path: '{$this->routePrefix}', methods: ['POST'], name: 'api_{$modelVar}_store')]
    public function store(): Response
    {
        \$data = \$this->getJsonBody();
        
        // TODO: Ajouter validation
        if (empty(\$data)) {
            return \$this->validationError(['body' => 'Request body is required']);
        }
        
        \${$modelVar} = new {$this->modelName}(\$data);
        
        if (\${$modelVar}->save()) {
            return \$this->created(\${$modelVar}, '{$this->modelName} created successfully');
        }
        
        return \$this->error('Failed to create {$this->modelName}');
    }

    /**
     * Met à jour un {$modelVar}
     */
    #[Route(path: '{$this->routePrefix}/{id}', methods: ['PUT', 'PATCH'], name: 'api_{$modelVar}_update')]
    public function update(int \$id): Response
    {
        \${$modelVar} = {$this->modelName}::find(\$id);
        
        if (!\${$modelVar}) {
            return \$this->notFound('{$this->modelName} not found');
        }
        
        \$data = \$this->getJsonBody();
        
        // Mettre à jour les attributs
        foreach (\$data as \$key => \$value) {
            \$setter = 'set' . ucfirst(\$key);
            if (method_exists(\${$modelVar}, \$setter)) {
                \${$modelVar}->\$setter(\$value);
            }
        }
        
        if (\${$modelVar}->save()) {
            return \$this->success(\${$modelVar}, '{$this->modelName} updated successfully');
        }
        
        return \$this->error('Failed to update {$this->modelName}');
    }

    /**
     * Supprime un {$modelVar}
     */
    #[Route(path: '{$this->routePrefix}/{id}', methods: ['DELETE'], name: 'api_{$modelVar}_destroy')]
    public function destroy(int \$id): Response
    {
        \${$modelVar} = {$this->modelName}::find(\$id);
        
        if (!\${$modelVar}) {
            return \$this->notFound('{$this->modelName} not found');
        }
        
        if (\${$modelVar}->delete()) {
            return \$this->noContent();
        }
        
        return \$this->error('Failed to delete {$this->modelName}');
    }
}
PHP;
    }
}
