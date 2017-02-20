<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP\CookieEncryption;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Panthor\Encryption\LibsodiumSymmetricCrypto;
use QL\Panthor\Exception\CryptoException;

class LibsodiumCookieEncryptionTest extends PHPUnit_Framework_TestCase
{
    public $crypto;
    public $binaryData;

    public function setUp()
    {
        $this->crypto = Mockery::mock(LibsodiumSymmetricCrypto::class);
        $this->binaryData = hex2bin('f09f8f80f09f9384f09f9aa7f09f9a80e28fb0f09f9aa8');
    }

    public function testEncryptReturnsNullWhenExceptionOccurs()
    {
        $this->crypto
            ->shouldReceive('encrypt')
            ->andThrow(new CryptoException);

        $cookieCrypto = new LibsodiumCookieEncryption($this->crypto);

        $this->assertSame(null, $cookieCrypto->encrypt('text'));
    }

    public function testDecryptReturnsNullWhenExceptionOccurs()
    {
        $this->crypto
            ->shouldReceive('decrypt')
            ->andThrow(new CryptoException);

        $cookieCrypto = new LibsodiumCookieEncryption($this->crypto);
        $this->assertSame(null, $cookieCrypto->decrypt('text'));
    }

    public function testEncryptionReturnsURISafeValue()
    {
        $this->crypto
            ->shouldReceive('encrypt')
            ->with('text')
            ->andReturn($this->binaryData);

        $base64Output = '8J+PgPCfk4Twn5qn8J+agOKPsPCfmqg=';
        $uriSafeOutput = '8J-PgPCfk4Twn5qn8J-agOKPsPCfmqg';

        $cookieCrypto = new LibsodiumCookieEncryption($this->crypto);
        $actual = $cookieCrypto->encrypt('text');

        $this->assertSame($uriSafeOutput, $actual);
        $this->assertNotSame($base64Output, $actual);
    }

    public function testDecryptionWorksWithUriSafe()
    {
        $this->crypto
            ->shouldReceive('decrypt')
            ->with($this->binaryData)
            ->andReturn('text');

        $uriSafeInput = '8J-PgPCfk4Twn5qn8J-agOKPsPCfmqg';
        $expected = 'text';

        $cookieCrypto = new LibsodiumCookieEncryption($this->crypto);
        $actual = $cookieCrypto->decrypt($uriSafeInput);

        $this->assertSame($expected, $actual);
    }

    public function testDecryptionWorksWithBase64()
    {
        $this->crypto
            ->shouldReceive('decrypt')
            ->with($this->binaryData)
            ->andReturn('text');

        $base64Input = '8J+PgPCfk4Twn5qn8J+agOKPsPCfmqg=';
        $expected = 'text';

        $cookieCrypto = new LibsodiumCookieEncryption($this->crypto);
        $actual = $cookieCrypto->decrypt($base64Input);

        $this->assertSame($expected, $actual);
    }
}
