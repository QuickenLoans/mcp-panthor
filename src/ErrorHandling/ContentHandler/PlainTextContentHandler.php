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
use Throwable;

class PlainTextContentHandler implements ContentHandlerInterface
{
    use NewBodyTrait;
    use StacktraceFormatterTrait;

    /**
     * @var bool
     */
    private $displayErrorDetails;

    /**
     * @param bool $displayErrorDetails
     */
    public function __construct($displayErrorDetails = false)
    {
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
        return $this
            ->withText($response, 'Not Found.')
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

        $text = [
            'Method not allowed.',
            sprintf('Allowed methods: %s', implode(', ', $methods))
        ];

        return $this
            ->withText($response, $text)
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
        $text = 'Application Error';

        if ($this->displayErrorDetails) {
            $throwables = $this->unpackThrowables($throwable);
            $text = [
                'Application Error',
                $throwable->getMessage(),
                '',
                'Error Details:',
                $this->formatStacktraceForExceptions($throwables)
            ];
        }

        return $this
            ->withText($response, $text)
            ->withStatus(500);
    }

    /**
     * @param ResponseInterface $response
     * @param string|string[] $text
     *
     * @return ResponseInterface
     */
    private function withText(ResponseInterface $response, $text)
    {
        if (is_array($text)) {
            $text = implode(PHP_EOL, $text);
        }

        return $this
            ->withNewBody($response, $text)
            ->withHeader('Content-Type', 'text/plain');
    }
}
