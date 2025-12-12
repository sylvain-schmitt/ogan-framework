<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ogan\Database\QueryBuilder;
use PDO;

class QueryBuilderTest extends TestCase
{
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        // Utiliser SQLite en mémoire pour les tests
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer une table de test
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    public function testSelectQuery(): void
    {
        // Insérer des données de test
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
        
        $qb = new QueryBuilder($this->pdo, 'users');
        $results = $qb->get();
        
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('John', $results[0]['name']);
    }

    public function testSelectWithColumns(): void
    {
        // Insérer des données de test
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
        
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->select(['id', 'name']);
        $results = $qb->get();
        
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertArrayHasKey('name', $results[0]);
        $this->assertArrayNotHasKey('email', $results[0]);
    }

    public function testWhereClause(): void
    {
        // Insérer des données de test
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('Jane', 'jane@example.com')");
        
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->where('id', '=', 1);
        $results = $qb->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('John', $results[0]['name']);
    }

    public function testMultipleWhereClauses(): void
    {
        // Insérer des données de test
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('Jane', 'jane@example.com')");
        
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->where('id', '>', 0)
           ->where('name', '=', 'John');
        $results = $qb->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('John', $results[0]['name']);
    }

    public function testOrderBy(): void
    {
        // Insérer des données de test
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('Jane', 'jane@example.com')");
        
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->orderBy('name', 'ASC');
        $results = $qb->get();
        
        $this->assertCount(2, $results);
        $this->assertEquals('Jane', $results[0]['name']); // Jane vient avant John alphabétiquement
        $this->assertEquals('John', $results[1]['name']);
    }

    public function testLimit(): void
    {
        // Insérer des données de test
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('Jane', 'jane@example.com')");
        
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->limit(1);
        $results = $qb->get();
        
        $this->assertCount(1, $results);
    }

    public function testInsert(): void
    {
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->insert(['name' => 'John', 'email' => 'john@example.com']);
        
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(1, $count);
    }

    public function testUpdate(): void
    {
        // Insérer d'abord
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
        
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->where('id', '=', 1)
           ->update(['name' => 'Jane']);
        
        $stmt = $this->pdo->query('SELECT name FROM users WHERE id = 1');
        $name = $stmt->fetchColumn();
        
        $this->assertEquals('Jane', $name);
    }

    public function testDelete(): void
    {
        // Insérer d'abord
        $this->pdo->exec("INSERT INTO users (name, email) VALUES ('John', 'john@example.com')");
        
        $qb = new QueryBuilder($this->pdo, 'users');
        $qb->where('id', '=', 1)
           ->delete();
        
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count);
    }
}

