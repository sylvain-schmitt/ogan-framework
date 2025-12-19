<?php

use Ogan\Console\Generator\{ControllerGenerator, FormGenerator, ModelGenerator, RepositoryGenerator, TemplateGenerator};
use Ogan\Console\Interactive\ModelBuilder;

/**
 * Affiche l'aide pour une commande
 */
function showMakeHelp(string $command, string $description, array $options = []): void {
    echo "\n📖 {$command}\n";
    echo str_repeat('─', 60) . "\n\n";
    echo "{$description}\n\n";
    echo "Usage:\n  php bin/console {$command} <Name> [options]\n\n";
    echo "Arguments:\n";
    echo "  Name          Nom de l'élément à générer (ex: Product, User)\n\n";
    echo "Options:\n";
    echo "  --force       Force l'écrasement si le fichier existe\n";
    echo "  --help, -h    Affiche cette aide\n";
    foreach ($options as $opt => $desc) {
        echo "  {$opt}    {$desc}\n";
    }
    echo "\n";
}

/**
 * Vérifie si --help ou -h est demandé
 */
function isHelpRequested(array $args): bool {
    return in_array('--help', $args) || in_array('-h', $args);
}

/**
 * Commandes Make (génération de code)
 */
function registerMakeCommands($app) {
    $projectRoot = dirname(__DIR__, 2);
    $controllersPath = $projectRoot . '/src/Controller';
    $formsPath = $projectRoot . '/src/Form';
    $modelsPath = $projectRoot . '/src/Model';
    $repositoriesPath = $projectRoot . '/src/Repository';
    $templatesPath = $projectRoot . '/templates';

    // make:controller (mode interactif)
    $app->addCommand('make:controller', function($args) use ($controllersPath) {
        if (isHelpRequested($args)) {
            showMakeHelp('make:controller', 'Génère un contrôleur CRUD avec choix des actions (mode interactif).', [
                '--all' => 'Génère toutes les actions sans demander'
            ]);
            return 0;
        }
        
        $name = $args[0] ?? null;
        $force = in_array('--force', $args);
        $all = in_array('--all', $args);
        
        if (!$name) {
            echo "❌ Nom du contrôleur requis.\n\n";
            echo "Usage: php bin/console make:controller <Name> [--force] [--all]\n";
            echo "Aide:  php bin/console make:controller --help\n";
            return 1;
        }
        
        echo "🎮 Génération du contrôleur : {$name}\n\n";
        
        $actions = [];
        
        if (!$all) {
            // Mode interactif : demander les actions à générer
            echo "📋 Actions CRUD disponibles\n";
            echo "───────────────────────────────────────────────────────────\n";
            echo "Sélectionnez les actions à générer (o/n) :\n\n";
            
            $availableActions = [
                'list'   => 'Liste (index)',
                'show'   => 'Afficher un élément',
                'create' => 'Formulaire de création',
                'store'  => 'Enregistrer (POST)',
                'edit'   => 'Formulaire d\'édition',
                'update' => 'Mettre à jour (POST)',
                'delete' => 'Supprimer (POST)'
            ];
            
            // Demander tout sélectionner d'abord
            echo "Tout sélectionner ? (o/n) [o] : ";
            $handle = fopen("php://stdin", "r");
            $allResponse = trim(fgets($handle));
            fclose($handle);
            
            if (empty($allResponse) || in_array(strtolower($allResponse), ['o', 'oui', 'y', 'yes'])) {
                $actions = array_keys($availableActions);
                echo "✅ Toutes les actions sélectionnées\n\n";
            } else {
                echo "\n";
                foreach ($availableActions as $action => $description) {
                    echo "  {$description} ({$action}) ? (o/n) [o] : ";
                    $handle = fopen("php://stdin", "r");
                    $response = trim(fgets($handle));
                    fclose($handle);
                    
                    if (empty($response) || in_array(strtolower($response), ['o', 'oui', 'y', 'yes'])) {
                        $actions[] = $action;
                        echo "    ✅ {$action}\n";
                    } else {
                        echo "    ⏭️  {$action} ignoré\n";
                    }
                }
                echo "\n";
            }
            
            if (empty($actions)) {
                echo "❌ Aucune action sélectionnée. Abandon.\n";
                return 1;
            }
            
            // Afficher récapitulatif
            echo "📝 Actions à générer : " . implode(', ', $actions) . "\n\n";
        }
        
        $generator = new ControllerGenerator();
        $filepath = $generator->generate($name, $controllersPath, $force, $actions);
        
        echo "✅ Contrôleur généré : " . basename($filepath) . "\n";
        echo "📁 Fichier : {$filepath}\n";
        
        // Rappeler de créer les templates
        echo "\n💡 N'oubliez pas : php bin/console make:templates " . str_replace('Controller', '', $name) . "\n";
        
        return 0;
    }, 'Génère un contrôleur');

    // make:templates (mode interactif)
    $app->addCommand('make:templates', function($args) use ($templatesPath, $modelsPath) {
        if (isHelpRequested($args)) {
            showMakeHelp('make:templates', 'Génère les templates .ogan pour un contrôleur CRUD.', [
                '--all' => 'Génère tous les templates sans demander'
            ]);
            return 0;
        }
        
        $name = $args[0] ?? null;
        $force = in_array('--force', $args);
        $all = in_array('--all', $args);
        
        if (!$name) {
            echo "❌ Nom du modèle/contrôleur requis.\n\n";
            echo "Usage: php bin/console make:templates <Name> [--force] [--all]\n";
            echo "Aide:  php bin/console make:templates --help\n";
            return 1;
        }
        
        echo "🎨 Génération des templates : {$name}\n\n";
        
        $templates = [];
        
        if (!$all) {
            echo "📋 Templates disponibles\n";
            echo "───────────────────────────────────────────────────────────\n";
            
            $availableTemplates = [
                'list'   => 'Liste des éléments (table)',
                'show'   => 'Détails d\'un élément',
                'create' => 'Formulaire de création',
                'edit'   => 'Formulaire d\'édition'
            ];
            
            echo "Tout sélectionner ? (o/n) [o] : ";
            $handle = fopen("php://stdin", "r");
            $allResponse = trim(fgets($handle));
            fclose($handle);
            
            if (empty($allResponse) || in_array(strtolower($allResponse), ['o', 'oui', 'y', 'yes'])) {
                $templates = array_keys($availableTemplates);
                echo "✅ Tous les templates sélectionnés\n\n";
            } else {
                echo "\n";
                foreach ($availableTemplates as $tpl => $description) {
                    echo "  {$description} ({$tpl}.ogan) ? (o/n) [o] : ";
                    $handle = fopen("php://stdin", "r");
                    $response = trim(fgets($handle));
                    fclose($handle);
                    
                    if (empty($response) || in_array(strtolower($response), ['o', 'oui', 'y', 'yes'])) {
                        $templates[] = $tpl;
                        echo "    ✅ {$tpl}.ogan\n";
                    } else {
                        echo "    ⏭️  {$tpl}.ogan ignoré\n";
                    }
                }
                echo "\n";
            }
            
            if (empty($templates)) {
                echo "❌ Aucun template sélectionné. Abandon.\n";
                return 1;
            }
        }
        
        $generator = new TemplateGenerator();
        $files = $generator->generate($name, $templatesPath, $templates, $force, $modelsPath);
        
        if (empty($files)) {
            echo "ℹ️  Aucun template généré (fichiers existants ?). Utilisez --force pour écraser.\n";
            return 0;
        }
        
        echo "✅ Templates générés :\n";
        foreach ($files as $file) {
            echo "   📄 " . basename(dirname($file)) . "/" . basename($file) . "\n";
        }
        echo "\n📁 Dossier : " . dirname($files[0]) . "\n";
        
        return 0;
    }, 'Génère les templates .ogan');

    // make:model
    $app->addCommand('make:model', function($args) use ($modelsPath, $repositoriesPath) {
        if (isHelpRequested($args)) {
            showMakeHelp('make:model', 'Génère un modèle avec propriétés et relations (mode interactif).');
            return 0;
        }
        
        $name = $args[0] ?? null;
        $force = in_array('--force', $args);
        
        $generator = new ModelGenerator();
        $builder = new ModelBuilder();
        
        echo "🎨 Mode interactif activé\n\n";
        
        if ($name) {
            $modelClassName = $generator->toClassName($name);
            $modelClass = "App\\Model\\{$modelClassName}";
            $modelPath = $modelsPath . '/' . $modelClassName . '.php';
            $modelExists = file_exists($modelPath) && class_exists($modelClass);
            
            $data = $modelExists ? $builder->build($modelClass) : $builder->build(null, $modelClassName);
        } else {
            $data = $builder->build();
        }
        
        $name = $data['name'];
        $properties = is_array($data['properties']) ? $data['properties'] : [];
        $relations = is_array($data['relations']) ? $data['relations'] : [];
        
        echo "\n📦 Génération du modèle : {$name}\n\n";
        
        $filepath = $generator->generate($name, $modelsPath, $properties, $relations, $force);
        echo "✅ Modèle généré : " . basename($filepath) . "\n";
        
        // ─────────────────────────────────────────────────────────────
        // RELATIONS BIDIRECTIONNELLES
        // Ajouter automatiquement les relations inverses aux modèles liés
        // ─────────────────────────────────────────────────────────────
        $modelClassName = $generator->toClassName($name);
        
        foreach ($relations as $relation) {
            $relationType = $relation['type'] ?? '';
            $relatedModel = $relation['relatedModel'] ?? '';
            $foreignKey = $relation['foreignKey'] ?? strtolower($relatedModel) . '_id';
            
            if ($relationType === 'ManyToOne' && !empty($relatedModel)) {
                $relatedClass = "App\\Model\\" . $relatedModel;
                
                if ($generator->addInverseRelation($relatedClass, $modelClassName, $foreignKey, $modelsPath)) {
                    echo "🔗 Relation inverse OneToMany ajoutée à {$relatedModel}\n";
                }
            }
        }
        
        // Générer le repository
        echo "\n📚 Génération du repository...\n";
        $modelClass = "App\\Model\\{$modelClassName}";
        $repoGenerator = new RepositoryGenerator();
        $repoPath = $repoGenerator->generate($name, $repositoriesPath, $modelClass, null, $force);
        echo "✅ Repository généré : " . basename($repoPath) . "\n";
        
        echo "\n💡 N'oubliez pas : php bin/console migrate:make {$name}\n";
        
        return 0;
    }, 'Génère un modèle (interactif)');

    // make:form
    $app->addCommand('make:form', function($args) use ($formsPath, $modelsPath) {
        if (isHelpRequested($args)) {
            showMakeHelp('make:form', 'Génère un FormType avec validation.');
            return 0;
        }
        
        $name = $args[0] ?? null;
        $force = in_array('--force', $args);
        
        if (!$name) {
            echo "❌ Nom du FormType requis.\n\n";
            echo "Usage: php bin/console make:form <Name> [--force]\n";
            echo "Aide:  php bin/console make:form --help\n";
            return 1;
        }
        
        echo "📝 Génération du FormType : {$name}\n\n";
        
        $generator = new FormGenerator();
        $filepath = $generator->generate($name, $formsPath, $modelsPath, $force);
        
        echo "✅ FormType généré : " . basename($filepath) . "\n";
        echo "📁 Fichier : {$filepath}\n";
        
        return 0;
    }, 'Génère un FormType');

    // make:all
    $app->addCommand('make:all', function($args) use ($modelsPath, $repositoriesPath, $formsPath, $controllersPath) {
        if (isHelpRequested($args)) {
            showMakeHelp('make:all', 'Génère un modèle complet avec repository, form et contrôleur.');
            return 0;
        }
        
        $name = $args[0] ?? null;
        $force = in_array('--force', $args);
        
        echo "🛠️  Génération complète\n\n";
        
        $modelGenerator = new ModelGenerator();
        $builder = new ModelBuilder();
        
        $data = $name ? $builder->build(null, $modelGenerator->toClassName($name)) : $builder->build();
        
        $modelName = $data['name'];
        $properties = is_array($data['properties']) ? $data['properties'] : [];
        $relations = is_array($data['relations']) ? $data['relations'] : [];
        
        echo "\n📦 Génération du modèle : {$modelName}\n";
        $modelPath = $modelGenerator->generate($modelName, $modelsPath, $properties, $relations, $force);
        echo "✅ Modèle : " . basename($modelPath) . "\n\n";
        
        echo "📚 Génération du repository...\n";
        $modelClassName = $modelGenerator->toClassName($modelName);
        $modelClass = "App\\Model\\{$modelClassName}";
        $repoGenerator = new RepositoryGenerator();
        $repoPath = $repoGenerator->generate($modelName, $repositoriesPath, $modelClass, null, $force);
        echo "✅ Repository : " . basename($repoPath) . "\n\n";
        
        echo "📝 Génération du FormType...\n";
        $formGenerator = new FormGenerator();
        $formPath = $formGenerator->generate($modelName, $formsPath, $modelsPath, $force);
        echo "✅ FormType : " . basename($formPath) . "\n\n";
        
        echo "🎮 Génération du contrôleur...\n";
        $controllerGenerator = new ControllerGenerator();
        $controllerPath = $controllerGenerator->generate($modelName, $controllersPath, $force);
        echo "✅ Contrôleur : " . basename($controllerPath) . "\n\n";
        
        echo "✅ Génération complète terminée !\n";
        echo "💡 N'oubliez pas : php bin/console migrate:make {$modelName}\n";
        
        return 0;
    }, 'Génère modèle + repository + form + contrôleur');

    // make:migration (alias de migrate:make pour cohérence du naming)
    $app->addCommand('make:migration', function($args) {
        $projectRoot = dirname(__DIR__, 2);
        $migrationsPath = $projectRoot . '/database/migrations';
        $modelsPath = $projectRoot . '/src/Model';
        
        if (isHelpRequested($args)) {
            showMakeHelp('make:migration', 'Génère une migration depuis un modèle (alias de migrate:make).');
            return 0;
        }
        
        $modelInput = $args[0] ?? null;
        $force = in_array('--force', $args);
        
        // Connexion à la base pour détecter les tables existantes
        try {
            $pdo = \Ogan\Database\Database::getConnection();
        } catch (\Exception $e) {
            $pdo = null; // Pas de connexion, on génère CREATE TABLE par défaut
        }
        
        if (!$modelInput) {
            echo "❌ Nom du modèle requis.\n\n";
            echo "Usage: php bin/console make:migration <ModelName> [--force]\n";
            return 1;
        }
        
        // Trouver la classe du modèle
        if (!str_contains($modelInput, '\\')) {
            echo "🔍 Recherche du modèle : {$modelInput}\n";
            $modelClass = findModelClass($modelInput, $modelsPath);
            
            if (!$modelClass) {
                echo "❌ Modèle '{$modelInput}' non trouvé\n";
                return 1;
            }
            
            echo "✅ Modèle trouvé : {$modelClass}\n\n";
        } else {
            $modelClass = $modelInput;
        }
        
        echo "🔧 Génération de la migration : {$modelClass}\n\n";
        
        try {
            $generator = new \Ogan\Database\Migration\MigrationGenerator();
            $filepath = $generator->generateFromModel($modelClass, $migrationsPath, $force, $pdo);
            
            echo "✅ Migration générée : " . basename($filepath) . "\n";
            echo "📁 Fichier : {$filepath}\n";
        } catch (\Exception $e) {
            echo "❌ Erreur : " . $e->getMessage() . "\n";
            return 1;
        }
        
        return 0;
    }, 'Génère une migration depuis un modèle');
}
