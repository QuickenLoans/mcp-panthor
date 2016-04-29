<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Bootstrap;

use PHPUnit_Framework_TestCase;
use Slim\App;
use Slim\RouteGroup;

class RouteLoaderTest extends PHPUnit_Framework_TestCase
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
