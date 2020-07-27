<?php

namespace ExampleApplication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\HTTP\NewBodyTrait;
use QL\Panthor\TemplateInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;

trait ControllerTrait
{
    use NewBodyTrait;

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function getRouteName(ServerRequestInterface $request): string
    {
        $route = $request->getAttribute(RouteContext::ROUTE);

        if (!$route instanceof Route) {
            return '';
        }

        return $route->getName();
    }

    /**
     * @param ResponseInterface $response
     * @param TemplateInterface $template
     * @param array $context
     *
     * @return ResponseInterface
     */
    private function withTemplate(ResponseInterface $response, TemplateInterface $template, array $context = [])
    {
        $rendered = $template->render($context);
        return $this->withNewBody($response, $rendered);
    }
}
