<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ErrorHandling\ContentHandlerInterface;
use QL\Panthor\ErrorHandling\ErrorHandler;
use QL\Panthor\ErrorHandling\StacktraceFormatterTrait;
use Throwable;

/**
 * This handler is meant to wrap another handler, logging the event/error before it is passed through.
 *
 * By default, exceptions and errors are logged as "error".
 * "not-found" and "not-allowed" events can be optionally logged as well. Or log levels can be changed individually.
 *
 * Customize logging by pass specify a PSR-3 log level.
 * ```php
 * $configuration = [
 *     'error' => 'critical',
 *     'not-allowed' => 'info',
 *     'not-found' => 'info'
 * ];
 *
 * $handler = new LoggingContentHandler($contentHandler, $psr3Logger, $configuration);
 * ```
 */
class LoggingContentHandler implements ContentHandlerInterface
{
    use StacktraceFormatterTrait;

    /**
     * @var ContentHandlerInterface
     */
    private $handler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var array
     */
    private static $levels = [
        LogLevel::EMERGENCY => 1,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 1,
        LogLevel::ERROR => 1,
        LogLevel::WARNING => 1,
        LogLevel::NOTICE => 1,
        LogLevel::INFO => 1,
        LogLevel::DEBUG => 1
    ];

    /**
     * @param ContentHandlerInterface $handler
     * @param LoggerInterface $logger
     * @param array $configuration
     */
    public function __construct(ContentHandlerInterface $handler, LoggerInterface $logger = null, array $configuration = [])
    {
        $this->handler = $handler;
        $this->configuration = $configuration;

        $this->logger = $logger ?: new NullLogger;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function handleNotFound(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($level = $this->shouldLog('not-found')) {
            $message = $request->getRequestTarget();
            $this->logEvent('not-found', $message, $level);
        }

        return $this->handler->handleNotFound($request, $response);
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
        if ($level = $this->shouldLog('not-allowed')) {
            $message = sprintf('%s on %s', $request->getMethod(), $request->getRequestTarget());
            $this->logEvent('not-allowed', $message, $level);
        }

        return $this->handler->handleNotAllowed($request, $response, $methods);
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
        if ($level = $this->shouldLog('error')) {
            $this->logError($exception, $level);
        }

        return $this->handler->handleException($request, $response, $exception);
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
        if ($level = $this->shouldLog('error')) {
            $this->logError($throwable, $level);
        }

        return $this->handler->handleThrowable($request, $response, $throwable);
    }

    /**
     * Should the event be logged?
     *
     * If yes - return the log level to log the event as.
     * If no  - return a falsey value
     *
     * @param string $event
     *
     * @return string
     */
    private function shouldLog($event)
    {
        $level = isset($this->configuration[$event]) ? $this->configuration[$event] : '';
        $level = strtolower($level);

        if (!$level) {
            return '';
        }

        if (!isset(self::$levels[$level])) {
            return '';
        }

        return $level;
    }

    /**
     * @param string $event
     * @param string $message
     * @param string $level
     *
     * @return void
     */
    private function logEvent($event, $message, $level)
    {
        if ($event === 'not-allowed') {
            $message = sprintf('Method Not Allowed: %s', $message);
        } elseif ($event === 'not-found') {
            $message = sprintf('Page Not Found: %s', $message);
        }

        $this->logger->$level($message);
    }

    /**
     * @param Exception|Throwable $throwable
     * @param string $level
     *
     * @return void
     */
    private function logError($throwable, $level)
    {
        $class = get_class($throwable);
        $code = 0;
        $type = $class;

        if ($throwable instanceof ErrorException) {
            $code = $throwable->getSeverity();
            $type = ErrorHandler::getErrorType($code);
        }

        // Unpack throwables
        $throwables = $this->unpackThrowables($throwable);

        $context = [
            'errorCode' => $code,
            'errorType' => $type,
            'errorClass' => $class,
            'errorStacktrace' => $this->formatStacktraceForExceptions($throwables)
        ];

        $this->log($level, $throwable->getMessage(), $context);
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    private function log($level, $message, array $context)
    {
        $this->logger->$level($message, $context);
    }
}
