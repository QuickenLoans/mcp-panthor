<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ContentHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function handleNotFound(ServerRequestInterface $request, ResponseInterface $response);

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string[] $methods
     *
     * @return ResponseInterface
     */
    public function handleNotAllowed(ServerRequestInterface $request, ResponseInterface $response, array $methods);

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Exception $exception
     *
     * @return ResponseInterface
     */
    public function handleException(ServerRequestInterface $request, ResponseInterface $response, Exception $exception);

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Throwable $throwable
     *
     * @return ResponseInterface
     */
    public function handleThrowable(ServerRequestInterface $request, ResponseInterface $response, Throwable $throwable);
}
