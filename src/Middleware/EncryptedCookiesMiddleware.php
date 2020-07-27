<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QL\MCP\Common\OpaqueProperty;
use QL\Panthor\HTTP\CookieEncryptionInterface;
use QL\Panthor\MiddlewareInterface;

/**
 * This middleware provides a way to manage encrypted cookies on PSR-7 http messages.
 * - This middleware should be attached globally and before any other.
 *
 * Configuration:
 * - Allow unencrypted cookies
 *   A list of cookie names can be provided for cookies that are allowed to be unencrypted. This
 *   is useful for analytics or frontend cookies such as google analytics (_ga, _ut*).
 *
 * - Should invalid cookies be deleted?
 *   Cookies in the request that cannot be decrypted can be expired, or left alone.
 *
 * How it works:
 *
 * When a request is passed into the middleware:
 *  - Request cookies will be decoded and decrypted.
 *  - Cookies set by the application will be rendering into the response by this middleware after all
 *    other middleware run.
 *
 * 1. Decrypted cookies will be set as a request attribute on the request and passed to the next middleware.
 *    - Note: This cookie container will be an instance of "Cookies" from "Dflydev\FigCookies" and obscured
 *      from memory by "OpaqueProperty" from "quickenloans-mcp/mcp-common".
 *
 * 2. Cookies that are invalid will be immediately set on the response to expire.
 *
 * 3. Cookies can be read in your application by retrieving them from the "request_cookies" attribute.
 * 4. Cookies can be set in your application by setting "SetCookie" "Dflydev\FigCookies" as a header in the response.
 *
 * 5. This middleware will read any headers that are "SetCookie" and encrypt if necessary.
 *
 * Note: Please see cookie documentation and CookieTrait for a convenience utility for setting cookies.
 *
 * Note: This currently relies upon Slim allowing non-string values for headers, which are only stringified when the
 *       response is rendered. Other PSR-7 implementations such as zend-diactoros do not allow non-string values to be
 *       set.
 */
class EncryptedCookiesMiddleware implements MiddlewareInterface
{
    const RESPONSE_COOKIE_NAME = 'Set-Cookie';
    const REQUEST_COOKIE_ATTRIBUTE = 'request_cookies';

    /**
     * @var CookieEncryptionInterface
     */
    private $encryption;

    /**
     * @var string
     */
    private $cookieName;
    private $attributeName;

    /**
     * @var array<string>
     */
    private $unencryptedCookies;

    /**
     * @var bool
     */
    private $deleteInvalid;

    /**
     * @param CookieEncryptionInterface $encryption
     * @param array<string> $unencryptedCookies
     * @param bool $deleteInvalid
     */
    public function __construct(
        CookieEncryptionInterface $encryption,
        array $unencryptedCookies = [],
        bool $deleteInvalid = true
    ) {
        $this->encryption = $encryption;
        $this->unencryptedCookies = $unencryptedCookies;
        $this->deleteInvalid = $deleteInvalid;

        $this->cookieName = static::RESPONSE_COOKIE_NAME;
        $this->attributeName = static::REQUEST_COOKIE_ATTRIBUTE;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        [$reqCookies, $cookiesToExpire] = $this->decryptCookies(Cookies::fromRequest($request));
        $request = $request->withAttribute($this->attributeName, $reqCookies);

        $response = $handler->handle($request);

        // Render and normalize cookies into response
        $resCookies = $response->getHeader($this->cookieName);
        $renderedCookies = $this->renderCookies($cookiesToExpire, $resCookies);

        if ($renderedCookies) {
            $response = $response
                ->withoutHeader($this->cookieName)
                ->withAddedHeader($this->cookieName, $renderedCookies);
        }

        return $response;
    }

    /**
     * @param Cookies $cookiesFromRequest
     *
     * @return array
     */
    private function decryptCookies(Cookies $cookiesFromRequest)
    {
        $reqCookies = [];
        $resCookies = [];

        foreach ($cookiesFromRequest->getAll() as $cookie) {
            $name = $cookie->getName();

            if (in_array($name, $this->unencryptedCookies)) {
                $reqCookies[$name] = $cookie->getValue();
                continue;
            }

            $decrypted = $this->encryption->decrypt($cookie->getValue());
            if (is_string($decrypted)) {
                $reqCookies[$name] = new OpaqueProperty($decrypted);

            } else {
                if ($this->deleteInvalid) {
                    $resCookies[] = SetCookie::createExpired($name);
                }
            }
        }

        return [$reqCookies, $resCookies];
    }

    /**
     * Parse all cookies set on the response
     *
     * - If string - leave alone (this is expected to be a fully rendered cookie string with expiry, etc)
     * - If instance of "SetCookie" - stringify
     *
     * Cookies will be automatically deduped.
     *
     * @param array<SetCookie> $expireCookies
     * @param array<string> $resCookies
     *
     * @return array<string>
     */
    private function renderCookies(array $expireCookies, array $resCookies)
    {
        $renderable = [];

        $resCookies = array_merge($expireCookies, $resCookies);

        foreach ($resCookies as $cookie) {
            // These are cookies set manually directly on the response.
            if (is_string($cookie)) {
                $cookie = SetCookie::fromSetCookieString($cookie);
            }

            // These are set through CookieHandler
            if ($cookie instanceof SetCookie) {
                $cookieName = $cookie->getName();
                $renderable[$cookieName] = (string) $cookie;
            }
        }

        return $renderable;
    }
}
