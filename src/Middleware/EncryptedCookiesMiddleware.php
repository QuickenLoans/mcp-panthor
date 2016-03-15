<?php
/**
 * @copyright ©2005—2016 Quicken Loans Inc. All rights reserved. Trade Secret, Confidential and Proprietary. Any
 *     dissemination outside of Quicken Loans is strictly prohibited.
 */

namespace QL\Panthor\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\Http\CookieEncryptionInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Json;
use Slim\Http\Cookies;

class EncryptedCookiesMiddleware implements MiddlewareInterface
{

    /** @var CookieEncryptionInterface $encryption */
    private $encryption;

    /** @var Json $json */
    private $json;

    /** @var array $unencryptedCookies */
    private $unencryptedCookies;

    /**
     * EncryptedCookiesMiddleware constructor.
     *
     * @param CookieEncryptionInterface $encryptionAlgorithms
     * @param Json $json
     * @param array $unencryptedCookies
     */
    public function __construct(
        JSON $json,
        CookieEncryptionInterface $encryptionAlgorithms,
        array $unencryptedCookies = []
    ) {
        $this->encryption = $encryptionAlgorithms;
        $this->json = $json;
        $this->unencryptedCookies = $unencryptedCookies;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $decryptedContainer = $this->getConvertedContainer($request, 'decrypt');

        $cookieHeader = $decryptedContainer->toHeaders();
        $decryptedCookies = Cookies::parseHeader($cookieHeader);
        $request = $request->withCookieParams($decryptedCookies);

        $next($request, $response);

        $encryptedContainer = $this->getConvertedContainer($request, 'encrypt');
        $response = $response->withHeader('Set-Cookie', $encryptedContainer->toHeaders());
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param $operation
     *
     * @return Cookies
     */
    private function getConvertedContainer(ServerRequestInterface $request, $operation)
    {
        $cookies = $request->getCookieParams();
        $keys = array_keys($cookies);
        $cookieContainer = new Cookies($cookies);
        return $this->convertCookies($keys, $cookieContainer, $operation);
    }

    /**
     * @param $cookieKeys
     * @param Cookies $cookies
     * @param $operation
     *
     * @return Cookies
     */
    private function convertCookies($cookieKeys, Cookies $cookies, $operation)
    {
        foreach ($cookieKeys as $key) {
            $cookie = $cookies->get($key);
            if (!in_array($key, $this->unencryptedCookies)) {
                $cookie = call_user_func([$this, $operation], $cookie);
            }
            $cookies->set($key, $cookie);
        }
        return $cookies;
    }

    /**
     * @param $cookie
     *
     * @return mixed
     */
    public function encrypt($cookie)
    {
        $value = array_key_exists('value', $cookie) ? $cookie['value']:null;
        if ($value) {
            $value = $this->json->encode($value);

            $value = $this->encryption->encrypt($value);
        }
        $cookie['value'] = $value;

        return $cookie;
    }

    /**
     * @param $cookie
     *
     * @return mixed
     */
    public function decrypt($cookie)
    {
        $value = array_key_exists('value', $cookie) ? $cookie['value']:null;
        if ($value) {
            $decrypted = $this->encryption->decrypt($value);

            if (is_string($decrypted)) {
                $cookie = $this->handleDecrypted($cookie, $decrypted);
            }
        }

        return $cookie;
    }

    /**
     * @param $cookie
     * @param $decrypted
     *
     * @return mixed
     */
    private function handleDecrypted($cookie, $decrypted)
    {
        $decoded = $this->json->decode($decrypted);
        if (!is_null($decoded)) {
            $cookie['value'] = $decoded;

            return $cookie;
        } elseif (!empty($decrypted)) {
            $cookie['value'] = $decrypted;

            return $cookie;
        } else {
            $cookie['value'] = null;
            $cookie['domain'] = null;

            return $cookie;
        }
    }
}
