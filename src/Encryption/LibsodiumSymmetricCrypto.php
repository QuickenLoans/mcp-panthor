<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Encryption;

use QL\MCP\Common\OpaqueProperty;
use QL\MCP\Common\Utility\ByteString;
use QL\Panthor\Exception\CryptoException;

/**
 * This uses libsodium encryption from libsodium ~2.0
 *
 * @see https://pecl.php.net/package/libsodium
 * @see https://github.com/jedisct1/libsodium-php
 */
class LibsodiumSymmetricCrypto
{
    const NONCE_SIZE_BYTES = 24;

    // 2 * 64 = 128 hexadecimal characters
    const REGEX_FULL_SECRET = '^[A-Fa-f0-9]{128}$';

    const FQDN_RANDOMBYTES = '\random_bytes';

    /**
     * Setup errors
     */
    const ERR_NEED_MORE_SALT = 'Libsodium extension is not installed. Please install "ext-sodium" (>=7.0).';
    const ERR_CSPRNG = 'CSPRNG "random_bytes" not found. Please use PHP 7.0 or install paragonie/random_compat.';
    const ERR_INVALID_SECRET = 'Invalid encryption secret. Secret must be 128 hexadecimal characters.';

    /**
     * Encryption errors
     */
    const ERR_CANNOT_ENCRYPT = 'Invalid type "%s" given. Only scalars can be encrypted.';
    const ERR_ENCRYPT = 'An error occured while encrypting data: %s';
    const ERR_ENCODE = 'An error occured while calculating MAC: %s';

    /**
     * Decryption errors
     */
    const ERR_CANNOT_DECRYPT = 'Invalid type "%s" given. Only strings can be decrypted.';
    const ERR_SIZE = 'Invalid encrypted payload provided.';
    const ERR_DECODE_UNEXPECTED = 'An error occured while verifying MAC: %s';
    const ERR_DECODE = 'Could not verify MAC.';
    const ERR_DECRYPT = 'An error occured while decrypting data: %s';

    /**
     * @var OpaqueProperty
     */
    private $cryptoSecret;

    /**
     * @var OpaqueProperty
     */
    private $authSecret;

    /**
     * @var string
     */
    private $libsodiumVersion;

    /**
     * $secret should each be a 128-character hexademical value.
     *
     * This will be broken into 2 64-character parts: crypto secret and auth secret.
     *
     * While in memory these are stored as OpaqueProperty, to obscure from debug code or stacktraces.
     *
     * @param string $secret
     *
     * @throws CryptoException
     */
    public function __construct($secret)
    {
        if (!extension_loaded('sodium')) {
            throw new CryptoException(self::ERR_NEED_MORE_SALT);
        }

        if (!function_exists(self::FQDN_RANDOMBYTES)) {
            throw new CryptoException(self::ERR_CSPRNG);
        }

        if (1 !== preg_match(sprintf('#%s#', self::REGEX_FULL_SECRET), $secret)) {
            throw new CryptoException(self::ERR_INVALID_SECRET);
        }

        $this->cryptoSecret = new OpaqueProperty($this->sodiumHex2bin(ByteString::substr($secret, 0, 64)));
        $this->authSecret = new OpaqueProperty($this->sodiumHex2bin(ByteString::substr($secret, 64)));
    }

    /**
     * @param mixed $unencrypted
     *
     * @throws CryptoException
     *
     * @return string
     */
    public function encrypt($unencrypted)
    {
        if (!is_scalar($unencrypted)) {
            throw new CryptoException(sprintf(self::ERR_CANNOT_ENCRYPT, gettype($unencrypted)));
        }

        // Generate 24 byte nonce
        $nonce = \random_bytes(self::NONCE_SIZE_BYTES);

        // Encrypt payload
        try {
            $encrypted = $this->sodiumSecretBox($unencrypted, $nonce, $this->cryptoSecret->getValue());
        } catch (Exception $ex) {
            throw new CryptoException(sprintf(self::ERR_ENCRYPT, $ex->getMessage()), $ex->getCode(), $ex);
        }

        // Calculate MAC
        try {
            $mac = $this->sodiumCryptoAuth($nonce . $encrypted, $this->authSecret->getValue());
        } catch (Exception $ex) {
            throw new CryptoException(sprintf(self::ERR_ENCODE, $ex->getMessage()), $ex->getCode(), $ex);
        }

        // Return appended binary string
        return $nonce . $mac . $encrypted;
    }

    /**
     * @param string $encrypted
     *
     * @throws CryptoException
     *
     * @return string
     */
    public function decrypt($encrypted)
    {
        if (!$encrypted || !is_string($encrypted)) {
            throw new CryptoException(sprintf(self::ERR_CANNOT_DECRYPT, gettype($encrypted)));
        }

        // Sanity check size of payload is larger than MAC + NONCE
        if (ByteString::strlen($encrypted) < self::NONCE_SIZE_BYTES + $this->sodiumCryptoBytes()) {
            throw new CryptoException(self::ERR_SIZE);
        }

        // Split into nonce, mac, and encrypted payload
        $nonce = ByteString::substr($encrypted, 0, self::NONCE_SIZE_BYTES);
        $mac = ByteString::substr($encrypted, self::NONCE_SIZE_BYTES, $this->sodiumCryptoBytes());
        $encrypted = ByteString::substr($encrypted, self::NONCE_SIZE_BYTES + $this->sodiumCryptoBytes());

        // Verify MAC
        try {
            $isVerified = $this->sodiumCryptoAuthVerify($mac, $nonce . $encrypted, $this->authSecret->getValue());
        } catch (Exception $ex) {
            throw new CryptoException(sprintf(self::ERR_DECODE_UNEXPECTED, $ex->getMessage()), $ex->getCode(), $ex);
        }

        if (!$isVerified) {
            throw new CryptoException(self::ERR_DECODE);
        }

        // Decrypt authenticated payload
        try {
            $unencrypted = $this->sodiumSecretBoxOpen($encrypted, $nonce, $this->cryptoSecret->getValue());
        } catch (Exception $ex) {
            throw new CryptoException(sprintf(self::ERR_DECRYPT, $ex->getMessage()), $ex->getCode(), $ex);
        }

        return $unencrypted;
    }

    /**
     * @param string
     *
     * @return string
     */
    private function sodiumHex2bin($var)
    {
        return \sodium_hex2bin($var);
    }

    /**
     * @param string $message
     * @param string $key
     *
     * @return string
     */
    public function sodiumCryptoAuth($message, $key)
    {
        return \sodium_crypto_auth($message, $key);
    }

    /**
     * @return int
     */
    public function sodiumCryptoBytes()
    {
        return SODIUM_CRYPTO_AUTH_BYTES;
    }

    /**
     * @param string $mac
     * @param string $message
     * @param string $key
     *
     * @return string
     */
    public function sodiumCryptoAuthVerify($mac, $message, $key)
    {
        return \sodium_crypto_auth_verify($mac, $message, $key);
    }

    /**
     * @param string $message
     * @param string $nonce
     * @param string $key
     *
     * @return string
     */
    public function sodiumSecretBox($message, $nonce, $key)
    {
        return \sodium_crypto_secretbox($message, $nonce, $key);
    }

    /**
     * @param string $message
     * @param string $nonce
     * @param string $key
     *
     * @return string
     */
    public function sodiumSecretBoxOpen($message, $nonce, $key)
    {
        return \sodium_crypto_secretbox_open($message, $nonce, $key);
    }
}
