<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ErrorHandlerInterface as SlimErrorHandlerInterface;
use Slim\ResponseEmitter;
use Throwable;

class ExceptionHandler implements ExceptionHandlerInterface, SlimErrorHandlerInterface
{
    const FALLBACK_ERROR_RESPONSE = <<<EOT
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <title>Panthor Error</title>
        </head>

        <body>
            <h1>Panthor Error</h1>
            <p>Internal Server Error. The application failed to launch.</p>
        </body>
    </html>
    EOT;

    /**
     * @var ContentHandlerInterface
     */
    private $handler;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var ServerRequestInterface|null
     */
    private $request;

    /**
     * @param ContentHandlerInterface $handler
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ContentHandlerInterface $handler, ResponseFactoryInterface $responseFactory)
    {
        $this->handler = $handler;
        $this->responseFactory = $responseFactory;

        $this->request = null;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return self
     */
    public function attachRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Handle a throwable, and return whether it was handled and the remaining stack should be aborted.
     *
     * @param Exception|Throwable|mixed $throwable
     *
     * @return bool
     */
    public function handle($throwable)
    {
        $defaultRequest = $this->request;
        $defaultResponse = $this->responseFactory->createResponse(500);

        // We require the http request be attached to fully render the error.
        // Ideally this never happens, since the error middleware will be triggered in __invoke(), NOT handle().
        // This will typically trigger for any exception that is fatal (such as oom), since that is on shutdown.
        if (!$defaultRequest) {
            $this->renderResponse($defaultResponse, self::FALLBACK_ERROR_RESPONSE);
            return true;
        }

        if ($throwable instanceof Exception) {
            $response = $this->handler->handleException($defaultRequest, $defaultResponse, $throwable);
        } elseif ($throwable instanceof Throwable) {
            $response = $this->handler->handleThrowable($defaultRequest, $defaultResponse, $throwable);
        } else {
            return false;
        }

        if ($response instanceof ResponseInterface) {
            $this->renderResponse($response);
            return true;
        } else {
            return false;
        }
    }

    /**
     * This is so this class can be used as the handler for 'Slim\Middleware\ErrorMiddleware'.
     *
     * @param ServerRequestInterface $request
     * @param Throwable $exception
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     * @param bool $logErrorDetails
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $defaultResponse = $this->responseFactory->createResponse(500);

        if ($exception instanceof HttpMethodNotAllowedException) {
            return $this->handler->handleNotAllowed($request, $defaultResponse, $exception->getAllowedMethods());
        } elseif ($exception instanceof HttpNotFoundException) {
            return $this->handler->handleNotFound($request, $defaultResponse);
        }

        return $this->handler->handleThrowable($request, $defaultResponse, $exception);
    }

    /**
     * @param ResponseInterface $response
     * @param string $body
     *
     * @return void
     */
    protected function renderResponse(ResponseInterface $response, string $body = ''): void
    {
        if ($body) {
            $response->getBody()->write($body);
        }

        $responseEmitter = new ResponseEmitter;
        $responseEmitter->emit($response);
    }
}
