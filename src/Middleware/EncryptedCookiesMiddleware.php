<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\Http\CookieEncryptionInterface;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\CookieTool;
use QL\Panthor\Utility\Json;
use Slim\Http\Cookies;

class EncryptedCookiesMiddleware implements MiddlewareInterface
{

    /** @var Json $json */
    private $json;

    /** @var CookieEncryptionInterface $encryption */
    private $encryption;

    /** @var CookieTool $cookieTool */
    private $cookieTool;

    /** @var array $unencryptedCookies */
    private $unencryptedCookies;

    /**
     * EncryptedCookiesMiddleware constructor.
     *
     * @param Json $json
     * @param CookieEncryptionInterface $encryptionAlgorithms
     * @param CookieTool $cookieTool
     * @param array $unencryptedCookies
     */
    public function __construct(
        Json $json,
        CookieEncryptionInterface $encryptionAlgorithms,
        CookieTool $cookieTool,
        array $unencryptedCookies = []
    ) {
        $this->json = $json;
        $this->encryption = $encryptionAlgorithms;
        $this->cookieTool = $cookieTool;
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
        $rawCookies = $request->getCookieParams();
        $decryptedCookies = $this->getConvertedContainer($rawCookies, 'decrypt');

        $decryptedResponse = $this->cookieTool->setCookies($response, $decryptedCookies);

        $response = $next($request, $decryptedResponse);

        $rawCookies = $this->cookieTool->getRawCookies($response);
        $encryptedCookies = $this->getConvertedContainer($rawCookies, 'encrypt');

        $decryptedResponse = $this->cookieTool->setCookies($decryptedResponse, $encryptedCookies);
        $response = $response->withHeader('Set-Cookie', $decryptedResponse->getHeader('Set-Cookie'));
        return $response;
    }

    /**
     * @param array $rawCookies
     * @param $operation
     *
     * @return Cookies
     */
    private function getConvertedContainer($rawCookies, $operation)
    {
        $keys = array_keys($rawCookies);
        $cookies = new Cookies($rawCookies);
        return $this->convertCookies($keys, $cookies, $operation);
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
            $value = $cookies->get($key);
            $value = call_user_func([$this, $operation], $key, $value);

            $cookies->set($key, $value);
        }
        return $cookies;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    public function encrypt($key, $value = null)
    {
        if (!in_array($key, $this->unencryptedCookies)) {
            if ($value) {
                $value = $this->encryption->encrypt($this->json->encode($value));
            }
        }

        return $value;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return mixed
     */
    public function decrypt($key, $value = null)
    {
        if ($value) {
            $decrypted = $this->encryption->decrypt($value);

            if (is_string($decrypted)) {
                $value = $this->handleDecrypted($value, $decrypted);
            }
        }

        return $value;
    }

    /**
     * @param string $value
     * @param mixed $decrypted
     *
     * @return mixed
     */
    private function handleDecrypted($value, $decrypted)
    {
        $decoded = $this->json->decode($decrypted);
        if (!is_null($decoded)) {
            $value = $decoded;
        } elseif (!empty($decrypted)) {
            $value = $decrypted;
        }

        return $value;
    }
}
