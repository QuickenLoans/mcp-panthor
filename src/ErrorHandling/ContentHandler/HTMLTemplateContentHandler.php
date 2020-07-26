<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ErrorHandling\ContentHandlerInterface;
use QL\Panthor\ErrorHandling\ErrorHandler;
use QL\Panthor\ErrorHandling\StacktraceFormatterTrait;
use QL\Panthor\HTTP\NewBodyTrait;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Templating\NullTemplate;
use Throwable;

class HTMLTemplateContentHandler implements ContentHandlerInterface
{
    use NewBodyTrait;
    use StacktraceFormatterTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var bool
     */
    private $displayErrorDetails;

    /**
     * @param TemplateInterface|null $template
     * @param bool $displayErrorDetails
     */
    public function __construct(?TemplateInterface $template = null, bool $displayErrorDetails = false)
    {
        $this->template = $template ?: new NullTemplate;
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
        $contents = [
            'message' => 'Not Found',
            'status' => 404,
        ];

        return $this
            ->withHTML($response, $contents)
            ->withStatus(404);
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

        $contents = [
            'message' => 'Method not allowed',
            'status' => $status,
            'allowed_methods' => implode(', ', $methods),
        ];

        return $this
            ->withHTML($response, $contents)
            ->withStatus($status);
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
        if ($throwable instanceof ErrorException) {
            $severity = ErrorHandler::getErrorType($throwable->getSeverity());
        } else {
            $severity = 'Error';
        }

        $contents = [
            'message' => 'Application Error',
            'status' => 500,
            'severity' => $severity,
            'throwable' => $throwable,
        ];

        if ($this->displayErrorDetails) {
            $throwables = $this->unpackThrowables($throwable);
            $contents['details'] = $this->formatStacktraceForExceptions($throwables);
            $contents['message'] = $throwable->getMessage();
        }

        return $this
            ->withHTML($response, $contents)
            ->withStatus(500);
    }

    /**
     * @param ResponseInterface $response
     * @param array $context
     *
     * @return ResponseInterface
     */
    private function withHTML(ResponseInterface $response, array $context)
    {
        $rendered = $this->template->render($context);

        return $this
            ->withNewBody($response, $rendered)
            ->withHeader('Content-Type', 'text/html');
    }
}
