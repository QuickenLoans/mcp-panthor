<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Http;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Panthor\Testing\Stub\StringableStub;
use QL\Panthor\Utility\Json;
use Slim\App;

class EncryptedCookiesTest extends PHPUnit_Framework_TestCase
{
    public $slim;
    public $json;
    public $encryption;

    public function setUp()
    {
        $this->slim = Mockery::mock(App::class);
        $this->json = new Json;
        $this->encryption = Mockery::mock('QL\Panthor\Http\CookieEncryptionInterface');
    }

    public function testDeleteCookie()
    {
        $cookies = new EncryptedCookies($this->json, $this->encryption);

        $cookies->deleteCookie('name');
    }

    public function testSetCookie()
    {
        $cookies = new EncryptedCookies($this->json, $this->encryption);

        $params = [
            'value' => 'testValue',
            'time' => 0,
            'path' => '/page',
            'domain' => '.domain.com',
            'secure' => false,
            'httponly' => false
        ];

        $cookies->setCookie('name', $params);
    }

    public function testGetCookieDecryptsCookieFromSlim()
    {
        $this->encryption
            ->shouldReceive('decrypt')
            ->with('encryptedvalue')
            ->andReturn('"decryptedvalue"');

        $cookies = new EncryptedCookies($this->json, $this->encryption);

        $cookies->set('name', ['value'=> "encryptedvalue"]);
        $actual = $cookies->getCookie('name');

        $this->assertSame('decryptedvalue', $actual);
    }

    public function testInvalidCookieDeletesByDefaultAndReturnsNull()
    {
        $this->encryption
            ->shouldReceive('decrypt')
            ->with('encryptedvalue')
            ->andReturnNull();

        $cookies = new EncryptedCookies($this->json, $this->encryption);

        $actual = $cookies->getCookie('name');
        $this->assertNull($actual);
    }

    public function testCookieIsAutomaticallyJsonSerialized()
    {
        $data = ['bing', 'bong'];
        $dataJsonified = '["bing","bong"]';

        $this->encryption
            ->shouldReceive('encrypt')
            ->with($dataJsonified)
            ->andReturn('encrypted-value')
            ->once();

        $cookies = new EncryptedCookies($this->json, $this->encryption);

        // prime the data
        $cookies->setCookie('cookie1', ['value' => $data]);

        $cookie = $cookies->getResponseCookie('cookie1');
        $expected = 'encrypted-value';
        $this->assertSame($expected, $cookie['value']);
    }

    public function testCookieIsAutomaticallyJsonDeserialized()
    {
        // $data = ['bing', 'bong'];
        $dataBadlyJsonified = '["bing","bong"}';

        $this->encryption
            ->shouldReceive('decrypt')
            ->with('encrypted')
            ->andReturn($dataBadlyJsonified)
            ->once();

        $cookies = new EncryptedCookies($this->json, $this->encryption);

        $cookies->setCookie('cookie1', ['value' => 'encrypted']);
        $cookie = $cookies->getCookie('cookie1');

        // Badly formatted json is passed back, rather than decoded json.
        $this->assertSame($dataBadlyJsonified, $cookie);
    }

    public function testCookieFailsDecryptionButIsAllowUnencrypted()
    {
        $data = ['bing', 'bong'];
        $dataJsonified = '["bing","bong"]';

        $this->encryption
            ->shouldReceive('decrypt')
            ->with($dataJsonified)
            ->andReturnNull()
            ->once();

        $cookies = new EncryptedCookies($this->json, $this->encryption, ['cookie1']);

        $cookies->setCookie('cookie1', ['value' => $dataJsonified]);
        $cookie = $cookies->getCookie('cookie1');
        $this->assertSame($data, $cookie);
    }

    public function testCookieFailsDecryptionButIsAllowUnencryptedAndThenAlsoFailsDecodingIsDeleted()
    {
        $dataBadlyJsonified = '["bing","bong"}';

        $this->encryption
            ->shouldReceive('decrypt')
            ->with($dataBadlyJsonified)
            ->andReturnNull()
            ->once();

        $this->slim
            ->shouldReceive('deleteCookie')
            ->with('cookie1')
            ->once();

        $cookies = new EncryptedCookies($this->json, $this->encryption, ['cookie1']);

        $cookies->setCookie('cookie1', ['value' => $dataBadlyJsonified]);
        $cookie = $cookies->getCookie('cookie1');
        $this->assertSame(null, $cookie);
    }

    public function testMissingCookieReturnsNull()
    {
        $cookies = new EncryptedCookies($this->json, $this->encryption);

        $cookie = $cookies->getResponseCookie('cookie1');
        $this->assertSame(null, $cookie);
    }
}
