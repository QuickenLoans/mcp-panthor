<?php

namespace ExampleApplication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class HelloWorldController implements ControllerInterface
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
        $session = $request->getAttribute('session');
        $cookies = $request->getAttribute('request_cookies');

        return $this->withTemplate($response, $this->template, [
            'route' => $this->getRouteName($request),
            'is_session_enabled' => ($session !== null),
            'is_cookie_enabled' => ($cookies !== null),
            'now' => (new Clock)->read()
        ]);
    }
}
