<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔍 SCHEMAANALYZER - Analyse et comparaison des schémas de base de données
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Compare le schéma actuel d'une table en base de données avec les
 * propriétés d'un modèle PHP pour détecter les différences.
 * 
 * FONCTIONNALITÉS :
 * -----------------
 * - Récupérer le schéma d'une table depuis la BDD
 * - Analyser les propriétés d'un modèle PHP
 * - Comparer et retourner les différences (ajouts, suppressions, modifications)
 * 
 * USAGE :
 * -------
 * $analyzer = new SchemaAnalyzer($pdo);
 * $diff = $analyzer->getDiff('App\Model\User', 'users');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Migration;

use PDO;
use ReflectionClass;
use ReflectionProperty;

class SchemaAnalyzer
{
    private PDO $pdo;
    private string $driver;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE SCHÉMA D'UNE TABLE DEPUIS LA BDD
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $tableName Nom de la table
     * @return array Schéma de la table [colonne => [type, nullable, default, ...]]
     */
    public function getTableSchema(string $tableName): array
    {
        if (!$this->tableExists($tableName)) {
            return [];
        }

        switch ($this->driver) {
            case 'mysql':
                return $this->getMySQLSchema($tableName);
            case 'pgsql':
                return $this->getPostgreSQLSchema($tableName);
            case 'sqlite':
                return $this->getSQLiteSchema($tableName);
            default:
                throw new \RuntimeException("Driver non supporté: {$this->driver}");
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UNE TABLE EXISTE
     * ═══════════════════════════════════════════════════════════════════
     */
    public function tableExists(string $tableName): bool
    {
        try {
            switch ($this->driver) {
                case 'mysql':
                    // Utiliser INFORMATION_SCHEMA pour MySQL (plus fiable avec PDO)
                    $sql = "SELECT COUNT(*) FROM information_schema.tables 
                            WHERE table_schema = DATABASE() AND table_name = :table";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['table' => $tableName]);
                    return (int) $stmt->fetchColumn() > 0;

                case 'pgsql':
                    $sql = "SELECT EXISTS (
                        SELECT FROM information_schema.tables 
                        WHERE table_schema = 'public' 
                        AND table_name = :table
                    )";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['table' => $tableName]);
                    return (bool) $stmt->fetchColumn();

                case 'sqlite':
                    $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name = :table";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['table' => $tableName]);
                    return $stmt->fetch() !== false;

                default:
                    return false;
            }
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SCHÉMA MYSQL
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getMySQLSchema(string $tableName): array
    {
        $sql = "DESCRIBE `{$tableName}`";
        $stmt = $this->pdo->query($sql);
        $columns = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = [
                'type' => $this->normalizeMySQLType($row['Type']),
                'nullable' => $row['Null'] === 'YES',
                'default' => $row['Default'],
                'key' => $row['Key'],
                'extra' => $row['Extra'],
                'raw_type' => $row['Type']
            ];
        }

        return $columns;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SCHÉMA POSTGRESQL
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getPostgreSQLSchema(string $tableName): array
    {
        $sql = "SELECT 
                    column_name, 
                    data_type, 
                    is_nullable, 
                    column_default,
                    character_maximum_length
                FROM information_schema.columns 
                WHERE table_name = :table 
                AND table_schema = 'public'
                ORDER BY ordinal_position";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['table' => $tableName]);
        $columns = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['column_name']] = [
                'type' => $this->normalizePostgreSQLType($row['data_type'], $row['character_maximum_length']),
                'nullable' => $row['is_nullable'] === 'YES',
                'default' => $row['column_default'],
                'raw_type' => $row['data_type']
            ];
        }

        return $columns;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SCHÉMA SQLITE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getSQLiteSchema(string $tableName): array
    {
        $sql = "PRAGMA table_info(`{$tableName}`)";
        $stmt = $this->pdo->query($sql);
        $columns = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['name']] = [
                'type' => $this->normalizeSQLiteType($row['type']),
                'nullable' => $row['notnull'] == 0,
                'default' => $row['dflt_value'],
                'pk' => $row['pk'] == 1,
                'raw_type' => $row['type']
            ];
        }

        return $columns;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE SCHÉMA DEPUIS UN MODÈLE PHP
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $modelClass Classe du modèle (ex: App\Model\User)
     * @return array Schéma du modèle [colonne => [type, nullable, ...]]
     */
    public function getModelSchema(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            throw new \RuntimeException("La classe {$modelClass} n'existe pas");
        }

        $reflection = new ReflectionClass($modelClass);
        // Chercher les propriétés PRIVATE (c'est là où les vraies colonnes sont définies)
        $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);
        
        $columns = [];
        
        // Colonnes et propriétés à ignorer (gérées automatiquement ou internes)
        $ignoredProperties = ['id', 'created_at', 'updated_at', 'attributes', 'exists', 'table', 'primaryKey'];
        
        foreach ($properties as $property) {
            $name = $property->getName();
            
            // Ignorer les propriétés de la classe parente Model
            if ($property->getDeclaringClass()->getName() !== $modelClass) {
                continue;
            }
            
            // Ignorer les propriétés statiques
            if ($property->isStatic()) {
                continue;
            }
            
            // Convertir camelCase en snake_case
            $columnName = $this->camelToSnake($name);
            
            // Ignorer les propriétés système
            if (in_array($name, $ignoredProperties) || in_array($columnName, $ignoredProperties)) {
                continue;
            }
            
            // Récupérer le type
            $type = $this->getPropertyType($property);
            
            // Ignorer les relations (types objets qui ne sont pas des types basiques)
            if ($this->isRelationType($type)) {
                continue;
            }
            
            $nullable = $this->isPropertyNullable($property);
            
            $columns[$columnName] = [
                'type' => $this->phpTypeToSQLType($type),
                'nullable' => $nullable,
                'php_type' => $type
            ];
        }
        
        return $columns;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * COMPARER DEUX SCHÉMAS ET RETOURNER LES DIFFÉRENCES
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param array $dbSchema Schéma de la base de données
     * @param array $modelSchema Schéma du modèle PHP
     * @return array Différences [added => [], dropped => [], modified => []]
     */
    public function compareSchemas(array $dbSchema, array $modelSchema): array
    {
        $diff = [
            'added' => [],    // Colonnes à ajouter
            'dropped' => [],  // Colonnes à supprimer
            'modified' => []  // Colonnes à modifier
        ];
        
        // Colonnes système à ignorer dans la comparaison
        $systemColumns = ['id', 'created_at', 'updated_at'];
        
        // Colonnes à ajouter (présentes dans le modèle mais pas en BDD)
        foreach ($modelSchema as $column => $modelDef) {
            if (!isset($dbSchema[$column])) {
                $diff['added'][$column] = $modelDef;
            }
        }
        
        // Colonnes à supprimer (présentes en BDD mais pas dans le modèle)
        foreach ($dbSchema as $column => $dbDef) {
            // Ne pas supprimer les colonnes système
            if (in_array($column, $systemColumns)) {
                continue;
            }
            
            if (!isset($modelSchema[$column])) {
                $diff['dropped'][$column] = $dbDef;
            }
        }
        
        // Colonnes à modifier (type différent)
        foreach ($modelSchema as $column => $modelDef) {
            if (isset($dbSchema[$column])) {
                $dbDef = $dbSchema[$column];
                
                // Comparer les types normalisés
                $dbType = $this->normalizeTypeForComparison($dbDef['type']);
                $modelType = $this->normalizeTypeForComparison($modelDef['type']);
                
                if ($dbType !== $modelType) {
                    $diff['modified'][$column] = [
                        'from' => $dbDef,
                        'to' => $modelDef
                    ];
                }
                
                // Comparer nullable (optionnel, peut être activé plus tard)
                // if ($dbDef['nullable'] !== $modelDef['nullable']) { ... }
            }
        }
        
        return $diff;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * OBTENIR LES DIFFÉRENCES ENTRE UN MODÈLE ET SA TABLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $modelClass Classe du modèle
     * @param string $tableName Nom de la table
     * @return array Différences
     */
    public function getDiff(string $modelClass, string $tableName): array
    {
        $dbSchema = $this->getTableSchema($tableName);
        $modelSchema = $this->getModelSchema($modelClass);
        
        return $this->compareSchemas($dbSchema, $modelSchema);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HELPERS : NORMALISATION DES TYPES
     * ═══════════════════════════════════════════════════════════════════
     */

    private function normalizeMySQLType(string $type): string
    {
        // Nettoyer le type (ex: "varchar(255)" -> "varchar", "int(11)" -> "int")
        $type = strtolower($type);
        
        if (preg_match('/^(\w+)/', $type, $matches)) {
            $baseType = $matches[1];
            
            // Normaliser
            $mapping = [
                'int' => 'integer',
                'bigint' => 'bigint',
                'tinyint' => 'boolean',
                'varchar' => 'string',
                'text' => 'text',
                'longtext' => 'text',
                'datetime' => 'datetime',
                'timestamp' => 'datetime',
                'date' => 'date',
                'decimal' => 'decimal',
                'float' => 'float',
                'double' => 'float',
            ];
            
            return $mapping[$baseType] ?? $baseType;
        }
        
        return $type;
    }

    private function normalizePostgreSQLType(string $type, ?int $maxLength = null): string
    {
        $type = strtolower($type);
        
        $mapping = [
            'integer' => 'integer',
            'bigint' => 'bigint',
            'smallint' => 'integer',
            'character varying' => 'string',
            'varchar' => 'string',
            'text' => 'text',
            'boolean' => 'boolean',
            'timestamp without time zone' => 'datetime',
            'timestamp with time zone' => 'datetime',
            'date' => 'date',
            'numeric' => 'decimal',
            'real' => 'float',
            'double precision' => 'float',
        ];
        
        return $mapping[$type] ?? $type;
    }

    private function normalizeSQLiteType(string $type): string
    {
        $type = strtoupper($type);
        
        $mapping = [
            'INTEGER' => 'integer',
            'TEXT' => 'text',
            'VARCHAR' => 'string',
            'REAL' => 'float',
            'BLOB' => 'blob',
            'BOOLEAN' => 'boolean',
            'DATETIME' => 'datetime',
            'DATE' => 'date',
        ];
        
        // Gérer les types avec taille (VARCHAR(255))
        if (preg_match('/^(\w+)/', $type, $matches)) {
            $baseType = strtoupper($matches[1]);
            return $mapping[$baseType] ?? strtolower($baseType);
        }
        
        return $mapping[$type] ?? strtolower($type);
    }

    private function normalizeTypeForComparison(string $type): string
    {
        $type = strtolower($type);
        
        // Grouper les types similaires
        $groups = [
            'string' => ['string', 'varchar', 'character varying'],
            'integer' => ['integer', 'int', 'bigint', 'smallint'],
            'text' => ['text', 'longtext', 'mediumtext'],
            'float' => ['float', 'double', 'real', 'double precision'],
            'datetime' => ['datetime', 'timestamp', 'timestamp without time zone', 'timestamp with time zone'],
        ];
        
        foreach ($groups as $normalized => $types) {
            if (in_array($type, $types)) {
                return $normalized;
            }
        }
        
        return $type;
    }

    private function phpTypeToSQLType(string $phpType): string
    {
        $mapping = [
            'string' => 'string',
            'int' => 'integer',
            'float' => 'float',
            'bool' => 'boolean',
            'array' => 'text',  // JSON sérialisé
            'DateTime' => 'datetime',
            '\DateTime' => 'datetime',
            'DateTimeInterface' => 'datetime',
            '\DateTimeInterface' => 'datetime',
        ];
        
        return $mapping[$phpType] ?? 'string';
    }

    private function getPropertyType(ReflectionProperty $property): string
    {
        $type = $property->getType();
        
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
        
        // Fallback: essayer de lire le docblock
        $docComment = $property->getDocComment();
        if ($docComment && preg_match('/@var\s+(\S+)/', $docComment, $matches)) {
            return $matches[1];
        }
        
        return 'string';
    }

    private function isPropertyNullable(ReflectionProperty $property): bool
    {
        $type = $property->getType();
        
        if ($type !== null) {
            return $type->allowsNull();
        }
        
        return true; // Par défaut nullable si pas de type
    }

    private function isRelationType(string $type): bool
    {
        // Types PHP basiques
        $basicTypes = ['string', 'int', 'float', 'bool', 'array', 'DateTime', '\DateTime', 'DateTimeInterface', '\DateTimeInterface'];
        
        // Si le type contient un backslash mais n'est pas un DateTime, c'est probablement une relation
        if (str_contains($type, '\\') && !in_array($type, $basicTypes)) {
            return true;
        }
        
        return false;
    }

    private function camelToSnake(string $camel): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $camel));
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GETTERS
     * ═══════════════════════════════════════════════════════════════════
     */
    
    public function getDriver(): string
    {
        return $this->driver;
    }
}
