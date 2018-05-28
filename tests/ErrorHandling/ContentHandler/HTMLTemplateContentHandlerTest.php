<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Testing\MockeryAssistantTrait;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class HTMLTemplateContentHandlerTest extends TestCase
{
    use MockeryAssistantTrait;
    use MockeryPHPUnitIntegration;

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

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 404;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Not Found';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/html'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = '<html>derp</html>';
        $actualBody = $response->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertSame($expectedBody, $actualBody->getContents());
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

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 405;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Method Not Allowed';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/html'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = '<html>derp</html>';
        $actualBody = $response->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertSame($expectedBody, $actualBody->getContents());
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

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 200;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'OK';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/html'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = '<html>derp</html>';
        $actualBody = $response->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertSame($expectedBody, $actualBody->getContents());
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

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 500;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Internal Server Error';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/html'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = '<html>error</html>';
        $actualBody = $response->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertSame($expectedBody, $actualBody->getContents());
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

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 500;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Internal Server Error';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/html'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = '<html>error</html>';
        $actualBody = $response->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertSame($expectedBody, $actualBody->getContents());

        $captured = $spy();
        $this->assertSame('exception message', $captured['message']);
        $this->assertSame(500, $captured['status']);
        $this->assertSame('E_ERROR', $captured['severity']);
        $this->assertSame($ex, $captured['throwable']);
        $this->assertContains('ErrorHandling/ContentHandler/HTMLTemplateContentHandlerTest.php:208', $captured['details']);
    }
}
