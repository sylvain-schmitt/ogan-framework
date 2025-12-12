<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔄 ABSTRACT MIGRATION - Classe de base pour les migrations
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Classe abstraite que toutes les migrations doivent étendre.
 * Fournit les méthodes up() et down() pour appliquer/annuler les migrations.
 * 
 * UTILISATION :
 * -------------
 * 
 * class CreateUsersTable extends AbstractMigration
 * {
 *     public function up(): void
 *     {
 *         $this->pdo->exec("CREATE TABLE user (...)");
 *     }
 * 
 *     public function down(): void
 *     {
 *         $this->pdo->exec("DROP TABLE user");
 *     }
 * }
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database\Migration;

use PDO;

abstract class AbstractMigration
{
    /**
     * @var PDO Connexion à la base de données
     */
    protected PDO $pdo;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CONSTRUCTEUR
     * ═══════════════════════════════════════════════════════════════════
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * APPLIQUER LA MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Cette méthode doit contenir le code pour appliquer la migration.
     * Exemple : créer une table, ajouter une colonne, etc.
     * 
     * @return void
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    abstract public function up(): void;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANNULER LA MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Cette méthode doit contenir le code pour annuler la migration.
     * Exemple : supprimer une table, retirer une colonne, etc.
     * 
     * @return void
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    abstract public function down(): void;

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXÉCUTER UNE REQUÊTE SQL
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Helper pour exécuter des requêtes SQL de manière sécurisée.
     * 
     * @param string $sql Requête SQL à exécuter
     * @return void
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function execute(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXÉCUTER PLUSIEURS REQUÊTES SQL
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Helper pour exécuter plusieurs requêtes SQL séparées par des points-virgules.
     * 
     * @param string $sql Requêtes SQL à exécuter
     * @return void
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    protected function executeMultiple(string $sql): void
    {
        // Nettoyer le SQL : supprimer les commentaires
        $lines = explode("\n", $sql);
        $cleanedLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // Ignorer les lignes vides et les commentaires
            if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '#')) {
                continue;
            }
            $cleanedLines[] = $line;
        }
        $sql = implode("\n", $cleanedLines);

        // Séparer les requêtes par point-virgule
        $queries = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($q) => !empty($q)
        );

        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $this->pdo->exec($query);
            }
        }
    }
}

