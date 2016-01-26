<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Route;
use Slim\App;
use Symfony\Component\DependencyInjection\Container;

class RouteLoaderMiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->di = Mockery::mock(Container::CLASS);
        $this->slim = Mockery::mock(App::CLASS);
    }

    public function testAddingRoutesOnInstantiation()
    {
        $this->fail(self::class . 'I re-wrote the class, now re-write the tests!');
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/:id',
                'stack' => ['middleware.test', 'test.page'],
                'conditions' => ['id' => '[\d]{6}']
            ],
            'derp' => [
                'method' => ['GET', 'POST'],
                'route' => '/resource/add',
                'stack' => ['resource.add.page']
            ]
        ];

        $route1 = Mockery::mock(Route::CLASS);
        $route2 = Mockery::mock(Route::CLASS);

        $this->slim
            ->shouldReceive('map')
            ->with('/users/:id', Mockery::type('Closure'), Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with('/resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('via')
            ->with('POST')
            ->once();
        $route1
            ->shouldReceive('name')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('conditions')
            ->with(['id' => '[\d]{6}'])
            ->once();

        // route 2
        $route2
            ->shouldReceive('via')
            ->with('GET', 'POST')
            ->once();
        $route2
            ->shouldReceive('name')
            ->with('derp')
            ->once();

        $hook = new RouteLoaderMiddleware($this->di, $routes);
        $hook($this->slim);
    }

    public function testAddingIncrementalRoutes()
    {
        $this->fail(self::class . 'I re-wrote the class, now re-write the tests!');
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/:id',
                'stack' => ['middleware.test', 'test.page'],
                'conditions' => ['id' => '[\d]{6}']
            ],
            'derp' => [
                'method' => ['GET', 'POST'],
                'route' => '/resource/add',
                'stack' => ['resource.add.page']
            ]
        ];

        $route1 = Mockery::mock(Route::CLASS);
        $route2 = Mockery::mock(Route::CLASS);

        $this->slim
            ->shouldReceive('map')
            ->with('/users/:id', Mockery::type('Closure'), Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with('/resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('via')
            ->with('POST')
            ->once();
        $route1
            ->shouldReceive('name')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('conditions')
            ->with(['id' => '[\d]{6}'])
            ->once();

        // route 2
        $route2
            ->shouldReceive('via')
            ->with('GET', 'POST')
            ->once();
        $route2
            ->shouldReceive('name')
            ->with('derp')
            ->once();

        $hook = new RouteLoaderMiddleware($this->di);
        $hook->addRoutes($routes);
        $hook($this->slim);
    }

    public function testMergingRoutes()
    {
        $this->fail(self::class . 'I re-wrote the class, now re-write the tests!');
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/:id',
                'stack' => ['middleware.test', 'test.page'],
                'conditions' => ['id' => '[\d]{6}']
            ],
            'derp' => [
                'method' => ['GET', 'POST'],
                'route' => '/resource/add',
                'stack' => ['resource.add.page']
            ]
        ];

        $route1 = Mockery::mock(Route::CLASS);
        $route2 = Mockery::mock(Route::CLASS);

        $this->slim
            ->shouldReceive('map')
            ->with('/users/:id', Mockery::type('Closure'), Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with('/new-resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('via')
            ->with('POST')
            ->once();
        $route1
            ->shouldReceive('name')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('conditions')
            ->with(['id' => '[\d]{6}'])
            ->once();

        // route 2
        $route2
            ->shouldReceive('via')
            ->with('DELETE')
            ->once();
        $route2
            ->shouldReceive('name')
            ->with('derp')
            ->once();

        $hook = new RouteLoaderMiddleware($this->di, $routes);

        // This overwrites the previously set route
        $hook->addRoutes([
            'derp' => [
                'method' => ['DELETE'],
                'route' => '/new-resource/add',
                'stack' => ['resource2.add.page']
            ]
        ]);
        $hook($this->slim);
    }
}
