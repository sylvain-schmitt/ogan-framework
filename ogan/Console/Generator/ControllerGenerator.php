<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎮 CONTROLLER GENERATOR - Générateur de contrôleurs
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère automatiquement des contrôleurs avec des méthodes CRUD de base.
 * 
 * UTILISATION :
 * -------------
 * 
 * $generator = new ControllerGenerator();
 * $generator->generate('User', 'src/Controller');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator;

class ControllerGenerator extends AbstractGenerator
{
    /**
     * Actions CRUD disponibles
     */
    public const AVAILABLE_ACTIONS = ['list', 'show', 'create', 'store', 'edit', 'update', 'delete'];
    
    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER UN CONTRÔLEUR
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $name Nom du contrôleur (ex: "User" ou "UserController")
     * @param string $controllersPath Chemin vers le dossier des contrôleurs
     * @param bool $force Forcer la création même si le fichier existe
     * @param array $actions Actions à générer (vide = toutes)
     * @return string Chemin du fichier créé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function generate(string $name, string $controllersPath, bool $force = false, array $actions = []): string
    {
        // Si aucune action spécifiée, générer toutes les actions
        if (empty($actions)) {
            $actions = self::AVAILABLE_ACTIONS;
        }
        
        // Normaliser le nom
        $className = $this->toClassName($name);
        if (!str_ends_with($className, 'Controller')) {
            $className .= 'Controller';
        }

        $filename = $this->toFileName($className);
        $filepath = rtrim($controllersPath, '/') . '/' . $filename;

        // Vérifier si le fichier existe
        if ($this->fileExists($filepath) && !$force) {
            throw new \RuntimeException("Le contrôleur existe déjà : {$filename}");
        }

        // Créer le dossier s'il n'existe pas
        $this->ensureDirectory($controllersPath);

        // Générer le contenu
        $content = $this->generateControllerContent($className, $actions);

        // Écrire le fichier
        $this->writeFile($filepath, $content);

        return $filepath;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE CONTENU DU CONTRÔLEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateControllerContent(string $className, array $actions): string
    {
        $routeName = $this->toRouteName($className);
        $modelName = str_replace('Controller', '', $className);
        $modelClass = "App\\Model\\{$modelName}";
        $formTypeName = $modelName . 'FormType';
        $formTypeClass = "App\\Form\\{$formTypeName}";
        
        // Générer les méthodes selon les actions sélectionnées
        $methods = [];
        
        if (in_array('list', $actions)) {
            $methods[] = $this->generateListMethod($routeName, $modelName);
        }
        
        if (in_array('show', $actions)) {
            $methods[] = $this->generateShowMethod($routeName, $modelName);
        }
        
        if (in_array('create', $actions)) {
            $methods[] = $this->generateCreateMethod($routeName, $modelName, $formTypeName);
        }
        
        if (in_array('store', $actions)) {
            $methods[] = $this->generateStoreMethod($routeName, $modelName, $formTypeName);
        }
        
        if (in_array('edit', $actions)) {
            $methods[] = $this->generateEditMethod($routeName, $modelName, $formTypeName);
        }
        
        if (in_array('update', $actions)) {
            $methods[] = $this->generateUpdateMethod($routeName, $modelName, $formTypeName);
        }
        
        if (in_array('delete', $actions)) {
            $methods[] = $this->generateDeleteMethod($routeName, $modelName);
        }
        
        $methodsCode = implode("\n", $methods);
        
        // N'importer FormType que si nécessaire
        $needsForm = array_intersect(['create', 'store', 'edit', 'update'], $actions);
        $formImport = !empty($needsForm) ? "use {$formTypeClass};" : "// use {$formTypeClass}; // Décommenter si vous utilisez des formulaires";

        return <<<PHP
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎮 {$className} - Contrôleur {$modelName}
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ce contrôleur a été généré automatiquement.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\\Controller;

use Ogan\\Controller\\AbstractController;
use Ogan\\Router\\Attributes\\Route;
use {$modelClass};
{$formImport}

class {$className} extends AbstractController
{
{$methodsCode}
}

PHP;
    }

    /**
     * Générer la méthode list (index)
     */
    private function generateListMethod(string $routeName, string $modelName): string
    {
        return <<<PHP
    /**
     * ═══════════════════════════════════════════════════════════════════
     * LISTER LES ÉLÉMENTS
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/{$routeName}s', methods: ['GET'], name: '{$routeName}_list')]
    public function list()
    {
        \$items = {$modelName}::all();

        return \$this->render('{$routeName}/list.ogan', [
            'title' => 'Liste des {$routeName}s',
            'items' => \$items
        ]);
    }

PHP;
    }

    /**
     * Générer la méthode show
     */
    private function generateShowMethod(string $routeName, string $modelName): string
    {
        return <<<PHP
    /**
     * ═══════════════════════════════════════════════════════════════════
     * AFFICHER UN ÉLÉMENT
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/{$routeName}/{id}', methods: ['GET'], name: '{$routeName}_show')]
    public function show(int \$id)
    {
        \$item = {$modelName}::find(\$id);

        if (!\$item) {
            \$this->session->setFlash('error', 'Élément non trouvé');
            return \$this->redirect('/{$routeName}s');
        }

        return \$this->render('{$routeName}/show.ogan', [
            'title' => 'Détails de {$routeName}',
            'item' => \$item
        ]);
    }

PHP;
    }

    /**
     * Générer la méthode create
     */
    private function generateCreateMethod(string $routeName, string $modelName, string $formTypeName): string
    {
        return <<<PHP
    /**
     * ═══════════════════════════════════════════════════════════════════
     * CRÉER UN ÉLÉMENT (Formulaire)
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/{$routeName}/create', methods: ['GET'], name: '{$routeName}_create')]
    public function create()
    {
        \$form = \$this->formFactory->create({$formTypeName}::class, [
            'action' => '/{$routeName}/store',
            'method' => 'POST'
        ]);

        return \$this->render('{$routeName}/create.ogan', [
            'title' => 'Créer un {$routeName}',
            'form' => \$form->createView()
        ]);
    }

PHP;
    }

    /**
     * Générer la méthode store
     */
    private function generateStoreMethod(string $routeName, string $modelName, string $formTypeName): string
    {
        return <<<PHP
    /**
     * ═══════════════════════════════════════════════════════════════════
     * STOCKER UN ÉLÉMENT
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/{$routeName}/store', methods: ['POST'], name: '{$routeName}_store')]
    public function store()
    {
        \$form = \$this->formFactory->create({$formTypeName}::class, [
            'action' => '/{$routeName}/store',
            'method' => 'POST'
        ]);

        \$form->handleRequest(\$this->request);

        if (\$form->isValid()) {
            \$data = \$form->getData();

            \$item = new {$modelName}();
            // TODO: Assigner les données au modèle
            // Exemple: \$item->setName(\$data['name']);
            \$item->save();

            \$this->session->setFlash('success', '{$modelName} créé avec succès');
            return \$this->redirect('/{$routeName}s');
        }

        return \$this->render('{$routeName}/create.ogan', [
            'title' => 'Créer un {$routeName}',
            'form' => \$form->createView()
        ]);
    }

PHP;
    }

    /**
     * Générer la méthode edit
     */
    private function generateEditMethod(string $routeName, string $modelName, string $formTypeName): string
    {
        return <<<PHP
    /**
     * ═══════════════════════════════════════════════════════════════════
     * ÉDITER UN ÉLÉMENT (Formulaire)
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/{$routeName}/{id}/edit', methods: ['GET'], name: '{$routeName}_edit')]
    public function edit(int \$id)
    {
        \$item = {$modelName}::find(\$id);

        if (!\$item) {
            \$this->session->setFlash('error', 'Élément non trouvé');
            return \$this->redirect('/{$routeName}s');
        }

        \$form = \$this->formFactory->create({$formTypeName}::class, [
            'action' => '/{$routeName}/' . \$id . '/update',
            'method' => 'POST'
        ]);

        // TODO: Pré-remplir le formulaire avec les données de l'élément
        // Exemple: \$form->setData(['name' => \$item->getName()]);

        return \$this->render('{$routeName}/edit.ogan', [
            'title' => 'Éditer {$routeName}',
            'item' => \$item,
            'form' => \$form->createView()
        ]);
    }

PHP;
    }

    /**
     * Générer la méthode update
     */
    private function generateUpdateMethod(string $routeName, string $modelName, string $formTypeName): string
    {
        return <<<PHP
    /**
     * ═══════════════════════════════════════════════════════════════════
     * METTRE À JOUR UN ÉLÉMENT
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/{$routeName}/{id}/update', methods: ['POST'], name: '{$routeName}_update')]
    public function update(int \$id)
    {
        \$item = {$modelName}::find(\$id);

        if (!\$item) {
            \$this->session->setFlash('error', 'Élément non trouvé');
            return \$this->redirect('/{$routeName}s');
        }

        \$form = \$this->formFactory->create({$formTypeName}::class, [
            'action' => '/{$routeName}/' . \$id . '/update',
            'method' => 'POST'
        ]);

        \$form->handleRequest(\$this->request);

        if (\$form->isValid()) {
            \$data = \$form->getData();

            // TODO: Mettre à jour les données du modèle
            // Exemple: \$item->setName(\$data['name']);
            \$item->save();

            \$this->session->setFlash('success', '{$modelName} mis à jour avec succès');
            return \$this->redirect('/{$routeName}s');
        }

        return \$this->render('{$routeName}/edit.ogan', [
            'title' => 'Éditer {$routeName}',
            'item' => \$item,
            'form' => \$form->createView()
        ]);
    }

PHP;
    }

    /**
     * Générer la méthode delete
     */
    private function generateDeleteMethod(string $routeName, string $modelName): string
    {
        return <<<PHP
    /**
     * ═══════════════════════════════════════════════════════════════════
     * SUPPRIMER UN ÉLÉMENT
     * ═══════════════════════════════════════════════════════════════════
     */
    #[Route(path: '/{$routeName}/{id}/delete', methods: ['POST'], name: '{$routeName}_delete')]
    public function delete(int \$id)
    {
        \$item = {$modelName}::find(\$id);

        if (!\$item) {
            \$this->session->setFlash('error', 'Élément non trouvé');
            return \$this->redirect('/{$routeName}s');
        }

        \$item->delete();

        \$this->session->setFlash('success', '{$modelName} supprimé avec succès');
        return \$this->redirect('/{$routeName}s');
    }
PHP;
    }
}


