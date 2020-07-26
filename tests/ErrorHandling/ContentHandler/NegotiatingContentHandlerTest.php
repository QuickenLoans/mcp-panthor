<?php

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class NegotiatingContentHandlerTest extends TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = (new RequestFactory)->createRequest('GET', '/path');
        $this->response = (new ResponseFactory)->createResponse();
    }

    public function testNotFoundWithNoSetHandlersUsesDefaultList()
    {
        $this->request = $this->request->withHeader('Accept', 'application/json');

        $handler = new NegotiatingContentHandler;
        $response = $handler->handleNotFound($this->request, $this->response);

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 404;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Not Found';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'application/json'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = '{"message":"Not Found"}';
        $actualBody = $response->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertSame($expectedBody, $actualBody->getContents());
    }

    public function testNotAllowedWithEmptyListOnlyUsesPlaintext()
    {
        $this->request = $this->request->withHeader('Accept', 'application/json');

        $handler = new NegotiatingContentHandler([]);
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

    public function testHandleExceptionNoMatchUsesFirstInList()
    {
        $this->request = $this->request->withHeader('Accept', 'weird/type');
        $ex = new ErrorException('exception message');

        $handler = new NegotiatingContentHandler([
            'application/json' => new JSONContentHandler,
            'text/plain' => new PlainTextContentHandler
        ]);

        $response = $handler->handleException($this->request, $this->response, $ex);

        $expectedHTTPVersion = '1.1';
        $actualHTTPVersion = $response->getProtocolVersion();

        $expectedStatusCode = 500;
        $actualStatusCode = $response->getStatusCode();

        $expectedReasonPhrase = 'Internal Server Error';
        $actualReasonPhrase = $response->getReasonPhrase();

        $expectedHeaders = [
            'Content-Type' => [
                'application/json'
            ]
        ];
        $actualHeaders = $response->getHeaders();

        $expectedBody = '{"error":"Application Error"}';
        $actualBody = $response->getBody();
        $actualBody->rewind();

        $this->assertSame($expectedHTTPVersion, $actualHTTPVersion);
        $this->assertSame($expectedStatusCode, $actualStatusCode);
        $this->assertSame($expectedReasonPhrase, $actualReasonPhrase);
        $this->assertSame($expectedHeaders, $actualHeaders);
        $this->assertSame($expectedBody, $actualBody->getContents());
    }
}
