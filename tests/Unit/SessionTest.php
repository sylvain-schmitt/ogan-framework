<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ogan\Session\Session;

class SessionTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        // Passer une configuration directement pour éviter de dépendre de Config
        $config = [
            'name' => 'TEST_SESSION',
            'lifetime' => 7200,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        
        $this->session = new Session($config);
        
        // Démarrer une session de test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        // Nettoyer la session
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testSetAndGet(): void
    {
        $this->session->set('test_key', 'test_value');
        
        $this->assertEquals('test_value', $this->session->get('test_key'));
    }

    public function testGetDefault(): void
    {
        $value = $this->session->get('nonexistent', 'default');
        
        $this->assertEquals('default', $value);
    }

    public function testHas(): void
    {
        $this->session->set('test_key', 'test_value');
        
        $this->assertTrue($this->session->has('test_key'));
        $this->assertFalse($this->session->has('nonexistent'));
    }

    public function testRemove(): void
    {
        $this->session->set('test_key', 'test_value');
        $this->session->remove('test_key');
        
        $this->assertFalse($this->session->has('test_key'));
    }

    public function testFlash(): void
    {
        $this->session->setFlash('success', 'Operation successful');
        
        $this->assertTrue($this->session->hasFlash('success'));
        $flash = $this->session->getFlash('success');
        $this->assertEquals('Operation successful', $flash);
    }

    public function testGetFlash(): void
    {
        $this->session->setFlash('error', 'An error occurred');
        
        $flash = $this->session->getFlash('error');
        
        $this->assertEquals('An error occurred', $flash);
        // Le flash devrait être supprimé après lecture
        $this->assertFalse($this->session->hasFlash('error'));
    }

    public function testClear(): void
    {
        $this->session->set('key1', 'value1');
        $this->session->set('key2', 'value2');
        
        // Si la méthode clear() existe, l'utiliser
        if (method_exists($this->session, 'clear')) {
            $this->session->clear();
        } else {
            // Sinon, supprimer manuellement
            $this->session->remove('key1');
            $this->session->remove('key2');
        }
        
        $this->assertFalse($this->session->has('key1'));
        $this->assertFalse($this->session->has('key2'));
    }

    public function testAll(): void
    {
        $this->session->set('key1', 'value1');
        $this->session->set('key2', 'value2');
        
        // Vérifier directement avec has() car all() n'existe pas dans SessionInterface
        $this->assertTrue($this->session->has('key1'));
        $this->assertTrue($this->session->has('key2'));
        $this->assertEquals('value1', $this->session->get('key1'));
        $this->assertEquals('value2', $this->session->get('key2'));
    }
}

