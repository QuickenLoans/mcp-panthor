<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use ErrorException;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use const E_ALL;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

/**
 * This handler requires:
 * - "psr/log"
 *
 * This class is responsible for:
 * - Handling exceptions
 * - Handling errors
 * - Attaching to Slim to take over notFound() and error() handling.
 * - Logging errors
 * - Convert all superfatals to exceptions
 *
 * Default errors converted to Exceptions:
 * - E_ALL - E_DEPRECATED - E_USER_DEPRECATED
 *
 * Default errors logged:
 * - E_DEPRECATED | E_USER_DEPRECATED
 *
 * Example usage:
 * ```php
 * $logger = new NullLogger;
 * $app = new Slim;
 *
 * $handler = new ErrorHandler($logger);
 * $handler->setStacktraceLogging(true);
 * $handler->setThrownErrors(\E_ALL & ~\E_DEPRECATED & ~\E_USER_DEPRECATED);
 * $handler->setLoggedErrors(\E_DEPRECATED | \E_USER_DEPRECATED);
 *
 * // The following will register ErrorHandler as error, exception, and shutdown handlers.
 * $handler->register();
 *
 * // Attach handler to Slim to take over 404 and error handling.
 * $handler->attach($app);
 *
 * // A stack of exception handlers can be provided to handle certain types of exceptions.
 * $handler->addHandler($handlerForNotFound);
 * $handler->addHandler($handlerForClientErrors);
 * $handler->addHandler($handlerForBaseException);
 * ```
 */
class ErrorHandler
{
    const LOG_LEVEL = 'error';

    // ::formatStacktrace()
    // ::setStacktraceLogging()
    use StacktraceFormatterTrait;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var ExceptionHandlerInterface
     */
    private $handler;

    /**
     * @var int
     */
    private $thrownErrors;
    private $loggedErrors;

    /**
     * @var array
     */
    private $logLevels;

    /**
     * Exception handler for errors only caught by the shutdown handler.
     *
     * @var callable|null
     */
    private static $exceptionHandler;

    /**
     * @var string
     */
    private static $reservedMemory;

    /**
     * @var array
     */
    private static $levels = array(
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_NOTICE => 'E_NOTICE',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_WARNING => 'E_WARNING',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',

        E_PARSE => 'E_PARSE',
        E_ERROR => 'E_ERROR',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
    );

    /**
     * @var array
     */
    private static $humanLevels = array(
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
        E_NOTICE => 'Notice',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Runtime Notice',
        E_WARNING => 'Warning',
        E_USER_WARNING => 'User Warning',
        E_USER_ERROR => 'User Error',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error',

        E_PARSE => 'Parse Error',
        E_ERROR => 'Error',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
    );

    /**
     * @param ExceptionHandlerInterface $handler
     * @param LoggerInterface|null $logger
     */
    public function __construct(ExceptionHandlerInterface $handler, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger;
        $this->handler = $handler;

        $this->thrownErrors = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED;
        $this->loggedErrors = E_ALL;

        $this->logLevels = [
            E_DEPRECATED => 'warning',
            E_USER_DEPRECATED => 'warning',
            E_NOTICE => 'warning',
            E_USER_NOTICE => 'warning',
            E_STRICT => 'warning',
            E_WARNING => 'error',
            E_USER_WARNING => 'error',
            E_USER_ERROR => 'error',
            E_RECOVERABLE_ERROR => 'error',
        ];
    }

    /**
     * @param Exception|Throwable $exception
     *
     * @throws Exception|Throwable if not handled correctly
     *
     * @return void
     */
    public function handleException($exception)
    {
        $isHandled = false;
        try {
            $isHandled = $this->handler->handle($exception);
        } catch (Exception $ex) {
        } catch (Throwable $ex) {
        }

        // Bomb out if handler returns true, since was able to render something to the client
        if ($isHandled) {
            exit;
        }

        // Rethrow to be handled by default php exception handling.
        throw $exception;
    }

    /**
     * @see http://php.net/manual/en/function.set-error-handler.php
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     *
     * @throws ErrorException
     *
     * @return bool
     */
    public function handleError($errno, $errstr, $errfile, $errline, array $errcontext = [])
    {
        $msg = sprintf('%s: %s', self::getErrorDescription($errno), $errstr);

        if ($errno & $this->thrownErrors) {
            throw new ErrorException($msg, 0, $errno, $errfile, $errline);
        }

        if ($this->logError($errno, $msg, $errfile, $errline)) {
            return true;
        }

        return false;
    }

    /**
     * Shutdown registered function for handling PHP fatal errors.
     *
     * @throws ErrorException
     *
     * @return void
     */
    public static function handleFatalError()
    {
        self::$reservedMemory = '';

        if (!$handler = self::$exceptionHandler) {
            return;
        }

        $error = error_get_last();

        if ($error && $error['type'] &= E_PARSE | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR) {
            $msg = sprintf('%s: %s', self::getErrorDescription($error['type']), $error['message']);

            if (strpos($error['message'], 'Allowed memory') === 0 || strpos($error['message'], 'Out of memory') === 0) {
                $exception = new ErrorException($msg, 0, $error['type'], $error['file'], $error['line']);
            } else {
                // @todo provide backtrace (through symfony/debug?)
                $exception = new ErrorException($msg, 0, $error['type'], $error['file'], $error['line']);
            }
        }

        if (!isset($exception)) {
            return;
        }

        try {
            $handler($exception);
        } catch (Exception $ex) {
            // Silence any further exceptions
        }
    }

    /**
     * Register this handler as the exception and error handler.
     *
     * @param int $handledErrors
     *
     * @return self
     */
    public function register($handledErrors = E_ALL)
    {
        $errHandler = [$this, 'handleError'];
        $exHandler = [$this, 'handleException'];

        $handledErrors = is_int($handledErrors) ? $handledErrors : E_ALL;

        set_error_handler($errHandler, $handledErrors);
        set_exception_handler($exHandler);

        return $this;
    }

    /**
     * Register this handler as the shutdown handler.
     *
     * @return self
     */
    public function registerShutdown()
    {
        if (self::$reservedMemory === null) {
            self::$reservedMemory = str_repeat('x', 10240);
            register_shutdown_function(__CLASS__ . '::handleFatalError');
        }

        self::$exceptionHandler = [$this, 'handleException'];

        return $this;
    }

    /**
     * Errors that will be thrown as exceptions.
     *
     * @param int $thrownTypes
     *
     * @return void
     */
    public function setThrownErrors($thrownTypes)
    {
        if (is_int($thrownTypes)) {
            $this->thrownErrors = $thrownTypes;
        }
    }

    /**
     * Errors that will be logged only.
     *
     * @param int $loggedTypes
     *
     * @return void
     */
    public function setLoggedErrors($loggedTypes)
    {
        if (is_int($loggedTypes)) {
            $this->loggedErrors = $loggedTypes;
        }
    }

    /**
     * @param int $errorSeverity
     *
     * @return string
     */
    public static function getErrorDescription($errorSeverity)
    {
        if (isset(self::$humanLevels[$errorSeverity])) {
            return self::$humanLevels[$errorSeverity];
        } else {
            return 'Exception';
        }
    }

    /**
     * @param int $errorSeverity
     *
     * @return string
     */
    public static function getErrorType($errorSeverity)
    {
        if (isset(self::$levels[$errorSeverity])) {
            return self::$levels[$errorSeverity];
        } else {
            return 'UNKNOWN';
        }
    }

    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     *
     * @return bool
     */
    private function logError($errno, $errstr, $errfile, $errline)
    {
        if (!($errno & $this->loggedErrors)) {
            return false;
        }

        $loggedLevel = isset($this->logLevels[$errno]) ? $this->logLevels[$errno] : 'error';

        $stacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $stacktrace = array_slice($stacktrace, 2);

        array_unshift($stacktrace, [
            'file' => $errfile,
            'line' => $errline,
        ]);

        $this->logger->log($loggedLevel, $errstr, [
            'errorCode' => $errno,
            'errorType' => self::getErrorType($errno),
            'errorFile' => $errfile,
            'errorLine' => $errline,
            'errorStacktrace' => $this->formatStacktrace($stacktrace),
        ]);

        return true;
    }
}
