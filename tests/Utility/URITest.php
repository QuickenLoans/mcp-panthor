<?php

namespace QL\Panthor\Utility;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Slim\Interfaces\RouteParserInterface;
use Slim\Psr7\Factory\UriFactory;

class URITest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $router;

    public function setUp()
    {
        $this->router = Mockery::mock(RouteParserInterface::class);
    }

    public function testUrlForReturnsEmptyStringIfNone()
    {
        $url = new URI($this->router);

        $this->assertSame('', $url->uriFor('', ['param1' => '1']));
    }

    public function testUrlGetsRouteAndAppendsQueryString()
    {
        $this->router
            ->shouldReceive('relativeUrlFor')
            ->with('route.name', ['param1' => '1'], ['query1' => '2'])
            ->andReturn('/path?query1=2');

        $url = new URI($this->router);

        $actual = $url->uriFor('route.name', ['param1' => '1'], ['query1' => '2']);
        $this->assertSame('/path?query1=2', $actual);
    }

    public function testAbsoluteUrlGetsRoute()
    {
        $uri = (new UriFactory)->createUri('https://example.com/path/page?query=1');

        $this->router
            ->shouldReceive('relativeUrlFor')
            ->with('route.name', ['param1' => '1'], [])
            ->andReturn('/test-route-page');

        $url = new URI($this->router);

        $actual = $url->absoluteURIFor($uri, 'route.name', ['param1' => '1']);
        $this->assertSame('https://example.com/test-route-page', $actual);
    }

    public function testAbsoluteUrlGetsRouteAndAppendsPortWhenNotStandard()
    {
        $uri = (new UriFactory)->createUri('http://host:8443/path/page?query=1');

        $this->router
            ->shouldReceive('relativeUrlFor')
            ->with('route.name', ['param1' => '1'], [])
            ->andReturn('/test-route-page');

        $url = new URI($this->router);

        $actual = $url->absoluteURIFor($uri, 'route.name', ['param1' => '1']);
        $this->assertSame('http://host:8443/test-route-page', $actual);
    }
}
