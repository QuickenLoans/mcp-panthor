<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ErrorHandling\ContentHandlerInterface;
use QL\Panthor\ErrorHandling\StacktraceFormatterTrait;
use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;
use QL\Panthor\HTTPProblem\ProblemRenderingTrait;
use QL\Panthor\HTTPProblem\Renderer\JSONRenderer;
use Throwable;

class HTTPProblemContentHandler implements ContentHandlerInterface
{
    use ProblemRenderingTrait;
    use StacktraceFormatterTrait;

    /**
     * @var ProblemRendererInterface
     */
    private $renderer;

    /**
     * @var bool
     */
    private $displayErrorDetails;

    /**
     * @param ProblemRendererInterface $renderer
     * @param bool $displayErrorDetails
     */
    public function __construct(ProblemRendererInterface $renderer = null, $displayErrorDetails = false)
    {
        $this->renderer = $renderer ?: new JSONRenderer;

        $this->displayErrorDetails = $displayErrorDetails;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function handleNotFound(ServerRequestInterface $request, ResponseInterface $response)
    {
        $problem = new HTTPProblem(404, null);
        return $this->renderProblem($response, $this->renderer, $problem);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string[] $methods
     *
     * @return ResponseInterface
     */
    public function handleNotAllowed(ServerRequestInterface $request, ResponseInterface $response, array $methods)
    {
        $status = ($request->getMethod() === 'OPTIONS') ? 200 : 405;

        $detail = "Allowed methods: " . implode(', ', $methods);
        $extensions = [
            'allowed_methods' => $methods
        ];

        $problem = new HTTPProblem($status, $detail, $extensions);
        return $this->renderProblem($response, $this->renderer, $problem);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Exception $exception
     *
     * @return ResponseInterface
     */
    public function handleException(ServerRequestInterface $request, ResponseInterface $response, Exception $exception)
    {
        return $this->withError($response, $exception);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Throwable $throwable
     *
     * @return ResponseInterface
     */
    public function handleThrowable(ServerRequestInterface $request, ResponseInterface $response, Throwable $throwable)
    {
        return $this->withError($response, $throwable);
    }

    /**
     * @param ResponseInterface $response
     * @param Throwable|Exception $throwable
     *
     * @return ResponseInterface
     */
    private function withError(ResponseInterface $response, $throwable)
    {
        $detail = 'Internal Server Error';
        $extensions = [];

        if ($this->displayErrorDetails) {
            $throwables = $this->unpackThrowables($throwable);
            $extensions['error_details'] = $this->formatStacktraceForExceptions($throwables);

            $detail = $throwable->getMessage();
        }

        $problem = new HTTPProblem(500, $detail, $extensions);
        return $this->renderProblem($response, $this->renderer, $problem);
    }
}
