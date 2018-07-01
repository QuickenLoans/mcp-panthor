<?php

namespace ExampleApplication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\MCP\Common\Clock;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class HelloWorldController implements ControllerInterface
{
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
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $rendered = $this->template->render([
            'now' => (new Clock)->read()
        ]);

        $response->getBody()->write($rendered);
        return $response;
    }
}
