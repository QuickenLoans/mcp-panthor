<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTPProblem;

use Psr\Http\Message\ResponseInterface;
use QL\Panthor\HTTP\NewBodyTrait;

/**
 * Render an HTTP Problem into a PSR-7 response
 */
trait ProblemRenderingTrait
{
    use NewBodyTrait;

    /**
     * @param ResponseInterface $response
     * @param ProblemRendererInterface $renderer
     * @param HTTPProblem $problem
     *
     * @return ResponseInterface
     */
    private function renderProblem(ResponseInterface $response, ProblemRendererInterface $renderer, HTTPProblem $problem)
    {
        $status = $renderer->status($problem);
        $headers = $renderer->headers($problem);
        $body = $renderer->body($problem);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $this
            ->withNewBody($response, $body)
            ->withStatus($status);
    }
}
