<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ogan\Router\Router;
use Ogan\Router\Route;
use Ogan\Http\Request;
use Ogan\Http\Response;
use Ogan\DependencyInjection\Container;
use Ogan\Exception\RouteNotFoundException;

class RouterTest extends TestCase
{
    private Router $router;
    private Container $container;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->container = new Container();
    }

    public function testAddRoute(): void
    {
        $this->router->addRoute(
            '/test',
            ['GET'],
            'TestController',
            'index',
            'test_route'
        );

        // Vérifier que la route peut être générée (indirectement qu'elle existe)
        $url = $this->router->generateUrl('test_route');
        $this->assertEquals('/test', $url);
    }

    public function testMatchSimpleRoute(): void
    {
        $this->router->addRoute(
            '/users',
            ['GET'],
            'UserController',
            'index',
            'users_list'
        );

        $request = new Request([], [], ['REQUEST_URI' => '/users', 'REQUEST_METHOD' => 'GET']);
        $response = new Response();

        // Mock du container pour retourner un contrôleur
        $controller = new class {
            public function index() {
                return 'Users list';
            }
        };

        $this->container->set('UserController', fn() => $controller);

        $this->expectNotToPerformAssertions();
        // Le dispatch devrait fonctionner sans erreur
        try {
            $this->router->dispatch('/users', 'GET', $request, $response, $this->container);
        } catch (RouteNotFoundException $e) {
            $this->fail('Route should be found');
        }
    }

    public function testMatchRouteWithParameters(): void
    {
        $this->router->addRoute(
            '/users/{id}',
            ['GET'],
            'UserController',
            'show',
            'user_show'
        );

        $request = new Request([], [], ['REQUEST_URI' => '/users/123', 'REQUEST_METHOD' => 'GET']);
        $response = new Response();

        $controller = new class {
            public function show($id) {
                return "User {$id}";
            }
        };

        $this->container->set('UserController', fn() => $controller);

        $this->expectNotToPerformAssertions();
        try {
            $this->router->dispatch('/users/123', 'GET', $request, $response, $this->container);
        } catch (RouteNotFoundException $e) {
            $this->fail('Route with parameters should be found');
        }
    }

    public function testRouteNotFound(): void
    {
        $this->expectException(RouteNotFoundException::class);

        $request = new Request([], [], ['REQUEST_URI' => '/nonexistent', 'REQUEST_METHOD' => 'GET']);
        $response = new Response();

        $this->router->dispatch('/nonexistent', 'GET', $request, $response, $this->container);
    }

    public function testGenerateUrl(): void
    {
        $this->router->addRoute(
            '/users/{id}',
            ['GET'],
            'UserController',
            'show',
            'user_show'
        );

        $url = $this->router->generateUrl('user_show', ['id' => 42]);
        $this->assertEquals('/users/42', $url);
    }

    public function testGenerateUrlWithQueryParams(): void
    {
        $this->router->addRoute(
            '/users',
            ['GET'],
            'UserController',
            'index',
            'users_list'
        );

        $url = $this->router->generateUrl('users_list', ['page' => 2, 'limit' => 10]);
        $this->assertStringContainsString('/users', $url);
        $this->assertStringContainsString('page=2', $url);
        $this->assertStringContainsString('limit=10', $url);
    }

    public function testMethodNotAllowed(): void
    {
        $this->router->addRoute(
            '/users',
            ['GET'],
            'UserController',
            'index',
            'users_list'
        );

        $this->expectException(RouteNotFoundException::class);

        $request = new Request([], [], ['REQUEST_URI' => '/users', 'REQUEST_METHOD' => 'POST']);
        $response = new Response();

        $this->router->dispatch('/users', 'POST', $request, $response, $this->container);
    }
}

