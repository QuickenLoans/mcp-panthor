<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Encryption;

use PHPUnit\Framework\TestCase;
use stdClass;

class LibsodiumSymmetricCryptoTest extends TestCase
{
    private $secret;

    public function setUp()
    {
        $this->secret = 'e1d4ca14194e027629e4446e7c534eb24b8953c3b7cf62cbb7b95977f0ab965cd8d2e8ac0dc1d9174ff401e86bf500112987eea4e552f9e201f3afe759b1a7dc';
    }

    /**
     * @expectedException QL\Panthor\Exception\CryptoException
     * @expectedExceptionMessage Invalid encryption secret. Secret must be 128 hexadecimal characters.
     */
    public function testInvalidSecretThrowsException()
    {
        $secret = 'derp';
        new LibsodiumSymmetricCrypto($secret);
    }

    /**
     * @expectedException QL\Panthor\Exception\CryptoException
     * @expectedExceptionMessage Invalid type "object" given. Only scalars can be encrypted.
     */
    public function testNonScalarThrowsCryptoException()
    {
        $crypto = new LibsodiumSymmetricCrypto($this->secret);

        $crypto->encrypt(new stdClass);
    }

    /**
     * @expectedException QL\Panthor\Exception\CryptoException
     * @expectedExceptionMessage Invalid type "string" given. Only strings can be decrypted.
     */
    public function testEmptyStringThrowsDecryptionException()
    {
        $crypto = new LibsodiumSymmetricCrypto($this->secret);

        $crypto->decrypt('');
    }

    /**
     * @expectedException QL\Panthor\Exception\CryptoException
     * @expectedExceptionMessage Invalid type "integer" given. Only strings can be decrypted.
     */
    public function testInvalidTypeThrowsDecryptionException()
    {
        $crypto = new LibsodiumSymmetricCrypto($this->secret);

        $crypto->decrypt(1234);
    }

    /**
     * @expectedException QL\Panthor\Exception\CryptoException
     * @expectedExceptionMessage Invalid encrypted payload provided.
     */
    public function testShortStringThrowsDecryptionException()
    {
        $crypto = new LibsodiumSymmetricCrypto($this->secret);

        $crypto->decrypt('small-string');
    }

    public function testEncryption()
    {
        $crypto = new LibsodiumSymmetricCrypto($this->secret);

        $cleartext = 'By the power of Grayskull!';

        $encrypted = $crypto->encrypt($cleartext);

        $len = \strlen($encrypted);
        $this->assertSame(98, $len);
    }

    public function testDecryption()
    {
        $crypto = new LibsodiumSymmetricCrypto($this->secret);

        $encrypted = <<<'HEX'
5f37653f690be967cd4c64bf7821db2fb98b8db6a8784e1c466921f38dd8c309d9b7ee1eb27b26c6d40c02d9b65838c0f93c74d99acb1ffbf8600d1033a14953a3ba8d7e3d1e71020b978fc14d59a659e5ba192be2102f2fb2ce6b4faf97f2195c5a
HEX;
        $expected = 'By the power of Grayskull!';

        $cleartext = $crypto->decrypt(hex2bin($encrypted));

        $this->assertSame($expected, $cleartext);
    }
}
