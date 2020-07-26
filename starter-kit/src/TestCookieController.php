<?php

namespace ExampleApplication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\HTTP\CookieHandler;
use QL\Panthor\TemplateInterface;

class TestCookieController implements ControllerInterface
{
    use ControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var CookieHandler
     */
    private $handler;

    /**
     * @param TemplateInterface $template
     * @param CookieHandler $handler
     */
    public function __construct(TemplateInterface $template, CookieHandler $handler)
    {
        $this->template = $template;
        $this->handler = $handler;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $random = $request->getQueryParams()['random'] ?? '';
        $randomFromCookie = null;

        $session = $request->getAttribute('session');
        $cookies = $request->getAttribute('request_cookies');
        $isCookiesEnabled = $cookies !== null;

        if ($isCookiesEnabled) {
            $randomFromCookie = $this->handler->getCookie($request, 'random');
        }

        if ($isCookiesEnabled && $random) {
            $randomFromCookie = $random;
            $response = $this->handler->withCookie($response, 'random', $random);
        }

        return $this->withTemplate($response, $this->template, [
            'route' => $this->getRouteName($request),
            'is_session_enabled' => ($session !== null),
            'is_cookie_enabled' => ($cookies !== null),

            'random_from_cookie' => $randomFromCookie,
            'random' => bin2hex(random_bytes(4)),
        ]);
    }
}
