<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookie;
use Mockery;
use PHPUnit_Framework_TestCase;
use QL\MCP\Common\OpaqueProperty;
use QL\Panthor\HTTP\CookieEncryptionInterface;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class EncryptedCookiesMiddlewareTest extends PHPUnit_Framework_TestCase
{
    private $encryption;

    private $request;
    private $reponse;

    private $capturedRequest;

    public function setUp()
    {
        $this->encryption = Mockery::mock(CookieEncryptionInterface::class);

        $this->request = Request::createFromEnvironment(Environment::mock());
        $this->response = new Response;

        $this->capturedRequest = null;
    }

    public function nextMiddleware($req, $res)
    {
        $this->capturedRequest = $req;
        return $res;
    }

    public function testCookiesDecryptedAndSetAsAttribute()
    {
        $request = $this->request
            ->withHeader('Cookie', 'cookietest1=abcde;cookietest2=12345');

        $this->encryption
            ->shouldReceive('decrypt')
            ->with('abcde')
            ->andReturn('decrypted_abcde');
        $this->encryption
            ->shouldReceive('decrypt')
            ->with('12345')
            ->andReturn('decrypted_12345');

        $mw = new EncryptedCookiesMiddleware($this->encryption, [], true);
        $response = $mw($request, $this->response, [$this, 'nextMiddleware']);

        $this->assertInstanceof(Request::class, $this->capturedRequest);

        $reqCookies = $this->capturedRequest->getAttribute('request_cookies');
        $this->assertInstanceof(Cookies::class, $reqCookies);

        $cookie1 = $reqCookies->get('cookietest1');
        $this->assertSame('decrypted_abcde', $cookie1->getValue()->getValue());

        $cookie2 = $reqCookies->get('cookietest2');
        $this->assertSame('decrypted_12345', $cookie2->getValue()->getValue());
    }

    public function testInvalidCookieDeleted()
    {
        $request = $this->request
            ->withHeader('Cookie', 'cookietest1=abcde;cookietest2=12345');

        $this->encryption
            ->shouldReceive('decrypt')
            ->with('abcde')
            ->andReturn('decrypted_abcde');
        $this->encryption
            ->shouldReceive('decrypt')
            ->with('12345')
            ->andReturnNull();
        $this->encryption
            ->shouldReceive(['encrypt' => null]);

        $mw = new EncryptedCookiesMiddleware($this->encryption, [], true);
        $response = $mw($request, $this->response, [$this, 'nextMiddleware']);

        $this->assertInstanceof(Request::class, $this->capturedRequest);

        $reqCookies = $this->capturedRequest->getAttribute('request_cookies');
        $this->assertInstanceof(Cookies::class, $reqCookies);

        $cookie1 = $reqCookies->get('cookietest1');
        $this->assertSame('decrypted_abcde', $cookie1->getValue()->getValue());

        $cookie2 = $reqCookies->get('cookietest2');
        $this->assertSame(null, $cookie2);

        $resCookie = $response->getHeaderLine('Set-Cookie');
        $this->assertStringStartsWith('cookietest2=;', $resCookie);
    }

    public function testInvalidCookieIgnoredWhenConfigured()
    {
        $request = $this->request
            ->withHeader('Cookie', 'cookietest1=abcde;cookietest2=12345');

        $this->encryption
            ->shouldReceive('decrypt')
            ->with('abcde')
            ->andReturn('decrypted_abcde');
        $this->encryption
            ->shouldReceive('decrypt')
            ->with('12345')
            ->andReturnNull();
        $this->encryption
            ->shouldReceive('encrypt')
            ->never();

        $mw = new EncryptedCookiesMiddleware($this->encryption, [], false);
        $response = $mw($request, $this->response, [$this, 'nextMiddleware']);

        $this->assertInstanceof(Request::class, $this->capturedRequest);

        $reqCookies = $this->capturedRequest->getAttribute('request_cookies');
        $this->assertInstanceof(Cookies::class, $reqCookies);

        $cookie1 = $reqCookies->get('cookietest1');
        $this->assertSame('decrypted_abcde', $cookie1->getValue()->getValue());

        $cookie2 = $reqCookies->get('cookietest2');
        $this->assertSame(null, $cookie2);

        $resCookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('', $resCookie);
    }

    public function testCookieAllowedUnencrypted()
    {
        $request = $this->request
            ->withHeader('Cookie', 'cookietest1=abcde;cookietest2=12345');

        $this->encryption
            ->shouldReceive('decrypt')
            ->with('12345')
            ->andReturnNull();
        $this->encryption
            ->shouldReceive('encrypt')
            ->never();

        $mw = new EncryptedCookiesMiddleware($this->encryption, ['cookietest1'], false);
        $response = $mw($request, $this->response, [$this, 'nextMiddleware']);

        $this->assertInstanceof(Request::class, $this->capturedRequest);

        $reqCookies = $this->capturedRequest->getAttribute('request_cookies');
        $this->assertInstanceof(Cookies::class, $reqCookies);

        $cookie1 = $reqCookies->get('cookietest1');
        $this->assertSame('abcde', $cookie1->getValue());

        $cookie2 = $reqCookies->get('cookietest2');
        $this->assertSame(null, $cookie2);

        $resCookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('', $resCookie);
    }

    public function testCookiesSetInApplication()
    {
        $this->encryption
            ->shouldReceive('encrypt')
            ->with('12345')
            ->andReturn('12345_encrypted');
        $this->encryption
            ->shouldReceive('encrypt')
            ->with('vwxyz')
            ->andReturn('vwxyz_encrypted');

        $appMiddleware = function($req, $res) {
           return $res
                ->withAddedHeader('Set-Cookie', 'cookietest1=abcde')
                ->withAddedHeader('Set-Cookie', SetCookie::create('cookietest2', '12345'))
                ->withAddedHeader('Set-Cookie', new \stdClass)
                ->withAddedHeader('Set-Cookie', SetCookie::create('cookietest4', new OpaqueProperty('vwxyz')));
        };

        $mw = new EncryptedCookiesMiddleware($this->encryption, ['cookietest1'], false);
        $response = $mw($this->request, $this->response, $appMiddleware);

        $resCookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('cookietest1=abcde,cookietest2=12345_encrypted,cookietest4=vwxyz_encrypted', $resCookie);
    }
}
