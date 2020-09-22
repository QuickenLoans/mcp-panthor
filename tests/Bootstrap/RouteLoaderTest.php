<?php

namespace QL\Panthor\Bootstrap;

use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Routing\RouteGroup;
use Symfony\Component\DependencyInjection\Container;

class RouteLoaderTest extends TestCase
{
    public $request;
    public $slim;

    public function setUp()
    {
        $this->request = (new RequestFactory)->createRequest('GET', '/test');

        $this->slim = new App(
            new ResponseFactory,
            new Container
        );
    }

    public function testRoutesAttached()
    {
        $routes = [
            'hello_world' => [
                'route' => '/',
                'stack' => ['middleware.one', 'page.one']
            ],
            'test' => [
                'route' => '/test',
                'stack' => ['page.one']
            ]
        ];

        $loader = new RouteLoader($routes);
        $loader($this->slim);

        $routes = $this->slim->getRouteCollector()->getRoutes();

        $this->assertCount(2, $routes);
    }

    public function testMultipleMiddlewareAreOrderedCorrectlyInReverse()
    {
        $di = $this->slim->getContainer();

        $di->set('m.one', new TestCallable('one'));
        $di->set('m.two', new TestCallable('two'));
        $di->set('m.three', new TestCallable('three'));
        $di->set('gm.one', new TestCallable('gm.one'));
        $di->set('gm.two', new TestCallable('gm.two'));
        $di->set('page', new TestCallable('controller'));

        $routes = [
            'group1' => [
                'route' => '/group1',
                'stack' => ['gm.one:mw', 'gm.two:mw'],
                'routes' => [
                    'hello_world' => [
                        'route' => '/',
                        'stack' => ['m.one:mw', 'm.two:mw', 'm.three:mw', 'page:controller']
                    ]
                ]
            ]
        ];

        $loader = new RouteLoader($routes);
        $loader($this->slim);

        $routes = $this->slim->getRouteCollector()->getRoutes();

        $route = array_shift($routes);
        $response = $route->run($this->request);

        $expected = <<<EOT
        before-gm.one
        before-gm.two
        before-one
        before-two
        before-three
        controller
        after-three
        after-two
        after-one
        after-gm.two
        after-gm.one

        EOT;

        $this->assertSame($expected, TestCallable::$output);
    }

    public function testRoutesWithGroupAttached()
    {
        $routes = [
            'hello_world' => [
                'route' => '/hello',
                'routes' => [
                    'test2' => [
                        'method' => 'GET',
                        'route' => '/goodbye',
                        'stack' => ['page.two']
                    ]

                ]
            ],
            'test' => [
                'method' => ['POST', 'PATCH'],
                'route' => '/test',
                'stack' => ['page.one']
            ]
        ];

        $loader = new RouteLoader($routes);
        $loader($this->slim);

        $routes = $this->slim->getRouteCollector()->getRoutes();

        $this->assertCount(2, $routes);

        $route1 = array_shift($routes);
        $route2 = array_shift($routes);

        $this->assertSame('test2', $route1->getName());
        $this->assertSame(['GET', 'HEAD'], $route1->getMethods());
        $this->assertInstanceOf(RouteGroup::class, $route1->getGroups()[0]);

        $this->assertSame('test', $route2->getName());
        $this->assertSame(['POST', 'PATCH'], $route2->getMethods());
        $this->assertSame([], $route2->getGroups());
    }

    public function testRoutesAreNotRecurviselyMerged()
    {
        $routes = [
            'hello_world' => [
                'route' => '/hello',
                'routes' => [
                    'test2' => [
                        'route' => '/test2',
                        'stack' => ['page.two']
                    ]

                ]
            ],
            'test' => [
                'route' => '/test',
                'stack' => ['page.one']
            ]
        ];

        $routes2 = [
            'hello_world' => [
                'route' => '/hello',
                'routes' => [
                    'test3' => [
                        'route' => '/test3',
                        'stack' => ['page.three']
                    ]

                ]
            ],
        ];

        $loader = new RouteLoader($routes);
        $loader->addRoutes($routes2);
        $loader($this->slim);

        $routes = $this->slim->getRouteCollector()->getRoutes();

        $this->assertCount(2, $routes);

        $route1 = array_shift($routes);
        $route2 = array_shift($routes);

        $this->assertSame('test3', $route1->getName());
        $this->assertSame('test', $route2->getName());

        $this->assertSame('/hello/test3', $route1->getPattern());
    }
}

class TestCallable
{
    private $name;

    public static $output;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function mw($req, $handler)
    {
        self::$output .= sprintf("before-%s\n", $this->name);
        $res = $handler->handle($req);
        self::$output .= sprintf("after-%s\n", $this->name);
        return $res;
    }

    public function controller($req, $res)
    {
        self::$output .= sprintf("%s\n", $this->name);
        return $res;
    }
}
