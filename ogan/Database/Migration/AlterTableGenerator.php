<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔧 ALTERTABLEGENERATOR - Génération de migrations ALTER TABLE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère le code SQL pour modifier une table existante (ALTER TABLE).
 * Support de MySQL, PostgreSQL et SQLite (avec recréation de table).
 * 
 * FONCTIONNALITÉS :
 * -----------------
 * - Génération ALTER TABLE ADD COLUMN
 * - Génération ALTER TABLE DROP COLUMN
 * - Génération ALTER TABLE MODIFY COLUMN
 * - Support multi-base (MySQL, PostgreSQL, SQLite)
 * 
 * NOTE SQLITE :
 * -------------
 * SQLite ne supporte pas DROP COLUMN (< 3.35) ni MODIFY COLUMN.
 * La stratégie utilisée est la recréation de table :
 * 1. Créer une nouvelle table avec le bon schéma
 * 2. Copier les données
 * 3. Supprimer l'ancienne table
 * 4. Renommer la nouvelle table
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Migration;

class AlterTableGenerator
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE CONTENU DE LA MIGRATION ALTER TABLE
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $tableName Nom de la table
     * @param array $diff Différences détectées
     * @param array $currentSchema Schéma actuel de la table
     * @return array [className, content]
     */
    public function generateMigrationContent(string $tableName, array $diff, array $currentSchema = []): array
    {
        $timestamp = date('Y_m_d_His');
        // Le nom de classe doit correspondre au pattern du fichier: alter_[table]_table -> AlterTableTable
        $className = 'Alter' . $this->snakeToPascal($tableName) . 'Table';
        
        $upMethods = $this->generateUpMethods($tableName, $diff);
        $downMethods = $this->generateDownMethods($tableName, $diff);
        
        // Générer la migration avec recréation de table pour SQLite
        $sqliteUp = $this->generateSQLiteUp($tableName, $diff, $currentSchema);
        $sqliteDown = $this->generateSQLiteDown($tableName, $diff, $currentSchema);
        
        $content = <<<PHP
<?php

use Ogan\\Database\\Migration\\AbstractMigration;

/**
 * Migration: Modification de la table {$tableName}
 * 
 * Changements :
{$this->generateChangesSummary($diff)}
 */
class {$className} extends AbstractMigration
{
    protected string \$table = '{$tableName}';
    
    /**
     * Appliquer la migration
     */
    public function up(): void
    {
        \$driver = \$this->pdo->getAttribute(\\PDO::ATTR_DRIVER_NAME);
        
        if (\$driver === 'sqlite') {
            // SQLite : Stratégie de recréation de table
            \$this->recreateTable();
        } else {
            // MySQL / PostgreSQL : ALTER TABLE standard
            \$this->alterTable();
        }
    }
    
    /**
     * Annuler la migration
     */
    public function down(): void
    {
        \$driver = \$this->pdo->getAttribute(\\PDO::ATTR_DRIVER_NAME);
        
        if (\$driver === 'sqlite') {
            \$this->recreateTableDown();
        } else {
            \$this->alterTableDown();
        }
    }
    
    /**
     * ALTER TABLE pour MySQL/PostgreSQL
     */
    private function alterTable(): void
    {
        \$driver = \$this->pdo->getAttribute(\\PDO::ATTR_DRIVER_NAME);
        
{$upMethods}
    }
    
    /**
     * Rollback ALTER TABLE pour MySQL/PostgreSQL
     */
    private function alterTableDown(): void
    {
        \$driver = \$this->pdo->getAttribute(\\PDO::ATTR_DRIVER_NAME);
        
{$downMethods}
    }
    
    /**
     * Recréation de table pour SQLite (up)
     */
    private function recreateTable(): void
    {
{$sqliteUp}
    }
    
    /**
     * Recréation de table pour SQLite (down)
     */
    private function recreateTableDown(): void
    {
{$sqliteDown}
    }
}

PHP;

        return [$className, $content, $timestamp];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES MÉTHODES UP (MySQL/PostgreSQL)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateUpMethods(string $tableName, array $diff): string
    {
        $lines = [];
        
        // Colonnes à ajouter
        foreach ($diff['added'] as $column => $def) {
            $sqlType = $this->getSQLType($def['type'], $def['nullable'] ?? true);
            $lines[] = "        // Ajouter la colonne '{$column}'";
            $lines[] = "        if (\$driver === 'mysql') {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE `{$tableName}` ADD COLUMN `{$column}` {$sqlType}\");";
            $lines[] = "        } else {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE \\\"{$tableName}\\\" ADD COLUMN \\\"{$column}\\\" {$sqlType}\");";
            $lines[] = "        }";
            $lines[] = "";
        }
        
        // Colonnes à supprimer
        foreach ($diff['dropped'] as $column => $def) {
            $lines[] = "        // Supprimer la colonne '{$column}'";
            $lines[] = "        if (\$driver === 'mysql') {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE `{$tableName}` DROP COLUMN `{$column}`\");";
            $lines[] = "        } else {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE \\\"{$tableName}\\\" DROP COLUMN \\\"{$column}\\\"\");";
            $lines[] = "        }";
            $lines[] = "";
        }
        
        // Colonnes à modifier
        foreach ($diff['modified'] as $column => $change) {
            $newType = $this->getSQLType($change['to']['type'], $change['to']['nullable'] ?? true);
            $lines[] = "        // Modifier la colonne '{$column}'";
            $lines[] = "        if (\$driver === 'mysql') {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE `{$tableName}` MODIFY COLUMN `{$column}` {$newType}\");";
            $lines[] = "        } else {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE \\\"{$tableName}\\\" ALTER COLUMN \\\"{$column}\\\" TYPE {$newType}\");";
            $lines[] = "        }";
            $lines[] = "";
        }
        
        if (empty($lines)) {
            $lines[] = "        // Aucune modification";
        }
        
        return implode("\n", $lines);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LES MÉTHODES DOWN (MySQL/PostgreSQL)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateDownMethods(string $tableName, array $diff): string
    {
        $lines = [];
        
        // Colonnes ajoutées -> à supprimer dans down
        foreach ($diff['added'] as $column => $def) {
            $lines[] = "        // Supprimer la colonne '{$column}' (ajoutée dans up)";
            $lines[] = "        if (\$driver === 'mysql') {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE `{$tableName}` DROP COLUMN `{$column}`\");";
            $lines[] = "        } else {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE \\\"{$tableName}\\\" DROP COLUMN \\\"{$column}\\\"\");";
            $lines[] = "        }";
            $lines[] = "";
        }
        
        // Colonnes supprimées -> à re-ajouter dans down
        foreach ($diff['dropped'] as $column => $def) {
            $sqlType = $this->getSQLType($def['type'] ?? 'string', $def['nullable'] ?? true);
            $lines[] = "        // Re-ajouter la colonne '{$column}' (supprimée dans up)";
            $lines[] = "        if (\$driver === 'mysql') {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE `{$tableName}` ADD COLUMN `{$column}` {$sqlType}\");";
            $lines[] = "        } else {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE \\\"{$tableName}\\\" ADD COLUMN \\\"{$column}\\\" {$sqlType}\");";
            $lines[] = "        }";
            $lines[] = "";
        }
        
        // Colonnes modifiées -> revenir au type précédent
        foreach ($diff['modified'] as $column => $change) {
            $oldType = $this->getSQLType($change['from']['type'], $change['from']['nullable'] ?? true);
            $lines[] = "        // Rétablir la colonne '{$column}' au type précédent";
            $lines[] = "        if (\$driver === 'mysql') {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE `{$tableName}` MODIFY COLUMN `{$column}` {$oldType}\");";
            $lines[] = "        } else {";
            $lines[] = "            \$this->pdo->exec(\"ALTER TABLE \\\"{$tableName}\\\" ALTER COLUMN \\\"{$column}\\\" TYPE {$oldType}\");";
            $lines[] = "        }";
            $lines[] = "";
        }
        
        if (empty($lines)) {
            $lines[] = "        // Aucune modification à annuler";
        }
        
        return implode("\n", $lines);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LA RECRÉATION DE TABLE POUR SQLITE (UP)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateSQLiteUp(string $tableName, array $diff, array $currentSchema): string
    {
        $lines = [];
        
        // Calculer le nouveau schéma
        $newColumns = $this->calculateNewSchema($currentSchema, $diff);
        $columnList = array_keys($newColumns);
        $commonColumns = array_intersect($columnList, array_keys($currentSchema));
        
        // Étape 1: Créer la table temporaire avec le nouveau schéma
        $lines[] = "        // Étape 1: Créer la table temporaire";
        $lines[] = "        \$createTempSQL = \"CREATE TABLE {$tableName}_new (";
        $lines[] = "            id INTEGER PRIMARY KEY AUTOINCREMENT,";
        
        foreach ($newColumns as $column => $def) {
            $sqlType = $this->getSQLiteType($def['type'], $def['nullable'] ?? true);
            $lines[] = "            {$column} {$sqlType},";
        }
        
        $lines[] = "            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,";
        $lines[] = "            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP";
        $lines[] = "        )\";";
        $lines[] = "        \$this->pdo->exec(\$createTempSQL);";
        $lines[] = "";
        
        // Étape 2: Copier les données
        if (!empty($commonColumns)) {
            $commonColumnsStr = implode(', ', $commonColumns);
            $lines[] = "        // Étape 2: Copier les données";
            $lines[] = "        \$this->pdo->exec(\"INSERT INTO {$tableName}_new (id, {$commonColumnsStr}, created_at, updated_at) SELECT id, {$commonColumnsStr}, created_at, updated_at FROM {$tableName}\");";
            $lines[] = "";
        }
        
        // Étape 3: Supprimer l'ancienne table
        $lines[] = "        // Étape 3: Supprimer l'ancienne table";
        $lines[] = "        \$this->pdo->exec(\"DROP TABLE {$tableName}\");";
        $lines[] = "";
        
        // Étape 4: Renommer la nouvelle table
        $lines[] = "        // Étape 4: Renommer la nouvelle table";
        $lines[] = "        \$this->pdo->exec(\"ALTER TABLE {$tableName}_new RENAME TO {$tableName}\");";
        
        return implode("\n", $lines);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LA RECRÉATION DE TABLE POUR SQLITE (DOWN)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateSQLiteDown(string $tableName, array $diff, array $currentSchema): string
    {
        $lines = [];
        
        // Pour le down, on inverse l'opération : on revient au schéma original (currentSchema)
        $columnList = array_keys($currentSchema);
        
        // Calculer quelles colonnes existent après le up
        $afterUpSchema = $this->calculateNewSchema($currentSchema, $diff);
        $commonColumns = array_intersect($columnList, array_keys($afterUpSchema));
        
        // Étape 1: Créer la table temporaire avec l'ancien schéma
        $lines[] = "        // Étape 1: Créer la table temporaire avec l'ancien schéma";
        $lines[] = "        \$createTempSQL = \"CREATE TABLE {$tableName}_old (";
        $lines[] = "            id INTEGER PRIMARY KEY AUTOINCREMENT,";
        
        foreach ($currentSchema as $column => $def) {
            $sqlType = $this->getSQLiteType($def['type'] ?? 'text', $def['nullable'] ?? true);
            $lines[] = "            {$column} {$sqlType},";
        }
        
        $lines[] = "            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,";
        $lines[] = "            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP";
        $lines[] = "        )\";";
        $lines[] = "        \$this->pdo->exec(\$createTempSQL);";
        $lines[] = "";
        
        // Étape 2: Copier les données
        if (!empty($commonColumns)) {
            $commonColumnsStr = implode(', ', $commonColumns);
            $lines[] = "        // Étape 2: Copier les données";
            $lines[] = "        \$this->pdo->exec(\"INSERT INTO {$tableName}_old (id, {$commonColumnsStr}, created_at, updated_at) SELECT id, {$commonColumnsStr}, created_at, updated_at FROM {$tableName}\");";
            $lines[] = "";
        }
        
        // Étape 3: Supprimer la table modifiée
        $lines[] = "        // Étape 3: Supprimer la table modifiée";
        $lines[] = "        \$this->pdo->exec(\"DROP TABLE {$tableName}\");";
        $lines[] = "";
        
        // Étape 4: Renommer
        $lines[] = "        // Étape 4: Renommer la table restaurée";
        $lines[] = "        \$this->pdo->exec(\"ALTER TABLE {$tableName}_old RENAME TO {$tableName}\");";
        
        return implode("\n", $lines);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * HELPERS
     * ═══════════════════════════════════════════════════════════════════
     */

    private function calculateNewSchema(array $currentSchema, array $diff): array
    {
        $newSchema = $currentSchema;
        
        // Ajouter les nouvelles colonnes
        foreach ($diff['added'] as $column => $def) {
            $newSchema[$column] = $def;
        }
        
        // Supprimer les colonnes
        foreach ($diff['dropped'] as $column => $def) {
            unset($newSchema[$column]);
        }
        
        // Modifier les colonnes
        foreach ($diff['modified'] as $column => $change) {
            $newSchema[$column] = $change['to'];
        }
        
        return $newSchema;
    }

    private function getSQLType(string $type, bool $nullable = true): string
    {
        $nullStr = $nullable ? 'NULL' : 'NOT NULL';
        
        $mapping = [
            'string' => "VARCHAR(255) {$nullStr}",
            'text' => "TEXT {$nullStr}",
            'integer' => "INT {$nullStr}",
            'bigint' => "BIGINT {$nullStr}",
            'float' => "FLOAT {$nullStr}",
            'decimal' => "DECIMAL(10,2) {$nullStr}",
            'boolean' => "TINYINT(1) {$nullStr}",
            'datetime' => "DATETIME {$nullStr}",
            'date' => "DATE {$nullStr}",
        ];
        
        return $mapping[$type] ?? "VARCHAR(255) {$nullStr}";
    }

    private function getSQLiteType(string $type, bool $nullable = true): string
    {
        $mapping = [
            'string' => 'TEXT',
            'text' => 'TEXT',
            'integer' => 'INTEGER',
            'bigint' => 'INTEGER',
            'float' => 'REAL',
            'decimal' => 'REAL',
            'boolean' => 'INTEGER',
            'datetime' => 'DATETIME',
            'date' => 'DATE',
        ];
        
        return $mapping[$type] ?? 'TEXT';
    }

    private function getActionName(array $diff): string
    {
        $actions = [];
        
        if (!empty($diff['added'])) {
            $actions[] = 'add';
        }
        if (!empty($diff['dropped'])) {
            $actions[] = 'drop';
        }
        if (!empty($diff['modified'])) {
            $actions[] = 'modify';
        }
        
        return implode('_', $actions) ?: 'update';
    }

    private function generateChangesSummary(array $diff): string
    {
        $lines = [];
        
        foreach ($diff['added'] as $column => $def) {
            $lines[] = " * - Ajout: {$column} ({$def['type']})";
        }
        foreach ($diff['dropped'] as $column => $def) {
            $lines[] = " * - Suppression: {$column}";
        }
        foreach ($diff['modified'] as $column => $change) {
            $lines[] = " * - Modification: {$column} ({$change['from']['type']} → {$change['to']['type']})";
        }
        
        return implode("\n", $lines) ?: " * - Aucun changement";
    }

    private function snakeToPascal(string $snake): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $snake)));
    }
}
