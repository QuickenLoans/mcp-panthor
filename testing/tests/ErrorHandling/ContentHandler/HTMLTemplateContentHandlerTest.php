<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Testing\MockeryAssistantTrait;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class HTMLTemplateContentHandlerTest extends PHPUnit_Framework_TestCase
{
    use MockeryAssistantTrait;

    private $request;
    private $response;
    private $template;

    public function setUp()
    {
        $this->request = Request::createFromEnvironment(Environment::mock());
        $this->response = new Response;

        $this->template = Mockery::mock(TemplateInterface::class);
    }

    public function testNotFound()
    {
        $this->template
            ->shouldReceive('render')
            ->with([
                'message' => 'Not Found',
                'status' => 404
            ])
            ->andReturn('<html>derp</html>');
        $handler = new HTMLTemplateContentHandler($this->template);

        $response = $handler->handleNotFound($this->request, $this->response);

        $expected = <<<HTML
HTTP/1.1 404 Not Found
Content-Type: text/html

<html>derp</html>
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testNotAllowed()
    {
        $this->template
            ->shouldReceive('render')
            ->with([
                'message' => 'Method not allowed',
                'status' => 405,
                'allowed_methods' => 'PATCH, STEVE'
            ])
            ->andReturn('<html>derp</html>');
        $handler = new HTMLTemplateContentHandler($this->template);

        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expected = <<<HTML
HTTP/1.1 405 Method Not Allowed
Content-Type: text/html

<html>derp</html>
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testNotAllowedSets200StatusIfOptionsRequest()
    {
        $this->request = $this->request->withMethod('OPTIONS');

        $this->template
            ->shouldReceive('render')
            ->with([
                'message' => 'Method not allowed',
                'status' => 200,
                'allowed_methods' => 'PATCH, STEVE'
            ])
            ->andReturn('<html>derp</html>');
        $handler = new HTMLTemplateContentHandler($this->template);

        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expected = <<<HTML
HTTP/1.1 200 OK
Content-Type: text/html

<html>derp</html>
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testHandleException()
    {
        $ex = new ErrorException('exception message');

        $this->template
            ->shouldReceive('render')
            ->with([
                'message' => 'Application Error',
                'status' => 500,
                'severity' => 'E_ERROR',
                'throwable' => $ex
            ])
            ->andReturn('<html>error</html>');

        $handler = new HTMLTemplateContentHandler($this->template);
        $response = $handler->handleException($this->request, $this->response, $ex);

        $expected = <<<HTML
HTTP/1.1 500 Internal Server Error
Content-Type: text/html

<html>error</html>
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testHandleExceptionWithErrorDetails()
    {
        $ex = new ErrorException('exception message');

        $spy = $this->buildSpy('context');
        $this
        ->spy($this->template, 'render', [$spy])
        ->andReturn('<html>error</html>');

        $handler = new HTMLTemplateContentHandler($this->template, true);
        $response = $handler->handleException($this->request, $this->response, $ex);

        $expected = <<<HTML
HTTP/1.1 500 Internal Server Error
Content-Type: text/html

<html>error</html>
HTML;

        $this->assertSame($expected, (string) $response);

        $captured = $spy();
        $this->assertSame('exception message', $captured['message']);
        $this->assertSame(500, $captured['status']);
        $this->assertSame('E_ERROR', $captured['severity']);
        $this->assertSame($ex, $captured['throwable']);
        $this->assertContains('ErrorHandling/ContentHandler/HTMLTemplateContentHandlerTest.php:137', $captured['details']);
    }
}
