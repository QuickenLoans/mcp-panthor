<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionRenderer;

use Exception;
use Psr\Http\Message\ResponseInterface;
use QL\Panthor\ErrorHandling\ExceptionRendererInterface;
use QL\Panthor\ErrorHandling\SlimRenderingTrait;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Templating\NullTemplate;

class HTMLRenderer implements ExceptionRendererInterface
{
    use SlimRenderingTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @param TemplateInterface|null $template
     */
    public function __construct(TemplateInterface $template = null)
    {
        $this->template = $template ?: new NullTemplate;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ResponseInterface $response, $status, array $context)
    {
        $rendered = $this->template->render($context);

        $response->withStatus($status, $rendered);
        $response->withHeader('Content-Type', 'text/html');
        $this->renderResponse($response);
    }
}
