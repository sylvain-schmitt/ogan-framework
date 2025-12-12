<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ogan\DependencyInjection\Container;
use Ogan\Exception\NotFoundException;
use Ogan\Exception\ContainerException;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testSetAndGet(): void
    {
        $this->container->set('test.service', fn() => new \stdClass());

        $service = $this->container->get('test.service');

        $this->assertInstanceOf(\stdClass::class, $service);
    }

    public function testGetNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('nonexistent.service');
    }

    public function testHas(): void
    {
        $this->container->set('test.service', fn() => new \stdClass());

        $this->assertTrue($this->container->has('test.service'));
        $this->assertFalse($this->container->has('nonexistent.service'));
    }

    public function testSingleton(): void
    {
        $this->container->set('test.service', fn() => new \stdClass());

        $service1 = $this->container->get('test.service');
        $service2 = $this->container->get('test.service');

        // Le même service devrait être retourné (singleton)
        $this->assertSame($service1, $service2);
    }

    public function testAutowiring(): void
    {
        // Test avec une classe simple
        $instance = $this->container->get(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    public function testFactoryClosure(): void
    {
        $counter = 0;
        $this->container->set('counter', function() use (&$counter) {
            return ++$counter;
        });

        $value1 = $this->container->get('counter');
        $value2 = $this->container->get('counter');

        // La closure devrait être appelée une seule fois (singleton)
        $this->assertEquals(1, $value1);
        $this->assertEquals(1, $value2);
    }

    public function testAlias(): void
    {
        $this->container->set('test.service', fn() => new \stdClass());
        // L'alias pointe vers le service réel : alias -> service
        $this->container->alias('alias.service', 'test.service');

        $service1 = $this->container->get('test.service');
        $service2 = $this->container->get('alias.service');

        $this->assertSame($service1, $service2);
    }
}

