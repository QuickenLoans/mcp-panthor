<?php

namespace QL\Panthor\ErrorHandling;

use ErrorException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use QL\Panthor\Exception\Exception;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use QL\Panthor\Testing\MockeryAssistantTrait;

class ExceptionHandlerTest extends TestCase
{
    use MockeryAssistantTrait;
    use MockeryPHPUnitIntegration;

    private $request;
    private $response;
    private $contentHandler;

    public function setUp()
    {
        $this->request = (new RequestFactory)->createRequest('GET', '/path');
        $this->response = (new ResponseFactory)->createResponse();

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
            ->times(1);


        $handler = new ExceptionHandler($this->contentHandler, $this->request, $this->response);
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

        $handler = new ExceptionHandler($this->contentHandler, $this->request, $this->response);

        ob_start();
        $handled = $handler->handle($ex);
        $output = ob_get_clean();

        $this->assertSame(true, $handled);
        $this->assertSame('sample response', $output);
    }
}
