<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionHandler;

use Exception;
use Psr\Http\Message\ResponseInterface;
use QL\Panthor\Exception\HTTPProblemException;
use QL\Panthor\ErrorHandling\ExceptionHandlerInterface;
use QL\Panthor\ErrorHandling\ExceptionRendererInterface;

class HTTPProblemHandler implements ExceptionHandlerInterface
{
    /**
     * @type ExceptionRendererInterface
     */
    private $renderer;

    /**
     * @var ResponseInterface $response
     */
    private $response;

    /**
     * @param ResponseInterface $response
     * @param ExceptionRendererInterface $renderer
     */
    public function __construct(ResponseInterface $response, ExceptionRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandledExceptions()
    {
        return [HTTPProblemException::CLASS];
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Exception $exception)
    {
        if (!$exception instanceof HTTPProblemException) return false;

        $status = $exception->problem()->status();

        $context = [
            'message' => $exception->getMessage(),
            'status' => $status,
            'severity' => 'Problem',
            'exception' => $exception
        ];

        $this->renderer->render($status, $context);

        return true;
    }
}
