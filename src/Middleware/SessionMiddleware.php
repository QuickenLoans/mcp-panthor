<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // build session
        $session = $this->buildSession($request);

        // attach to request
        $request = $request->withAttribute($this->options['request_attribute'], $session);

        $response = $next($request, $response);

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
        $data = $this->handler->getCookie($request, $this->options['cookie_name']);

        $session = call_user_func([$this->options['session_class'], 'fromSerialized'], $data);
        if (!$session) {
            $session = new $this->options['session_class'];
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
        if ($session->hasChanged()) {
            $serialized = call_user_func([$this->options['session_class'], 'toSerialized'], $session);

            $response = $this->handler->withCookie(
                $response,
                $this->options['cookie_name'],
                $serialized,
                $this->options['lifetime']
            );
        }

        return $response;
    }
}
