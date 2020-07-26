<?php

namespace QL\Panthor\Bootstrap;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Slim\Routing\RouteCollector;

class CacheableRouteCollectorConfiguratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public $collector;

    public function setUp()
    {
        $this->collector = Mockery::mock(RouteCollector::class);
    }

    public function testCachingEnabled()
    {
        $this->collector
            ->shouldReceive('setCacheFile')
            ->with('/path/file.php')
            ->times(1);

        $configurator = new CacheableRouteCollectorConfigurator('/path/file.php', false);
        $configurator($this->collector);
    }

    public function testCachingDisabled()
    {
        $this->collector
            ->shouldReceive('setCacheFile')
            ->with('/path/file.php')
            ->never();

        $configurator = new CacheableRouteCollectorConfigurator('/path/file.php', true);
        $configurator($this->collector);
    }
}
