<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🗄️ AUTH MIGRATION GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class AuthMigrationGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $migrationsDir = $projectRoot . '/database/migrations';
        $this->ensureDirectory($migrationsDir);

        // Migration users - ne jamais recréer si existe
        $existingUsersMigrations = glob($migrationsDir . '/*_create_users_table.php');
        if (empty($existingUsersMigrations)) {
            $timestamp = date('Y_m_d_His');
            $this->writeFile($migrationsDir . "/{$timestamp}_create_users_table.php", $this->getUsersTemplate());
            $generated[] = "database/migrations/{$timestamp}_create_users_table.php";
        } else {
            $skipped[] = 'Migration users (existe déjà)';
        }

        // Migration remember_tokens - ne jamais recréer si existe
        $existingRememberMigrations = glob($migrationsDir . '/*_create_remember_tokens_table.php');
        if (empty($existingRememberMigrations)) {
            usleep(1000000); // 1 seconde pour timestamp différent
            $timestamp = date('Y_m_d_His');
            $this->writeFile($migrationsDir . "/{$timestamp}_create_remember_tokens_table.php", $this->getRememberTokensTemplate());
            $generated[] = "database/migrations/{$timestamp}_create_remember_tokens_table.php";
        } else {
            $skipped[] = 'Migration remember_tokens (existe déjà)';
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getUsersTemplate(): string
    {
        return <<<'PHP'
<?php

use Ogan\Database\Migration\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    protected string $table = 'users';

    public function up(): void
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $sql = match ($driver) {
            'mysql' => "
                CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    roles JSON DEFAULT NULL,
                    email_verified_at DATETIME DEFAULT NULL,
                    email_verification_token VARCHAR(255) DEFAULT NULL,
                    password_reset_token VARCHAR(255) DEFAULT NULL,
                    password_reset_expires_at DATETIME DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_verification_token (email_verification_token),
                    INDEX idx_reset_token (password_reset_token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql' => "
                CREATE TABLE users (
                    id SERIAL PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    roles JSONB DEFAULT '[]',
                    email_verified_at TIMESTAMP DEFAULT NULL,
                    email_verification_token VARCHAR(255) DEFAULT NULL,
                    password_reset_token VARCHAR(255) DEFAULT NULL,
                    password_reset_expires_at TIMESTAMP DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX idx_users_email ON users(email);
                CREATE INDEX idx_users_verification_token ON users(email_verification_token);
                CREATE INDEX idx_users_reset_token ON users(password_reset_token);
            ",
            'sqlite' => "
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    roles TEXT DEFAULT '[]',
                    email_verified_at DATETIME DEFAULT NULL,
                    email_verification_token VARCHAR(255) DEFAULT NULL,
                    password_reset_token VARCHAR(255) DEFAULT NULL,
                    password_reset_expires_at DATETIME DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX idx_users_email ON users(email);
                CREATE INDEX idx_users_verification_token ON users(email_verification_token);
                CREATE INDEX idx_users_reset_token ON users(password_reset_token);
            ",
            default => throw new \RuntimeException("Driver non supporté: {$driver}")
        };

        $this->pdo->exec($sql);
    }

    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS users");
    }
}
PHP;
    }

    private function getRememberTokensTemplate(): string
    {
        return <<<'PHP'
<?php

use Ogan\Database\Migration\AbstractMigration;

class CreateRememberTokensTable extends AbstractMigration
{
    protected string $table = 'remember_tokens';

    public function up(): void
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $sql = match ($driver) {
            'mysql', 'mariadb' => "
                CREATE TABLE remember_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_token (token),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            'pgsql', 'postgresql' => "
                CREATE TABLE remember_tokens (
                    id SERIAL PRIMARY KEY,
                    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    token VARCHAR(255) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX idx_remember_tokens_user_id ON remember_tokens(user_id);
                CREATE INDEX idx_remember_tokens_token ON remember_tokens(token);
            ",
            'sqlite' => "
                CREATE TABLE remember_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
                CREATE INDEX idx_remember_tokens_user_id ON remember_tokens(user_id);
                CREATE INDEX idx_remember_tokens_token ON remember_tokens(token);
            ",
            default => throw new \RuntimeException("Driver non supporté: {$driver}")
        };

        $this->pdo->exec($sql);
    }

    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS remember_tokens");
    }
}
PHP;
    }
}
