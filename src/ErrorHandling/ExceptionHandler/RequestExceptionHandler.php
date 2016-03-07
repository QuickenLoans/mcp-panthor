<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionHandler;

use Exception;
use Psr\Http\Message\ResponseInterface;
use QL\Panthor\ErrorHandling\ExceptionHandlerInterface;
use QL\Panthor\ErrorHandling\ExceptionRendererInterface;
use QL\Panthor\Exception\RequestException;

/**
 * Handler for 400s and other client requests.
 */
class RequestExceptionHandler implements ExceptionHandlerInterface
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
        return [RequestException::CLASS];
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Exception $exception)
    {
        if (!$exception instanceof RequestException) return false;

        $status = $exception->getCode();
        if ($status < 400 || $status >= 500) {
            $status = 400;
        }

        $context = [
            'message' => $exception->getMessage(),
            'status' => $status,
            'severity' => 'Exception',
            'exception' => $exception
        ];

        $this->renderer->render($this->response, $status, $context);

        return true;
    }
}
