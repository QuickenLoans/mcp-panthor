<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Http;

use ArrayIterator;
use QL\Panthor\Utility\Json;
use Slim\Http\Cookies;
use Slim\Slim;

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
     * @type Slim
     */
    private $slim;

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
     * @param Slim $slim
     * @param Json $json
     * @param CookieEncryptionInterface $encryption
     * @param string[] $unencryptedCookies
     */
    public function __construct(
        Slim $slim,
        Json $json,
        CookieEncryptionInterface $encryption,
        array $unencryptedCookies = []
    ) {
        $this->slim = $slim;
        $this->json = $json;
        $this->encryption = $encryption;

        $this->unencryptedCookies = $unencryptedCookies;
    }

    /**
     * Used by slim to render out cookies. Never retrieve response cookies within the application!
     *
     * @param string $key
     *
     * @return array|null
     */
    public function getResponseCookie($key)
    {
        if (!$cookie = parent::get($key)) {
            return null;
        }

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
     * {inheritdoc}
     */
    public function getIterator()
    {
        $sanitized = $this->all();

        array_walk($sanitized, function(&$v, $key) {
            $v = $this->getResponseCookie($key);
        });

        return new ArrayIterator($sanitized);
    }

    /**
     * Convenience method to centralize cookie handling (also so we dont have to pass Slim\Slim around as a dependency)
     *
     * @see Slim::deleteCookie
     *
     * @param string $name
     *
     * @return mixed|false
     */
    public function deleteCookie($name)
    {
        return call_user_func_array([$this->slim, __FUNCTION__], func_get_args());
    }

    /**
     * Convenience method to centralize cookie handling (also so we dont have to pass Slim\Slim around as a dependency)
     *
     * @see Slim::getCookie
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getCookie($name)
    {
        if ($value = $this->slim->getCookie($name)) {
            $decrypted = $this->encryption->decrypt($value);

            // Successful decryption
            if (is_string($decrypted)) {
                $decoded = $this->json->decode($decrypted);
                if ($decoded !== null) {
                    return $decoded;
                } else {
                    // If json decode fails, just return the raw decrypted string. This is to maintain BC.
                    // @todo remove in 3.0
                    return $decrypted;
                }

            // Allow straight value through if fails decryption and allowed to be unencrypted.
            } elseif (in_array($name, $this->unencryptedCookies)) {
                $decoded = $this->json->decode($value);
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
        return call_user_func_array([$this->slim, __FUNCTION__], func_get_args());
    }
}
