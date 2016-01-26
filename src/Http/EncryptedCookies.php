<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Http;

use QL\Panthor\Utility\Json;
use Slim\Http\Cookies;

/**
 * Hi! You guys want some cookies?
 *
 * @see https://www.youtube.com/watch?v=m1LUnPTlpbQ
 *
 * This is a replacement for Slim cookies that encapsulates all cookie management and encrypts them with our
 * protocol.
 */
class EncryptedCookies extends Cookies
{
    /**
     * @type Json
     */
    private $json;

    /**
     * @type CookieEncryptionInterface
     */
    private $encryption;

    /**
     * @type string[]
     */
    private $unencryptedCookies;

    /**
     * @param Json $json
     * @param CookieEncryptionInterface $encryption
     * @param string[] $unencryptedCookies
     */
    public function __construct(
        Json $json,
        CookieEncryptionInterface $encryption,
        array $unencryptedCookies = []
    ) {
        $this->json = $json;
        $this->encryption = $encryption;

        parent::__construct($unencryptedCookies);
        $this->unencryptedCookies = $unencryptedCookies;
    }

    /**
     * Used by slim to render out cookies. Never retrieve response cookies within the application!
     */
    public function getResponseCookie($key)
    {
        if (!array_key_exists($key, $this->responseCookies)) {
            return null;
        }

        $cookie = $this->responseCookies[$key];
        $value = array_key_exists('value', $cookie) ? $cookie['value'] : null;

        if ($value) {
            $value = $this->json->encode($value);

            if (!in_array($key, $this->unencryptedCookies)) {
                $value = $this->encryption->encrypt($value);
            }
        }

        $cookie['value'] = $value;

        return $cookie;
    }

    /**
     * Convenience method to centralize cookie handling.
     *
     * @param string $name
     * @return null
     */
    public function deleteCookie($name)
    {
        call_user_func_array([$this, 'set'], [$name, ['value' => null, 'domain' => null]]);
    }

    /**
     * Convenience method to centralize cookie handling.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getCookie($name)
    {
        if ($value = parent::get($name)) {
            $decrypted = $this->encryption->decrypt($value['value']);

            // Successful decryption
            if (is_string($decrypted)) {
                $decoded = $this->json->decode($decrypted);
                if ($decoded !== null) {
                    return $decoded;
                }

            // Allow straight value through if fails decryption and allowed to be unencrypted.
            } elseif (in_array($name, $this->unencryptedCookies)) {
                $decoded = $this->json->decode($value['value']);
                if ($decoded !== null) {
                    return $decoded;
                }
            }

            $this->deleteCookie($name);
        }
    }

    /**
     * Convenience method to centralize cookie handling (also so we dont have to pass Slim\Slim around as a dependency)
     *
     * @see Slim::setCookie
     */
    public function setCookie()
    {
        return call_user_func_array([$this, 'set'], func_get_args());
    }
}
