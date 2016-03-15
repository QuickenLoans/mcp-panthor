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
use Slim\Http\Cookies;
use Mockery;

class EncryptedCookiesMiddlewareTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Mockery\MockInterface */
    private $json;
    /** @var \Mockery\MockInterface */
    private $encryption;
    /** @var \Mockery\MockInterface */
    private $request;
    /** @var \Mockery\MockInterface */
    private $response;

    public function setUp()
    {
        $this->json = Mockery::mock(Json::class);
        $this->encryption = Mockery::mock(CookieEncryptionInterface::class);
        $this->request = Mockery::mock(ServerRequestInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
    }

    public function testNoneEncrypted()
    {
        $unencryptedCookies = ['unencrypted'];
        $cookies = [
            'unencrypted' => [
                'value' => 'stuff',
                'domain' => 'place.com'
            ]
        ];
        $next = function ($request, $response) {return $response;};

        $this->request->shouldReceive('getCookieParams')
            ->andReturn($cookies)
            ->byDefault();
        $this->request->shouldReceive('withCookieParams')
            ->andReturn($this->request);

        $this->response->shouldReceive('withHeader')
            ->andReturn($this->response);

        $mw = new EncryptedCookiesMiddleware($this->json, $this->encryption, $unencryptedCookies);
        $this->assertEquals($this->response, $mw($this->request, $this->response, $next));
    }

    public function testEncrypted()
    {
        $encryptedValue = 'stuff';
        $unencryptedValue = 'thing';
        $unEncodedValue = 'blah';
        $unencryptedCookies = ['unencrypted'];
        $encryptedCookies = [
            'encrypted' => [
                'value' => $encryptedValue,
                'domain' => 'place.com'
            ]
        ];
        $unencryptedCookie = [
            'encrypted' => [
                'value' => $unEncodedValue,
                'domain' => 'place.com'
            ]
        ];
        $next = function ($request, $response) {return $response;};

        $this->request->shouldReceive('getCookieParams')
            ->andReturn($encryptedCookies)
            ->once();
        $this->request->shouldReceive('getCookieParams')
            ->andReturn($unencryptedCookie)
            ->once();
        $this->request->shouldReceive('withCookieParams')
            ->andReturn($this->request);

        $this->response->shouldReceive('withHeader')
            ->andReturn($this->response);

        $this->encryption->shouldReceive('decrypt')
            ->with($encryptedValue)
            ->andReturn($unencryptedValue);
        $this->encryption->shouldReceive('encrypt')
            ->with($unencryptedValue)
            ->andReturn($encryptedValue);

        $this->json->shouldReceive('decode')
            ->with($unencryptedValue)
            ->andReturn($unEncodedValue);
        $this->json->shouldReceive('encode')
            ->with($unEncodedValue)
            ->andReturn($unencryptedValue);

        $mw = new EncryptedCookiesMiddleware($this->json, $this->encryption, $unencryptedCookies);
        $this->assertEquals($this->response, $mw($this->request, $this->response, $next));
    }

    public function decryptionProvider()
    {
        return [
            ['decrypted' => 'decrypted', 'decoded' => 'decoded', 'expected' => ['value' => 'decoded', 'domain' => 'blah']],
            ['decrypted' => 'decrypted', 'decoded' => null, 'expected' => ['value' => 'decrypted', 'domain' => 'blah']],
            ['decrypted' => '', 'decoded' => null, 'expected' => ['value' => null, 'domain' => null]],
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
        $cookie = [
            'value' => $encrypted,
            'domain' => 'blah'
        ];

        $this->encryption->shouldReceive('decrypt')
            ->with($encrypted)
            ->andReturn($decrypted);

        $this->json->shouldReceive('decode')
            ->with($decrypted)
            ->andReturn($decoded);

        $mw = new EncryptedCookiesMiddleware($this->json, $this->encryption);
        $this->assertEquals($expected, $mw->decrypt($cookie));
    }
}
