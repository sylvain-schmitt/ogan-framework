<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ogan\Database\Model;
use Ogan\Database\Database;
use PDO;

class ModelTest extends TestCase
{
    private ?PDO $pdo = null;
    private ?Database $db = null;

    protected function setUp(): void
    {
        // Utiliser SQLite en mémoire pour les tests
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer une table de test
        $this->pdo->exec('
            CREATE TABLE test_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        $this->db = new Database($this->pdo);
        
        // Injecter la connexion PDO dans Database pour les tests
        $reflection = new \ReflectionClass(\Ogan\Database\Database::class);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue(null, $this->pdo);
    }

    protected function tearDown(): void
    {
        // Réinitialiser la connexion Database
        $reflection = new \ReflectionClass(\Ogan\Database\Database::class);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        $this->pdo = null;
        $this->db = null;
    }

    public function testCreate(): void
    {
        // Utiliser directement Model avec une table
        $user = new class extends Model {
            protected static ?string $table = 'test_users';
        };
        
        $user->name = 'John Doe';
        $user->email = 'john@example.com';
        $user->save();
        
        // Utiliser la réflexion pour accéder aux attributs protégés
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue($user);
        
        $this->assertNotNull($attributes['id'] ?? null);
        $this->assertEquals('John Doe', $attributes['name']);
    }

    public function testFind(): void
    {
        // Créer d'abord
        $this->pdo->exec("INSERT INTO test_users (name, email) VALUES ('John', 'john@example.com')");
        
        $userClass = new class extends Model {
            protected static ?string $table = 'test_users';
        };
        $found = $userClass::find(1);
        
        $this->assertNotNull($found);
        // Utiliser __get() pour accéder aux attributs
        $this->assertEquals('John', $found->name);
    }

    public function testUpdate(): void
    {
        // Créer d'abord
        $this->pdo->exec("INSERT INTO test_users (name, email) VALUES ('John', 'john@example.com')");
        
        $userClass = new class extends Model {
            protected static ?string $table = 'test_users';
        };
        $user = $userClass::find(1);
        
        $user->name = 'Jane Doe';
        $user->save();
        
        $updated = $userClass::find(1);
        $this->assertEquals('Jane Doe', $updated->name);
    }

    public function testDelete(): void
    {
        // Créer d'abord
        $this->pdo->exec("INSERT INTO test_users (name, email) VALUES ('John', 'john@example.com')");
        
        $userClass = new class extends Model {
            protected static ?string $table = 'test_users';
        };
        $user = $userClass::find(1);
        
        $user->delete();
        
        $deleted = $userClass::find(1);
        $this->assertNull($deleted);
    }

    public function testAll(): void
    {
        // Créer plusieurs utilisateurs
        $this->pdo->exec("INSERT INTO test_users (name, email) VALUES ('John', 'john@example.com')");
        $this->pdo->exec("INSERT INTO test_users (name, email) VALUES ('Jane', 'jane@example.com')");
        
        $userClass = new class extends Model {
            protected static ?string $table = 'test_users';
        };
        $users = $userClass::all();
        
        $this->assertCount(2, $users);
    }

    public function testWhere(): void
    {
        // Créer plusieurs utilisateurs
        $this->pdo->exec("INSERT INTO test_users (name, email) VALUES ('John', 'john@example.com')");
        $this->pdo->exec("INSERT INTO test_users (name, email) VALUES ('Jane', 'jane@example.com')");
        
        $userClass = new class extends Model {
            protected static ?string $table = 'test_users';
        };
        // where() retourne un QueryBuilder, get() retourne des tableaux
        // Il faut utiliser query()->where()->get() ou créer une méthode qui hydrate
        $results = $userClass::where('name', '=', 'John')->get();
        
        // Utiliser la méthode hydrate pour convertir en modèles
        $reflection = new \ReflectionClass($userClass);
        $method = $reflection->getMethod('hydrate');
        $method->setAccessible(true);
        $users = $method->invoke(null, $results);
        
        $this->assertCount(1, $users);
        $this->assertEquals('John', $users[0]->name);
    }
}

