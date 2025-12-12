<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📦 MODEL GENERATOR - Générateur de modèles
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère automatiquement des modèles avec des propriétés de base.
 * 
 * UTILISATION :
 * -------------
 * 
 * $generator = new ModelGenerator();
 * $generator->generate('User', 'src/Model');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator;

class ModelGenerator extends AbstractGenerator
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER UN MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $name Nom du modèle (ex: "User")
     * @param string $modelsPath Chemin vers le dossier des modèles
     * @param array $properties Propriétés à ajouter (optionnel)
     * @param array $relations Relations à ajouter (optionnel)
     * @param bool $force Forcer la création même si le fichier existe
     * @return string Chemin du fichier créé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function generate(string $name, string $modelsPath, array $properties = [], array $relations = [], bool $force = false): string
    {
        // S'assurer que $properties et $relations sont des tableaux
        if (!is_array($properties)) {
            $properties = [];
        }
        if (!is_array($relations)) {
            $relations = [];
        }

        // Normaliser le nom
        $className = $this->toClassName($name);

        $filename = $this->toFileName($className);
        $filepath = rtrim($modelsPath, '/') . '/' . $filename;

        // Vérifier si le fichier existe
        if ($this->fileExists($filepath) && !$force) {
            throw new \RuntimeException("Le modèle existe déjà : {$filename}");
        }

        // Créer le dossier s'il n'existe pas
        $this->ensureDirectory($modelsPath);

        // Générer le contenu
        $content = $this->generateModelContent($className, $properties, $relations);

        // Écrire le fichier
        $this->writeFile($filepath, $content);

        return $filepath;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE CONTENU DU MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateModelContent(string $className, array $properties = [], array $relations = []): string
    {
        // S'assurer que $relations est toujours un tableau
        if (!is_array($relations)) {
            $relations = [];
        }

        // Propriétés de base toujours présentes (id, created_at, updated_at)
        // On ne génère pas de propriétés par défaut, seulement les propriétés de base
        $baseProperties = [
            ['name' => 'id', 'type' => 'int', 'nullable' => true],
            ['name' => 'createdAt', 'type' => 'DateTime', 'nullable' => true],
            ['name' => 'updatedAt', 'type' => 'DateTime', 'nullable' => true],
        ];
        
        // Fusionner les propriétés de base avec les propriétés fournies
        // Éviter les doublons (si id, createdAt, updatedAt sont déjà dans $properties)
        $existingBaseNames = array_column($properties, 'name');
        foreach ($baseProperties as $baseProp) {
            if (!in_array($baseProp['name'], $existingBaseNames)) {
                $properties[] = $baseProp;
            }
        }

        $propertiesCode = $this->generateProperties($properties);
        $gettersCode = $this->generateGetters($properties);
        $settersCode = $this->generateSetters($properties);
        $relationsCode = $this->generateRelations($className, $relations);

        return <<<PHP
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📦 {$className} - Modèle {$className}
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ce modèle a été généré automatiquement.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\\Model;

use Ogan\\Database\\Model;
{$this->generateRelationImports($relations)}

class {$className} extends Model
{
    protected static ?string \$primaryKey = 'id';

    // ─────────────────────────────────────────────────────────────
    // PROPRIÉTÉS
    // ─────────────────────────────────────────────────────────────

{$propertiesCode}

    // ─────────────────────────────────────────────────────────────
    // GETTERS
    // ─────────────────────────────────────────────────────────────

{$gettersCode}

    // ─────────────────────────────────────────────────────────────
    // SETTERS
    // ─────────────────────────────────────────────────────────────

{$settersCode}
}

PHP;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES PROPRIÉTÉS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateProperties(array $properties): string
    {
        $code = [];
        
        foreach ($properties as $prop) {
            $name = $prop['name'];
            $type = $this->normalizeType($prop['type'] ?? 'string');
            $nullable = $prop['nullable'] ?? true;
            $typeHint = $nullable ? "?{$type}" : $type;
            $comment = $prop['comment'] ?? ucfirst($name);
            
            // Valeur par défaut selon le type et nullable
            $defaultValue = $this->getDefaultValue($type, $nullable);
            
            // Nettoyer le commentaire pour éviter les caractères problématiques
            $cleanComment = trim($comment);
            if (!empty($cleanComment)) {
                $code[] = "    /**";
                $code[] = "     * @var {$typeHint} {$cleanComment}";
                $code[] = "     */";
            } else {
                $code[] = "    /**";
                $code[] = "     * @var {$typeHint}";
                $code[] = "     */";
            }
            $code[] = "    private {$typeHint} \${$name} = {$defaultValue};";
            $code[] = "";
        }
        
        return implode("\n", $code);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LA VALEUR PAR DÉFAUT SELON LE TYPE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getDefaultValue(string $type, bool $nullable): string
    {
        if ($nullable) {
            return 'null';
        }

        // Valeurs par défaut pour les types non-nullable
        return match ($type) {
            'int' => '0',
            'float' => '0.0',
            'bool' => 'false',
            'string' => "''",
            'array' => '[]',
            '\\DateTime' => 'null', // DateTime ne peut pas être initialisé directement
            default => 'null'
        };
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES GETTERS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateGetters(array $properties): string
    {
        $code = [];
        
        foreach ($properties as $prop) {
            $name = $prop['name'];
            $type = $this->normalizeType($prop['type'] ?? 'string');
            $nullable = $prop['nullable'] ?? true;
            $typeHint = $nullable ? "?{$type}" : $type;
            $methodName = 'get' . ucfirst($name);
            
            $code[] = "    public function {$methodName}(): {$typeHint}";
            $code[] = "    {";
            $code[] = "        return \$this->{$name};";
            $code[] = "    }";
            $code[] = "";
        }
        
        return implode("\n", $code);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES SETTERS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateSetters(array $properties): string
    {
        $code = [];
        
        foreach ($properties as $prop) {
            $name = $prop['name'];
            $type = $this->normalizeType($prop['type'] ?? 'string');
            $nullable = $prop['nullable'] ?? true;
            $typeHint = $nullable ? "?{$type}" : $type;
            $methodName = 'set' . ucfirst($name);
            
            $code[] = "    public function {$methodName}({$typeHint} \${$name}): self";
            $code[] = "    {";
            $code[] = "        \$this->{$name} = \${$name};";
            $code[] = "        return \$this;";
            $code[] = "    }";
            $code[] = "";
        }
        
        return implode("\n", $code);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * NORMALISER UN TYPE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function normalizeType(string $type): string
    {
        $type = strtolower($type);
        
        return match ($type) {
            'int', 'integer' => 'int',
            'float', 'double' => 'float',
            'bool', 'boolean' => 'bool',
            'string' => 'string',
            'datetime', 'date' => '\\DateTime',
            'array' => 'array',
            default => 'string'
        };
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES RELATIONS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateRelations(string $className, array $relations): string
    {
        if (empty($relations)) {
            return "    // Aucune relation définie\n";
        }

        $code = [];
        
        foreach ($relations as $relation) {
            $type = $relation['type'] ?? 'ManyToOne';
            $relatedModel = $relation['relatedModel'] ?? '';
            $foreignKey = $relation['foreignKey'] ?? null;
            $localKey = $relation['localKey'] ?? 'id';

            $methodName = lcfirst($relatedModel);
            if ($type === 'OneToMany') {
                $methodName = lcfirst($relatedModel . 's'); // Pluraliser
            }

            switch ($type) {
                case 'OneToMany':
                    $foreignKey = $foreignKey ?? strtolower($className) . '_id';
                    $code[] = "    /**";
                    $code[] = "     * Relation OneToMany : {$className} a plusieurs {$relatedModel}";
                    $code[] = "     */";
                    $code[] = "    public function {$methodName}(): \\Ogan\\Database\\Relations\\OneToMany";
                    $code[] = "    {";
                    $code[] = "        return \$this->oneToMany({$relatedModel}::class, '{$foreignKey}');";
                    $code[] = "    }";
                    $code[] = "";
                    break;

                case 'ManyToOne':
                    $foreignKey = $foreignKey ?? strtolower($relatedModel) . '_id';
                    $code[] = "    /**";
                    $code[] = "     * Relation ManyToOne : {$className} appartient à {$relatedModel}";
                    $code[] = "     */";
                    $code[] = "    public function {$methodName}(): \\Ogan\\Database\\Relations\\ManyToOne";
                    $code[] = "    {";
                    $code[] = "        return \$this->manyToOne({$relatedModel}::class, '{$foreignKey}');";
                    $code[] = "    }";
                    $code[] = "";
                    break;

                case 'OneToOne':
                    $foreignKey = $foreignKey ?? strtolower($relatedModel) . '_id';
                    $code[] = "    /**";
                    $code[] = "     * Relation OneToOne : {$className} a un seul {$relatedModel}";
                    $code[] = "     */";
                    $code[] = "    public function {$methodName}(): \\Ogan\\Database\\Relations\\OneToOne";
                    $code[] = "    {";
                    $code[] = "        return \$this->oneToOne({$relatedModel}::class, '{$foreignKey}');";
                    $code[] = "    }";
                    $code[] = "";
                    break;

                case 'ManyToMany':
                    $pivotTable = $this->generatePivotTableName($className, $relatedModel);
                    $code[] = "    /**";
                    $code[] = "     * Relation ManyToMany : {$className} a plusieurs {$relatedModel}";
                    $code[] = "     */";
                    $code[] = "    public function {$methodName}(): \\Ogan\\Database\\Relations\\ManyToMany";
                    $code[] = "    {";
                    $code[] = "        return \$this->manyToMany({$relatedModel}::class, '{$pivotTable}');";
                    $code[] = "    }";
                    $code[] = "";
                    break;
            }
        }

        return implode("\n", $code);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES IMPORTS POUR LES RELATIONS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateRelationImports(array $relations): string
    {
        if (empty($relations)) {
            return "";
        }

        $imports = [];
        foreach ($relations as $relation) {
            $relatedModel = $relation['relatedModel'] ?? '';
            if (!empty($relatedModel)) {
                $imports[] = "use App\\Model\\{$relatedModel};";
            }
        }

        return "\n" . implode("\n", array_unique($imports));
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE NOM DE LA TABLE PIVOT
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generatePivotTableName(string $model1, string $model2): string
    {
        $tables = [strtolower($model1), strtolower($model2)];
        sort($tables);
        return implode('_', $tables);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * AJOUTER UNE RELATION INVERSE À UN MODÈLE EXISTANT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Ajoute automatiquement une relation OneToMany dans le modèle lié
     * lorsqu'une relation ManyToOne est créée.
     * 
     * @param string $relatedModelClass Classe du modèle lié (ex: "App\Model\Category")
     * @param string $currentModelName Nom du modèle actuel (ex: "Product")
     * @param string $foreignKey Clé étrangère (ex: "category_id")
     * @param string $modelsPath Chemin vers le dossier des modèles
     * @return bool True si la relation a été ajoutée, false sinon
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function addInverseRelation(string $relatedModelClass, string $currentModelName, string $foreignKey, string $modelsPath): bool
    {
        // Vérifier que le modèle lié existe
        if (!class_exists($relatedModelClass)) {
            return false;
        }

        // Extraire le nom court du modèle lié
        $relatedModelName = substr($relatedModelClass, strrpos($relatedModelClass, '\\') + 1);
        $relatedModelPath = $modelsPath . '/' . $relatedModelName . '.php';

        // Vérifier que le fichier existe
        if (!file_exists($relatedModelPath)) {
            return false;
        }

        // Lire le contenu du fichier
        $content = file_get_contents($relatedModelPath);

        // Vérifier si la relation existe déjà
        $methodName = lcfirst($currentModelName . 's'); // Pluraliser (Product → products)
        if (strpos($content, "function {$methodName}()") !== false) {
            return false; // La relation existe déjà
        }

        // Générer le code de la relation inverse (OneToMany)
        $relationCode = "    /**\n";
        $relationCode .= "     * Relation OneToMany : {$relatedModelName} a plusieurs {$currentModelName}\n";
        $relationCode .= "     */\n";
        $relationCode .= "    public function {$methodName}(): \\Ogan\\Database\\Relations\\OneToMany\n";
        $relationCode .= "    {\n";
        $relationCode .= "        return \$this->oneToMany({$currentModelName}::class, '{$foreignKey}');\n";
        $relationCode .= "    }\n";

        // Ajouter l'import si nécessaire
        $importStatement = "use App\\Model\\{$currentModelName};";
        if (strpos($content, $importStatement) === false) {
            // Trouver la position après "use Ogan\\Database\\Model;"
            $insertPosition = strpos($content, "use Ogan\\Database\\Model;");
            if ($insertPosition !== false) {
                $insertPosition = strpos($content, "\n", $insertPosition) + 1;
                $content = substr_replace($content, $importStatement . "\n", $insertPosition, 0);
            }
        }

        // Vérifier si une section RELATIONS existe déjà
        $hasRelationsSection = strpos($content, "// RELATIONS") !== false;
        
        if ($hasRelationsSection) {
            // Trouver la fin de la section RELATIONS (avant la dernière accolade)
            $relationsSectionEnd = strrpos($content, "// ─────────────────────────────────────────────────────────────");
            if ($relationsSectionEnd !== false) {
                // Trouver la fin de la dernière méthode de relation
                $lastMethodEnd = strrpos($content, "    }\n", $relationsSectionEnd);
                if ($lastMethodEnd !== false) {
                    $insertPosition = $lastMethodEnd + strlen("    }\n");
                    $content = substr_replace($content, "\n" . $relationCode . "\n", $insertPosition, 0);
                } else {
                    // Insérer après la section RELATIONS
                    $insertPosition = strpos($content, "\n", $relationsSectionEnd) + 1;
                    $content = substr_replace($content, "\n" . $relationCode . "\n", $insertPosition, 0);
                }
            } else {
                // Insérer avant la dernière accolade
                $lastBrace = strrpos($content, '}');
                if ($lastBrace !== false) {
                    $content = substr_replace($content, "\n" . $relationCode . "\n", $lastBrace, 0);
                }
            }
        } else {
            // Créer une nouvelle section RELATIONS
            $relationCodeWithSection = "\n    // ─────────────────────────────────────────────────────────────\n";
            $relationCodeWithSection .= "    // RELATIONS\n";
            $relationCodeWithSection .= "    // ─────────────────────────────────────────────────────────────\n\n";
            $relationCodeWithSection .= $relationCode;
            
            // Trouver la position avant la dernière accolade fermante
            $lastBrace = strrpos($content, '}');
            if ($lastBrace !== false) {
                // Insérer la relation juste avant la dernière accolade
                $content = substr_replace($content, $relationCodeWithSection . "\n", $lastBrace, 0);
            } else {
                // Si pas d'accolade trouvée, ajouter à la fin
                $content = rtrim($content) . "\n" . $relationCodeWithSection . "\n";
            }
        }

        // Écrire le fichier modifié
        file_put_contents($relatedModelPath, $content);

        return true;
    }
}

