<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Templating;

use QL\Panthor\Twig\LazyTwig;
use Slim\Http\Response;

/**
 * This template automatically sets the rendered template onto the current response.
 *
 * This can allow your controllers to only need a template, instead of the template and the response, since its likely
 * all you need the response for is to add the template to the body.
 */
class AutoRenderingTemplate extends LazyTwig
{
    /**
     * @type Response
     */
    private $response;

    /**
     * @param Response $response
     *
     * @return void
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Render the template with context data and automatically add to response
     *
     * @param array $context
     *
     * @return string
     */
    public function render(array $context = [])
    {
        $rendered = parent::render($context);

        if ($this->response) {
            $this->response->setBody($rendered);
        }

        return $rendered;
    }
}
