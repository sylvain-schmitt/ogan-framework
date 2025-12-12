<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ogan\Router\Router;
use Ogan\Http\Request;
use Ogan\Http\Response;
use Ogan\DependencyInjection\Container;
use Ogan\Exception\RouteNotFoundException;

class RouteIntegrationTest extends TestCase
{
    private Router $router;
    private Container $container;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->container = new Container();
    }

    public function testFullRouteDispatch(): void
    {
        // Créer un contrôleur de test
        $controller = new class {
            public function index() {
                return 'Index page';
            }
        };

        $this->container->set('TestController', fn() => $controller);

        // Ajouter une route
        $this->router->addRoute(
            '/test',
            ['GET'],
            'TestController',
            'index',
            'test_index'
        );

        // Créer une requête
        $request = new Request([], [], ['REQUEST_URI' => '/test', 'REQUEST_METHOD' => 'GET']);
        $response = new Response();

        // Capturer la sortie
        ob_start();
        try {
            $this->router->dispatch('/test', 'GET', $request, $response, $this->container);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        $output = ob_get_clean();

        // Vérifier que la route a été trouvée (pas d'exception)
        $this->assertTrue(true);
    }

    public function testRouteWithParameters(): void
    {
        $controller = new class {
            public function show($id) {
                return "User {$id}";
            }
        };

        $this->container->set('UserController', fn() => $controller);

        $this->router->addRoute(
            '/users/{id}',
            ['GET'],
            'UserController',
            'show',
            'user_show'
        );

        $request = new Request([], [], ['REQUEST_URI' => '/users/123', 'REQUEST_METHOD' => 'GET']);
        $response = new Response();

        ob_start();
        try {
            $this->router->dispatch('/users/123', 'GET', $request, $response, $this->container);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        ob_end_clean();

        $this->assertTrue(true);
    }

    public function testRouteNotFound(): void
    {
        $request = new Request([], [], ['REQUEST_URI' => '/nonexistent', 'REQUEST_METHOD' => 'GET']);
        $response = new Response();

        $this->expectException(RouteNotFoundException::class);

        $this->router->dispatch('/nonexistent', 'GET', $request, $response, $this->container);
    }

    public function testRouteGeneration(): void
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
}

