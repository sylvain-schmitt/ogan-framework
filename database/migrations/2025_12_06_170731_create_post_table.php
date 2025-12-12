<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * MIGRATION : Création de la table post
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Cette migration a été générée automatiquement depuis le modèle Post.
 * 
 * Table : post
 * Modèle : App\Model\Post
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\Database\Migration;

use Ogan\Database\Migration\AbstractMigration;

class CreatePostTable extends AbstractMigration
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
                CREATE TABLE IF NOT EXISTS post (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255),
                    content TEXT,
                    user_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql', 'postgresql' => "
                CREATE TABLE IF NOT EXISTS post (
                    id SERIAL PRIMARY KEY,
                    title VARCHAR(255),
                    content TEXT,
                    user_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                CREATE INDEX IF NOT EXISTS idx_user_id ON post(user_id);
            ",
            'sqlite' => "
                CREATE TABLE IF NOT EXISTS post (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title VARCHAR(255),
                    content TEXT,
                    user_id INTEGER,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE INDEX IF NOT EXISTS idx_user_id ON post(user_id);
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
        $this->execute("DROP TABLE IF EXISTS post");
    }
}
