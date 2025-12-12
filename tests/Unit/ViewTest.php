<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ogan\View\View;
use Ogan\Exception\ViewException;

class ViewTest extends TestCase
{
    private string $testTemplatesPath;

    protected function setUp(): void
    {
        $this->testTemplatesPath = __DIR__ . '/../fixtures/templates';
        
        // Créer le répertoire de templates de test
        if (!is_dir($this->testTemplatesPath)) {
            mkdir($this->testTemplatesPath, 0777, true);
        }
        
        // Créer un template de test
        file_put_contents(
            $this->testTemplatesPath . '/test.html.php',
            '<h1><?= $this->e($title) ?></h1><p><?= $this->e($message) ?></p>'
        );
    }

    protected function tearDown(): void
    {
        // Nettoyer
        if (is_dir($this->testTemplatesPath)) {
            array_map('unlink', glob($this->testTemplatesPath . '/*'));
            rmdir($this->testTemplatesPath);
        }
    }

    public function testRender(): void
    {
        $view = new View($this->testTemplatesPath, false);
        
        $output = $view->render('test.html.php', [
            'title' => 'Test Title',
            'message' => 'Test Message'
        ]);
        
        $this->assertStringContainsString('Test Title', $output);
        $this->assertStringContainsString('Test Message', $output);
    }

    public function testRenderNotFound(): void
    {
        $view = new View($this->testTemplatesPath, false);
        
        $this->expectException(ViewException::class);
        
        $view->render('nonexistent.html.php', []);
    }

    public function testEscape(): void
    {
        $view = new View($this->testTemplatesPath, false);
        
        $output = $view->render('test.html.php', [
            'title' => '<script>alert("XSS")</script>',
            'message' => 'Safe message'
        ]);
        
        // Le script devrait être échappé
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    public function testSection(): void
    {
        $view = new View($this->testTemplatesPath, false);
        
        $view->start('test_section');
        echo 'Section content';
        $view->end();
        
        $section = $view->section('test_section');
        
        $this->assertEquals('Section content', $section);
    }

    public function testExtend(): void
    {
        // Créer un layout
        file_put_contents(
            $this->testTemplatesPath . '/layout.html.php',
            '<html><body><?= $this->section("content") ?></body></html>'
        );
        
        // Créer un template qui étend le layout
        file_put_contents(
            $this->testTemplatesPath . '/page.html.php',
            '<?php $this->extend("layout"); $this->start("content"); ?>Page content<?php $this->end(); ?>'
        );
        
        $view = new View($this->testTemplatesPath, false);
        $output = $view->render('page.html.php', []);
        
        $this->assertStringContainsString('Page content', $output);
        $this->assertStringContainsString('<html>', $output);
    }
}

