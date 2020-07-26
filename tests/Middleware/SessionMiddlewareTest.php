<?php

namespace QL\Panthor\Middleware;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\Session\SessionInterface;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class SessionMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public $handler;
    public $request;
    public $reqHandler;
    public $capturedRequest;

    public function setUp()
    {
        $this->handler = Mockery::mock(CookieHandler::class);

        $this->request = (new RequestFactory)->createRequest('GET', '/path');
        $this->response = (new ResponseFactory)->createResponse();

        $this->capturedRequest = null;

        $this->reqHandler = new class($this) implements RequestHandlerInterface {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->test->capturedRequest = $request;
                return (new ResponseFactory)->createResponse();
            }
        };
    }

    public function testNewSession()
    {
        $request = $this->request;
        $this->handler
            ->shouldReceive('getCookie')
            ->with($request, 'session')
            ->andReturnNull();

        $mw = new SessionMiddleware($this->handler, []);
        $response = $mw->process($request, $this->reqHandler);

        $this->assertInstanceof(ServerRequestInterface::class, $this->capturedRequest);

        $session = $this->capturedRequest->getAttribute('session');
        $this->assertInstanceof(SessionInterface::class, $session);
    }

    public function testDeserializedSession()
    {
        $request = $this->request;
        $this->handler
            ->shouldReceive('getCookie')
            ->with($request, 'session')
            ->andReturn('{"derp": 123}');

        $mw = new SessionMiddleware($this->handler, []);
        $response = $mw->process($request, $this->reqHandler);

        $session = $this->capturedRequest->getAttribute('session');
        $this->assertSame(123, $session->get('derp'));
    }

    public function testSerializeSessionIfChanged()
    {
        $request = $this->request;

        $this->handler
            ->shouldReceive('getCookie')
            ->with($request, 'cookie_session')
            ->andReturn('{"derp": 123}');

        $modifier = new class($this) implements RequestHandlerInterface {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $request->getAttribute('custom_session')->set('herp', 456);
                return $this->test->response;
            }
        };

        $this->handler
            ->shouldReceive('withCookie')
            ->with($this->response, 'cookie_session', '{"derp":123,"herp":456}', '+1 day')
            ->andReturn($this->response);

        $mw = new SessionMiddleware($this->handler, [
            'request_attribute' => 'custom_session',
            'cookie_name' => 'cookie_session',
            'lifetime' => '+1 day'
        ]);
        $response = $mw->process($request, $modifier);

        $this->assertInstanceof(ResponseInterface::class, $response);
    }
}
