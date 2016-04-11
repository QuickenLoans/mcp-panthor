<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Utility;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Router;
use Slim\Route;

class UrlTest extends PHPUnit_Framework_TestCase
{
    /** @var \Mockery\MockInterface */
    private $router;
    /** @var \Mockery\MockInterface */
    private $request;
    /** @var \Mockery\MockInterface */
    private $response;
    /** @var \Mockery\MockInterface */
    private $halt;

    public function setUp()
    {
        $this->router = Mockery::mock(Router::CLASS);
        $this->request = Mockery::mock(Request::CLASS);
        $this->response = Mockery::mock(Response::CLASS);

        $this->halt = function() {};
    }

    public function testCurrentRouteReturnsNullIfNoRouteMatches()
    {
        $this->request
            ->shouldReceive('getAttribute')
            ->with('route')
            ->andReturnNull();

        $url = new Url($this->router, $this->request, $this->response, $this->halt);

        $this->assertSame(null, $url->currentRoute());
    }

    public function testCurrentRouteReturnsRouteName()
    {
        $this->request
            ->shouldReceive('getAttribute')
            ->with('route')
            ->andReturn(Mockery::mock(Route::CLASS, [
                'getName' => 'route.name'
            ]));

        $url = new Url($this->router, $this->request, $this->response, $this->halt);

        $this->assertSame('route.name', $url->currentRoute());
    }

    public function testUrlForReturnsEmptyStringIfNone()
    {
        $url = new Url($this->router, $this->request, $this->response, $this->halt);

        $this->assertSame('', $url->urlFor('', ['param1' => '1']));
    }

    public function testUrlGetsRouteAndAppendsQueryString()
    {
        $this->router
            ->shouldReceive('urlFor')
            ->with('route.name', ['param1' => '1'])
            ->andReturn('/path');

        $url = new Url($this->router, $this->request, $this->response, $this->halt);

        $actual = $url->urlFor('route.name', ['param1' => '1'], ['query1' => '2']);
        $this->assertSame('/path?query1=2', $actual);
    }

    public function testAbsoluteUrlGetsRouteAndAppendsQueryString()
    {
        $uri = Mockery::mock(Uri::class);
        $uri->shouldReceive('__toString')
            ->andReturn('http://example.com');
        $uri->shouldReceive('getPort')
            ->andReturnNull();
        $this->request
            ->shouldReceive('getUri')
            ->andReturn($uri);
        $this->router
            ->shouldReceive('urlFor')
            ->with('route.name', ['param1' => '1'])
            ->andReturn('/path');

        $url = new Url($this->router, $this->request, $this->response, $this->halt);

        $actual = $url->absoluteUrlFor('route.name', ['param1' => '1'], ['query1' => '2']);
        $this->assertSame('http://example.com/path?query1=2', $actual);
    }

    public function testAbsoluteUrlGetsRouteAndAppendsPortWhenNotStandard()
    {
        $uri = Mockery::mock(Uri::class);
        $uri->shouldReceive('__toString')
            ->andReturn('http://example.com');
        $uri->shouldReceive('getPort')
            ->andReturn('2345');
        $this->request
            ->shouldReceive('getUri')
            ->andReturn($uri);
        $this->router
            ->shouldReceive('urlFor')
            ->with('route.name', ['param1' => '1'])
            ->andReturn('/path');

        $url = new Url($this->router, $this->request, $this->response, $this->halt);

        $actual = $url->absoluteUrlFor('route.name', ['param1' => '1'], ['query1' => '2']);
        $this->assertSame('http://example.com:2345/path?query1=2', $actual);
    }

    public function testRedirectForRetrievesRoute()
    {
        $uri = Mockery::mock(Uri::class);
        $uri->shouldReceive('__toString')
            ->andReturn('http://example.com');
        $uri->shouldReceive('getPort')
            ->andReturnNull();
        $this->request
            ->shouldReceive('getUri')
            ->andReturn($uri);
        $this->router
            ->shouldReceive('urlFor')
            ->with('route.name', [])
            ->andReturn('/path');

//        $headers = Mockery::mock(Headers::CLASS);
//        $headers
//            ->shouldReceive('set')
//            ->with('Location', 'http://example.com/path')
//            ->once();
        $this->response
            ->shouldReceive('withHeader')
            ->with('Location', 'http://example.com/path')
            ->andReturn($this->response);
        $this->response
            ->shouldReceive('withStatus')
            ->with(302)
            ->andReturn($this->response);

        $code = null;
        $this->halt = function($v) use (&$code) {
            $code = $v;
        };

        $url = new Url($this->router, $this->request, $this->response, $this->halt);

        $url->redirectFor('route.name');
        $this->assertSame(302, $code);
    }
}

