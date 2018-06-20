<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\RouteGroup;

class RouteLoaderTest extends TestCase
{
    public $slim;

    public function setUp()
    {
        $this->slim = new App;
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

        $routes = $this->slim->getContainer()->get('router')->getRoutes();

        $this->assertCount(2, $routes);
    }

    public function testMultipleMiddlewareAreOrderedCorrectlyInReverse()
    {
        $di = $this->slim->getContainer();
        $di['m.one'] = new TestCallable('one');
        $di['m.two'] = new TestCallable('two');
        $di['m.three'] = new TestCallable('three');
        $di['gm.one'] = new TestCallable('gm.one');
        $di['gm.two'] = new TestCallable('gm.two');
        $di['page'] = new TestCallable('controller');

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

        $routes = $di->get('router')->getRoutes();

        $route = array_shift($routes);
        $route->finalize();
        $route->run($di->get('request'), $di->get('response'));

        $expected = <<<OUTPUT
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

OUTPUT;
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

        $routes = $this->slim->getContainer()->get('router')->getRoutes();

        $this->assertCount(2, $routes);

        $route1 = array_shift($routes);
        $route2 = array_shift($routes);

        $this->assertSame('test2', $route1->getName());
        $this->assertSame(['GET'], $route1->getMethods());
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

        $routes = $this->slim->getContainer()->get('router')->getRoutes();

        $this->assertCount(2, $routes);

        $route1 = array_shift($routes);
        $route2 = array_shift($routes);

        $this->assertSame('test3', $route1->getName());
        $this->assertSame('test', $route2->getName());
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

    public function mw($req, $res, $next)
    {
        self::$output .= sprintf("before-%s\n", $this->name);
        $r = $next($req, $res);
        self::$output .= sprintf("after-%s\n", $this->name);
        return $r;
    }

    public function controller($req, $res)
    {
        self::$output .= sprintf("%s\n", $this->name);
    }
}
