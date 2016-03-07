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
    use HandledExceptionsTrait;

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

        $this->setHandledThrowables([
            HTTPProblemException::CLASS
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function handle($throwable)
    {
        if (!$this->canHandleThrowable($throwable)) {
            return false;
        }

        $status = $throwable->problem()->status();

        $context = [
            'message' => $throwable->getMessage(),
            'status' => $status,
            'severity' => 'Problem',
            'exception' => $throwable
        ];

        $this->renderer->render($status, $context);

        return true;
    }
}
