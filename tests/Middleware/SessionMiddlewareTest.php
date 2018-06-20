<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Mockery;
use PHPUnit\Framework\TestCase;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\Session\SessionInterface;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use QL\Panthor\Testing\MockeryAssistantTrait;

class SessionMiddlewareTest extends TestCase
{
    use MockeryAssistantTrait;

    private $handler;

    private $request;
    private $reponse;

    private $capturedRequest;

    public function setUp()
    {
        $this->handler = Mockery::mock(CookieHandler::class);

        $this->request = Request::createFromEnvironment(Environment::mock());
        $this->response = new Response;

        $this->capturedRequest = null;
    }

    public function nextMiddleware($req, $res)
    {
        $this->capturedRequest = $req;
        return $res;
    }

    public function testNewSession()
    {
        $request = $this->request;
        $this->handler
            ->shouldReceive('getCookie')
            ->with($request, 'session')
            ->andReturnNull();

        $mw = new SessionMiddleware($this->handler, []);
        $response = $mw($request, $this->response, [$this, 'nextMiddleware']);

        $this->assertInstanceof(Request::class, $this->capturedRequest);

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
        $response = $mw($request, $this->response, [$this, 'nextMiddleware']);

        $session = $this->capturedRequest->getAttribute('session');
        $this->assertSame(123, $session->get('derp'));
    }

    public function testSerializeSessionIfChanged()
    {
        $request = $this->request;
        $response = $this->response;

        $this->handler
            ->shouldReceive('getCookie')
            ->with($request, 'cookie_session')
            ->andReturn('{"derp": 123}');

        $modifier = function($req, $res) {
            $req->getAttribute('custom_session')->set('herp', 456);
            return $res;
        };

        $this->handler
            ->shouldReceive('withCookie')
            ->with($response, 'cookie_session', '{"derp":123,"herp":456}', '+1 day')
            ->andReturn($response);

        $mw = new SessionMiddleware($this->handler, [
            'request_attribute' => 'custom_session',
            'cookie_name' => 'cookie_session',
            'lifetime' => '+1 day'
        ]);
        $response = $mw($request, $response, $modifier);

        $this->assertInstanceof(Response::class, $response);
    }
}
