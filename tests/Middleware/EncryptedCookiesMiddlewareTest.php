<?php

namespace QL\Panthor\Middleware;

use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookie;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QL\MCP\Common\OpaqueProperty;
use QL\Panthor\HTTP\CookieEncryptionInterface;
use QL\Panthor\Testing\MockeryAssistantTrait;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class EncryptedCookiesMiddlewareTest extends TestCase
{
    use MockeryAssistantTrait;
    use MockeryPHPUnitIntegration;

    public $encryption;
    public $request;
    public $reqHandler;
    public $capturedRequest;

    public function setUp(): void
    {
        $this->encryption = Mockery::mock(CookieEncryptionInterface::class);

        $this->request = (new RequestFactory)->createRequest('GET', '/path');

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
        $response = $mw->process($request, $this->reqHandler);

        $this->assertInstanceof(ServerRequestInterface::class, $this->capturedRequest);

        $reqCookies = $this->capturedRequest->getAttribute('request_cookies');
        $this->assertSame(['cookietest1', 'cookietest2'], array_keys($reqCookies));

        $this->assertSame('decrypted_abcde', $reqCookies['cookietest1']->getValue());
        $this->assertSame('decrypted_12345', $reqCookies['cookietest2']->getValue());
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
        $response = $mw->process($request, $this->reqHandler);

        $this->assertInstanceof(ServerRequestInterface::class, $this->capturedRequest);

        $reqCookies = $this->capturedRequest->getAttribute('request_cookies');
        $this->assertSame(['cookietest1'], array_keys($reqCookies));

        $this->assertSame('decrypted_abcde', $reqCookies['cookietest1']->getValue());

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
        $response = $mw->process($request, $this->reqHandler);

        $this->assertInstanceof(ServerRequestInterface::class, $this->capturedRequest);

        $reqCookies = $this->capturedRequest->getAttribute('request_cookies');
        $this->assertSame(['cookietest1'], array_keys($reqCookies));

        $this->assertSame('decrypted_abcde', $reqCookies['cookietest1']->getValue());

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
        $response = $mw->process($request, $this->reqHandler);

        $this->assertInstanceof(ServerRequestInterface::class, $this->capturedRequest);

        $reqCookies = $this->capturedRequest->getAttribute('request_cookies');
        $this->assertSame(['cookietest1'], array_keys($reqCookies));

        $this->assertSame('abcde', $reqCookies['cookietest1']);

        $resCookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('', $resCookie);
    }

    public function testCookiesSetInApplicationAreNotEncryptedByMiddleware()
    {
        $appMiddleware = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $res = (new ResponseFactory)->createResponse();

               return $res
                    ->withAddedHeader('Set-Cookie', 'cookietest1=abcde')
                    ->withAddedHeader('Set-Cookie', (string) SetCookie::create('cookietest2', '12345'));
            }
        };

        $mw = new EncryptedCookiesMiddleware($this->encryption, ['cookietest1'], false);
        $response = $mw->process($this->request, $appMiddleware);

        $resCookie = $response->getHeaderLine('Set-Cookie');
        $this->assertSame('cookietest1=abcde,cookietest2=12345', $resCookie);
    }
}
