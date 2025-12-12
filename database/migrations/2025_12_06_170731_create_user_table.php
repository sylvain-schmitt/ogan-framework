<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * MIGRATION : Création de la table user
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Cette migration a été générée automatiquement depuis le modèle User.
 * 
 * Table : user
 * Modèle : App\Model\User
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Database\Migration;

use Ogan\Database\Migration\AbstractMigration;

class CreateUserTable extends AbstractMigration
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * APPLIQUER LA MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     */
    public function up(): void
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $sql = match (strtolower($driver)) {
            'mysql', 'mariadb' => "
                CREATE TABLE IF NOT EXISTS user (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255),
                    email VARCHAR(255) UNIQUE,
                    password VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql', 'postgresql' => "
                CREATE TABLE IF NOT EXISTS user (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255),
                    email VARCHAR(255) UNIQUE,
                    password VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

            ",
            'sqlite' => "
                CREATE TABLE IF NOT EXISTS user (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255),
                    email VARCHAR(255) UNIQUE,
                    password VARCHAR(255),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

            ",
            default => throw new \RuntimeException("Driver de base de données non supporté: {$driver}")
        };

        $this->execute($sql);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANNULER LA MIGRATION
     * ═══════════════════════════════════════════════════════════════════
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS user");
    }
}
