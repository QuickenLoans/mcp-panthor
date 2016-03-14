<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionHandler;

use ErrorException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QL\Panthor\ErrorHandling\ExceptionHandlerInterface;
use QL\Panthor\ErrorHandling\ExceptionRendererInterface;
use QL\Panthor\ErrorHandling\ErrorHandler;
use QL\Panthor\ErrorHandling\StacktraceFormatterTrait;

/**
 * Handler for base exception. This should be attached last to ensure if no other handler can handle an exception, this one can.
 *
 * In addition, this logger will log all exceptions. If an exception has gotten to this point it would cause an uncaught exception.
 */
class BaseHandler implements ExceptionHandlerInterface
{
    use StacktraceFormatterTrait;

    /**
     * @type ExceptionRendererInterface
     */
    private $renderer;

    /**
     * @type LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseInterface $response
     */
    private $response;

    /**
     * @param ResponseInterface $response
     * @param ExceptionRendererInterface $renderer
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ResponseInterface $response,
        ExceptionRendererInterface $renderer,
        LoggerInterface $logger = null
    ) {
        $this->response = $response;
        $this->renderer = $renderer;
        $this->logger = $logger ?: new NullLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandledExceptions()
    {
        return [Exception::CLASS];
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Exception $exception)
    {
        $status = 500;
        $context = [
            'message' => $exception->getMessage(),
            'status' => $status,
            'severity' => 'Exception',
            'exception' => $exception
        ];

        if ($exception instanceof ErrorException) {
            $context['severity'] = ErrorHandler::getErrorType($exception->getSeverity());
        }

        $this->log($exception);
        $this->renderer->render($this->response, $status, $context);

        return true;
    }

    /**
     * @param Exception $exception
     *
     * @return void
     */
    private function log(Exception $exception)
    {
        $class = get_class($exception);
        $code = 0;
        $type = $class;
        if ($exception instanceof ErrorException) {
            $code = $exception->getSeverity();
            $type = ErrorHandler::getErrorType($code);
        }

        // Unpack exceptions
        $exceptions = [$exception];
        $e = $exception;
        while ($e = $e->getPrevious()) {
            $exceptions[] = $e;
        }

        $context = [
            'errorCode' => $code,
            'errorType' => $type,
            'errorClass' => $class,
            'errorStacktrace' => $this->formatStacktraceForExceptions($exceptions)
        ];

        $this->logger->error($exception->getMessage(), $context);
    }
}
