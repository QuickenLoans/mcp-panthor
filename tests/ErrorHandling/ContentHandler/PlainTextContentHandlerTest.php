<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class PlainTextContentHandlerTest extends TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = Request::createFromEnvironment(Environment::mock());
        $this->response = new Response;
    }

    public function testNotFound()
    {
        $handler = new PlainTextContentHandler;
        $response = $handler->handleNotFound($this->request, $this->response);

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 404;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Not Found';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/plain'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = 'Not Found.';
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
        $handler = new PlainTextContentHandler;
        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 405;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Method Not Allowed';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/plain'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = <<<HTML
Method not allowed.
Allowed methods: PATCH, STEVE
HTML;
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

        $handler = new PlainTextContentHandler;
        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 200;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'OK';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/plain'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = <<<HTML
Method not allowed.
Allowed methods: PATCH, STEVE
HTML;
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

        $handler = new PlainTextContentHandler;
        $response = $handler->handleException($this->request, $this->response, $ex);

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 500;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Internal Server Error';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/plain'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = 'Application Error';
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

        $handler = new PlainTextContentHandler(true);
        $response = $handler->handleException($this->request, $this->response, $ex);

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 500;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Internal Server Error';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'text/plain'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = <<<HTML
Application Error
exception message
HTML;
        $actualBody = $response->getBody();
        $actualBody->rewind();
        $rendered = $actualBody->getContents();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertContains($expectedBody, $rendered);
        $this->assertContains('ErrorHandling/ContentHandler/PlainTextContentHandlerTest.php:167', $rendered);
        $this->assertContains('Error Details:', $rendered);
    }
}
