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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Container\ContainerInterface;

class RouteLoaderMiddlewareTest extends PHPUnit_Framework_TestCase
{
    /** @var \Mockery\MockInterface */
    private $di;
    /** @var \Mockery\MockInterface */
    private $slim;
    /** @var \Mockery\MockInterface */
    private $request;
    /** @var \Mockery\MockInterface */
    private $response;
    /** @var callable $next */
    private $next;

    public function setUp()
    {
        $this->di = Mockery::mock(ContainerInterface::CLASS);
        $this->slim = Mockery::mock(App::CLASS);
        $this->di
            ->shouldReceive('get')
            ->with('panthor.slim')
            ->andReturn($this->slim);
        $this->request = Mockery::mock(RequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->next = function(){};
    }

    public function testAddingRoutesOnInstantiation()
    {
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/{id:[\d]{6}}',
                'stack' => ['middleware.test', 'test.page']
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
            ->with(['POST'], '/users/{id:[\d]{6}}', Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with(['GET', 'POST'], '/resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('setName')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->once();

        // route 2
        $route2
            ->shouldReceive('setName')
            ->with('derp')
            ->once();
        $route2
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->never();

        $hook = new RouteLoaderMiddleware($this->di, $routes);
        $hook($this->request, $this->response, $this->next);
    }

    public function testLoadRoutesOnInstantiation()
    {
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/{id:[\d]{6}}',
                'stack' => ['middleware.test', 'test.page']
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
            ->with(['POST'], '/users/{id:[\d]{6}}', Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with(['GET', 'POST'], '/resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('setName')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->once();

        // route 2
        $route2
            ->shouldReceive('setName')
            ->with('derp')
            ->once();
        $route2
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->never();

        $hook = new RouteLoaderMiddleware($this->di, $routes);
        $hook->loadRoutes($this->slim);
    }

    public function testAddingIncrementalRoutes()
    {
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/{id:[\d]{6}}',
                'stack' => ['middleware.test', 'test.page']
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
            ->with(['POST'], '/users/{id:[\d]{6}}', Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with(['GET', 'POST'], '/resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('setName')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->once();

        // route 2
        $route2
            ->shouldReceive('setName')
            ->with('derp')
            ->once();
        $route2
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->never();

        $hook = new RouteLoaderMiddleware($this->di);
        $hook->addRoutes($routes);
        $hook($this->request, $this->response, $this->next);
    }

    public function testMergingRoutes()
    {
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/{id:[\d]{6}}',
                'stack' => ['middleware.test', 'test.page']
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
            ->with(['POST'], '/users/{id:[\d]{6}}', Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with(['DELETE'], '/new-resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('setName')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->once();

        // route 2
        $route2
            ->shouldReceive('setName')
            ->with('derp')
            ->once();
        $route2
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->never();

        $hook = new RouteLoaderMiddleware($this->di, $routes);

        // This overwrites the previously set route
        $hook->addRoutes([
            'derp' => [
                'method' => ['DELETE'],
                'route' => '/new-resource/add',
                'stack' => ['resource2.add.page']
            ]
        ]);
        $hook($this->request, $this->response, $this->next);
    }

    public function testAddingGroupedRoutesOnInstantiation()
    {
        $routes = [
            'herp' => [
                'route' => '/users/{id:[\d]{6}}',
                'group' => [
                    'derp' => [
                        'method' => ['GET'],
                        'route' => '/resource/add',
                        'stack' => ['resource.add.page']
                    ]
                ],
                'stack' => ['middleware.test', 'test.page']
            ],

        ];

        $route1 = Mockery::mock(Route::CLASS);
        $route2 = Mockery::mock(Route::CLASS);

        $this->slim
            ->shouldReceive('group')
            ->with('/users/{id:[\d]{6}}', Mockery::type('Closure'))
            ->andReturn($route1);
//        $this->slim
//            ->shouldReceive('map')
//            ->with(['GET', 'POST'], '/resource/add', Mockery::type('Closure'))
//            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->twice();

        $hook = new RouteLoaderMiddleware($this->di, $routes);
        $hook($this->request, $this->response, $this->next);
        //If internal closures could be called, this examples that occurring from where we left off...

        $this->slim
            ->shouldReceive('map')
            ->with(
                $routes['herp']['group']['derp']['method'],
                $routes['herp']['group']['derp']['route'],
                Mockery::type('Closure')
            )
            ->andReturn($route2);
        // route 2
        $route2
            ->shouldReceive('setName')
            ->with('derp')
            ->once();
        $route2
            ->shouldReceive('add')
            ->with(Mockery::type('Closure'))
            ->never();

        $hook->loadRoutes($this->slim, $routes['herp']['group']);

    }
}
