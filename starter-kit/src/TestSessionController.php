<?php

namespace ExampleApplication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class TestSessionController implements ControllerInterface
{
    use ControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @param TemplateInterface $template
     */
    public function __construct(TemplateInterface $template)
    {
        $this->template = $template;
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
        $randomFromSession = null;

        $session = $request->getAttribute('session');
        $cookies = $request->getAttribute('request_cookies');
        $isSessionEnabled = $session !== null;

        if ($isSessionEnabled) {
            $randomFromSession = $session->get('random');
        }

        if ($isSessionEnabled && $random) {
            $randomFromSession = $random;
            $session->set('random', $random);
        }

        return $this->withTemplate($response, $this->template, [
            'route' => $this->getRouteName($request),
            'is_session_enabled' => ($session !== null),
            'is_cookie_enabled' => ($cookies !== null),

            'random_from_session' => $randomFromSession,
            'random' => random_int(1000, 9999),
        ]);
    }
}
