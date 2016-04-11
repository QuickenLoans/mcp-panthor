<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use QL\Panthor\Utility\Json;
use QL\Panthor\Http\CookieEncryptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use QL\Panthor\Utility\CookieTool;
use Mockery;

class EncryptedCookiesMiddlewareTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Mockery\MockInterface */
    private $json;
    /** @var \Mockery\MockInterface */
    private $encryption;
    /** @var \Mockery\MockInterface */
    private $cookieTool;
    /** @var \Mockery\MockInterface */
    private $request;
    /** @var \Mockery\MockInterface */
    private $response;

    public function setUp()
    {
        $this->json = Mockery::mock(Json::class);
        $this->encryption = Mockery::mock(CookieEncryptionInterface::class);
        $this->cookieTool = Mockery::mock(CookieTool::class);
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
    }

    public function testNoneEncrypted()
    {
        $unencryptedCookies = ['unencrypted'];
        $cookies = [
            'unencrypted' => 'stuff'
        ];
        $cookieHeaders = ['cookieHeaders'];
        $next = function ($request, $response) {return $this->response;};
        $decryptedResponse = Mockery::mock(ResponseInterface::class);

        $this->request->shouldReceive('getCookieParams')
            ->andReturn($cookies)
            ->byDefault();

        $this->encryption->shouldReceive('decrypt')
            ->with('stuff')
            ->andReturn(null);

        $this->cookieTool->shouldReceive('setCookies')
            ->andReturn($decryptedResponse);
        $this->cookieTool->shouldReceive('getRawCookies')
            ->with($this->response)
            ->andReturn($cookies);
        $this->cookieTool->shouldReceive('setCookies')
            ->andReturn($decryptedResponse);

        $decryptedResponse->shouldReceive('getHeader')
            ->with('Set-Cookie')
            ->andReturn($cookieHeaders);

        $this->response->shouldReceive('withHeader')
            ->withArgs(['Set-Cookie', $cookieHeaders])
            ->andReturn($this->response);

        $mw = new EncryptedCookiesMiddleware($this->json, $this->encryption, $this->cookieTool, $unencryptedCookies);
        $this->assertEquals($this->response, $mw($this->request, $this->response, $next));
    }

    public function testEncrypted()
    {
        $encryptedValue = 'stuff';
        $unencryptedValue = 'thing';
        $unEncodedValue = 'blah';
        $unencryptedCookies = ['unencrypted'];
        $encryptedCookies = [
            'encrypted' => $encryptedValue
        ];
        $unencryptedCookie = [
            'encrypted' => $unEncodedValue
        ];
        $cookieHeaders = ['cookieHeaders'];
        $next = function ($request, $response) {return $this->response;};
        $decryptedResponse = Mockery::mock(ResponseInterface::class);

        $this->request->shouldReceive('getCookieParams')
            ->andReturn($encryptedCookies)
            ->byDefault();

        $this->encryption->shouldReceive('decrypt')
            ->with($encryptedValue)
            ->andReturn($unencryptedValue);
        $this->encryption->shouldReceive('encrypt')
            ->with($unencryptedValue)
            ->andReturn($encryptedValue);

        $this->cookieTool->shouldReceive('setCookies')
            ->andReturn($decryptedResponse);
        $this->cookieTool->shouldReceive('getRawCookies')
            ->with($this->response)
            ->andReturn($unencryptedCookie);
        $this->cookieTool->shouldReceive('setCookies')
            ->andReturn($decryptedResponse);

        $decryptedResponse->shouldReceive('getHeader')
            ->with('Set-Cookie')
            ->andReturn($cookieHeaders);

        $this->json->shouldReceive('decode')
            ->with($unencryptedValue)
            ->andReturn($unEncodedValue);
        $this->json->shouldReceive('encode')
            ->with($unEncodedValue)
            ->andReturn($unencryptedValue);

        $this->response->shouldReceive('withHeader')
            ->andReturn($this->response);

        $mw = new EncryptedCookiesMiddleware($this->json, $this->encryption, $this->cookieTool, $unencryptedCookies);
        $this->assertEquals($this->response, $mw($this->request, $this->response, $next));
    }

    public function decryptionProvider()
    {
        return [
            ['decrypted' => 'decrypted', 'decoded' => 'decoded', 'expected' => 'decoded'],
            ['decrypted' => 'decrypted', 'decoded' => null, 'expected' => 'decrypted'],
            ['decrypted' => '', 'decoded' => null, 'expected' => null],
        ];
    }

    /**
     * @dataProvider decryptionProvider
     *
     * @param string $decrypted
     * @param string $decoded
     * @param array $expected
     */
    public function testDecryptionScenarios($decrypted, $decoded, $expected)
    {
        $encrypted = 'encrypted';
        $expected = is_null($expected)? $encrypted:$expected;

        $this->encryption->shouldReceive('decrypt')
            ->with($encrypted)
            ->andReturn($decrypted);

        $this->json->shouldReceive('decode')
            ->with($decrypted)
            ->andReturn($decoded);

        $mw = new EncryptedCookiesMiddleware($this->json, $this->encryption, $this->cookieTool);
        $this->assertEquals($expected, $mw->decrypt('blah', $encrypted));
    }
}
