<?php
/**
 * @copyright ©2005—2016 Quicken Loans Inc. All rights reserved. Trade Secret, Confidential and Proprietary. Any
 *     dissemination outside of Quicken Loans is strictly prohibited.
 */

namespace QL\Panthor\Bootstrap;

use FastRoute\RouteParser;
use Slim\Interfaces\RouteInterface;
use Slim\Router;
use Mockery;

class FastRouteDispatcherFactoryTest extends \PHPUnit_Framework_TestCase
{
    const ROUTE_FILE = 'route.php';

    /** @var \Mockery\MockInterface */
    private $router;

    public function setUp()
    {
        $this->router = Mockery::mock(Router::class);
    }

    public function testGetDispatcherCaches() {
        $dispatcherFactory = new FastRouteDispatcherFactory(
            $this->router,
            __DIR__,
            self::ROUTE_FILE,
            true
        );

        $route = Mockery::mock(RouteInterface::class);
        $routes = [$route];

        $route->shouldReceive('getMethods')
            ->andReturn('method');
        $route->shouldReceive('getPattern')
            ->andReturn('pattern');
        $route->shouldReceive('getIdentifier')
            ->andReturn('id');

        $this->router->shouldReceive('getRoutes')
            ->andReturn($routes);

        $this->assertEquals(
            $dispatcherFactory->loadCachedDispatcher(),
            $dispatcherFactory->getDispatcher()
        );

        $cacheFile = $dispatcherFactory->getAbsolutePath();
        $this->assertTrue(file_exists($cacheFile));
        unlink($cacheFile);
    }

    public function testGetDispatcherNotCached() {
        $dispatcherFactory = new FastRouteDispatcherFactory(
            $this->router,
            __DIR__,
            self::ROUTE_FILE,
            false
        );

        $route = Mockery::mock(RouteInterface::class);
        $routes = [$route];

        $route->shouldReceive('getMethods')
            ->andReturn('method');
        $route->shouldReceive('getPattern')
            ->andReturn('pattern');
        $route->shouldReceive('getIdentifier')
            ->andReturn('id');

        $this->router->shouldReceive('getRoutes')
            ->andReturn($routes);

        $this->assertEquals(
            $dispatcherFactory->getDefaultDispatcher(),
            $dispatcherFactory->getDispatcher()
        );

        $cacheFile = $dispatcherFactory->getAbsolutePath();
        $this->assertFalse(file_exists($cacheFile));
    }

    public function testGetAbsolutePathIsNul() {
        $dispatcherFactory = new FastRouteDispatcherFactory(
            $this->router,
            __DIR__,
            '',
            false
        );

        $this->assertEquals(null,$dispatcherFactory->getAbsolutePath());
    }
}
