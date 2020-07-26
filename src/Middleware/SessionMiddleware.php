<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Session\JSONEncodedSession;
use QL\Panthor\Session\SessionInterface;

/**
 * Loads the session from a cookie and populates into:
 * - Request (attribute: session)
 */
class SessionMiddleware implements MiddlewareInterface
{
    const DEFAULT_REQUEST_ATTRIBUTE = 'session';
    const DEFAULT_COOKIE_NAME = 'session';
    const DEFAULT_LIFETIME = '+20 minutes';
    const DEFAULT_SESSION_CLASS = JSONEncodedSession::class;

    /**
     * @var CookieHandler
     */
    private $handler;

    /**
     * @var array
     */
    private $options;

    /**
     * @param CookieHandler $handler
     * @param array $options
     */
    public function __construct(CookieHandler $handler, array $options = [])
    {
        $this->handler = $handler;

        $defaults = [
            'lifetime' => self::DEFAULT_LIFETIME,
            'request_attribute' => self::DEFAULT_REQUEST_ATTRIBUTE,
            'cookie_name' => self::DEFAULT_COOKIE_NAME,
            'session_class' => self::DEFAULT_SESSION_CLASS,
        ];

        $this->options = $options + $defaults;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // build session
        $session = $this->buildSession($request);

        // attach to request
        $request = $request->withAttribute($this->options['request_attribute'], $session);

        $response = $handler->handle($request);

        // render session
        return $this->serializeSession($response, $session);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return SessionInterface
     */
    private function buildSession(ServerRequestInterface $request)
    {
        $sessionClass = $this->options['session_class'];
        $cookieName = $this->options['cookie_name'];

        $data = $this->handler->getCookie($request, $cookieName);

        $session = $sessionClass::fromSerialized($data ?? '');
        if (!$session) {
            $session = new $sessionClass;
        }

        return $session;
    }

    /**
     * @param ResponseInterface $response
     * @param SessionInterface $session
     *
     * @return ResponseInterface
     */
    private function serializeSession(ResponseInterface $response, SessionInterface $session)
    {
        $sessionClass = $this->options['session_class'];
        $cookieName = $this->options['cookie_name'];
        $lifeTime = $this->options['lifetime'];

        if ($session->hasChanged()) {
            $serialized = $sessionClass::toSerialized($session);
            $response = $this->handler->withCookie($response, $cookieName, $serialized, $lifeTime);
        }

        return $response;
    }
}
