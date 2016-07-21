<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Http\Uri as SlimUri;
use Slim\Router;

class URITest extends PHPUnit_Framework_TestCase
{
    private $router;

    public function setUp()
    {
        $this->router = Mockery::mock(Router::class);
    }

    public function testUrlForReturnsEmptyStringIfNone()
    {
        $url = new URI($this->router);

        $this->assertSame('', $url->uriFor('', ['param1' => '1']));
    }

    public function testUrlGetsRouteAndAppendsQueryString()
    {
        $this->router
            ->shouldReceive('relativePathFor')
            ->with('route.name', ['param1' => '1'], ['query1' => '2'])
            ->andReturn('/path?query1=2');

        $url = new URI($this->router);

        $actual = $url->uriFor('route.name', ['param1' => '1'], ['query1' => '2']);
        $this->assertSame('/path?query1=2', $actual);
    }

    public function testAbsoluteUrlGetsRoute()
    {
        $uri = SlimUri::createFromString('https://example.com/path/page?query=1');

        $this->router
            ->shouldReceive('relativePathFor')
            ->with('route.name', ['param1' => '1'], [])
            ->andReturn('/test-route-page');

        $url = new URI($this->router);

        $actual = $url->absoluteURIFor($uri, 'route.name', ['param1' => '1']);
        $this->assertSame('https://example.com/test-route-page', $actual);
    }

    public function testAbsoluteUrlGetsRouteAndAppendsPortWhenNotStandard()
    {
        $uri = SlimUri::createFromString('http://host:8443/path/page?query=1');

        $this->router
            ->shouldReceive('relativePathFor')
            ->with('route.name', ['param1' => '1'], [])
            ->andReturn('/test-route-page');

        $url = new URI($this->router);

        $actual = $url->absoluteURIFor($uri, 'route.name', ['param1' => '1']);
        $this->assertSame('http://host:8443/test-route-page', $actual);
    }
}

