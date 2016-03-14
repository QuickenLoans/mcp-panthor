<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ExceptionHandler;

use Exception as BaseException;
use Mockery;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use QL\Panthor\ErrorHandling\ExceptionRendererInterface;
use QL\Panthor\Exception\Exception;
use QL\Panthor\Exception\NotFoundException;
use QL\Panthor\Exception\RequestException;
use QL\Panthor\Testing\MockeryAssistantTrait;

class NotFoundHandlerTest extends PHPUnit_Framework_TestCase
{
    use MockeryAssistantTrait;

    public function testCanHandleNotFoundException()
    {
        $renderer = Mockery::mock(ExceptionRendererInterface::CLASS);
        $response = Mockery::mock(ResponseInterface::class);

        $handler = new NotFoundHandler($response, $renderer);

        $handled = $handler->getHandledExceptions();
        $this->assertCount(1, $handled);

        $handled = $handled[0];

        $this->assertNotInstanceOf($handled, new BaseException);
        $this->assertNotInstanceOf($handled, new Exception);
        $this->assertNotInstanceOf($handled, new RequestException);

        $this->assertInstanceOf($handled, new NotFoundException);
    }

    public function testDoesNotHandleIfExceptionNotRequestException()
    {
        $renderer = Mockery::mock(ExceptionRendererInterface::CLASS);
        $response = Mockery::mock(ResponseInterface::class);

        $handler = new NotFoundHandler($response, $renderer);

        $this->assertFalse($handler->handle(new Exception));
        $this->assertFalse($handler->handle(new RequestException));
        $this->assertFalse($handler->handle(new BaseException));
    }

    public function testStatusAndContextPassedToRenderer()
    {
        $renderer = Mockery::mock(ExceptionRendererInterface::CLASS);
        $response = Mockery::mock(ResponseInterface::class);
        $this->spy($renderer, 'render', [$response, 404, $this->buildSpy('renderer')]);

        $handler = new NotFoundHandler($response, $renderer);

        $ex = new NotFoundException;
        $this->assertTrue($handler->handle($ex));

        $context = $this->getSpy('renderer');
        $context = $context();

        $this->assertCount(4, $context);

        $this->assertSame('Page Not Found', $context['message']);
        $this->assertSame(404, $context['status']);
        $this->assertSame('NotFound', $context['severity']);
        $this->assertSame($ex, $context['exception']);
    }
}
