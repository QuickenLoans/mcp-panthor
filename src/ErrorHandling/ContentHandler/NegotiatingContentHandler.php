<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use Exception as BaseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ErrorHandling\ContentHandlerInterface;
use QL\Panthor\Exception\Exception;
use Throwable;

class NegotiatingContentHandler implements ContentHandlerInterface
{
    /**
     * @var array
     */
    private $handlers;

    /**
     * If there is no match between the provided content handlers and the client's desired media type,
     * the first handler will be used.
     *
     * @param ContentHandlerInterface[] $handlers
     */
    public function __construct(array $handlers = [])
    {
        if (func_num_args() === 0) {
            $handlers = $this->getDefaultHandlers();
        }

        foreach ($handlers as $type => $handler) {
            $this->registerHandler($type, $handler);
        }

        if (!$this->handlers) {
            $this->registerHandler('*/*', new PlainTextContentHandler);
        }
    }

    /**
     * @param string $contentType
     * @param ContentHandlerInterface $handler
     *
     * @return void
     */
    public function registerHandler($contentType, ContentHandlerInterface $handler)
    {
        if (!is_string($contentType)) {
            throw new Exception('Invalid content handler specified.');
        }

        $this->handlers[$contentType] = $handler;
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
            ->getContentTypeHandler($request)
            ->handleNotFound($request, $response);
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
        return $this
            ->getContentTypeHandler($request)
            ->handleNotAllowed($request, $response, $methods);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param BaseException $exception
     *
     * @return ResponseInterface
     */
    public function handleException(ServerRequestInterface $request, ResponseInterface $response, BaseException $exception)
    {
        return $this
            ->getContentTypeHandler($request)
            ->handleException($request, $response, $exception);
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
        return $this
            ->getContentTypeHandler($request)
            ->handleThrowable($request, $response, $throwable);
    }

    /**
     * @return array
     */
    protected function getDefaultHandlers()
    {
        $plain = new PlainTextContentHandler;
        return [
            '*/*' => $plain,
            'text/plain' => $plain,
            'application/problem' => new HTTPProblemContentHandler,
            'application/json' => new JSONContentHandler,
        ];
    }

    /**
     * Get a content handler based on Accept header in the request.
     *
     * Falls back to default handler if no matches found.
     *
     * @param ServerRequestInterface $acceptHeader
     *
     * @return ContentHandlerInterface
     */
    private function getContentTypeHandler(ServerRequestInterface $request)
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        $acceptedTypes = explode(',', $acceptHeader);

        foreach ($this->handlers as $contentType => $handler) {
            if ($this->doesTypeMatch($acceptedTypes, $contentType)) {
                return $handler;
            }
        }

        return reset($this->handlers);
    }

    /**
     * @param string[] $acceptedTypes
     * @param string $contentType
     *
     * @return bool
     */
    private function doesTypeMatch(array $acceptedTypes, $contentType)
    {
        foreach ($acceptedTypes as $mimeType) {
            if (stripos($mimeType, $contentType) === 0) {
                return true;
            }
        }

        return false;
    }
}
