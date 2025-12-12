<?php

use Ogan\Console\Generator\{ControllerGenerator, FormGenerator, ModelGenerator, RepositoryGenerator};
use Ogan\Console\Interactive\ModelBuilder;

/**
 * Commandes Make (génération de code)
 */
function registerMakeCommands($app) {
    $projectRoot = dirname(__DIR__, 2);
    $controllersPath = $projectRoot . '/src/Controller';
    $formsPath = $projectRoot . '/src/Form';
    $modelsPath = $projectRoot . '/src/Model';
    $repositoriesPath = $projectRoot . '/src/Repository';

    // make:controller
    $app->addCommand('make:controller', function($args) use ($controllersPath) {
        $name = $args[0] ?? null;
        $force = in_array('--force', $args);
        
        if (!$name) {
            echo "Usage: php bin/console make:controller <Name> [--force]\n";
            return 1;
        }
        
        echo "🎮 Génération du contrôleur : {$name}\n\n";
        
        $generator = new ControllerGenerator();
        $filepath = $generator->generate($name, $controllersPath, $force);
        
        echo "✅ Contrôleur généré : " . basename($filepath) . "\n";
        echo "📁 Fichier : {$filepath}\n";
        
        return 0;
    }, 'Génère un contrôleur');

    // make:model
    $app->addCommand('make:model', function($args) use ($modelsPath, $repositoriesPath) {
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
        
        // Générer le repository
        echo "\n📚 Génération du repository...\n";
        $modelClassName = $generator->toClassName($name);
        $modelClass = "App\\Model\\{$modelClassName}";
        $repoGenerator = new RepositoryGenerator();
        $repoPath = $repoGenerator->generate($name, $repositoriesPath, $modelClass, null, $force);
        echo "✅ Repository généré : " . basename($repoPath) . "\n";
        
        echo "\n💡 N'oubliez pas : php bin/console migrate:make {$name}\n";
        
        return 0;
    }, 'Génère un modèle (interactif)');

    // make:form
    $app->addCommand('make:form', function($args) use ($formsPath, $modelsPath) {
        $name = $args[0] ?? null;
        $force = in_array('--force', $args);
        
        if (!$name) {
            echo "Usage: php bin/console make:form <Name> [--force]\n";
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
}
