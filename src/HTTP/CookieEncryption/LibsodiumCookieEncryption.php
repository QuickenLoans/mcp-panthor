<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP\CookieEncryption;

use QL\Panthor\Encryption\LibsodiumSymmetricCrypto;
use QL\Panthor\Exception\CryptoException;
use QL\Panthor\HTTP\CookieEncryptionInterface;

/**
 * Encrypts payload using libsodium authenticated symmetric encryption.
 *
 * The payload is then encoded with uri-safe base64.
 */
class LibsodiumCookieEncryption implements CookieEncryptionInterface
{
    /**
     * @var LibsodiumSymmetricCrypto
     */
    private $crypto;

    /**
     * @param LibsodiumSymmetricCrypto $crypto
     */
    public function __construct(LibsodiumSymmetricCrypto $crypto)
    {
        $this->crypto = $crypto;
    }

    /**
     * @param string $unencrypted
     *
     * @return string|null
     */
    public function encrypt($unencrypted)
    {
        try {
            $encrypted = $this->crypto->encrypt($unencrypted);
        } catch (CryptoException $ex) {
            return null;
        }

        return $this->uriSafeEncode($encrypted);
    }

    /**
     * @param string $encrypted
     *
     * @return string|null
     */
    public function decrypt($encrypted)
    {
        $payload = $this->uriSafeDecode($encrypted);
        if (!$payload) {
            return null;
        }

        try {
            $unencrypted = $this->crypto->decrypt($payload);
        } catch (CryptoException $ex) {
            return null;
        }

        return $unencrypted;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    private function uriSafeEncode($message)
    {
        $encoded = base64_encode($message);
        $uriSafe = str_replace(['+', '/'], ['-', '_'], $encoded);

        return rtrim($uriSafe, '=');
    }

    /**
     * @param string $message
     *
     * @return string|null
     */
    private function uriSafeDecode($message)
    {
        $message = str_replace(['-', '_'], ['+', '/'], $message);

        $decoded = base64_decode($message, true);
        if (!is_string($decoded)) {
            return null;
        }

        return $decoded;
    }
}
