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
use QL\Panthor\HTTP\NewBodyTrait;
use QL\Panthor\Utility\JSON;
use Throwable;

class JSONContentHandler implements ContentHandlerInterface
{
    use NewBodyTrait;
    use StacktraceFormatterTrait;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @var bool
     */
    private $displayErrorDetails;

    /**
     * @param JSON $json
     * @param bool $displayErrorDetails
     */
    public function __construct(JSON $json = null, $displayErrorDetails = false)
    {
        $this->json = $json ?: new JSON;

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
            'message' => 'Not Found'
        ];

        return $this
            ->withJSON($response, $contents)
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
            'message' => 'Method not allowed.',
            'allowed_methods' => implode(', ', $methods)
        ];

        return $this
            ->withJSON($response, $contents)
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
        $contents = [
            'error' => 'Application Error'
        ];

        if ($this->displayErrorDetails) {
            $throwables = $this->unpackThrowables($throwable);
            $contents = [
                'error' => $throwable->getMessage(),
                'details' => $this->formatStacktraceForExceptions($throwables)
            ];
        }

        return $this
            ->withJSON($response, $contents)
            ->withStatus(500);
    }

    /**
     * @param ResponseInterface $response
     * @param mixed $jsonable
     *
     * @return ResponseInterface
     */
    private function withJSON(ResponseInterface $response, $jsonable)
    {
        $jsoned = $this->json->encode($jsonable);

        return $this
            ->withNewBody($response, $jsoned)
            ->withHeader('Content-Type', 'application/json');
    }
}
