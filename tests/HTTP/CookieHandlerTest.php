<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP;

use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\Cookies;
use Mockery;
use PHPUnit\Framework\TestCase;
use QL\MCP\Common\OpaqueProperty;
use QL\Panthor\Exception\Exception;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class CookieHandlerTest extends TestCase
{
    private $request;
    private $reponse;

    public function setUp()
    {
        $this->request = Request::createFromEnvironment(Environment::mock());
        $this->response = new Response;
    }

    public function testBadExpiresCookieConfiguration()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(CookieHandler::ERR_BAD_EXPIRES);

        new CookieHandler([
            'expires' => ['bad-value']
        ]);
    }

    public function testBadSecureCookieConfiguration()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(CookieHandler::ERR_BAD_SECURE);

        new CookieHandler([
            'secure' => ['bad-value']
        ]);
    }

    public function testBadHttpOnlyCookieConfiguration()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(CookieHandler::ERR_BAD_HTTP);

        new CookieHandler([
            'httpOnly' => ['bad-value']
        ]);
    }

    public function testGettingMissingCookies()
    {
        $handler = new CookieHandler;

        $cookie = $handler->getCookie($this->request, 'cookie');
        $this->assertSame(null, $cookie);
    }

    public function testGettingMissingCookie()
    {
        $cookies = Cookies::fromCookieString('testcookie1=abcde;testcookie2=12345');
        $request = $this->request->withAttribute('request_cookies', $cookies);

        $handler = new CookieHandler;

        $cookie = $handler->getCookie($request, 'cookie');
        $this->assertSame(null, $cookie);
    }

    public function testGetCookie()
    {
        $cookies = Cookies::fromCookieString('testcookie1=abcde;testcookie2=12345');
        $request = $this->request->withAttribute('request_cookies', $cookies);

        $handler = new CookieHandler;

        $cookie = $handler->getCookie($request, 'testcookie1');
        $this->assertSame('abcde', $cookie);
    }

    public function testGetObscuredCookie()
    {
        $cookies = Cookies::fromCookieString('testcookie1=abcde;testcookie2=12345')
            ->with(Cookie::create('testcookie3', new OpaqueProperty('vwxyz')));
        $request = $this->request->withAttribute('request_cookies', $cookies);

        $handler = new CookieHandler;

        $cookie = $handler->getCookie($request, 'testcookie3');
        $this->assertSame('vwxyz', $cookie);
    }

    public function testExpiringCookie()
    {
        $handler = new CookieHandler;

        $response = $handler->expireCookie($this->response, 'testcookie1');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertStringStartsWith('testcookie1=; Expires=', $cookie);
    }

    public function testSetCookieObscuresValueIfNotEncrypted()
    {
        $handler = new CookieHandler;

        $response = $handler->withCookie($this->response, 'testcookie1', 'value1');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('testcookie1=%5Bopaque+property%5D', $cookie);
    }

    public function testSetCookieWithExpiry()
    {
        $handler = new CookieHandler;

        $response = $handler->withCookie($this->response, 'testcookie1', 'value1', '+7 days');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertStringStartsWith('testcookie1=%5Bopaque+property%5D; Expires=', $cookie);
    }

    public function testSetCookieWithConfiguration()
    {
        $handler = new CookieHandler([
            'expires' => '+30 days',
            'maxAge' => '',
            'path' => '/page',
            'domain' => 'example.com',
            'secure' => true,
            'httpOnly' => true,
        ]);

        $response = $handler->withCookie($this->response, 'testcookie1', 'value1', '+7 days');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertContains('testcookie1=%5Bopaque+property%5D;', $cookie);
        $this->assertContains('Domain=example.com;', $cookie);
        $this->assertContains('Path=/page;', $cookie);
        $this->assertContains('Secure;', $cookie);
        $this->assertContains('HttpOnly', $cookie);

        $this->assertContains('Expires=', $cookie);
    }
}
