<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP;

use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\OpaqueProperty;
use QL\Panthor\Exception\Exception;

/**
 * Simplified convenience utility for getting, setting, and expiring cookies.
 *
 * Do not use without attaching "EncryptedCookiesMiddleware" middleware to Slim!
 *
 * Usage in a controller:
 * ```php
 * class ExampleController extends ControllerInterface
 * {
 *     private $cookie;
 *
 *     public function __construct(CookieHandler $cookie)
 *     {
 *         $this->cookie = $cookie;
 *     }
 *
 *     public function __invoke($request, $response, $args)
 *     {
 *         $testcookie = $this->cookie->getCookie('alphacookie');
 *
 *         if ($testcookie === null) {
 *             // Cookie does not exist.
 *         } else {
 *             // Cookie exists
 *
 *             // Expire cookie and save into response
 *             $response = $this->cookie->expireCookie($response, 'alphacookie');
 *         }
 *
 *         // Set a new cookie, expires at default time (set in app config)
 *         $response = $this->cookie->withCookie($response, 'betacookie', '1234');
 *
 *         // Set a new cookie, with custom expiry
 *         $response = $this->cookie->withCookie($response, 'gammacookie', 'abcd', '+7 days');
 *
 *         return $response;
 *     }
 * }
 * ```
 */
class CookieHandler
{
    const RESPONSE_COOKIE_NAME = 'Set-Cookie';

    const ERR_BAD_EXPIRES = 'Invalid cookie parameter "expires" specified. "expires" must be a unix timestamp or a string passed to strtotime such as "+30 days".';
    const ERR_BAD_SECURE = 'Invalid cookie parameter "secure" specified. Expected a boolean.';
    const ERR_BAD_HTTP = 'Invalid cookie parameter "httpOnly" specified. Expected a boolean.';

    /**
     * @param array
     */
    private $configuration;

    /**
     * @param array $cookieConfiguration
     */
    public function __construct(array $cookieSettings = [])
    {
        $this->configuration = [
            'expires' => $this->nullable($cookieSettings, 'expires', 0),
            'maxAge' => $this->nullable($cookieSettings, 'maxAge', 0),
            'path' => $this->nullable($cookieSettings, 'path'),
            'domain' => $this->nullable($cookieSettings, 'domain'),
            'secure' => $this->nullable($cookieSettings, 'secure', false),
            'httpOnly' => $this->nullable($cookieSettings, 'httpOnly', false)
        ];

        if (!is_int($this->configuration['expires']) && !is_string($this->configuration['expires'])) {
            throw new Exception(self::ERR_BAD_EXPIRES);
        }

        if (!is_bool($this->configuration['secure'])) {
            throw new Exception(self::ERR_BAD_SECURE);
        }

        if (!is_bool($this->configuration['httpOnly'])) {
            throw new Exception(self::ERR_BAD_HTTP);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $name
     *
     * @return string|null
     */
    public function getCookie(ServerRequestInterface $request, $name)
    {
        $reqCookies = $request->getAttribute('request_cookies');
        if (!$reqCookies instanceof Cookies) {
            return null;
        }

        if (!$cookie = $reqCookies->get($name)) {
            return null;
        }

        $val = $cookie->getValue();

        return ($val instanceof OpaqueProperty) ? $val->getValue() : $val;
    }

    /**
     * Add a cookie to the response.
     *
     * Do not worry about duplicated cookies, they will be deduped when the
     * response is rendered by "EncryptedCookieMiddleware".
     *
     * @param ResponseInterface $response
     * @param string $name
     * @param string $value
     * @param int|string $expires Unix timestamp or string date format supported by strototime.
     *
     * @return ResponseInterface
     */
    public function withCookie(ResponseInterface $response, $name, $value, $expires = 0)
    {
        $cookie = SetCookie::create($name, new OpaqueProperty($value))
            ->withExpires($this->configuration['expires'] ?: $expires)
            ->withMaxAge($this->configuration['maxAge'])
            ->withPath($this->configuration['path'])
            ->withDomain($this->configuration['domain'])
            ->withSecure($this->configuration['secure'])
            ->withHttpOnly($this->configuration['httpOnly']);

        return $response->withAddedHeader(static::RESPONSE_COOKIE_NAME, $cookie);
    }

    /**
     * Expire a cookie in the response.
     *
     * Do not worry about duplicated cookies, they will be deduped when the
     * response is rendered by "EncryptedCookieMiddleware".
     *
     * @param ResponseInterface $response
     * @param string $name
     *
     * @return ResponseInterface
     */
    public function expireCookie(ResponseInterface $response, $name)
    {
        $cookie = SetCookie::createExpired($name)
            ->withMaxAge($this->configuration['maxAge'])
            ->withPath($this->configuration['path'])
            ->withDomain($this->configuration['domain'])
            ->withSecure($this->configuration['secure'])
            ->withHttpOnly($this->configuration['httpOnly']);

        return $response->withAddedHeader(static::RESPONSE_COOKIE_NAME, $cookie);
    }

    /**
     * @param array $data
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    private function nullable(array $data, $name, $default = null)
    {
        if (array_key_exists($name, $data)) {
            return $data[$name];
        }

        return $default;
    }
}
