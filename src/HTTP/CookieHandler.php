<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTP;

use Dflydev\FigCookies\Modifier\SameSite;
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
 *         $testcookie = $this->cookie->getCookie($request, 'alphacookie');
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
    const REQUEST_COOKIE_ATTRIBUTE = 'request_cookies';

    const ERR_BAD_MAX_AGE = 'Invalid cookie parameter "maxAge" specified. ' .
        '"maxAge" must be number of seconds or a string passed to strtotime such as "+30 days".';
    const ERR_BAD_SECURE = 'Invalid cookie parameter "secure" specified. Expected a boolean.';
    const ERR_BAD_HTTP = 'Invalid cookie parameter "httpOnly" specified. Expected a boolean.';

    /**
     * @var CookieEncryptionInterface
     */
    private $encryption;

    /**
     * @var array<string>
     */
    private $unencryptedCookies;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param CookieEncryptionInterface $encryption
     * @param array $unencryptedCookies
     * @param array $cookieSettings
     */
    public function __construct(
        CookieEncryptionInterface $encryption,
        array $unencryptedCookies = [],
        array $cookieSettings = []
    ) {
        $this->encryption = $encryption;
        $this->unencryptedCookies = $unencryptedCookies;

        $this->configuration = [
            'maxAge' => $cookieSettings['maxAge'] ?? 0,
            'path' => $cookieSettings['path'] ?? null,
            'domain' => $cookieSettings['domain'] ?? null,
            'secure' => $cookieSettings['secure'] ?? false,
            'httpOnly' => $cookieSettings['httpOnly'] ?? false,
            'sameSite' => $cookieSettings['sameSite'] ?? '',
        ];

        if (!is_int($this->configuration['maxAge']) && !is_string($this->configuration['maxAge'])) {
            throw new Exception(self::ERR_BAD_MAX_AGE);
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
    public function getCookie(ServerRequestInterface $request, $name): ?string
    {
        $reqCookies = $request->getAttribute(self::REQUEST_COOKIE_ATTRIBUTE);
        if (!is_array($reqCookies)) {
            return null;
        }

        if (!$cookie = $reqCookies[$name] ?? null) {
            return null;
        }

        return ($cookie instanceof OpaqueProperty) ? $cookie->getValue() : $cookie;
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
     * @param int|string|null $expires Unix timestamp or string date format supported by strototime.
     *
     * @return ResponseInterface
     */
    public function withCookie(ResponseInterface $response, string $name, string $value, $expires = null)
    {
        if (!in_array($name, $this->unencryptedCookies)) {
            $value = $this->encryption->encrypt($value);
        }

        // If user specifies an expiry here, use it instead of default value from config
        $maxAge = ($expires !== null) ? $expires : $this->configuration['maxAge'];

        $cookie = SetCookie::create($name, $value)
            ->withMaxAge($this->resolveMaxAge($maxAge))
            ->withPath($this->configuration['path'])
            ->withDomain($this->configuration['domain'])
            ->withSecure($this->configuration['secure'])
            ->withHttpOnly($this->configuration['httpOnly']);

        if ($sameSite = $this->resolveSameSite()) {
            $cookie = $cookie->withSameSite($sameSite);
        }

        return $response->withAddedHeader(static::RESPONSE_COOKIE_NAME, (string) $cookie);
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
    public function expireCookie(ResponseInterface $response, string $name)
    {
        $cookie = SetCookie::createExpired($name)
            ->withPath($this->configuration['path'])
            ->withDomain($this->configuration['domain'])
            ->withSecure($this->configuration['secure'])
            ->withHttpOnly($this->configuration['httpOnly']);

        if ($sameSite = $this->resolveSameSite()) {
            $cookie = $cookie->withSameSite($sameSite);
        }

        return $response->withAddedHeader(static::RESPONSE_COOKIE_NAME, (string) $cookie);
    }

    /**
     * This allows "Max-Age" (which should be # of seconds til expiration) to take the same
     * type of parameters as "Expires".
     *
     * @param mixed $maxAge
     *
     * @return mixed
     */
    private function resolveMaxAge($maxAge)
    {
        if (is_null($maxAge)) {
            return null;
        }

        if (is_numeric($maxAge)) {
            return $maxAge;
        }

        $now = time();

        return strtotime($maxAge, $now) - $now;
    }

    /**
     * @return SameSite|null
     */
    private function resolveSameSite()
    {
        $sameSite = $this->configuration['sameSite'];
        if (!$sameSite) {
            return null;
        }

        return SameSite::fromString($sameSite);
    }
}
