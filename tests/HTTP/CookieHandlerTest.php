<?php

namespace QL\Panthor\HTTP;

use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\Cookies;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use QL\MCP\Common\OpaqueProperty;
use QL\Panthor\Exception\Exception;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class CookieHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $request;
    private $reponse;

    public function setUp()
    {
        $this->encryption = Mockery::mock(CookieEncryptionInterface::class);

        $this->request = (new RequestFactory)->createRequest('GET', '/path');
        $this->response = (new ResponseFactory)->createResponse();
    }

    public function testBadExpiresCookieConfiguration()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(CookieHandler::ERR_BAD_MAX_AGE);

        new CookieHandler($this->encryption, [], [
            'maxAge' => ['bad-value']
        ]);
    }

    public function testBadSecureCookieConfiguration()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(CookieHandler::ERR_BAD_SECURE);

        new CookieHandler($this->encryption, [], [
            'secure' => ['bad-value']
        ]);
    }

    public function testBadHttpOnlyCookieConfiguration()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(CookieHandler::ERR_BAD_HTTP);

        new CookieHandler($this->encryption, [], [
            'httpOnly' => ['bad-value']
        ]);
    }

    public function testGettingMissingCookies()
    {
        $handler = new CookieHandler($this->encryption);

        $cookie = $handler->getCookie($this->request, 'cookie');
        $this->assertSame(null, $cookie);
    }

    public function testGettingMissingCookie()
    {
        $request = $this->request->withAttribute('request_cookies', [
            'testcookie1' => 'abcde',
            'testcookie2' => '12345',
        ]);

        $handler = new CookieHandler($this->encryption);

        $cookie = $handler->getCookie($request, 'cookie');
        $this->assertSame(null, $cookie);
    }

    public function testGetCookie()
    {
        $request = $this->request->withAttribute('request_cookies', [
            'testcookie1' => 'abcde',
            'testcookie2' => '12345',
        ]);

        $handler = new CookieHandler($this->encryption);

        $cookie = $handler->getCookie($request, 'testcookie1');
        $this->assertSame('abcde', $cookie);
    }

    public function testGetObscuredCookie()
    {
        $request = $this->request->withAttribute('request_cookies', [
            'testcookie1' => 'abcde',
            'testcookie2' => '12345',
            'testcookie3' => new OpaqueProperty('vwxyz'),
        ]);

        $handler = new CookieHandler($this->encryption);

        $cookie = $handler->getCookie($request, 'testcookie3');
        $this->assertSame('vwxyz', $cookie);
    }

    public function testExpiringCookie()
    {
        $handler = new CookieHandler($this->encryption);

        $response = $handler->expireCookie($this->response, 'testcookie1');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertStringStartsWith('testcookie1=; Expires=', $cookie);
    }

    public function testSetCookieObscuresValueIfNotEncrypted()
    {
        $this->encryption
            ->shouldReceive('encrypt')
            ->with('value1')
            ->andReturn('encrypted_value_here1');

        $handler = new CookieHandler($this->encryption);

        $response = $handler->withCookie($this->response, 'testcookie1', 'value1');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('testcookie1=encrypted_value_here1', $cookie);
    }

    public function testSetCookieWithExpiry()
    {
        $this->encryption
            ->shouldReceive('encrypt')
            ->with('value1')
            ->andReturn('encrypted_value_here2');

        $handler = new CookieHandler($this->encryption);

        $response = $handler->withCookie($this->response, 'testcookie1', 'value1', '+7 days');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('testcookie1=encrypted_value_here2; Max-Age=604800', $cookie);
    }

    public function testSetCookieWithoutEncrypting()
    {
        $this->encryption
            ->shouldReceive('encrypt')
            ->never();

        $handler = new CookieHandler($this->encryption, ['testcookie1']);

        $response = $handler->withCookie($this->response, 'testcookie1', 'value1');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('testcookie1=value1', $cookie);
    }

    public function testSetCookieWithConfiguration()
    {
        $this->encryption
            ->shouldReceive('encrypt')
            ->with('value1')
            ->andReturn('encrypted_value_here3');

        $handler = new CookieHandler($this->encryption, [], [
            'maxAge' => '',
            'path' => '/page',
            'domain' => 'example.com',
            'secure' => true,
            'httpOnly' => true,
        ]);

        $response = $handler->withCookie($this->response, 'testcookie1', 'value1', '+5 minutes');

        $cookie = $response->getHeaderLine('Set-Cookie');
        $this->assertContains('testcookie1=encrypted_value_here3;', $cookie);
        $this->assertContains('Domain=example.com;', $cookie);
        $this->assertContains('Path=/page;', $cookie);
        $this->assertContains('Secure;', $cookie);
        $this->assertContains('HttpOnly', $cookie);

        $this->assertContains('Max-Age=300', $cookie);
    }
}
