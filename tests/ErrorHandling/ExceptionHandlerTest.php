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
use QL\Panthor\Exception\Exception;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class ExceptionHandlerTest extends PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $contentHandler;

    public function setUp()
    {
        $this->request = Request::createFromEnvironment(Environment::mock());
        $this->response = new Response;

        $this->contentHandler = Mockery::mock(ContentHandlerInterface::class);
    }

    public function testBadParamDoesNotHandle()
    {
        $handler = new ExceptionHandler($this->contentHandler, $this->request, $this->response);
        $handled = $handler->handle('derp');

        $this->assertSame(false, $handled);
    }

    public function testHandlerReturnsBadResponse()
    {
        $ex = new ErrorException('exception message');

        $this->contentHandler
            ->shouldReceive('handleException')
            ->with($this->request, $this->response, $ex)
            ->andReturn('badresponse')
            ->once();


        $handler = new ExceptionHandler($this->contentHandler, $this->request, $this->response);
        $handled = $handler->handle($ex);

        $this->assertSame(false, $handled);
    }

    public function testHandlerDoesNotRenderIfSlimNotAttached()
    {
        $ex = new ErrorException('exception message');

        $this->contentHandler
            ->shouldReceive('handleException')
            ->with($this->request, $this->response, $ex)
            ->andReturn($this->response)
            ->once();


        $handler = new ExceptionHandler($this->contentHandler, $this->request, $this->response);
        $handled = $handler->handle($ex);

        $this->assertSame(true, $handled);
    }

    public function testHandlerRendersThroughSlim()
    {
        $ex = new ErrorException('exception message');

        $this->contentHandler
            ->shouldReceive('handleException')
            ->with($this->request, $this->response, $ex)
            ->andReturn($this->response)
            ->once();

        $slim = Mockery::mock(App::class);
        $slim
            ->shouldReceive('respond')
            ->with($this->response)
            ->once();

        $handler = new ExceptionHandler($this->contentHandler, $this->request, $this->response);
        $handler->attachSlim($slim);
        $handled = $handler->handle($ex);

        $this->assertSame(true, $handled);
    }
}
