<?php

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class PlainTextContentHandlerTest extends TestCase
{
    private $request;
    private $response;

    public function setUp(): void
    {
        $this->request = (new RequestFactory)->createRequest('GET', '/path');
        $this->response = (new ResponseFactory)->createResponse();
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

        $expectedBody = <<<EOT
        Method not allowed.
        Allowed methods: PATCH, STEVE
        EOT;
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

        $expectedBody = <<<EOT
        Method not allowed.
        Allowed methods: PATCH, STEVE
        EOT;
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

        $expectedBody = <<<EOT
        Application Error
        exception message
        EOT;
        $actualBody = $response->getBody();
        $actualBody->rewind();
        $rendered = $actualBody->getContents();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertStringContainsString($expectedBody, $rendered);
        $this->assertStringContainsString('ErrorHandling/ContentHandler/PlainTextContentHandlerTest.php:161', $rendered);
        $this->assertStringContainsString('Error Details:', $rendered);
    }
}
