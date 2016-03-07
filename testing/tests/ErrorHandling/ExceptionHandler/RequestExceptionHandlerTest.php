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

class RequestExceptionHandlerTest extends PHPUnit_Framework_TestCase
{
    use MockeryAssistantTrait;

    public function testDoesNotHandleIfExceptionNotRequestException()
    {
        $renderer = Mockery::mock(ExceptionRendererInterface::CLASS);
        $response = Mockery::mock(ResponseInterface::class);

        $handler = new RequestExceptionHandler($response, $renderer);

        $this->assertFalse($handler->handle(new Exception));
        $this->assertFalse($handler->handle(new NotFoundException));
        $this->assertFalse($handler->handle(new BaseException));
    }

    public function testStatusAndContextPassedToRenderer()
    {
        $renderer = Mockery::mock(ExceptionRendererInterface::CLASS);
        $response = Mockery::mock(ResponseInterface::class);
        $this->spy($renderer, 'render', [$response, 410, $this->buildSpy('renderer')]);

        $handler = new RequestExceptionHandler($response, $renderer);

        $ex = new RequestException('msg', 410);
        $this->assertTrue($handler->handle($ex));

        $context = $this->getSpy('renderer');
        $context = $context();

        $this->assertCount(4, $context);

        $this->assertSame('msg', $context['message']);
        $this->assertSame(410, $context['status']);
        $this->assertSame('Exception', $context['severity']);
        $this->assertSame($ex, $context['exception']);
    }

    public function testInvalidStatusIsResetTo400()
    {
        $renderer = Mockery::mock(ExceptionRendererInterface::CLASS);
        $response = Mockery::mock(ResponseInterface::class);
        $this->spy($renderer, 'render', [$response, 400, $this->buildSpy('renderer')]);


        $handler = new RequestExceptionHandler($response, $renderer);

        $ex = new RequestException('msg');
        $this->assertTrue($handler->handle($ex));

        $context = $this->getSpy('renderer');
        $context = $context();

        $this->assertSame(400, $context['status']);
    }
}
