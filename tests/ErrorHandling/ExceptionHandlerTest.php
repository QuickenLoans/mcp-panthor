<?php

namespace QL\Panthor\ErrorHandling;

use ErrorException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use QL\Panthor\Exception\Exception;
use QL\Panthor\Testing\MockeryAssistantTrait;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;

class ExceptionHandlerTest extends TestCase
{
    use MockeryAssistantTrait;
    use MockeryPHPUnitIntegration;

    private $contentHandler;
    private $responseFactory;

    private $request;
    private $response;

    public function setUp(): void
    {
        $this->request = (new RequestFactory)->createRequest('GET', '/path');
        $this->response = (new ResponseFactory)->createResponse();

        $this->contentHandler = Mockery::mock(ContentHandlerInterface::class);

        $this->responseFactory = Mockery::mock(ResponseFactory::class, [
            'createResponse' => $this->response,
        ]);
    }

    public function testBadParamDoesNotHandle()
    {
        $handler = new ExceptionHandler($this->contentHandler, $this->responseFactory);
        $handler->attachRequest($this->request);
        $handled = $handler->handle('derp');

        $this->assertSame(false, $handled);
    }

    public function testNoRequestRendersDefaultResponse()
    {
        $handler = new ExceptionHandler($this->contentHandler, $this->responseFactory);

        ob_start();
        $handled = $handler->handle('derp');
        $output = ob_get_clean();

        $this->assertSame(true, $handled);
        $this->assertStringContainsString('Internal Server Error. The application failed to launch.', $output);
    }

    public function testHandlerReturnsBadResponse()
    {
        $ex = new ErrorException('exception message');

        $this->contentHandler
            ->shouldReceive('handleException')
            ->with($this->request, $this->response, $ex)
            ->andReturn('badresponse')
            ->times(1);


        $handler = new ExceptionHandler($this->contentHandler, $this->responseFactory);
        $handler->attachRequest($this->request);
        $handled = $handler->handle($ex);

        $this->assertSame(false, $handled);
    }

    public function testHandlerRenders()
    {
        $ex = new ErrorException('exception message');

        $response = $this->response
            ->withBody(
                (new StreamFactory)->createStream('sample response')
            );

        $this->contentHandler
            ->shouldReceive('handleException')
            ->with($this->request, $this->response, $ex)
            ->andReturn($response)
            ->times(1);

        $handler = new ExceptionHandler($this->contentHandler, $this->responseFactory);
        $handler->attachRequest($this->request);

        ob_start();
        $handled = $handler->handle($ex);
        $output = ob_get_clean();

        $this->assertSame(true, $handled);
        $this->assertSame('sample response', $output);
    }

    public function testExceptionIsHandledAsSlimMiddleware()
    {
        $ex = new ErrorException('exception message');

        $response = (new ResponseFactory)->createResponse();

        $this->contentHandler
            ->shouldReceive('handleThrowable')
            ->with($this->request, $this->response, $ex)
            ->andReturn($response)
            ->times(1);

        $handler = new ExceptionHandler($this->contentHandler, $this->responseFactory);
        $actual = $handler($this->request, $ex, true, true, true);

        $this->assertSame($response, $actual);
    }
    public function testSlimExceptionsAreHandledUniquelyAsSlimMiddleware()
    {
        $ex1 = new HttpMethodNotAllowedException($this->request);
        $ex1->setAllowedMethods(['THIS', 'THAT']);
        $ex2 = new HttpNotFoundException($this->request);

        $response = (new ResponseFactory)->createResponse();

        $this->contentHandler
            ->shouldReceive('handleNotAllowed')
            ->with($this->request, $this->response, ['THIS', 'THAT'])
            ->andReturn($response)
            ->times(1);
        $this->contentHandler
            ->shouldReceive('handleNotFound')
            ->with($this->request, $this->response)
            ->andReturn($response)
            ->times(1);

        $handler = new ExceptionHandler($this->contentHandler, $this->responseFactory);

        $actual = $handler($this->request, $ex1, false, false, false);
        $this->assertSame($response, $actual);

        $actual = $handler($this->request, $ex2, false, false, false);
        $this->assertSame($response, $actual);
    }
}
