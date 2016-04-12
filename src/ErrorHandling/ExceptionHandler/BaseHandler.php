<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionHandler;

use ErrorException;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use QL\Panthor\ErrorHandling\ExceptionHandlerInterface;
use QL\Panthor\ErrorHandling\ExceptionRendererInterface;
use QL\Panthor\ErrorHandling\ErrorHandler;
use QL\Panthor\ErrorHandling\StacktraceFormatterTrait;
use Throwable;

/**
 * Handler for base exception. This should be attached last to ensure if no other handler can handle an exception, this one can.
 *
 * In addition, this logger will log all exceptions. If an exception has gotten to this point it would cause an uncaught exception.
 */
class BaseHandler implements ExceptionHandlerInterface
{
    use HandledExceptionsTrait;
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
     * @param ExceptionRendererInterface $renderer
     * @param LoggerInterface|null $logger
     */
    public function __construct(ExceptionRendererInterface $renderer, LoggerInterface $logger = null)
    {
        $this->renderer = $renderer;
        $this->logger = $logger ?: new NullLogger;

        $this->setHandledThrowables([
            Exception::CLASS,
            Throwable::CLASS
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

        $status = 500;
        $context = [
            'message' => $throwable->getMessage(),
            'status' => $status,
            'severity' => 'Exception',
            'exception' => $throwable
        ];

        if ($throwable instanceof ErrorException) {
            $context['severity'] = ErrorHandler::getErrorType($throwable->getSeverity());
        }

        $this->log($throwable);
        $this->renderer->render($status, $context);

        return true;
    }

    /**
     * @param Exception|Throwable $throwable
     *
     * @return void
     */
    private function log($throwable)
    {
        $class = get_class($throwable);
        $code = 0;
        $type = $class;

        if ($throwable instanceof ErrorException) {
            $code = $throwable->getSeverity();
            $type = ErrorHandler::getErrorType($code);
        }

        // Unpack exceptions
        $throwables = [$throwable];
        $e = $throwable;
        while ($e = $e->getPrevious()) {
            $throwables[] = $e;
        }

        $context = [
            'errorCode' => $code,
            'errorType' => $type,
            'errorClass' => $class,
            'errorStacktrace' => $this->formatStacktraceForExceptions($throwables)
        ];

        $this->logger->error($throwable->getMessage(), $context);
    }
}
