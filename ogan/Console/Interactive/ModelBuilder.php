<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎨 MODEL BUILDER - Assistant interactif pour créer des modèles
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Guide l'utilisateur pour créer un modèle avec ses propriétés et relations.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Interactive;

use Ogan\Console\Interactive\ModelAnalyzer;

class ModelBuilder
{
    private ModelAnalyzer $analyzer;

    public function __construct()
    {
        $this->analyzer = new ModelAnalyzer();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUIRE UN MODÈLE INTERACTIVEMENT
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string|null $existingModelClass Classe du modèle existant à modifier (optionnel)
     * @param string|null $predefinedName Nom du modèle pré-défini (optionnel, utilisé quand fourni en ligne de commande)
     */
    public function build(?string $existingModelClass = null, ?string $predefinedName = null): array
    {
        $existingProperties = [];
        $existingRelations = [];

        // Si un modèle existe, l'analyser
        if ($existingModelClass && class_exists($existingModelClass)) {
            echo "📋 Modèle existant détecté : {$existingModelClass}\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
            
            try {
                $analysis = $this->analyzer->analyze($existingModelClass);
                $existingProperties = $analysis['properties'];
                $existingRelations = $analysis['relations'];
                
                echo "✅ Propriétés existantes trouvées :\n";
                foreach ($existingProperties as $prop) {
                    $nullable = $prop['nullable'] ? 'nullable' : 'required';
                    echo "   - {$prop['name']} ({$prop['type']}, {$nullable})\n";
                }
                echo "\n";
            } catch (\Exception $e) {
                echo "⚠️  Impossible d'analyser le modèle existant : {$e->getMessage()}\n\n";
            }
        } else {
            echo "🎨 Assistant de création de modèle\n";
            echo "═══════════════════════════════════════════════════════════\n\n";
        }

        // Nom du modèle
        if ($existingModelClass) {
            $modelName = basename(str_replace('\\', '/', $existingModelClass));
            echo "Nom du modèle : {$modelName}\n";
        } elseif ($predefinedName) {
            // Utiliser le nom pré-défini (fourni en ligne de commande)
            $modelName = $predefinedName;
            echo "Nom du modèle : {$modelName}\n";
        } else {
            // Demander le nom seulement si pas fourni
            $modelName = $this->ask("Nom du modèle (ex: User, Product) : ");
            if (empty($modelName)) {
                throw new \RuntimeException("Le nom du modèle est requis");
            }
        }

        // Propriétés
        echo "\n📋 Propriétés du modèle\n";
        echo "───────────────────────────────────────────────────────────\n";
        echo "Les propriétés 'id', 'created_at' et 'updated_at' sont ajoutées automatiquement.\n";
        echo "Appuyez sur Entrée (sans saisir de nom) pour terminer l'ajout de propriétés.\n\n";

        // Préserver les propriétés existantes si on modifie un modèle
        $properties = $existingProperties ?? [];
        $detectedRelations = []; // Relations détectées automatiquement depuis les propriétés
        
        // Si on modifie un modèle, informer que les propriétés existantes seront préservées
        if (!empty($existingProperties)) {
            echo "💡 Les propriétés existantes seront préservées.\n\n";
        }
        
        // Demander les propriétés de manière continue (sans demander "oui/non" à chaque fois)
        while (true) {
            $property = $this->askProperty();
            if (!$property) {
                // Nom vide = fin de l'ajout de propriétés
                break;
            }
            
            // Vérifier si la propriété existe déjà
            $exists = false;
            foreach ($properties as $existingProp) {
                if ($existingProp['name'] === $property['name']) {
                    $exists = true;
                    echo "⚠️  La propriété '{$property['name']}' existe déjà. Ignorée.\n\n";
                    break;
                }
            }
            
            if (!$exists) {
                $properties[] = $property;
                
                // Si c'est une relation détectée, l'ajouter à la liste des relations
                if (isset($property['isRelation']) && $property['isRelation'] && isset($property['relation'])) {
                    $detectedRelations[] = $property['relation'];
                }
                
                echo "✅ Propriété '{$property['name']}' ajoutée.\n\n";
            }
        }
        
        // Fusionner les relations détectées avec les relations existantes
        $relations = array_merge($existingRelations ?? [], $detectedRelations);

        // Afficher les relations détectées automatiquement
        if (!empty($detectedRelations)) {
            echo "\n✅ Relations détectées automatiquement :\n";
            foreach ($detectedRelations as $rel) {
                echo "   - {$rel['type']} vers {$rel['relatedModel']}\n";
            }
            echo "\n💡 Ces relations seront générées automatiquement dans le modèle.\n";
        }
        
        // Les relations sont maintenant détectées automatiquement via les noms de propriétés
        // Plus besoin de demander manuellement les relations

        return [
            'name' => $modelName,
            'properties' => $properties,
            'relations' => $relations
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DEMANDER UNE PROPRIÉTÉ (avec détection automatique des relations)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function askProperty(): ?array
    {
        $name = $this->ask("Nom de la propriété (ex: email, firstName, categoryId) : ");
        if (empty($name)) {
            return null;
        }

        // Détecter automatiquement si c'est une relation (categoryId, category_id, userId, etc.)
        $detectedRelation = $this->detectRelationFromPropertyName($name);
        
        if ($detectedRelation) {
            echo "\n🔗 Relation détectée automatiquement vers {$detectedRelation['relatedModel']}\n";
            
            // Permettre le choix du type de relation (les 4 types disponibles)
            echo "\nTypes de relations disponibles :\n";
            echo "  1. ManyToOne (Plusieurs {$name} appartiennent à un {$detectedRelation['relatedModel']}) [défaut]\n";
            echo "  2. OneToOne (Un {$name} a un seul {$detectedRelation['relatedModel']})\n";
            echo "  3. OneToMany (Un {$name} a plusieurs {$detectedRelation['relatedModel']})\n";
            echo "  4. ManyToMany (Plusieurs {$name} ont plusieurs {$detectedRelation['relatedModel']})\n";
            
            $relationChoice = $this->ask("Type de relation (1-4) [1] : ", "1");
            
            $typeMap = [
                '1' => 'ManyToOne',
                '2' => 'OneToOne',
                '3' => 'OneToMany',
                '4' => 'ManyToMany'
            ];
            
            $relationType = $typeMap[$relationChoice] ?? 'ManyToOne';
            $detectedRelation['type'] = $relationType;
            
            $confirm = $this->askYesNo("Confirmer cette relation {$relationType} ? (o/n) [o] : ", true);
            
            if ($confirm) {
                $nullable = $this->askYesNo("Nullable ? (o/n) [o] : ", true);
                
                // Retourner une propriété avec un flag de relation
                return [
                    'name' => $name,
                    'type' => 'int', // Les clés étrangères sont toujours int
                    'nullable' => $nullable,
                    'comment' => "Clé étrangère vers {$detectedRelation['relatedModel']}",
                    'isRelation' => true,
                    'relation' => $detectedRelation
                ];
            }
        }

        // ─────────────────────────────────────────────────────────────
        // AUTO-DÉTECTION DU TYPE SELON LE NOM DE LA PROPRIÉTÉ
        // ─────────────────────────────────────────────────────────────
        $suggestedType = $this->detectTypeFromPropertyName($name);
        $suggestedTypeName = $this->getTypeDisplayName($suggestedType);

        echo "\nTypes disponibles :\n";
        echo "  1. string (VARCHAR/TEXT)\n";
        echo "  2. int (INTEGER)\n";
        echo "  3. float (DECIMAL)\n";
        echo "  4. bool (BOOLEAN/TINYINT)\n";
        echo "  5. datetime (DATETIME)\n";
        echo "  6. text (TEXT)\n";
        echo "  7. email (VARCHAR avec validation email)\n";

        // Afficher le type suggéré
        if ($suggestedType !== 'string') {
            echo "\n💡 Type suggéré pour '{$name}' : {$suggestedTypeName}\n";
        }
        
        $defaultChoice = $this->typeToChoice($suggestedType);
        $typeChoice = $this->ask("Type (1-7) [{$defaultChoice}] : ", $defaultChoice);
        $type = $this->mapTypeChoice($typeChoice);

        $nullable = $this->askYesNo("Nullable ? (o/n) [o] : ", true);

        return [
            'name' => $name,
            'type' => $type,
            'nullable' => $nullable,
            'comment' => '',
            'isRelation' => false
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉTECTER LE TYPE DEPUIS LE NOM D'UNE PROPRIÉTÉ
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Patterns reconnus :
     * - *_at, *At (createdAt, deletedAt, publishedAt) → datetime
     * - email, *Email → email
     * - is*, has* (isActive, hasAccess) → bool
     * - price, amount, *Price, *Amount → float
     * - count, *Count, quantity, age, *_count → int
     * - description, content, body, *Description → text
     */
    private function detectTypeFromPropertyName(string $name): string
    {
        $lower = strtolower($name);
        $normalized = strtolower(preg_replace('/([A-Z])/', '_$1', $name));
        
        // DateTime patterns
        if (preg_match('/(_at|At)$/', $name) || in_array($lower, ['date', 'datetime', 'birthday', 'birthdate'])) {
            return 'datetime';
        }
        
        // Email pattern
        if ($lower === 'email' || str_ends_with($lower, 'email')) {
            return 'email';
        }
        
        // Boolean patterns (is*, has*, can*, should*)
        if (preg_match('/^(is|has|can|should|was|will|do|does)[A-Z]/', $name) ||
            in_array($lower, ['active', 'enabled', 'visible', 'verified', 'published', 'deleted'])) {
            return 'bool';
        }
        
        // Float patterns (price, amount, rate, percentage, salary...)
        if (in_array($lower, ['price', 'amount', 'rate', 'percentage', 'salary', 'wage', 'cost', 'fee', 'total', 'subtotal', 'tax', 'discount', 'balance']) ||
            preg_match('/(price|amount|rate|cost|fee|total|tax|discount)$/i', $name)) {
            return 'float';
        }
        
        // Integer patterns (count, quantity, age, number, position, order...)
        if (in_array($lower, ['count', 'quantity', 'age', 'number', 'position', 'order', 'rank', 'priority', 'level', 'score', 'points', 'views', 'likes', 'stock']) ||
            preg_match('/(_count|Count|_number|Number|_qty|Qty)$/', $name)) {
            return 'int';
        }
        
        // Text patterns (description, content, body, bio, summary...)
        if (in_array($lower, ['description', 'content', 'body', 'bio', 'biography', 'summary', 'notes', 'comment', 'message', 'text', 'html', 'markdown'])) {
            return 'text';
        }
        
        // Default
        return 'string';
    }

    /**
     * Convertit un type en numéro de choix
     */
    private function typeToChoice(string $type): string
    {
        return match ($type) {
            'string' => '1',
            'int' => '2',
            'float' => '3',
            'bool' => '4',
            'datetime' => '5',
            'text' => '6',
            'email' => '7',
            default => '1'
        };
    }

    /**
     * Nom d'affichage pour un type
     */
    private function getTypeDisplayName(string $type): string
    {
        return match ($type) {
            'string' => 'string (VARCHAR)',
            'int' => 'int (INTEGER)',
            'float' => 'float (DECIMAL)',
            'bool' => 'bool (BOOLEAN)',
            'datetime' => 'datetime (DATETIME)',
            'text' => 'text (TEXT)',
            'email' => 'email (VARCHAR)',
            default => $type
        };
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉTECTER UNE RELATION DEPUIS LE NOM D'UNE PROPRIÉTÉ
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Détecte automatiquement les relations basées sur les noms de propriétés :
     * - categoryId, category_id → ManyToOne vers Category
     * - userId, user_id → ManyToOne vers User
     * etc.
     */
    private function detectRelationFromPropertyName(string $propertyName): ?array
    {
        // Normaliser le nom (categoryId ou category_id → category)
        $normalized = strtolower($propertyName);
        $normalized = str_replace('_', '', $normalized);
        
        // Vérifier si ça se termine par "id" (categoryId, userId, etc.)
        if (!str_ends_with($normalized, 'id')) {
            return null;
        }
        
        // Extraire le nom du modèle (categoryId → Category)
        $modelName = substr($normalized, 0, -2); // Enlever "id"
        if (empty($modelName)) {
            return null;
        }
        
        // Capitaliser la première lettre (category → Category)
        $modelName = ucfirst($modelName);
        
        // Vérifier si le modèle existe
        $modelClass = "App\\Model\\{$modelName}";
        if (!class_exists($modelClass)) {
            // Proposer quand même la relation, l'utilisateur pourra créer le modèle après
            echo "⚠️  Le modèle {$modelName} n'existe pas encore. Il sera créé automatiquement si nécessaire.\n";
        }
        
        // Déterminer la clé étrangère (categoryId → category_id)
        $foreignKey = $this->camelToSnake($propertyName);
        
        return [
            'type' => 'ManyToOne',
            'relatedModel' => $modelName,
            'foreignKey' => $foreignKey,
            'localKey' => 'id'
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR CAMELCASE EN SNAKE_CASE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function camelToSnake(string $string): string
    {
        // Si c'est déjà en snake_case, le retourner tel quel
        if (str_contains($string, '_')) {
            return strtolower($string);
        }
        
        // Convertir camelCase en snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DEMANDER UNE RELATION
     * ═══════════════════════════════════════════════════════════════════
     */
    private function askRelation(): ?array
    {
        echo "\nTypes de relations :\n";
        echo "  1. OneToMany (Un modèle a plusieurs X)\n";
        echo "  2. ManyToOne (Plusieurs modèles appartiennent à un X)\n";
        echo "  3. OneToOne (Un modèle a un seul X)\n";
        echo "  4. ManyToMany (Plusieurs modèles ont plusieurs X)\n";

        $relationType = $this->ask("Type de relation (1-4) : ");
        if (empty($relationType)) {
            return null;
        }

        $relatedModel = $this->ask("Modèle lié (ex: User, Post) : ");
        if (empty($relatedModel)) {
            return null;
        }

        $foreignKey = $this->ask("Clé étrangère (ex: user_id) [auto] : ");
        if (empty($foreignKey)) {
            $foreignKey = null; // Sera généré automatiquement
        }

        $localKey = $this->ask("Clé locale (ex: id) [id] : ", "id");

        $typeMap = [
            '1' => 'OneToMany',
            '2' => 'ManyToOne',
            '3' => 'OneToOne',
            '4' => 'ManyToMany'
        ];

        return [
            'type' => $typeMap[$relationType] ?? 'ManyToOne',
            'relatedModel' => $relatedModel,
            'foreignKey' => $foreignKey,
            'localKey' => $localKey
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * MAPPER LE CHOIX DE TYPE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function mapTypeChoice(string $choice): string
    {
        return match ($choice) {
            '1' => 'string',
            '2' => 'int',
            '3' => 'float',
            '4' => 'bool',
            '5' => 'datetime',
            '6' => 'text',
            '7' => 'email',
            default => 'string'
        };
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DEMANDER UNE RÉPONSE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function ask(string $question, string $default = ""): string
    {
        echo $question;
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        return $line ?: $default;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DEMANDER OUI/NON
     * ═══════════════════════════════════════════════════════════════════
     */
    private function askYesNo(string $question, bool $default = true): bool
    {
        $response = $this->ask($question);
        if (empty($response)) {
            return $default;
        }
        return in_array(strtolower($response), ['o', 'oui', 'y', 'yes', '1', 'true']);
    }
}

