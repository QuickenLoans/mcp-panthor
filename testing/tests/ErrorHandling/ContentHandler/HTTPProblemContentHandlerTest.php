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
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class HTTPProblemContentHandlerTest extends PHPUnit_Framework_TestCase
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
        $handler = new HTTPProblemContentHandler;
        $response = $handler->handleNotFound($this->request, $this->response);

        $expected = <<<HTML
HTTP/1.1 404 Not Found
Content-Type: application/problem+json

{"status":404,"title":"Not Found"}
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testNotAllowed()
    {
        $handler = new HTTPProblemContentHandler;
        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expected = <<<HTML
HTTP/1.1 405 Method Not Allowed
Content-Type: application/problem+json

{"status":405,"title":"Method Not Allowed","detail":"Allowed methods: PATCH, STEVE","allowed_methods":["PATCH","STEVE"]}
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testNotAllowedSets200StatusIfOptionsRequest()
    {
        $this->request = $this->request->withMethod('OPTIONS');

        $handler = new HTTPProblemContentHandler;
        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expected = <<<HTML
HTTP/1.1 200 OK
Content-Type: application/problem+json

{"status":200,"title":"OK","detail":"Allowed methods: PATCH, STEVE","allowed_methods":["PATCH","STEVE"]}
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testHandleException()
    {
        $ex = new ErrorException('exception message');

        $handler = new HTTPProblemContentHandler;
        $response = $handler->handleException($this->request, $this->response, $ex);

        $expected = <<<HTML
HTTP/1.1 500 Internal Server Error
Content-Type: application/problem+json

{"status":500,"title":"Internal Server Error","detail":"Internal Server Error"}
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testHandleExceptionWithDetails()
    {
        $ex = new ErrorException('exception message');

        $handler = new HTTPProblemContentHandler(null, true);
        $response = $handler->handleException($this->request, $this->response, $ex);

        $expected = <<<HTML
HTTP/1.1 500 Internal Server Error
Content-Type: application/problem+json
HTML;

        $rendered = (string) $response;
        $this->assertContains($expected, $rendered);
        $this->assertContains('"detail":"exception message"', $rendered);
        $this->assertContains('"error_details":"ERR ', $rendered);
    }
}
