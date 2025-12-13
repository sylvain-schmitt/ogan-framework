<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔧 MIGRATION GENERATOR - Générateur de migrations depuis les modèles
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère automatiquement des fichiers de migration à partir des modèles.
 * Analyse les propriétés privées et leurs types pour créer le schéma SQL.
 * 
 * UTILISATION :
 * -------------
 * 
 * $generator = new MigrationGenerator();
 * $generator->generateFromModel('App\\Model\\User', 'database/migrations');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Migration;

use ReflectionClass;
use ReflectionProperty;

class MigrationGenerator
{
    /**
     * Mapping des types PHP vers les types SQL
     */
    private array $typeMapping = [
        'int' => 'INT',
        'integer' => 'INT',
        'float' => 'FLOAT',
        'double' => 'DOUBLE',
        'string' => 'VARCHAR(255)',
        'bool' => 'BOOLEAN',
        'boolean' => 'BOOLEAN',
        'datetime' => 'TIMESTAMP',
        'date' => 'DATE',
        'text' => 'TEXT',
    ];

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER UNE MIGRATION DEPUIS UN MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $modelClass Nom complet de la classe du modèle (avec namespace)
     * @param string $migrationsPath Chemin vers le dossier des migrations
     * @param bool $force Forcer la création même si le fichier existe
     * @return string Chemin du fichier de migration créé
     * @throws \RuntimeException Si le modèle n'existe pas ou n'est pas valide
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function generateFromModel(string $modelClass, string $migrationsPath, bool $force = false, ?\PDO $pdo = null): string
    {
        // Vérifier que la classe existe
        if (!class_exists($modelClass)) {
            throw new \RuntimeException("La classe {$modelClass} n'existe pas.");
        }

        // Charger la classe et vérifier qu'elle étend Model
        $reflection = new ReflectionClass($modelClass);
        if (!$reflection->isSubclassOf(\Ogan\Database\Model::class)) {
            throw new \RuntimeException("La classe {$modelClass} doit étendre Ogan\\Database\\Model.");
        }

        // Récupérer le nom de la table
        $tableName = $this->getTableName($modelClass);
        
        // Si une connexion PDO est fournie, vérifier si la table existe déjà
        if ($pdo !== null) {
            $analyzer = new SchemaAnalyzer($pdo);
            
            if ($analyzer->tableExists($tableName)) {
                // La table existe, générer une migration ALTER TABLE si des changements sont détectés
                return $this->generateAlterFromModel($modelClass, $migrationsPath, $pdo, $force);
            }
        }
        
        // Analyser les propriétés du modèle
        $properties = $this->analyzeModelProperties($reflection);
        
        // Générer le nom du fichier de migration
        $timestamp = date('Y_m_d_His');
        $className = $this->modelClassToMigrationName($modelClass);
        $filename = "{$timestamp}_{$className}.php";
        $filepath = rtrim($migrationsPath, '/') . '/' . $filename;

        // Vérifier si le fichier existe déjà
        if (file_exists($filepath) && !$force) {
            throw new \RuntimeException("Le fichier de migration existe déjà : {$filename}");
        }

        // Générer le contenu de la migration
        $content = $this->generateMigrationContent($modelClass, $tableName, $properties);

        // Créer le dossier s'il n'existe pas
        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        // Écrire le fichier
        file_put_contents($filepath, $content);
        
        // S'assurer que le fichier est bien écrit et accessible
        // Cela aide certains IDEs à détecter le nouveau fichier
        clearstatcache(true, $filepath);
        
        // Vérifier que le fichier existe bien
        if (!file_exists($filepath)) {
            throw new \RuntimeException("Impossible de créer le fichier de migration : {$filepath}");
        }

        return $filepath;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER UNE MIGRATION ALTER TABLE DEPUIS UN MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Compare le schéma de la base de données avec le modèle PHP et génère
     * une migration ALTER TABLE si des différences sont détectées.
     * 
     * @param string $modelClass Nom complet de la classe du modèle
     * @param string $migrationsPath Chemin vers le dossier des migrations
     * @param \PDO $pdo Connexion à la base de données
     * @param bool $force Forcer la création même si aucun changement
     * @return string Chemin du fichier de migration créé ou message
     * @throws \RuntimeException Si le modèle n'existe pas
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function generateAlterFromModel(string $modelClass, string $migrationsPath, \PDO $pdo, bool $force = false): string
    {
        // Vérifier que la classe existe
        if (!class_exists($modelClass)) {
            throw new \RuntimeException("La classe {$modelClass} n'existe pas.");
        }

        // Charger la classe et vérifier qu'elle étend Model
        $reflection = new ReflectionClass($modelClass);
        if (!$reflection->isSubclassOf(\Ogan\Database\Model::class)) {
            throw new \RuntimeException("La classe {$modelClass} doit étendre Ogan\\Database\\Model.");
        }

        // Récupérer le nom de la table
        $tableName = $this->getTableName($modelClass);
        
        // Analyser les schémas
        $analyzer = new SchemaAnalyzer($pdo);
        
        // Vérifier que la table existe
        if (!$analyzer->tableExists($tableName)) {
            // La table n'existe pas, générer une migration CREATE TABLE
            return $this->generateFromModel($modelClass, $migrationsPath, $force);
        }
        
        // Récupérer les différences
        $diff = $analyzer->getDiff($modelClass, $tableName);
        
        // Vérifier s'il y a des changements
        if (empty($diff['added']) && empty($diff['dropped']) && empty($diff['modified'])) {
            if (!$force) {
                throw new \RuntimeException("Aucun changement détecté pour le modèle {$modelClass}.");
            }
        }
        
        // Récupérer le schéma actuel pour SQLite
        $currentSchema = $analyzer->getTableSchema($tableName);
        
        // Générer la migration ALTER TABLE
        $generator = new AlterTableGenerator();
        [$className, $content, $timestamp] = $generator->generateMigrationContent($tableName, $diff, $currentSchema);
        
        // Créer le fichier
        $filename = "{$timestamp}_alter_{$this->camelToSnake(substr($modelClass, strrpos($modelClass, '\\') + 1))}_table.php";
        $filepath = rtrim($migrationsPath, '/') . '/' . $filename;
        
        // Vérifier si le fichier existe déjà
        if (file_exists($filepath) && !$force) {
            throw new \RuntimeException("Le fichier de migration existe déjà : {$filename}");
        }
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }
        
        // Écrire le fichier
        file_put_contents($filepath, $content);
        clearstatcache(true, $filepath);
        
        if (!file_exists($filepath)) {
            throw new \RuntimeException("Impossible de créer le fichier de migration : {$filepath}");
        }
        
        return $filepath;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * OBTENIR LES DIFFÉRENCES ENTRE UN MODÈLE ET SA TABLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $modelClass Classe du modèle
     * @param \PDO $pdo Connexion à la base de données
     * @return array Différences [added, dropped, modified, table_exists]
     */
    public function getDiff(string $modelClass, \PDO $pdo): array
    {
        $tableName = $this->getTableName($modelClass);
        $analyzer = new SchemaAnalyzer($pdo);
        
        if (!$analyzer->tableExists($tableName)) {
            return [
                'table_exists' => false,
                'added' => [],
                'dropped' => [],
                'modified' => []
            ];
        }
        
        $diff = $analyzer->getDiff($modelClass, $tableName);
        $diff['table_exists'] = true;
        
        return $diff;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE NOM DE LA TABLE DU MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getTableName(string $modelClass): string
    {
        // Utiliser la méthode statique getTableName() du modèle
        if (method_exists($modelClass, 'getTableName')) {
            return $modelClass::getTableName();
        }

        // Sinon, déduire depuis le nom de la classe
        $shortName = substr($modelClass, strrpos($modelClass, '\\') + 1);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANALYSER LES PROPRIÉTÉS DU MODÈLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param ReflectionClass $reflection Réflexion de la classe
     * @return array Propriétés analysées avec leurs types et contraintes
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    private function analyzeModelProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        $reflectionProperties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);

        foreach ($reflectionProperties as $property) {
            $name = $property->getName();
            
            // Ignorer les propriétés spéciales
            if (in_array($name, ['attributes', 'exists'])) {
                continue;
            }

            // Convertir le nom de propriété en nom de colonne (camelCase → snake_case)
            $columnName = $this->camelToSnake($name);
            
            // Récupérer le type depuis le docblock ou le type déclaré
            $type = $this->getPropertyType($property);
            
            // Détecter si c'est une clé étrangère (categoryId, category_id, categoryid, etc.)
            $isForeignKey = false;
            $normalizedName = strtolower($name);
            if ($normalizedName !== 'id' && (
                str_ends_with($normalizedName, 'id') || 
                str_ends_with($columnName, '_id') ||
                preg_match('/^[a-z]+id$/', $normalizedName) // categoryid, userid, etc.
            )) {
                $isForeignKey = true;
                $type = 'int';
            }
            
            // Ajuster les types spécifiques selon le nom de la colonne
            if ($columnName === 'content' || $columnName === 'body' || $columnName === 'description') {
                $type = 'text';
            } elseif ($isForeignKey) {
                $type = 'int';
            } elseif ($columnName === 'password') {
                $type = 'string'; // VARCHAR(255) pour les mots de passe
            }
            
            // Déterminer si c'est la clé primaire
            $isPrimaryKey = ($name === 'id' || $columnName === 'id');
            
            // Déterminer si c'est nullable
            $isNullable = $this->isPropertyNullable($property, $type);
            
            // Déterminer si c'est unique (basé sur le nom ou des conventions)
            $isUnique = ($columnName === 'email' || $columnName === 'slug');
            
            // Déterminer si c'est un index (email ou clé étrangère)
            $isIndexed = ($columnName === 'email' || $isForeignKey);

            $properties[] = [
                'name' => $columnName,
                'type' => $type,
                'phpType' => $this->getPhpType($type),
                'isPrimaryKey' => $isPrimaryKey,
                'isNullable' => $isNullable,
                'isUnique' => $isUnique,
                'isIndexed' => $isIndexed,
                'isAutoIncrement' => $isPrimaryKey,
            ];
        }

        return $properties;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE TYPE D'UNE PROPRIÉTÉ
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getPropertyType(ReflectionProperty $property): string
    {
        // PHP 7.4+ : type déclaré
        if ($property->hasType()) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                
                // Gérer les types nullable
                if ($type->allowsNull() && $typeName !== 'mixed') {
                    return $typeName;
                }
                
                return $typeName;
            }
        }

        // Fallback : analyser le docblock
        $docComment = $property->getDocComment();
        if ($docComment && preg_match('/@var\s+(\S+)/', $docComment, $matches)) {
            $type = $matches[1];
            // Nettoyer le type (enlever |null, etc.)
            $type = preg_replace('/\|.*/', '', $type);
            return trim($type, '?');
        }

        // Type par défaut basé sur le nom
        $name = $property->getName();
        if (str_contains($name, 'email')) {
            return 'string';
        }
        if (str_contains($name, 'password')) {
            return 'string';
        }
        if (str_contains($name, 'created') || str_contains($name, 'updated')) {
            return 'datetime';
        }

        return 'string';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UNE PROPRIÉTÉ EST NULLABLE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function isPropertyNullable(ReflectionProperty $property, string $type): bool
    {
        // Vérifier le type déclaré
        if ($property->hasType()) {
            $reflectionType = $property->getType();
            if ($reflectionType instanceof \ReflectionNamedType) {
                return $reflectionType->allowsNull();
            }
        }

        // Vérifier le docblock
        $docComment = $property->getDocComment();
        if ($docComment && preg_match('/@var\s+(\S+)/', $docComment, $matches)) {
            return str_contains($matches[1], 'null') || str_starts_with($matches[1], '?');
        }

        // Par défaut, les propriétés non-id sont nullable
        return $property->getName() !== 'id';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR UN TYPE PHP EN TYPE SQL
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getPhpType(string $type): string
    {
        $type = strtolower($type);
        
        // Gérer les types avec namespace
        $type = substr($type, strrpos($type, '\\') + 1);
        
        return $this->typeMapping[$type] ?? 'VARCHAR(255)';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR CAMELCASE EN SNAKE_CASE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function camelToSnake(string $camel): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $camel));
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR LE NOM DE CLASSE EN NOM DE MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     */
    private function modelClassToMigrationName(string $modelClass): string
    {
        $shortName = substr($modelClass, strrpos($modelClass, '\\') + 1);
        
        // Enlever "Model" ou "Entity" du nom si présent
        $shortName = preg_replace('/Model$|Entity$/', '', $shortName);
        
        // Convertir en snake_case
        $snake = $this->camelToSnake($shortName);
        
        // Créer le nom de migration
        return "create_{$snake}_table";
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE CONTENU DU FICHIER DE MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateMigrationContent(string $modelClass, string $tableName, array $properties): string
    {
        $shortClassName = substr($modelClass, strrpos($modelClass, '\\') + 1);
        $migrationClassName = $this->filenameToClassName($this->modelClassToMigrationName($modelClass));
        
        // Générer le SQL pour MySQL
        $mysqlColumns = $this->generateMySQLColumns($properties);
        $mysqlIndexesInline = $this->generateMySQLIndexesInline($properties);
        $mysqlIndexesInline = $this->generateMySQLIndexesInline($properties);
        
        // Générer le SQL pour PostgreSQL
        $pgsqlColumns = $this->generatePostgreSQLColumns($properties);
        $pgsqlIndexes = $this->generatePostgreSQLIndexes($properties, $tableName);
        
        // Générer le SQL pour SQLite
        $sqliteColumns = $this->generateSQLiteColumns($properties);
        $sqliteIndexes = $this->generateSQLiteIndexes($properties, $tableName);

        return <<<PHP
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * MIGRATION : Création de la table {$tableName}
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Cette migration a été générée automatiquement depuis le modèle {$shortClassName}.
 * 
 * Table : {$tableName}
 * Modèle : {$modelClass}
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\\Database\\Migration;

use Ogan\\Database\\Migration\\AbstractMigration;

class {$migrationClassName} extends AbstractMigration
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * APPLIQUER LA MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     */
    public function up(): void
    {
        \$driver = \$this->pdo->getAttribute(\\PDO::ATTR_DRIVER_NAME);

        \$sql = match (strtolower(\$driver)) {
            'mysql', 'mariadb' => "
                CREATE TABLE IF NOT EXISTS {$tableName} (
{$mysqlColumns}{$mysqlIndexesInline}
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql', 'postgresql' => "
                CREATE TABLE IF NOT EXISTS {$tableName} (
{$pgsqlColumns}
                );
{$pgsqlIndexes}
            ",
            'sqlite' => "
                CREATE TABLE IF NOT EXISTS {$tableName} (
{$sqliteColumns}
                );
{$sqliteIndexes}
            ",
            default => throw new \\RuntimeException("Driver de base de données non supporté: {\$driver}")
        };

        \$this->execute(\$sql);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANNULER LA MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     */
    public function down(): void
    {
        \$this->execute("DROP TABLE IF EXISTS {$tableName}");
    }
}

PHP;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES COLONNES POUR MYSQL
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateMySQLColumns(array $properties): string
    {
        $columns = [];
        
        foreach ($properties as $prop) {
            $column = "                    {$prop['name']} ";
            
            // Type SQL
            $sqlType = $prop['phpType'];
            
            // Ajuster les types spécifiques
            if ($prop['name'] === 'id' && $prop['isPrimaryKey']) {
                $column .= 'INT AUTO_INCREMENT PRIMARY KEY';
                $columns[] = $column;
                continue;
            } elseif ($prop['name'] === 'email') {
                $sqlType = 'VARCHAR(255)';
            } elseif ($prop['name'] === 'password') {
                $sqlType = 'VARCHAR(255)';
            } elseif ($prop['name'] === 'content' || $prop['name'] === 'body' || $prop['name'] === 'description') {
                $sqlType = 'TEXT';
            } elseif ($this->isForeignKey($prop['name'])) {
                $sqlType = 'INT';
            } elseif (str_contains($prop['name'], 'created_at') || str_contains($prop['name'], 'updated_at')) {
                if ($prop['name'] === 'updated_at') {
                    $column .= 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
                    $columns[] = $column;
                    continue;
                } else {
                    $column .= 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                    $columns[] = $column;
                    continue;
                }
            }
            
            // Ajouter le type si pas déjà fait
            if (!str_contains($column, 'PRIMARY KEY') && !str_contains($column, 'TIMESTAMP')) {
                $column .= $sqlType;
            }
            
            // Contraintes
            if (!$prop['isNullable'] && !$prop['isPrimaryKey']) {
                $column .= ' NOT NULL';
            }
            
            if ($prop['isUnique'] && !$prop['isPrimaryKey']) {
                $column .= ' UNIQUE';
            }
            
            $columns[] = $column;
        }
        
        return implode(",\n", $columns);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES INDEX POUR MYSQL (INLINE - dans le CREATE TABLE)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateMySQLIndexesInline(array $properties): string
    {
        $indexes = [];
        
        foreach ($properties as $prop) {
            if ($prop['isIndexed'] && !$prop['isPrimaryKey'] && !$prop['isUnique']) {
                $indexName = "idx_{$prop['name']}";
                $indexes[] = ",\n                    INDEX {$indexName} ({$prop['name']})";
            }
        }
        
        return implode('', $indexes);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES COLONNES POUR POSTGRESQL
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generatePostgreSQLColumns(array $properties): string
    {
        $columns = [];
        
        foreach ($properties as $prop) {
            $column = "                    {$prop['name']} ";
            
            if ($prop['name'] === 'id' && $prop['isPrimaryKey']) {
                $column .= "SERIAL PRIMARY KEY";
            } else {
                $sqlType = $prop['phpType'];
                
                if ($prop['name'] === 'email') {
                    $sqlType = 'VARCHAR(255)';
                } elseif ($prop['name'] === 'password') {
                    $sqlType = 'VARCHAR(255)';
                } elseif ($prop['name'] === 'content' || $prop['name'] === 'body' || $prop['name'] === 'description') {
                    $sqlType = 'TEXT';
                } elseif ($this->isForeignKey($prop['name'])) {
                    $sqlType = 'INT';
                } elseif (str_contains($prop['name'], 'created_at') || str_contains($prop['name'], 'updated_at')) {
                    $sqlType = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
                }
                
                $column .= $sqlType;
                
                if (!$prop['isNullable'] && !$prop['isPrimaryKey']) {
                    $column .= ' NOT NULL';
                }
                
                if ($prop['isUnique'] && !$prop['isPrimaryKey']) {
                    $column .= ' UNIQUE';
                }
            }
            
            $columns[] = $column;
        }
        
        return implode(",\n", $columns);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES INDEX POUR POSTGRESQL
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generatePostgreSQLIndexes(array $properties, string $tableName): string
    {
        $indexes = [];
        
        foreach ($properties as $prop) {
            if ($prop['isIndexed'] && !$prop['isPrimaryKey'] && !$prop['isUnique']) {
                $indexName = "idx_{$prop['name']}";
                $indexes[] = "                CREATE INDEX IF NOT EXISTS {$indexName} ON {$tableName}({$prop['name']});";
            }
        }
        
        if (empty($indexes)) {
            return '';
        }
        
        return "\n" . implode("\n", $indexes);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES COLONNES POUR SQLITE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateSQLiteColumns(array $properties): string
    {
        $columns = [];
        
        foreach ($properties as $prop) {
            $column = "                    {$prop['name']} ";
            
            if ($prop['name'] === 'id' && $prop['isPrimaryKey']) {
                $column .= "INTEGER PRIMARY KEY AUTOINCREMENT";
            } else {
                $sqlType = $prop['phpType'];
                
                if ($prop['name'] === 'email') {
                    $sqlType = 'VARCHAR(255)';
                } elseif ($prop['name'] === 'password') {
                    $sqlType = 'VARCHAR(255)';
                } elseif ($prop['name'] === 'content' || $prop['name'] === 'body' || $prop['name'] === 'description') {
                    $sqlType = 'TEXT';
                } elseif ($this->isForeignKey($prop['name'])) {
                    $sqlType = 'INTEGER';
                } elseif (str_contains($prop['name'], 'created_at') || str_contains($prop['name'], 'updated_at')) {
                    $sqlType = 'DATETIME DEFAULT CURRENT_TIMESTAMP';
                }
                
                $column .= $sqlType;
                
                if (!$prop['isNullable'] && !$prop['isPrimaryKey']) {
                    $column .= ' NOT NULL';
                }
                
                if ($prop['isUnique'] && !$prop['isPrimaryKey']) {
                    $column .= ' UNIQUE';
                }
            }
            
            $columns[] = $column;
        }
        
        return implode(",\n", $columns);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES INDEX POUR SQLITE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateSQLiteIndexes(array $properties, string $tableName): string
    {
        $indexes = [];
        
        foreach ($properties as $prop) {
            if ($prop['isIndexed'] && !$prop['isPrimaryKey'] && !$prop['isUnique']) {
                $indexName = "idx_{$prop['name']}";
                $indexes[] = "                CREATE INDEX IF NOT EXISTS {$indexName} ON {$tableName}({$prop['name']});";
            }
        }
        
        if (empty($indexes)) {
            return '';
        }
        
        return "\n" . implode("\n", $indexes);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONVERTIR UN NOM DE FICHIER EN NOM DE CLASSE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function filenameToClassName(string $filename): string
    {
        $parts = explode('_', $filename);
        $parts = array_map('ucfirst', $parts);
        return implode('', $parts);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉTECTER SI UN NOM DE COLONNE EST UNE CLÉ ÉTRANGÈRE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Détecte les clés étrangères selon plusieurs conventions :
     * - categoryId, category_id, categoryid → true
     * - userId, user_id, userid → true
     * - id → false (c'est la clé primaire)
     */
    private function isForeignKey(string $columnName): bool
    {
        // Ne pas considérer "id" comme une clé étrangère
        if (strtolower($columnName) === 'id') {
            return false;
        }
        
        $normalized = strtolower($columnName);
        
        // Détecter les patterns : categoryId, category_id, categoryid
        return (
            str_ends_with($normalized, 'id') || 
            str_ends_with($columnName, '_id') ||
            preg_match('/^[a-z]+id$/', $normalized) // categoryid, userid, etc.
        );
    }
}


