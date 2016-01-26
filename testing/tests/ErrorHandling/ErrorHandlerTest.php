<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling;

use ErrorException;
use Mockery;
use PHPUnit_Framework_TestCase;
use QL\MCP\Common\Testing\MemoryLogger;
use QL\Panthor\Exception\Exception;
use QL\Panthor\Exception\NotFoundException;
use QL\Panthor\Exception\RequestException;
use QL\Panthor\ErrorHandling\ExceptionHandler\GenericHandler;
use Slim\App;

class ErrorHandlerTest extends PHPUnit_Framework_TestCase
{
    public function testHandlerIsAttached()
    {
        $handler = new ErrorHandler;

        $slim = Mockery::mock(App::CLASS);

        $slim
            ->shouldReceive('notFound')
            ->with([$handler, 'handleNotFound'])
            ->once();

        $slim
            ->shouldReceive('error')
            ->with([$handler, 'handleException'])
            ->once();

        $handler->attach($slim);
    }

    /**
     * @expectedException QL\Panthor\Exception\NotFoundException
     */
    public function testNotFoundExceptionIsThrownWhenNotFoundEventTriggered()
    {
        $handler = new ErrorHandler;

        $handler->handleNotFound();
    }

    /**
     * @expectedException QL\Panthor\Exception\RequestException
     */
    public function testUnhandledExceptionIsRethrown()
    {
        $handler = new ErrorHandler;

        $handler->handleException(new RequestException);
    }

    public function testHandlerThrowsExceptionWillAbortStackAndRethrow()
    {
        $exHandler1 = new GenericHandler([Exception::CLASS], function($ex) {
            throw $ex;
        });

        $called = false;
        $exHandler2 = new GenericHandler([Exception::CLASS], function($ex) use (&$called) {
            $called = true;
            return false;
        });

        $handler = new ErrorHandler;
        $handler->addHandlers([
            $exHandler1,
            $exHandler2,
        ]);

        $exception = new Exception;
        try {
            $handler->handleException($exception);
        } catch (Exception $ex) {
            $rethrown = $ex;
        }

        $this->assertSame($ex, $rethrown);
        $this->assertSame(false, $called);
    }

    public function testThrowableErrorIsThrownAsErrorException()
    {
        $handler = new ErrorHandler;
        $handler->setThrownErrors(\E_DEPRECATED);
        $handler->setLoggedErrors(\E_DEPRECATED);

        try {
            $isHandled = $handler->handleError(\E_DEPRECATED, 'error message', 'filename.php', '80');
        } catch (ErrorException $ex) {}

        $this->assertInstanceOf(ErrorException::CLASS, $ex);
        $this->assertSame(\E_DEPRECATED, $ex->getSeverity());
    }

    public function testErrorIsNotThrownAndNotHandled()
    {
        $handler = new ErrorHandler;
        $handler->setThrownErrors(\E_NOTICE);
        $handler->setLoggedErrors(\E_NOTICE);

        $isHandled = $handler->handleError(\E_DEPRECATED, 'error message', 'filename.php', '80');
        $this->assertSame(false, $isHandled);
    }

    public function testLoggableErrorIsLoggedIfNotThrown()
    {
        $logger = new MemoryLogger;
        $handler = new ErrorHandler($logger);
        $handler->setThrownErrors(\E_NOTICE);
        $handler->setLoggedErrors(\E_DEPRECATED);

        $isHandled = $handler->handleError(\E_DEPRECATED, 'error message', 'filename.php', '80');

        $this->assertSame(true, $isHandled);
        $this->assertCount(1, $logger->messages);
        $this->assertSame('Deprecated: error message', $logger->messages[0]['message']);
    }

    public function testErrorSeverityType()
    {
        $this->assertSame('E_DEPRECATED', ErrorHandler::getErrorType(\E_DEPRECATED));
        $this->assertSame('E_USER_DEPRECATED', ErrorHandler::getErrorType(\E_USER_DEPRECATED));

        $this->assertSame('E_NOTICE', ErrorHandler::getErrorType(\E_NOTICE));
        $this->assertSame('E_USER_NOTICE', ErrorHandler::getErrorType(\E_USER_NOTICE));
        $this->assertSame('E_STRICT', ErrorHandler::getErrorType(\E_STRICT));

        $this->assertSame('E_WARNING', ErrorHandler::getErrorType(\E_WARNING));
        $this->assertSame('E_USER_WARNING', ErrorHandler::getErrorType(\E_USER_WARNING));
        $this->assertSame('E_COMPILE_WARNING', ErrorHandler::getErrorType(\E_COMPILE_WARNING));
        $this->assertSame('E_CORE_WARNING', ErrorHandler::getErrorType(\E_CORE_WARNING));

        $this->assertSame('E_USER_ERROR', ErrorHandler::getErrorType(\E_USER_ERROR));
        $this->assertSame('E_RECOVERABLE_ERROR', ErrorHandler::getErrorType(\E_RECOVERABLE_ERROR));

        $this->assertSame('E_COMPILE_ERROR', ErrorHandler::getErrorType(\E_COMPILE_ERROR));
        $this->assertSame('E_PARSE', ErrorHandler::getErrorType(\E_PARSE));
        $this->assertSame('E_ERROR', ErrorHandler::getErrorType(\E_ERROR));
        $this->assertSame('E_CORE_ERROR', ErrorHandler::getErrorType(\E_CORE_ERROR));

        $this->assertSame('UNKNOWN', ErrorHandler::getErrorType('derp'));
    }

    public function testErrorSeverityDescription()
    {
        $this->assertSame('Deprecated', ErrorHandler::getErrorDescription(\E_DEPRECATED));
        $this->assertSame('User Deprecated', ErrorHandler::getErrorDescription(\E_USER_DEPRECATED));

        $this->assertSame('Notice', ErrorHandler::getErrorDescription(\E_NOTICE));
        $this->assertSame('User Notice', ErrorHandler::getErrorDescription(\E_USER_NOTICE));
        $this->assertSame('Runtime Notice', ErrorHandler::getErrorDescription(\E_STRICT));

        $this->assertSame('Warning', ErrorHandler::getErrorDescription(\E_WARNING));
        $this->assertSame('User Warning', ErrorHandler::getErrorDescription(\E_USER_WARNING));
        $this->assertSame('Compile Warning', ErrorHandler::getErrorDescription(\E_COMPILE_WARNING));
        $this->assertSame('Core Warning', ErrorHandler::getErrorDescription(\E_CORE_WARNING));

        $this->assertSame('User Error', ErrorHandler::getErrorDescription(\E_USER_ERROR));
        $this->assertSame('Catchable Fatal Error', ErrorHandler::getErrorDescription(\E_RECOVERABLE_ERROR));

        $this->assertSame('Compile Error', ErrorHandler::getErrorDescription(\E_COMPILE_ERROR));
        $this->assertSame('Parse Error', ErrorHandler::getErrorDescription(\E_PARSE));
        $this->assertSame('Error', ErrorHandler::getErrorDescription(\E_ERROR));
        $this->assertSame('Core Error', ErrorHandler::getErrorDescription(\E_CORE_ERROR));

        $this->assertSame('Exception', ErrorHandler::getErrorDescription('derp'));
    }
}
