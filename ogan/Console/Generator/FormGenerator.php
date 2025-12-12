<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 FORM GENERATOR - Générateur de FormTypes
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère automatiquement des FormTypes avec des champs de base.
 * 
 * UTILISATION :
 * -------------
 * 
 * $generator = new FormGenerator();
 * $generator->generate('User', 'src/Form');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator;

use Ogan\Console\Interactive\ModelAnalyzer;

class FormGenerator extends AbstractGenerator
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER UN FORMTYPE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $name Nom du FormType (ex: "User" ou "UserFormType")
     * @param string $formsPath Chemin vers le dossier des FormTypes
     * @param string|null $modelsPath Chemin vers le dossier des modèles (pour analyser le modèle)
     * @param bool $force Forcer la création même si le fichier existe
     * @return string Chemin du fichier créé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function generate(string $name, string $formsPath, ?string $modelsPath = null, bool $force = false): string
    {
        // Normaliser le nom
        $className = $this->toClassName($name);
        if (!str_ends_with($className, 'FormType')) {
            $className .= 'FormType';
        }

        $filename = $this->toFileName($className);
        $filepath = rtrim($formsPath, '/') . '/' . $filename;

        // Vérifier si le fichier existe
        if ($this->fileExists($filepath) && !$force) {
            throw new \RuntimeException("Le FormType existe déjà : {$filename}");
        }

        // Créer le dossier s'il n'existe pas
        $this->ensureDirectory($formsPath);

        // Extraire le nom du modèle (sans FormType)
        $baseName = str_replace('FormType', '', $className);
        
        // Analyser le modèle si le chemin est fourni
        $modelProperties = null;
        if ($modelsPath) {
            $modelClass = "App\\Model\\{$baseName}";
            if (class_exists($modelClass)) {
                try {
                    $analyzer = new ModelAnalyzer();
                    $analysis = $analyzer->analyze($modelClass);
                    $modelProperties = $analysis['properties'];
                } catch (\Exception $e) {
                    // Si l'analyse échoue, on continue sans propriétés du modèle
                }
            }
        }

        // Générer le contenu
        $content = $this->generateFormContent($className, $baseName, $modelProperties);

        // Écrire le fichier
        $this->writeFile($filepath, $content);

        return $filepath;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE CONTENU DU FORMTYPE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateFormContent(string $className, string $baseName, ?array $modelProperties = null): string
    {
        // Générer les champs selon les propriétés du modèle ou des champs d'exemple
        $fields = $this->generateFields($modelProperties);
        $usedTypes = $this->getUsedFieldTypes($modelProperties);
        $imports = $this->generateImports($usedTypes);

        return <<<PHP
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📝 {$className} - Formulaire {$baseName}
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ce FormType a été généré automatiquement.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\\Form;

use Ogan\\Form\\AbstractType;
use Ogan\\Form\\FormBuilder;
{$imports}

class {$className} extends AbstractType
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUIRE LE FORMULAIRE
     * ═══════════════════════════════════════════════════════════════════
     */
    public function buildForm(FormBuilder \$builder, array \$options): void
    {
        \$builder
{$fields}
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500'
                ]
            ]);
    }
}

PHP;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES CHAMPS DU FORMULAIRE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateFields(?array $modelProperties): string
    {
        // Si le modèle existe, générer les champs selon ses propriétés
        if ($modelProperties && !empty($modelProperties)) {
            $fields = [];
            
            foreach ($modelProperties as $prop) {
                $name = $prop['name'];
                $type = $prop['type'] ?? 'string';
                $nullable = $prop['nullable'] ?? true;
                
                // Ignorer les propriétés spéciales
                if (in_array($name, ['id', 'createdAt', 'updatedAt', 'attributes', 'exists'])) {
                    continue;
                }
                
                // Ignorer les clés étrangères (relations)
                if (str_ends_with(strtolower($name), 'id') && $name !== 'id') {
                    continue; // Les relations sont gérées séparément
                }
                
                // Améliorer la détection du type basée sur le nom de la propriété
                $type = $this->improveTypeDetection($name, $type);
                
                $fieldType = $this->mapPropertyTypeToFormType($type);
                $label = ucfirst($name);
                $required = !$nullable;
                
                $fields[] = $this->generateFieldCode($name, $fieldType, $label, $required);
            }
            
            return implode("\n", $fields);
        }
        
        // Sinon, générer des champs d'exemple
        return $this->generateExampleFields();
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LES TYPES DE CHAMPS UTILISÉS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getUsedFieldTypes(?array $modelProperties): array
    {
        $types = ['TextType', 'SubmitType'];
        
        if ($modelProperties && !empty($modelProperties)) {
            foreach ($modelProperties as $prop) {
                $name = $prop['name'];
                $type = $prop['type'] ?? 'string';
                
                // Ignorer les propriétés spéciales
                if (in_array($name, ['id', 'createdAt', 'updatedAt', 'attributes', 'exists'])) {
                    continue;
                }
                
                // Ignorer les clés étrangères
                if (str_ends_with(strtolower($name), 'id') && $name !== 'id') {
                    continue;
                }
                
                // Améliorer la détection du type
                $type = $this->improveTypeDetection($name, $type);
                $fieldType = $this->mapPropertyTypeToFormType($type);
                
                if (!in_array($fieldType, $types)) {
                    $types[] = $fieldType;
                }
            }
        }
        
        return $types;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER UN CHAMP
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateFieldCode(string $name, string $fieldType, string $label, bool $required): string
    {
        $placeholder = "Entrez {$label}";
        
        return "            ->add('{$name}', {$fieldType}::class, [\n" .
               "                'label' => '{$label}',\n" .
               "                'required' => " . ($required ? 'true' : 'false') . ",\n" .
               "                'attr' => [\n" .
               "                    'placeholder' => '{$placeholder}',\n" .
               "                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'\n" .
               "                ]\n" .
               "            ])";
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER DES CHAMPS D'EXEMPLE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateExampleFields(): string
    {
        return "            ->add('name', TextType::class, [\n" .
               "                'label' => 'Nom',\n" .
               "                'required' => true,\n" .
               "                'attr' => [\n" .
               "                    'placeholder' => 'Entrez le nom',\n" .
               "                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'\n" .
               "                ]\n" .
               "            ])";
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AMÉLIORER LA DÉTECTION DU TYPE BASÉE SUR LE NOM
     * ═══════════════════════════════════════════════════════════════════
     */
    private function improveTypeDetection(string $name, string $type): string
    {
        $lowerName = strtolower($name);
        
        // Détection basée sur le nom de la propriété
        if (str_contains($lowerName, 'email')) {
            return 'email';
        }
        if (in_array($lowerName, ['description', 'content', 'body', 'text', 'message', 'comment'])) {
            return 'text';
        }
        if (str_contains($lowerName, 'date') || str_contains($lowerName, 'time')) {
            return 'datetime';
        }
        
        return $type;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * MAPPER LE TYPE DE PROPRIÉTÉ VERS LE TYPE DE CHAMP
     * ═══════════════════════════════════════════════════════════════════
     */
    private function mapPropertyTypeToFormType(string $type): string
    {
        return match (strtolower($type)) {
            'int', 'integer' => 'NumberType',
            'float', 'double' => 'NumberType',
            'bool', 'boolean' => 'CheckboxType',
            'datetime', 'date' => 'DateType',
            'email' => 'EmailType',
            'text' => 'TextareaType',
            default => 'TextType'
        };
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES IMPORTS NÉCESSAIRES
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateImports(array $usedTypes): string
    {
        $imports = [];
        
        foreach ($usedTypes as $type) {
            $imports[] = "use Ogan\\Form\\Types\\{$type};";
        }
        
        return implode("\n", array_unique($imports));
    }
}
